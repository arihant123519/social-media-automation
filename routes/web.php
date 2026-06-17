<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\CaptionDraftController;
use App\Http\Controllers\OAuthController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\PublishController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\SettingController;
use Illuminate\Support\Facades\Route;

Route::get('/', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.submit');

    // Public legal pages (required for Meta App Review — must be reachable without login)
    Route::view('/privacy', 'legal.privacy')->name('privacy');
    Route::view('/terms', 'legal.terms')->name('terms');

    // Serve uploaded media from the public disk WITHOUT relying on the
    // `public/storage` symlink (which `php artisan serve` can't follow on
    // Windows). Must stay public so Meta/IG can fetch media when publishing.
    // If a real symlink exists (Apache/production), the web server serves the
    // file first and this route is never hit — so it's a safe fallback.
    Route::get('/storage/{path}', function (string $path) {
        $path = ltrim(str_replace('\\', '/', $path), '/');
        abort_if(str_contains($path, '..'), 404);
        $disk = \Illuminate\Support\Facades\Storage::disk('public');
        abort_unless($disk->exists($path), 404);

        // BinaryFileResponse → supports HTTP Range requests (video seeking).
        return response()->file($disk->path($path));
    })->where('path', '.*')->name('storage.serve');

    Route::middleware(['auth', 'session.valid'])->group(function () {

        Route::get('/dashboard', [AuthController::class, 'index'])->name('dashboard');
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

        Route::get('/clients/scope', [ClientController::class, 'scope'])->name('clients.scope');
        Route::post('/client-scopes', [ClientController::class, 'scopestore'])->name('clients.scope.store');
        Route::post('/clients/{client}/scope-update', [ClientController::class, 'scopeUpdate'])->name('clients.scope.update');
        Route::get('/clients/{client}/settings', [ClientController::class, 'settings'])->name('clients.settings');
        // Meta App credentials + Facebook-Login (JS SDK) authorization
        Route::post('/clients/{client}/meta/config',  [ClientController::class, 'saveMetaConfig'])->name('clients.meta.config');
        Route::post('/clients/{client}/meta/connect', [OAuthController::class, 'metaConnect'])->name('clients.meta.connect');
        Route::post('/clients/{client}/meta/select',  [OAuthController::class, 'metaSelect'])->name('clients.meta.select');
        Route::resource('clients', ClientController::class);
        Route::get('/clients/data', [ClientController::class, 'data'])->name('clients.data');
        

        // Profile
        Route::get('/profile', function () {
            return view('profile');
        })->name('profile');

        // App Settings (DB-backed API keys / config)
        Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
        Route::post('/settings', [SettingController::class, 'update'])->name('settings.update');

        // Prompts — view & edit every AI prompt the app applies
        Route::get('/prompts',                 [\App\Http\Controllers\PromptController::class, 'index'])->name('prompts.index');
        Route::put('/prompts/{prompt}',        [\App\Http\Controllers\PromptController::class, 'update'])->name('prompts.update');
        Route::post('/prompts/{prompt}/reset', [\App\Http\Controllers\PromptController::class, 'reset'])->name('prompts.reset');



    Route::get('/calendar', [CalendarController::class, 'index'])->name('calendar.index');
    Route::post('/calendar/update-status', [CalendarController::class, 'updateStatus'])->name('calendar.updateStatus');

    // #12 — Weekly AI caption drafts
    Route::get('/caption-drafts',                 [CaptionDraftController::class, 'index'])->name('captions.index');
    Route::post('/caption-drafts/generate',       [CaptionDraftController::class, 'generate'])->name('captions.generate');
    Route::post('/caption-drafts/{draft}',        [CaptionDraftController::class, 'update'])->name('captions.update');
    Route::delete('/caption-drafts/{draft}',      [CaptionDraftController::class, 'destroy'])->name('captions.destroy');

    // #15 — Social analytics auto-report
    Route::get('/reports/analytics',              [\App\Http\Controllers\AnalyticsController::class, 'index'])->name('reports.analytics');
    Route::get('/reports/analytics/data',         [\App\Http\Controllers\AnalyticsController::class, 'data'])->name('reports.analytics.data');

    // ── Growth Intelligence (per-client, cross-platform) ──
    // #1 Content Health Scorecard
    Route::get('/growth/scorecard',       [\App\Http\Controllers\ScorecardController::class, 'index'])->name('growth.scorecard');
    // #2 All-Platform Content Command Center
    Route::get('/growth/command-center',  [\App\Http\Controllers\CommandCenterController::class, 'index'])->name('growth.command');
    // #3 Best Time To Post Calculator
    Route::get('/growth/best-time',       [\App\Http\Controllers\BestTimeController::class, 'index'])->name('growth.besttime');
    // #4 Viral Probability Predictor
    Route::get('/growth/viral-predictor', [\App\Http\Controllers\ViralPredictorController::class, 'index'])->name('ai.viral');
    Route::post('/growth/viral-predictor',[\App\Http\Controllers\ViralPredictorController::class, 'predict'])->name('ai.viral.predict');

    // #13 — Hashtag bank per specialty
    Route::get('/hashtag-bank',                   [\App\Http\Controllers\HashtagBankController::class, 'index'])->name('hashtags.index');
    Route::post('/hashtag-bank',                  [\App\Http\Controllers\HashtagBankController::class, 'store'])->name('hashtags.store');
    Route::post('/hashtag-bank/{hashtag}',        [\App\Http\Controllers\HashtagBankController::class, 'update'])->name('hashtags.update');
    Route::delete('/hashtag-bank/{hashtag}',      [\App\Http\Controllers\HashtagBankController::class, 'destroy'])->name('hashtags.destroy');
    Route::get('/hashtag-bank/suggest',           [\App\Http\Controllers\HashtagBankController::class, 'suggest'])->name('hashtags.suggest');

    // ── AI Studio (all DB-free — outputs generated on demand, never persisted) ──
    // #1 AI Video Script Generator
    Route::get('/ai/script',            [\App\Http\Controllers\ScriptGeneratorController::class, 'index'])->name('ai.script');
    Route::post('/ai/script/generate',  [\App\Http\Controllers\ScriptGeneratorController::class, 'generate'])->name('ai.script.generate');

    // #3 Multi-Format Caption Engine
    Route::get('/ai/captions',          [\App\Http\Controllers\MultiCaptionController::class, 'index'])->name('ai.captions');
    Route::post('/ai/captions/generate',[\App\Http\Controllers\MultiCaptionController::class, 'generate'])->name('ai.captions.generate');

    // #4 Reel Analyzer
    Route::get('/ai/reel-analyzer',     [\App\Http\Controllers\ReelAnalyzerController::class, 'index'])->name('ai.reel');
    Route::post('/ai/reel-analyzer',    [\App\Http\Controllers\ReelAnalyzerController::class, 'analyze'])->name('ai.reel.analyze');

    // #5 Competitor Profile Auditor
    Route::get('/ai/profile-auditor',   [\App\Http\Controllers\ProfileAuditorController::class, 'index'])->name('ai.auditor');
    Route::post('/ai/profile-auditor',  [\App\Http\Controllers\ProfileAuditorController::class, 'audit'])->name('ai.auditor.audit');

    // #2 Competitor Post Intelligence Feed
    Route::get('/ai/competitors',       [\App\Http\Controllers\CompetitorFeedController::class, 'index'])->name('ai.competitors');
    Route::post('/ai/competitors',      [\App\Http\Controllers\CompetitorFeedController::class, 'analyze'])->name('ai.competitors.analyze');

    // Post Creator
    Route::get('/post',                [PostController::class, 'index'])->name('Post.index');
    Route::post('/posts/trending',     [PostController::class, 'trending'])->name('posts.trending');
    Route::post('/posts/inspiration',  [PostController::class, 'inspiration'])->name('posts.inspiration');
    Route::post('/posts/start',        [PostController::class, 'start'])->name('posts.start');
    Route::post('/posts/upload',       [PostController::class, 'upload'])->name('posts.upload');
    Route::get('/posts/{post}/download', [PostController::class, 'download'])->name('posts.download');

    // Drafts — resume / delete an unfinished post (Phase 1 #3)
    Route::get('/posts/drafts',           [PostController::class, 'drafts'])->name('posts.drafts');
    Route::get('/posts/{post}/resume',    [PostController::class, 'resume'])->name('posts.resume');
    Route::delete('/posts/{post}/draft',  [PostController::class, 'destroyDraft'])->name('posts.draft.destroy');

    // Edit caption/hashtags of an approved post's winning attempt (Phase 1 #2)
    Route::get('/posts/{post}/edit-caption',  [PostController::class, 'editCaption'])->name('posts.editCaption');
    Route::post('/posts/{post}/edit-caption', [PostController::class, 'updateCaption'])->name('posts.updateCaption');

    // Pre-publish preview JSON (Phase 1 #4)
    Route::get('/posts/{post}/preview', [PostController::class, 'preview'])->name('posts.preview');
    Route::post('/posts/{post}/publish', [PublishController::class, 'publish'])->name('posts.publish');
    Route::post('/posts/{post}/save', [PublishController::class, 'save'])->name('posts.save');
    Route::post('/posts/{post}/schedule', [PublishController::class, 'schedule'])->name('posts.schedule');
    Route::post('/posts/{post}/unschedule', [PublishController::class, 'unschedule'])->name('posts.unschedule');
    Route::post('/posts/{post}/reopen', [PublishController::class, 'reopen'])->name('posts.reopen');

    // OAuth: per-client Connect / Callback / Disconnect
    Route::prefix('oauth')->name('oauth.')->group(function () {
        Route::get('/youtube/connect/{client}',   [OAuthController::class, 'youtubeConnect'])->name('youtube.connect');
        Route::get('/youtube/callback',           [OAuthController::class, 'youtubeCallback'])->name('youtube.callback');
        Route::get('/youtube/disconnect/{client}',[OAuthController::class, 'youtubeDisconnect'])->name('youtube.disconnect');

        Route::get('/instagram/connect/{client}',   [OAuthController::class, 'instagramConnect'])->name('instagram.connect');
        Route::get('/instagram/callback',           [OAuthController::class, 'instagramCallback'])->name('instagram.callback');
        Route::post('/instagram/select-page',       [OAuthController::class, 'instagramSelectPage'])->name('instagram.selectpage');
        Route::get('/instagram/disconnect/{client}',[OAuthController::class, 'instagramDisconnect'])->name('instagram.disconnect');

        Route::get('/facebook/connect/{client}',    [OAuthController::class, 'facebookConnect'])->name('facebook.connect');
        Route::get('/facebook/callback',            [OAuthController::class, 'facebookCallback'])->name('facebook.callback');
        Route::post('/facebook/select-page',        [OAuthController::class, 'facebookSelectPage'])->name('facebook.selectpage');
        Route::get('/facebook/disconnect/{client}', [OAuthController::class, 'facebookDisconnect'])->name('facebook.disconnect');
    });
    });