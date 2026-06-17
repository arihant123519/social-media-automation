@extends('layouts.app')

@section('title', $client->name . ' — Settings')
@section('page_header', $client->name . ' — Settings')
@section('page_icon', 'mdi mdi-cog')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('clients.index') }}">Clients</a></li>
    <li class="breadcrumb-item active">{{ $client->name }} Settings</li>
@endsection

@push('styles')
<style>
    .conn-card { border:1px solid var(--bs-border-color); border-radius:1rem; transition:box-shadow .2s; }
    .conn-card:hover { box-shadow:0 .4rem 1.2rem rgba(0,0,0,.07); }
    .conn-ico { width:46px; height:46px; border-radius:.8rem; display:flex; align-items:center; justify-content:center; font-size:1.5rem; }
    @media (max-width:575.98px){ .conn-actions .btn{ width:100%; } }
</style>
@endpush

@section('content')

@if(session('success'))
    <div class="alert alert-success py-2"><i class="mdi mdi-check-circle me-1"></i> {{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert alert-danger py-2"><i class="mdi mdi-alert-circle me-1"></i> {{ session('error') }}</div>
@endif

<div class="d-flex flex-wrap gap-2 align-items-center justify-content-between mb-3">
    <p class="text-muted mb-0">
        Connect <strong>{{ $client->name }}</strong>'s own social accounts. Tokens are generated via secure
        login and kept long-lived automatically — no manual token pasting, no re-login.
    </p>
    <a href="{{ route('clients.edit', $client) }}" class="btn btn-sm btn-light"><i class="mdi mdi-pencil me-1"></i>Edit profile</a>
</div>

{{-- ─── Meta credentials + Facebook Login (one click connects FB + IG) ─── --}}
<div class="card mb-3">
    <div class="card-body">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
            <h6 class="mb-0"><i class="mdi mdi-facebook text-primary me-1"></i>Facebook Login — authorize Instagram &amp; Facebook</h6>
            <button class="btn btn-sm btn-light" type="button" data-bs-toggle="collapse" data-bs-target="#metaCredForm">
                <i class="mdi mdi-key-outline me-1"></i>App credentials
            </button>
        </div>

        {{-- App credentials (App ID / Secret / Config ID) --}}
        <div class="collapse {{ $metaReady && $meta['config_id'] ? '' : 'show' }}" id="metaCredForm">
            <form method="POST" action="{{ route('clients.meta.config', $client) }}" class="border rounded p-3 mb-3 bg-light">
                @csrf
                <p class="small text-muted mb-3"><i class="mdi mdi-information-outline me-1"></i>From your Meta App dashboard. Shared across all clients — set once. The secret stays server-side (never sent to the browser).</p>
                <div class="row g-2">
                    <div class="col-12 col-md-4">
                        <label class="form-label small">App ID</label>
                        <input type="text" name="meta_app_id" class="form-control form-control-sm" value="{{ $meta['app_id'] }}" placeholder="904530936725782">
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label small">App Secret</label>
                        <input type="password" name="meta_app_secret" class="form-control form-control-sm" autocomplete="new-password"
                               placeholder="{{ $meta['has_secret'] ? '•••••••• (leave blank to keep)' : 'enter app secret' }}">
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label small">Login Config ID</label>
                        <input type="text" name="meta_config_id" class="form-control form-control-sm" value="{{ $meta['config_id'] }}" placeholder="979994297104255">
                    </div>
                </div>
                <button class="btn btn-sm btn-primary mt-3"><i class="mdi mdi-content-save-outline me-1"></i>Save credentials</button>
            </form>
        </div>

        {{-- One-click login --}}
        <div class="d-flex flex-wrap align-items-center gap-2">
            <button id="fbLoginBtn" class="btn btn-primary {{ ($meta['app_id'] && $meta['config_id']) ? '' : 'disabled' }}" type="button">
                <i class="mdi mdi-facebook me-1"></i> Login with Facebook &amp; Connect
            </button>
            <span id="fbSpinner" class="spinner-border spinner-border-sm text-primary" style="display:none"></span>
            <span id="fbStatus" class="small text-muted"></span>
        </div>
        @if(! ($meta['app_id'] && $meta['config_id']))
            <p class="small text-warning mt-2 mb-0"><i class="mdi mdi-alert-outline me-1"></i>Enter App ID + Login Config ID above first.</p>
        @endif

        {{-- Page picker (shown only when the account has multiple Pages) --}}
        <div id="fbPagePicker" class="mt-3 border rounded p-3" style="display:none">
            <label class="form-label small fw-semibold">Choose the Page to connect</label>
            <div class="input-group input-group-sm">
                <select id="fbPageSelect" class="form-select"></select>
                <button id="fbPageConnect" class="btn btn-primary" type="button">Connect</button>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">

    {{-- Instagram --}}
    <div class="col-12 col-lg-6">
        <div class="conn-card h-100 p-3">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <div class="d-flex align-items-center gap-2">
                    <span class="conn-ico" style="background:#fce7f3;color:#be185d"><i class="mdi mdi-instagram"></i></span>
                    <h6 class="mb-0">Instagram</h6>
                </div>
                @if($client->hasInstagramConnected())
                    <span class="badge bg-success">Connected</span>
                @else
                    <span class="badge bg-secondary">Not connected</span>
                @endif
            </div>

            @if($client->hasInstagramConnected())
                <p class="mb-1 small"><strong>IG Business ID:</strong> <code>{{ $client->ig_business_id }}</code></p>
                <p class="text-muted small mb-3"><i class="mdi mdi-shield-check text-success me-1"></i>Long-lived Page token — auto-maintained, does not expire while connected.</p>
                <div class="d-flex gap-2 conn-actions flex-wrap">
                    <a href="{{ route('oauth.instagram.connect', $client) }}" class="btn btn-sm btn-outline-primary"><i class="mdi mdi-refresh me-1"></i>Re-authorize</a>
                    <a href="{{ route('oauth.instagram.disconnect', $client) }}" class="btn btn-sm btn-outline-danger" onclick="return confirm('Disconnect Instagram?')"><i class="mdi mdi-link-variant-off me-1"></i>Disconnect</a>
                </div>
            @else
                <p class="text-muted small mb-3">IG must be a <strong>Business/Creator</strong> account linked to a Facebook Page.</p>
                <a href="{{ $metaReady ? route('oauth.instagram.connect', $client) : '#' }}"
                   class="btn btn-sm {{ $metaReady ? '' : 'disabled' }}" style="background:#be185d;color:#fff">
                    <i class="mdi mdi-instagram me-1"></i>Connect Instagram
                </a>
            @endif
        </div>
    </div>

    {{-- Facebook --}}
    <div class="col-12 col-lg-6">
        <div class="conn-card h-100 p-3">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <div class="d-flex align-items-center gap-2">
                    <span class="conn-ico" style="background:#dbeafe;color:#1d4ed8"><i class="mdi mdi-facebook"></i></span>
                    <h6 class="mb-0">Facebook Page</h6>
                </div>
                @if($client->hasFacebookConnected())
                    <span class="badge bg-success">Connected</span>
                @else
                    <span class="badge bg-secondary">Not connected</span>
                @endif
            </div>

            @if($client->hasFacebookConnected())
                <p class="mb-1 small"><strong>Page ID:</strong> <code>{{ $client->fb_page_id }}</code></p>
                <p class="text-muted small mb-3"><i class="mdi mdi-shield-check text-success me-1"></i>Never-expiring Page access token — auto-maintained.</p>
                <div class="d-flex gap-2 conn-actions flex-wrap">
                    <a href="{{ route('oauth.facebook.connect', $client) }}" class="btn btn-sm btn-outline-primary"><i class="mdi mdi-refresh me-1"></i>Re-authorize</a>
                    <a href="{{ route('oauth.facebook.disconnect', $client) }}" class="btn btn-sm btn-outline-danger" onclick="return confirm('Disconnect Facebook?')"><i class="mdi mdi-link-variant-off me-1"></i>Disconnect</a>
                </div>
            @else
                <p class="text-muted small mb-3">Connect a Facebook Page this client manages to auto-publish posts.</p>
                <a href="{{ $metaReady ? route('oauth.facebook.connect', $client) : '#' }}"
                   class="btn btn-sm btn-primary {{ $metaReady ? '' : 'disabled' }}">
                    <i class="mdi mdi-facebook me-1"></i>Connect Facebook
                </a>
            @endif
        </div>
    </div>

    {{-- YouTube --}}
    <div class="col-12 col-lg-6">
        <div class="conn-card h-100 p-3">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <div class="d-flex align-items-center gap-2">
                    <span class="conn-ico" style="background:#fee2e2;color:#dc2626"><i class="mdi mdi-youtube"></i></span>
                    <h6 class="mb-0">YouTube</h6>
                </div>
                @if($client->hasYouTubeConnected())
                    <span class="badge bg-success">Connected</span>
                @else
                    <span class="badge bg-secondary">Not connected</span>
                @endif
            </div>

            @if($client->hasYouTubeConnected())
                <p class="mb-1 small"><strong>Channel:</strong>
                    @if($client->yt_channel_id)
                        <a href="https://www.youtube.com/channel/{{ $client->yt_channel_id }}" target="_blank" rel="noopener">{{ $client->yt_channel_id }} <i class="mdi mdi-open-in-new"></i></a>
                    @else <span class="text-muted">unknown</span> @endif
                </p>
                <p class="text-muted small mb-3"><i class="mdi mdi-shield-refresh text-success me-1"></i>Access token auto-refreshes via stored refresh token.</p>
                <div class="d-flex gap-2 conn-actions flex-wrap">
                    <a href="{{ route('oauth.youtube.connect', $client) }}" class="btn btn-sm btn-outline-primary"><i class="mdi mdi-refresh me-1"></i>Re-authorize</a>
                    <a href="{{ route('oauth.youtube.disconnect', $client) }}" class="btn btn-sm btn-outline-danger" onclick="return confirm('Disconnect YouTube?')"><i class="mdi mdi-link-variant-off me-1"></i>Disconnect</a>
                </div>
            @else
                <p class="text-muted small mb-3">Connect this client's YouTube channel to auto-publish Shorts / long videos.</p>
                <a href="{{ $googleReady ? route('oauth.youtube.connect', $client) : '#' }}"
                   class="btn btn-sm btn-danger {{ $googleReady ? '' : 'disabled' }}">
                    <i class="mdi mdi-youtube me-1"></i>Connect YouTube
                </a>
                @unless($googleReady)
                    <p class="text-muted small mt-2 mb-0"><i class="mdi mdi-information-outline me-1"></i>Set <code>GOOGLE_CLIENT_ID</code> + <code>GOOGLE_CLIENT_SECRET</code> in <code>.env</code> first.</p>
                @endunless
            @endif
        </div>
    </div>

    {{-- Brand voice shortcut --}}
    <div class="col-12 col-lg-6">
        <div class="conn-card h-100 p-3 d-flex flex-column">
            <div class="d-flex align-items-center gap-2 mb-2">
                <span class="conn-ico" style="background:#ede9fe;color:#6d28d9"><i class="mdi mdi-bullhorn-variant-outline"></i></span>
                <h6 class="mb-0">Brand Voice &amp; AI</h6>
            </div>
            <p class="text-muted small">This client's tone &amp; do's/don'ts drive every AI caption. Manage it on the profile.</p>
            <div class="mt-auto">
                <a href="{{ route('clients.edit', $client) }}" class="btn btn-sm btn-outline-primary"><i class="mdi mdi-pencil me-1"></i>Edit brand voice</a>
            </div>
        </div>
    </div>

</div>

@endsection

@push('scripts')
<script>
(function () {
    const APP_ID    = @json($meta['app_id']);
    const CONFIG_ID = @json($meta['config_id']);
    const VERSION   = @json($meta['version'] ?: 'v19.0');
    const CSRF      = document.querySelector('meta[name="csrf-token"]').content;
    const CONNECT   = @json(route('clients.meta.connect', $client));
    const SELECT    = @json(route('clients.meta.select', $client));

    const btn     = document.getElementById('fbLoginBtn');
    const spinner = document.getElementById('fbSpinner');
    const statusE = document.getElementById('fbStatus');
    const picker  = document.getElementById('fbPagePicker');
    const select  = document.getElementById('fbPageSelect');
    const pickBtn = document.getElementById('fbPageConnect');

    if (!btn || !APP_ID || !CONFIG_ID) return;

    function setBusy(b, msg) {
        spinner.style.display = b ? 'inline-block' : 'none';
        btn.disabled = b;
        if (msg !== undefined) { statusE.textContent = msg; statusE.className = 'small text-muted'; }
    }
    function fail(msg) { setBusy(false); statusE.textContent = msg; statusE.className = 'small text-danger'; }
    function done(msg) { statusE.textContent = msg + ' — reloading…'; statusE.className = 'small text-success'; setTimeout(() => location.reload(), 900); }

    // Load FB SDK once
    window.fbAsyncInit = function () {
        FB.init({ appId: APP_ID, cookie: true, xfbml: false, version: VERSION });
    };
    (function (d, s, id) {
        if (d.getElementById(id)) return;
        const js = d.createElement(s); js.id = id;
        js.src = 'https://connect.facebook.net/en_US/sdk.js';
        d.body.appendChild(js);
    })(document, 'script', 'facebook-jssdk');

    async function postJson(url, body) {
        const res = await fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
            body: JSON.stringify(body),
        });
        return res.json().catch(() => ({ success: false, error: 'Bad server response.' }));
    }

    btn.addEventListener('click', function () {
        if (typeof FB === 'undefined') { fail('Facebook SDK still loading — try again in a second.'); return; }
        setBusy(true, 'Opening Facebook login…');
        FB.login(function (response) {
            if (!response.authResponse) { fail('Login cancelled.'); return; }
            const shortToken = response.authResponse.accessToken;
            setBusy(true, 'Authorizing & generating long-lived token…');
            postJson(CONNECT, { access_token: shortToken }).then(function (data) {
                if (!data.success) { fail(data.error || 'Connect failed.'); return; }
                if (data.needs_selection) {
                    setBusy(false, 'Pick the Page to connect.');
                    select.innerHTML = data.pages.map(p =>
                        `<option value="${p.page_id}">${p.page_name}${p.ig_username ? ' · @' + p.ig_username : ''}</option>`
                    ).join('');
                    picker.style.display = 'block';
                    return;
                }
                done(data.message || 'Connected');
            });
        }, { config_id: CONFIG_ID });
    });

    pickBtn?.addEventListener('click', function () {
        const pageId = select.value;
        if (!pageId) return;
        pickBtn.disabled = true;
        postJson(SELECT, { page_id: pageId }).then(function (data) {
            pickBtn.disabled = false;
            if (!data.success) { fail(data.error || 'Selection failed.'); return; }
            done(data.message || 'Connected');
        });
    });
})();
</script>
@endpush
