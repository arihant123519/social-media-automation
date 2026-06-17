@extends('layouts.app')

@section('title', 'Settings')
@section('page_header', 'Settings')
@section('page_icon', 'mdi mdi-cog')

@section('breadcrumb')
    <li class="breadcrumb-item active">Settings</li>
@endsection

@php
    $groupMeta = [
        'ai'        => ['AI (Gemini)',   'mdi mdi-robot'],
        'youtube'   => ['YouTube',       'mdi mdi-youtube'],
        'instagram' => ['Instagram',     'mdi mdi-instagram'],
        'facebook'  => ['Facebook',      'mdi mdi-facebook-box'],
        'meta'      => ['Meta App',      'mdi mdi-facebook'],
        'google'    => ['Google OAuth',  'mdi mdi-google'],
        'mail'      => ['Mail / SMTP',   'mdi mdi-email'],
        'app'       => ['Application',   'mdi mdi-application'],
    ];
@endphp

@section('content')

<div class="row">
    <div class="col-12">
        <div class="alert alert-info alert-permanent d-flex align-items-center" role="alert">
            <i class="mdi mdi-information-outline fs-4 me-2"></i>
            <div>
                These values override your <code>.env</code> file. Leave a secret field
                <strong>blank</strong> to keep the currently stored value. Empty a non-secret
                field to fall back to <code>.env</code>.
            </div>
        </div>
    </div>
</div>

<form method="POST" action="{{ route('settings.update') }}">
    @csrf

    <div class="row">
        @foreach ($groups as $group => $fields)
        <div class="col-md-6 grid-margin stretch-card">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">
                        <i class="{{ $groupMeta[$group][1] ?? 'mdi mdi-cog' }} me-1"></i>
                        {{ $groupMeta[$group][0] ?? ucfirst($group) }}
                    </h4>

                    @foreach ($fields as $field)
                    <div class="mb-3">
                        <label class="form-label d-flex justify-content-between align-items-center">
                            <span>{{ $field['label'] }}</span>
                            @if ($field['secret'] && $field['isSet'])
                                <span class="badge bg-success">Set</span>
                            @elseif (! $field['isSet'])
                                <span class="badge bg-secondary">Not set</span>
                            @endif
                        </label>

                        @if ($field['secret'])
                            <input type="password" name="{{ $field['key'] }}" class="form-control"
                                autocomplete="new-password"
                                placeholder="{{ $field['isSet'] ? '•••••••• (leave blank to keep)' : 'Not set' }}">
                        @else
                            <input type="text" name="{{ $field['key'] }}" class="form-control"
                                value="{{ $field['value'] }}">
                        @endif
                    </div>
                    @endforeach

                    @if (in_array($group, ['instagram', 'facebook']))
                        @php $st = $tokenStatus[$group] ?? null; @endphp
                        <div class="d-flex align-items-start gap-2 small rounded p-2 {{ $st ? ($st['healthy'] ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning') : 'bg-light text-muted' }}">
                            <i class="mdi {{ $st ? 'mdi-shield-check' : 'mdi-shield-refresh-outline' }} fs-6"></i>
                            <div>
                                @if ($st)
                                    <strong>Auto-authorized.</strong> Long-lived token valid till
                                    <strong>{{ $st['expires_human'] }}</strong> ({{ $st['days_left'] }} days) — auto-refreshed weekly, no re-login needed.
                                @else
                                    Paste a token here with <strong>Meta App ID + Secret</strong> set, and it auto-converts to a long-lived (60-day) token that refreshes itself.
                                @endif
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
        @endforeach
    </div>

    <div class="row">
        <div class="col-12 mb-4">
            <button type="submit" class="btn btn-primary">
                <i class="mdi mdi-content-save me-1"></i> Save Settings
            </button>
        </div>
    </div>
</form>

@endsection
