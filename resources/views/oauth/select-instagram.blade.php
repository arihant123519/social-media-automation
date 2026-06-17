@extends('layouts.app')

@section('title', 'Choose Instagram Account')
@section('page_header', 'Choose Instagram Account')
@section('page_icon', 'mdi mdi-instagram')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('clients.index') }}">Clients</a></li>
    <li class="breadcrumb-item"><a href="{{ route('clients.settings', $client) }}">{{ $client->name }}</a></li>
    <li class="breadcrumb-item active">Choose Instagram</li>
@endsection

@section('content')

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-1">Multiple Instagram accounts found</h4>
                <p class="text-muted mb-4">
                    The Facebook account you connected manages more than one Page with a linked
                    Instagram Business account. Pick the one to use for <strong>{{ $client->name }}</strong>.
                </p>

                <form method="POST" action="{{ route('oauth.instagram.selectpage') }}">
                    @csrf

                    @foreach ($pages as $i => $p)
                    <label class="d-flex align-items-center border rounded p-3 mb-2" style="cursor:pointer;">
                        <input class="form-check-input me-3" type="radio" name="ig_id"
                            value="{{ $p['ig_id'] }}" {{ $i === 0 ? 'checked' : '' }} required>
                        <span class="me-3"><i class="mdi mdi-instagram fs-3 text-danger"></i></span>
                        <span>
                            <span class="d-block fw-semibold">
                                {{ $p['ig_username'] ? '@' . $p['ig_username'] : 'IG ID ' . $p['ig_id'] }}
                            </span>
                            <span class="d-block text-muted small">
                                Page: {{ $p['page_name'] }} · IG ID: {{ $p['ig_id'] }}
                            </span>
                        </span>
                    </label>
                    @endforeach

                    <div class="mt-4 d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="mdi mdi-check me-1"></i> Connect this account
                        </button>
                        <a href="{{ route('clients.settings', $client) }}" class="btn btn-light">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection
