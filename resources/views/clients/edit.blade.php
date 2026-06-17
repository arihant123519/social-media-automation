@extends('layouts.app')

@section('title', 'Edit Client — ' . $client->name)
@section('page_header', 'Edit Client')
@section('page_icon', 'mdi mdi-account-edit')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('clients.index') }}">Clients</a></li>
    <li class="breadcrumb-item active">Edit — {{ $client->name }}</li>
@endsection

@section('content')

<form action="{{ route('clients.update', $client->id) }}" method="POST" autocomplete="off">
    @csrf
    @method('PUT')

    <div class="row g-3">

        <div class="col-12">
            <div class="card mb-0">
                <div class="card-body">
                    <h6 class="card-title mb-1">Basic Information</h6>
                    <p class="card-subtitle mb-4">Client contact details</p>

                    <div class="row g-3">

                        <div class="col-md-4">
                            <label class="form-label">Name <span class="text-danger">*</span></label>
                            <input type="text" name="name"
                                   class="form-control @error('name') is-invalid @enderror"
                                   value="{{ old('name', $client->name) }}">
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Phone <span class="text-danger">*</span></label>
                            <input type="text" name="phone"
                                   class="form-control @error('phone') is-invalid @enderror"
                                   value="{{ old('phone', $client->phone) }}">
                            @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Email <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="mdi mdi-at"></i></span>
                                <input type="email" name="email"
                                       class="form-control @error('email') is-invalid @enderror"
                                       value="{{ old('email', $client->email) }}">
                                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        <div class="col-md-8">
                            <label class="form-label">
                                Slug <span class="text-danger">*</span>
                                <small class="text-muted fw-normal ms-1">(same as used in LMS)</small>
                            </label>
                            <input type="text" name="slug"
                                   class="form-control @error('slug') is-invalid @enderror"
                                   value="{{ old('slug', $client->slug) }}">
                            @error('slug')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                    </div>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card mb-0">
                <div class="card-body">
                    <h6 class="card-title mb-1">Business Details</h6>
                    <p class="card-subtitle mb-4">Industry, location and assignment</p>

                    <div class="row g-3">

                        <div class="col-md-4">
                            <label class="form-label">Industry <span class="text-danger">*</span></label>
                            <select name="industry" class="form-select @error('industry') is-invalid @enderror">
                                @foreach(['dermatologist' => 'Dermatologist', 'ivf' => 'IVF', 'other' => 'Other'] as $val => $label)
                                    <option value="{{ $val }}" {{ old('industry', $client->industry) == $val ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                            @error('industry')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">City</label>
                            <select name="city" class="form-select @error('city') is-invalid @enderror">
                                <option value="">— Select city —</option>
                                @foreach(['Delhi','Mumbai','Bangalore','Chennai','Hyderabad','Pune','Other'] as $city)
                                    <option value="{{ $city }}" {{ old('city', $client->city) == $city ? 'selected' : '' }}>
                                        {{ $city }}
                                    </option>
                                @endforeach
                            </select>
                            @error('city')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Zip</label>
                            <input type="text" name="zip"
                                   class="form-control @error('zip') is-invalid @enderror"
                                   value="{{ old('zip', $client->zip) }}">
                            @error('zip')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">
                                Assigned User <span class="text-danger">*</span>
                                <small class="text-muted fw-normal ms-1">(team auto-assigned)</small>
                            </label>
                            <select name="user_id" class="form-select @error('user_id') is-invalid @enderror">
                                <option value="" disabled>Select user…</option>
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}" {{ old('user_id', $client->user_id) == $user->id ? 'selected' : '' }}>
                                        {{ $user->name }}
                                        @if($user->team) — {{ $user->team->name }} @endif
                                    </option>
                                @endforeach
                            </select>
                            @error('user_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        @if($client->team)
                        <div class="col-md-4">
                            <label class="form-label">Current Team</label>
                            <input type="text" class="form-control" value="{{ $client->team->name }}" readonly disabled>
                            <small class="text-muted">Auto-updated when user changes</small>
                        </div>
                        @endif

                    </div>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card mb-0">
                <div class="card-body">
                    <h6 class="card-title mb-1"><i class="mdi mdi-bullhorn-variant-outline text-primary me-1"></i>Brand Voice <small class="text-muted fw-normal">(AI uses this for captions)</small></h6>
                    <p class="card-subtitle mb-4">Fed into every Gemini caption &amp; scoring prompt so AI output stays on-brand.</p>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Tone</label>
                            <input type="text" name="brand_tone"
                                   class="form-control @error('brand_tone') is-invalid @enderror"
                                   value="{{ old('brand_tone', $client->brand_tone) }}" placeholder="e.g. Warm, professional, reassuring">
                            @error('brand_tone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label">Brand voice / do's &amp; don'ts</label>
                            <textarea name="brand_voice" rows="4"
                                      class="form-control @error('brand_voice') is-invalid @enderror"
                                      placeholder="Audience, words to use/avoid, emoji style, CTA preferences, claims to never make…">{{ old('brand_voice', $client->brand_voice) }}</textarea>
                            @error('brand_voice')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card mb-0">
                <div class="card-body">
                    <h6 class="card-title mb-1">Status</h6>
                    <p class="card-subtitle mb-3">Account availability</p>
                    <div class="d-flex gap-3">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="status" id="status_active"
                                   value="active" {{ old('status', $client->status) == 'active' ? 'checked' : '' }}>
                            <label class="form-check-label fw-medium" for="status_active">Active</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="status" id="status_inactive"
                                   value="inactive" {{ old('status', $client->status) == 'inactive' ? 'checked' : '' }}>
                            <label class="form-check-label fw-medium" for="status_inactive">Inactive</label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="mdi mdi-content-save me-1"></i> Update Client
                </button>
                <a href="{{ route('clients.index') }}" class="btn btn-light">
                    <i class="mdi mdi-arrow-left me-1"></i> Back
                </a>
            </div>
        </div>

    </div>
</form>

{{-- ───────────────────────────────────────────────────────────────
     Connected Accounts (OAuth — separate from main update form so
     submitting the form doesn't trigger disconnect / connect)
─────────────────────────────────────────────────────────────── --}}
<div class="row g-3 mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title mb-1"><i class="mdi mdi-link-variant me-1"></i> Connected Accounts</h5>
                <p class="card-subtitle mb-4">Authorize this client's social accounts so posts can publish automatically.</p>

                @if(session('success'))
                    <div class="alert alert-success py-2 mb-3"><i class="mdi mdi-check-circle me-1"></i> {{ session('success') }}</div>
                @endif
                @if(session('error'))
                    <div class="alert alert-danger py-2 mb-3"><i class="mdi mdi-alert-circle me-1"></i> {{ session('error') }}</div>
                @endif

                <div class="row g-3">

                    {{-- YouTube Card --}}
                    <div class="col-md-6">
                        <div class="border rounded p-3 h-100">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <h6 class="mb-0">
                                    <i class="mdi mdi-youtube text-danger me-1" style="font-size:1.3rem"></i>
                                    YouTube
                                </h6>
                                @if($client->hasYouTubeConnected())
                                    <span class="badge bg-success">Connected</span>
                                @else
                                    <span class="badge bg-secondary">Not connected</span>
                                @endif
                            </div>

                            @if($client->hasYouTubeConnected())
                                <p class="mb-1" style="font-size:13px">
                                    <strong>Channel ID:</strong>
                                    @if($client->yt_channel_id)
                                        <a href="https://www.youtube.com/channel/{{ $client->yt_channel_id }}" target="_blank" rel="noopener">
                                            {{ $client->yt_channel_id }} <i class="mdi mdi-open-in-new"></i>
                                        </a>
                                    @else
                                        <span class="text-muted">unknown</span>
                                    @endif
                                </p>
                                @if($client->yt_token_expires_at)
                                    <p class="text-muted small mb-2">Access token expires {{ $client->yt_token_expires_at->diffForHumans() }} (auto-refreshes)</p>
                                @endif
                                <div class="d-flex gap-2">
                                    <a href="{{ route('oauth.youtube.connect', $client) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="mdi mdi-refresh"></i> Re-authorize
                                    </a>
                                    <a href="{{ route('oauth.youtube.disconnect', $client) }}"
                                       class="btn btn-sm btn-outline-danger"
                                       onclick="return confirm('Disconnect YouTube for this client?')">
                                        <i class="mdi mdi-link-variant-off"></i> Disconnect
                                    </a>
                                </div>
                            @else
                                <p class="text-muted small mb-2">Connect this client's YouTube channel to enable auto-publish of Shorts / long videos.</p>
                                <a href="{{ route('oauth.youtube.connect', $client) }}" class="btn btn-sm btn-danger">
                                    <i class="mdi mdi-youtube me-1"></i> Connect YouTube
                                </a>
                            @endif
                        </div>
                    </div>

                    {{-- Instagram Card --}}
                    <div class="col-md-6">
                        <div class="border rounded p-3 h-100">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <h6 class="mb-0">
                                    <i class="mdi mdi-instagram text-danger me-1" style="font-size:1.3rem;color:#be185d!important"></i>
                                    Instagram
                                </h6>
                                @if($client->hasInstagramConnected())
                                    <span class="badge bg-success">Connected</span>
                                @else
                                    <span class="badge bg-secondary">Not connected</span>
                                @endif
                            </div>

                            @if($client->hasInstagramConnected())
                                <p class="mb-1" style="font-size:13px">
                                    <strong>IG Business ID:</strong> <code>{{ $client->ig_business_id }}</code>
                                </p>
                                <p class="text-muted small mb-2">Token is long-lived (60 days). Re-authorize before expiry.</p>
                                <div class="d-flex gap-2">
                                    <a href="{{ route('oauth.instagram.connect', $client) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="mdi mdi-refresh"></i> Re-authorize
                                    </a>
                                    <a href="{{ route('oauth.instagram.disconnect', $client) }}"
                                       class="btn btn-sm btn-outline-danger"
                                       onclick="return confirm('Disconnect Instagram for this client?')">
                                        <i class="mdi mdi-link-variant-off"></i> Disconnect
                                    </a>
                                </div>
                            @else
                                <p class="text-muted small mb-2">IG Business account must be linked to a Facebook Page first.</p>
                                <a href="{{ route('oauth.instagram.connect', $client) }}" class="btn btn-sm" style="background:#be185d;color:#fff">
                                    <i class="mdi mdi-instagram me-1"></i> Connect Instagram
                                </a>
                            @endif
                        </div>
                    </div>

                </div>

            </div>
        </div>
    </div>
</div>

{{-- ───────────────────────────────────────────────────────────────
     Content Scope Plan (versioned — editing creates a new plan from a
     new start date; previous dates on the calendar are preserved)
─────────────────────────────────────────────────────────────── --}}
<div class="row g-3 mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title mb-1"><i class="mdi mdi-calendar-edit me-1"></i> Content Scope Plan</h5>
                <p class="card-subtitle mb-4">
                    Update how many posts/month this client gets. Editing starts a <strong>new plan</strong> from
                    the start date you choose — older calendar dates stay as they were.
                </p>

                @if($errors->any())
                    <div class="alert alert-danger py-2">
                        @foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach
                    </div>
                @endif

                <div class="row g-3">

                    {{-- YouTube Plan --}}
                    <div class="col-md-6">
                        <div class="border rounded p-3 h-100">
                            <h6 class="mb-2"><i class="mdi mdi-youtube text-danger me-1"></i> YouTube Plan</h6>
                            @if($ytScope)
                                <p class="text-muted small mb-3">
                                    Current: {{ (int)$ytScope->long_video }} long + {{ (int)$ytScope->short_video }} short / month,
                                    since {{ \Carbon\Carbon::parse($ytScope->start_date)->format('d M Y') }}
                                </p>
                            @else
                                <p class="text-muted small mb-3">No active YouTube plan yet — set one below.</p>
                            @endif

                            <form action="{{ route('clients.scope.update', $client->id) }}" method="POST" autocomplete="off">
                                @csrf
                                <input type="hidden" name="scope" value="0">
                                <div class="row g-2">
                                    <div class="col-6">
                                        <label class="form-label small">Long Video / Month</label>
                                        <input type="number" min="0" name="long_video" class="form-control form-control-sm"
                                               value="{{ old('long_video', $ytScope->long_video ?? 0) }}">
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label small">Short Video / Month</label>
                                        <input type="number" min="0" name="short_video" class="form-control form-control-sm"
                                               value="{{ old('short_video', $ytScope->short_video ?? 0) }}">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label small">New plan start date <span class="text-danger">*</span></label>
                                        <input type="date" name="start_date" class="form-control form-control-sm" required
                                               value="{{ old('start_date', now()->toDateString()) }}">
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-sm btn-danger mt-3"
                                        onclick="return confirm('Update YouTube plan? A new plan will start from the chosen date; older dates stay unchanged.')">
                                    <i class="mdi mdi-content-save me-1"></i> Update YouTube Plan
                                </button>
                            </form>
                        </div>
                    </div>

                    {{-- Instagram Plan --}}
                    <div class="col-md-6">
                        <div class="border rounded p-3 h-100">
                            <h6 class="mb-2"><i class="mdi mdi-instagram me-1" style="color:#be185d"></i> Instagram Plan</h6>
                            @if($igScope)
                                <p class="text-muted small mb-3">
                                    Current: {{ (int)$igScope->reels }} reels + {{ (int)$igScope->story }} story + {{ (int)$igScope->photo }} photo / month,
                                    since {{ \Carbon\Carbon::parse($igScope->start_date)->format('d M Y') }}
                                </p>
                            @else
                                <p class="text-muted small mb-3">No active Instagram plan yet — set one below.</p>
                            @endif

                            <form action="{{ route('clients.scope.update', $client->id) }}" method="POST" autocomplete="off">
                                @csrf
                                <input type="hidden" name="scope" value="1">
                                <div class="row g-2">
                                    <div class="col-4">
                                        <label class="form-label small">Reels / Mo</label>
                                        <input type="number" min="0" name="reels" class="form-control form-control-sm"
                                               value="{{ old('reels', $igScope->reels ?? 0) }}">
                                    </div>
                                    <div class="col-4">
                                        <label class="form-label small">Story / Mo</label>
                                        <input type="number" min="0" name="story" class="form-control form-control-sm"
                                               value="{{ old('story', $igScope->story ?? 0) }}">
                                    </div>
                                    <div class="col-4">
                                        <label class="form-label small">Photo / Mo</label>
                                        <input type="number" min="0" name="photo" class="form-control form-control-sm"
                                               value="{{ old('photo', $igScope->photo ?? 0) }}">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label small">New plan start date <span class="text-danger">*</span></label>
                                        <input type="date" name="start_date" class="form-control form-control-sm" required
                                               value="{{ old('start_date', now()->toDateString()) }}">
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-sm mt-3" style="background:#be185d;color:#fff"
                                        onclick="return confirm('Update Instagram plan? A new plan will start from the chosen date; older dates stay unchanged.')">
                                    <i class="mdi mdi-content-save me-1"></i> Update Instagram Plan
                                </button>
                            </form>
                        </div>
                    </div>

                </div>

                <a href="{{ route('calendar.index', ['client_id' => $client->id]) }}" class="btn btn-sm btn-outline-secondary mt-3">
                    <i class="mdi mdi-calendar me-1"></i> View Calendar
                </a>
            </div>
        </div>
    </div>
</div>

@endsection