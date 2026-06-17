<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

/**
 * Re-encodes videos to an Instagram-Reels-compliant MP4 so the Graph API stops
 * rejecting uploads with errors like 2207026 / 2207076 ("Media upload has failed").
 *
 * Target spec (Meta Reels requirements):
 *   - Container : MP4, web-optimized (+faststart — moov atom at the front)
 *   - Video     : H.264 (High profile, level 4.2), yuv420p, constant 30fps
 *   - Frame     : 1080x1920 (9:16) — letterboxed/padded, never stretched
 *   - Audio     : AAC-LC, 128k, 48kHz stereo (silent track injected if missing)
 *
 * Detection of ffmpeg/ffprobe: explicit config path → PATH → common Windows
 * install locations (winget Gyan.FFmpeg, choco, C:\ffmpeg).
 */
class VideoNormalizer
{
    /**
     * Produce a Reels-compliant copy of $srcAbs and return its absolute path.
     * On any failure (no ffmpeg, encode error) returns null so callers can fall
     * back to uploading the original file untouched.
     *
     * @param string $srcAbs Absolute path to the source video.
     * @param string $dstAbs Absolute path the normalized MP4 should be written to.
     */
    public function toReels(string $srcAbs, string $dstAbs): ?string
    {
        if (! config('services.ffmpeg.enabled', true)) {
            return null;
        }
        if (! is_file($srcAbs)) {
            Log::warning('VideoNormalizer: source missing', ['src' => $srcAbs]);
            return null;
        }

        // Managed/shared hosts (e.g. Cloudways) commonly disable proc_open, which
        // Symfony's Process class requires. Without it we cannot shell out to
        // ffmpeg, so skip re-encoding and let the caller upload the original file
        // as-is (publishing still works — resumable upload is pure HTTP).
        if (! function_exists('proc_open')) {
            Log::warning('VideoNormalizer: proc_open is disabled — skipping re-encode, uploading the original video unchanged.');
            return null;
        }

        $ffmpeg = $this->binary('ffmpeg');
        if (! $ffmpeg) {
            Log::warning('VideoNormalizer: ffmpeg not found — uploading original. Set FFMPEG_PATH in .env.');
            return null;
        }

        // Reuse a previously normalized file (idempotent across retries).
        if (is_file($dstAbs) && filesize($dstAbs) > 0 && filemtime($dstAbs) >= filemtime($srcAbs)) {
            return $dstAbs;
        }

        @mkdir(dirname($dstAbs), 0775, true);

        // scale to fit inside 1080x1920 then pad to exactly 1080x1920 (9:16),
        // forcing even dimensions and yuv420p which IG requires.
        $vf = "scale=1080:1920:force_original_aspect_ratio=decrease,"
            . "pad=1080:1920:(ow-iw)/2:(oh-ih)/2:color=black,"
            . "format=yuv420p";

        $process = new Process($this->buildArgs($ffmpeg, $srcAbs, $dstAbs, $vf));
        $process->setTimeout(600);

        try {
            $process->run();
        } catch (\Throwable $e) {
            Log::error('VideoNormalizer: ffmpeg threw', ['e' => $e->getMessage()]);
            return null;
        }

        if (! $process->isSuccessful() || ! is_file($dstAbs) || filesize($dstAbs) === 0) {
            Log::error('VideoNormalizer: encode failed', [
                'exit' => $process->getExitCode(),
                'err'  => mb_substr($process->getErrorOutput(), -2000),
            ]);
            @unlink($dstAbs);
            return null;
        }

        Log::info('VideoNormalizer: normalized for Reels', [
            'src_mb' => round(filesize($srcAbs) / 1048576, 2),
            'out_mb' => round(filesize($dstAbs) / 1048576, 2),
        ]);

        return $dstAbs;
    }

    /**
     * Build the ffmpeg argument vector. Detects whether the source has an audio
     * stream and only injects a silent track when it does not — avoids the
     * double-audio-map problem of doing both unconditionally.
     */
    private function buildArgs(string $ffmpeg, string $src, string $dst, string $vf): array
    {
        $hasAudio = $this->hasAudioStream($src);

        $args = [$ffmpeg, '-y', '-i', $src];

        if (! $hasAudio) {
            // add a silent stereo source as a second input
            $args = array_merge($args, [
                '-f', 'lavfi', '-i', 'anullsrc=channel_layout=stereo:sample_rate=48000',
                '-map', '0:v:0', '-map', '1:a:0', '-shortest',
            ]);
        } else {
            $args = array_merge($args, ['-map', '0:v:0', '-map', '0:a:0']);
        }

        return array_merge($args, [
            '-vf', $vf,
            '-r', '30',
            '-c:v', 'libx264',
            '-profile:v', 'high', '-level', '4.2',
            '-preset', 'medium', '-crf', '23',
            '-pix_fmt', 'yuv420p',
            '-c:a', 'aac', '-b:a', '128k', '-ar', '48000', '-ac', '2',
            '-movflags', '+faststart',
            $dst,
        ]);
    }

    private function hasAudioStream(string $src): bool
    {
        $probe = $this->binary('ffprobe');
        if (! $probe) {
            return false; // assume no audio → safest is to inject a silent track
        }

        $p = new Process([
            $probe, '-v', 'error',
            '-select_streams', 'a',
            '-show_entries', 'stream=codec_type',
            '-of', 'csv=p=0',
            $src,
        ]);
        $p->setTimeout(30);
        try {
            $p->run();
        } catch (\Throwable) {
            return false;
        }

        return $p->isSuccessful() && str_contains(trim($p->getOutput()), 'audio');
    }

    /**
     * Locate an ffmpeg/ffprobe binary. Order: explicit config → PATH → common
     * Windows install dirs (winget Gyan.FFmpeg, chocolatey, C:\ffmpeg).
     */
    public function binary(string $name): ?string
    {
        $key = $name === 'ffprobe' ? 'services.ffmpeg.probe' : 'services.ffmpeg.bin';
        $configured = (string) config($key);
        if ($configured !== '' && is_file($configured)) {
            return $configured;
        }

        // If ffmpeg.bin is set, derive ffprobe from the same directory.
        $ffmpegCfg = (string) config('services.ffmpeg.bin');
        if ($name === 'ffprobe' && $ffmpegCfg !== '' && is_file($ffmpegCfg)) {
            $cand = dirname($ffmpegCfg) . DIRECTORY_SEPARATOR . 'ffprobe.exe';
            if (is_file($cand)) {
                return $cand;
            }
        }

        $exe  = $name . (str_starts_with(PHP_OS, 'WIN') ? '.exe' : '');
        $home = getenv('LOCALAPPDATA') ?: (getenv('USERPROFILE') . '\\AppData\\Local');

        $candidates = [
            // winget (Gyan.FFmpeg) — glob the versioned folder
            ...glob($home . '\\Microsoft\\WinGet\\Packages\\Gyan.FFmpeg*\\*\\bin\\' . $exe) ?: [],
            $home . '\\Microsoft\\WinGet\\Links\\' . $exe,
            'C:\\ffmpeg\\bin\\' . $exe,
            'C:\\ProgramData\\chocolatey\\bin\\' . $exe,
        ];

        foreach ($candidates as $c) {
            if ($c && is_file($c)) {
                return $c;
            }
        }

        // Last resort: rely on PATH resolution.
        return $this->onPath($exe) ? $exe : null;
    }

    private function onPath(string $exe): bool
    {
        if (! function_exists('proc_open')) {
            return false; // can't spawn a detection process without proc_open
        }
        $which = str_starts_with(PHP_OS, 'WIN') ? 'where' : 'which';
        $p = new Process([$which, $exe]);
        try {
            $p->run();
            return $p->isSuccessful();
        } catch (\Throwable) {
            return false;
        }
    }
}
