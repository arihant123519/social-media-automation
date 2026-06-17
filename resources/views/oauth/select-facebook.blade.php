@extends('layouts.app')

@section('title', 'Choose Facebook Page')
@section('page_header', 'Choose Facebook Page')
@section('page_icon', 'mdi mdi-facebook')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('clients.index') }}">Clients</a></li>
    <li class="breadcrumb-item"><a href="{{ route('clients.settings', $client) }}">{{ $client->name }}</a></li>
    <li class="breadcrumb-item active">Choose Page</li>
@endsection

@section('content')

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-1">Multiple Facebook Pages found</h4>
                <p class="text-muted mb-4">
                    Pick the Page to publish to for <strong>{{ $client->name }}</strong>.
                </p>

                <form method="POST" action="{{ route('oauth.facebook.selectpage') }}">
                    @csrf

                    @foreach ($pages as $i => $p)
                    <label class="d-flex align-items-center border rounded p-3 mb-2" style="cursor:pointer;">
                        <input class="form-check-input me-3" type="radio" name="page_id"
                            value="{{ $p['page_id'] }}" {{ $i === 0 ? 'checked' : '' }} required>
                        <span class="me-3"><i class="mdi mdi-facebook fs-3 text-primary"></i></span>
                        <span>
                            <span class="d-block fw-semibold">{{ $p['page_name'] }}</span>
                            <span class="d-block text-muted small">Page ID: {{ $p['page_id'] }}</span>
                        </span>
                    </label>
                    @endforeach

                    <div class="mt-4 d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="mdi mdi-check me-1"></i> Connect this Page
                        </button>
                        <a href="{{ route('clients.settings', $client) }}" class="btn btn-light">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection
