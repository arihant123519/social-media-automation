@extends('layouts.app')

@section('title', 'Add Scope')
@section('page_header', 'Add Scope')
@section('page_icon', 'mdi mdi-account-plus')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('clients.index') }}">Clients</a></li>
    <li class="breadcrumb-item active">Add Scope</li>
@endsection

@section('content')
    @if ($errors->any())
        <div class="alert alert-danger">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif
    <form action="{{ route('clients.scope.store') }}" method="POST" autocomplete="off">
        @csrf

        <div class="card">
            <div class="card-body">

                <div class="row g-3">

                    <!-- Client -->
                    <div class="col-md-4">
                        <label class="form-label">Select Client *</label>
                        <select name="client_id" class="form-select @error('client_id') is-invalid @enderror">
                            <option value="">Choose…</option>
                            @foreach ($clients as $client)
                                <option value="{{ $client->id }}" {{ old('client_id') == $client->id ? 'selected' : '' }}>
                                    {{ $client->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Scope -->
                    <div class="col-md-4">
                        <label class="form-label">Select Scope *</label>
                        <select name="scope" id="scopeSelect" class="form-select @error('scope') is-invalid @enderror">
                            <option value="">Choose…</option>
                            <option value="0" {{ old('scope') == '0' ? 'selected' : '' }}>Youtube</option>
                            <option value="1" {{ old('scope') == '1' ? 'selected' : '' }}>Instagram</option>
                        </select>
                    </div>

                    <!-- Start Date -->
                    <div class="col-md-4">
                        <label class="form-label">Start Date *</label>
                        <input type="date" name="start_date" class="form-control" value="{{ old('start_date') }}">
                    </div>

                    <!-- 🔴 YouTube Fields -->
                    <div id="youtubeFields" class="d-none w-100">
                        <div class="row g-3 mt-1">
                            <div class="col-md-4">
                                <label class="form-label">Long Video / Month</label>
                                <input type="number" name="long_video" class="form-control" value="{{ old('long_video') }}" placeholder="5">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Short Video / Month</label>
                                <input type="number" name="short_video" class="form-control" value="{{ old('short_video') }}" placeholder="5">
                            </div>
                        </div>
                    </div>

                    <!-- Instagram Fields -->
                    <div id="instagramFields" class="d-none w-100">
                        <div class="row g-3 mt-1">
                            <div class="col-md-4">
                                <label class="form-label">Reels / Month</label>
                                <input type="number" name="reels" class="form-control" value="{{ old('reels') }}" placeholder="5">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Story / Month</label>
                                <input type="number" name="story" class="form-control" value="{{ old('story') }}" placeholder="30">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Photo / Month</label>
                                <input type="number" name="photo" class="form-control" value="{{ old('photo') }}" placeholder="5">
                            </div>
                        </div>
                    </div>

                    <!-- Notes -->
                    <div class="col-md-12">
                        <label class="form-label">Notes</label>
                       <textarea name="notes" class="form-control" rows="2">{{ old('notes') }}</textarea>
                    </div>

                    <!-- Buttons -->
                    <div class="col-12">
                        <div class="d-flex gap-2 mt-2">
                            <button type="submit" class="btn btn-primary">Save</button>
                            <a href="{{ route('clients.index') }}" class="btn btn-light">Back</a>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </form>

@endsection
<script>
    document.addEventListener('DOMContentLoaded', function() {

        const scope = document.getElementById('scopeSelect');
        const yt = document.getElementById('youtubeFields');
        const insta = document.getElementById('instagramFields');

        function toggleFields(value) {
            yt.classList.add('d-none');
            insta.classList.add('d-none');

            if (value == "0") yt.classList.remove('d-none');
            if (value == "1") insta.classList.remove('d-none');
        }

        scope.addEventListener('change', function() {
            toggleFields(this.value);
        });

        toggleFields(scope.value);
    });
</script>
