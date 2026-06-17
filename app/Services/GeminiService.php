<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Google Gemini API wrapper.
 *
 * Free tier (Gemini 2.0 Flash):
 *   - 15 req/min, 1500 req/day
 *   - Native video + image input (no ffmpeg needed)
 *   - No billing setup required
 *
 * Strategy:
 *   - File ≤ 18MB  → inline base64 in the request
 *   - File >  18MB → upload via Files API, reference by URI
 *   - For videos uploaded via Files API, poll until state == ACTIVE
 */
class GeminiService
{
    private const INLINE_LIMIT = 18 * 1024 * 1024;
    private const BASE_URL     = 'https://generativelanguage.googleapis.com';
    private const FILES_URL    = 'https://generativelanguage.googleapis.com/upload/v1beta/files';

    private string $key;
    private string $model;

    public function __construct()
    {
        $this->key   = (string) config('services.gemini.key');
        $this->model = (string) config('services.gemini.model', 'gemini-2.0-flash');
    }

    public function isConfigured(): bool
    {
        return $this->key !== '';
    }

    /**
     * Run a scoring prompt with optional media attachment.
     *
     * @return array{success: bool, data?: array, error?: string}
     */
    public function generate(string $prompt, ?string $absolutePath, string $mime): array
    {
        if (! $this->isConfigured()) {
            return ['success' => false, 'error' => 'GEMINI_API_KEY not set in .env.'];
        }

        try {
            $parts = [['text' => $prompt]];

            if ($absolutePath && is_file($absolutePath)) {
                $size = filesize($absolutePath);

                if ($size <= self::INLINE_LIMIT) {
                    $parts[] = [
                        'inline_data' => [
                            'mime_type' => $mime,
                            'data'      => base64_encode(file_get_contents($absolutePath)),
                        ],
                    ];
                } else {
                    $upload = $this->uploadFile($absolutePath, $mime);
                    if (! ($upload['success'] ?? false)) {
                        return ['success' => false, 'error' => 'Gemini file upload failed: ' . ($upload['error'] ?? 'unknown')];
                    }
                    $parts[] = [
                        'file_data' => [
                            'mime_type' => $mime,
                            'file_uri'  => $upload['uri'],
                        ],
                    ];
                    if (str_starts_with($mime, 'video/')) {
                        $this->waitUntilActive($upload['name']);
                    }
                }
            }

            $payload = [
                'contents' => [[
                    'role'  => 'user',
                    'parts' => $parts,
                ]],
                'generationConfig' => [
                    'temperature'      => 0.4,
                    'maxOutputTokens'  => 16000,
                    'responseMimeType' => 'application/json',
                    'thinkingConfig'   => [
                        'thinkingBudget' => 2000,
                    ],
                ],
            ];

            // Try the configured model first, then fall back to alternates when the
            // model is overloaded ("high demand"/503). Each model gets 2 quick retries.
            $models = array_values(array_unique(array_filter([
                $this->model,
                'gemini-flash-latest',
                'gemini-2.0-flash',
            ])));

            // Text-only requests come back in seconds; native video analysis needs
            // longer. A timeout now falls THROUGH to the next model instead of
            // aborting the whole call (the previous code let the cURL-28 exception
            // bubble out, so the fallback models were never tried and the user
            // waited the full 180s for nothing).
            $hasMedia = $absolutePath && is_file($absolutePath);
            $timeout  = $hasMedia ? 150 : 60;

            $res = null;
            $lastErr = 'unknown';

            foreach ($models as $model) {
                $endpoint = sprintf('%s/v1beta/models/%s:generateContent?key=%s',
                    self::BASE_URL, $model, $this->key);

                for ($i = 1; $i <= 2; $i++) {
                    try {
                        $res = Http::timeout($timeout)->post($endpoint, $payload);
                    } catch (\Illuminate\Http\Client\ConnectionException $e) {
                        // No response within the timeout (cURL 28) or connection
                        // dropped. Retrying the same hung model just burns another
                        // timeout, so record it and move straight to the next model.
                        $lastErr = "no response within {$timeout}s";
                        Log::warning("Gemini '{$model}' timed out after {$timeout}s — trying next model");
                        $res = null;
                        break;
                    }
                    if ($res->ok()) break 2;   // success — exit both loops

                    $status  = $res->status();
                    $errMsg  = (string) $res->json('error.message', '');
                    $lastErr = $errMsg ?: $res->body();
                    $isOverload = in_array($status, [429, 500, 502, 503, 504], true)
                        || str_contains($errMsg, 'overloaded')
                        || str_contains($errMsg, 'high demand')
                        || str_contains($errMsg, 'try again');

                    if (! $isOverload) break;   // non-transient — try next model

                    if ($i < 2) {
                        Log::info("Gemini '{$model}' overloaded — retry {$i}/2");
                        sleep(1);
                    }
                }
                Log::info("Gemini '{$model}' unavailable, trying next model");
            }

            if (! $res || ! $res->ok()) {
                return ['success' => false, 'error' => 'Gemini (all models busy or timed out): ' . $lastErr];
            }

            $text = (string) $res->json('candidates.0.content.parts.0.text', '');
            $finishReason = (string) $res->json('candidates.0.finishReason', '');
            $text = preg_replace('/^```json\s*/i', '', trim($text));
            $text = preg_replace('/```\s*$/i', '', $text);

            $data = json_decode($text, true);
            if (! is_array($data)) {
                // Try harder: strip control chars / fix common JSON issues that Gemini sometimes produces
                $cleaned = $this->sanitizeJsonString($text);
                $data = json_decode($cleaned, true);
            }

            if (! is_array($data)) {
                $jsonErr = json_last_error_msg();
                // Dump full failed response so we can inspect it
                $debugPath = storage_path('logs/gemini-bad-' . date('Ymd-His') . '.json');
                @file_put_contents($debugPath, $text);

                Log::warning('Gemini returned non-JSON', [
                    'finishReason' => $finishReason,
                    'json_error'   => $jsonErr,
                    'text_len'     => strlen($text),
                    'text_head'    => mb_substr($text, 0, 400),
                    'text_tail'    => mb_substr($text, max(0, strlen($text) - 400)),
                    'dump_file'    => $debugPath,
                    'usage'        => $res->json('usageMetadata'),
                ]);

                // Friendlier error messages so caller knows the actual cause
                $hint = match (true) {
                    $finishReason === 'MAX_TOKENS' => ' (response truncated — increase maxOutputTokens)',
                    $finishReason === 'SAFETY'     => ' (blocked by Gemini safety filter — adjust caption/content)',
                    $finishReason === 'RECITATION' => ' (blocked: content matches public sources)',
                    $text === ''                   => ' (empty response — possibly safety block)',
                    default                        => " (JSON error: {$jsonErr})",
                };
                return ['success' => false, 'error' => 'Gemini returned invalid JSON' . $hint . '.'];
            }

            return ['success' => true, 'data' => $data];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Best-effort cleanup of JSON strings that Gemini sometimes produces with quirks.
     * Handles: trailing commas, control chars, literal newlines in strings,
     *          and UNBALANCED braces/brackets (Gemini's most damaging hallucination).
     */
    private function sanitizeJsonString(string $raw): string
    {
        // 1. Strip BOM and surrounding whitespace
        $raw = preg_replace('/^\xEF\xBB\xBF/', '', $raw);
        $raw = trim($raw);

        // 2. Drop trailing commas before } or ] — common Gemini quirk
        $raw = preg_replace('/,(\s*[}\]])/', '$1', $raw);

        // 3. Remove invisible control chars except \n \t (json_decode allows these escaped)
        $raw = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $raw);

        // 4. Walk to escape literal \n/\r/\t inside strings AND track bracket balance
        $out = '';
        $inString = false;
        $escape = false;
        $stack = []; // tracks opened structures: { or [
        $extraClosers = ''; // strings we strip from the END (extra } or ])
        $len = strlen($raw);

        // First pass: clean strings + scan brackets, but DON'T close prematurely
        for ($i = 0; $i < $len; $i++) {
            $ch = $raw[$i];
            if ($escape) { $out .= $ch; $escape = false; continue; }
            if ($ch === '\\') { $out .= $ch; $escape = true; continue; }
            if ($ch === '"') { $inString = ! $inString; $out .= $ch; continue; }
            if ($inString) {
                if ($ch === "\n") { $out .= '\\n'; continue; }
                if ($ch === "\r") { $out .= '\\r'; continue; }
                if ($ch === "\t") { $out .= '\\t'; continue; }
                $out .= $ch;
                continue;
            }
            // Outside strings — track brackets
            if ($ch === '{') { $stack[] = '{'; $out .= $ch; continue; }
            if ($ch === '[') { $stack[] = '['; $out .= $ch; continue; }
            if ($ch === '}') {
                if (! empty($stack) && end($stack) === '{') {
                    array_pop($stack);
                    $out .= $ch;
                } else {
                    // Mismatched — Gemini wrote } where it shouldn't have
                    // OR stack has [ — we should close array first
                    if (! empty($stack) && end($stack) === '[') {
                        // Replace } with ] to close the array
                        array_pop($stack);
                        $out .= ']';
                    } else {
                        // Extra } with empty stack — drop it
                        $extraClosers .= $ch;
                    }
                }
                continue;
            }
            if ($ch === ']') {
                if (! empty($stack) && end($stack) === '[') {
                    array_pop($stack);
                    $out .= $ch;
                } else {
                    if (! empty($stack) && end($stack) === '{') {
                        array_pop($stack);
                        $out .= '}';
                    } else {
                        $extraClosers .= $ch;
                    }
                }
                continue;
            }
            $out .= $ch;
        }

        // 5. Close any still-open structures (LIFO)
        while (! empty($stack)) {
            $opener = array_pop($stack);
            $out .= $opener === '{' ? '}' : ']';
        }

        return $out;
    }

    /**
     * Upload a large file via Gemini Files API (resumable).
     */
    private function uploadFile(string $absolutePath, string $mime): array
    {
        try {
            $size     = filesize($absolutePath);
            $filename = basename($absolutePath);

            $start = Http::timeout(30)->withHeaders([
                'X-Goog-Upload-Protocol'              => 'resumable',
                'X-Goog-Upload-Command'               => 'start',
                'X-Goog-Upload-Header-Content-Length' => (string) $size,
                'X-Goog-Upload-Header-Content-Type'   => $mime,
                'Content-Type'                        => 'application/json',
            ])->post(self::FILES_URL . '?key=' . $this->key, [
                'file' => ['display_name' => $filename],
            ]);

            if (! $start->ok()) {
                return ['success' => false, 'error' => 'upload init: ' . $start->body()];
            }

            $uploadUrl = $start->header('X-Goog-Upload-URL');
            if (! $uploadUrl) {
                return ['success' => false, 'error' => 'no upload URL returned'];
            }

            $upload = Http::timeout(180)->withHeaders([
                'Content-Length'        => (string) $size,
                'X-Goog-Upload-Offset'  => '0',
                'X-Goog-Upload-Command' => 'upload, finalize',
            ])->withBody(file_get_contents($absolutePath), $mime)
              ->post($uploadUrl);

            if (! $upload->ok()) {
                return ['success' => false, 'error' => 'upload bytes: ' . $upload->body()];
            }

            $uri  = $upload->json('file.uri');
            $name = $upload->json('file.name');
            if (! $uri) {
                return ['success' => false, 'error' => 'upload completed but no URI returned'];
            }

            return ['success' => true, 'uri' => $uri, 'name' => $name];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    private function waitUntilActive(string $name, int $maxSeconds = 30): void
    {
        $start = time();
        while ((time() - $start) < $maxSeconds) {
            try {
                $res = Http::timeout(10)->get(self::BASE_URL . '/v1beta/' . $name . '?key=' . $this->key);
                $state = $res->json('state');
                if ($state === 'ACTIVE') return;
                if ($state === 'FAILED') {
                    Log::warning('Gemini file processing FAILED', ['name' => $name]);
                    return;
                }
            } catch (\Throwable) {
                // ignore, retry
            }
            usleep(1_500_000);
        }
    }
}
