@extends('layouts.app')

@section('title', 'Add Client')
@section('page_header', 'Add Client')
@section('page_icon', 'mdi mdi-account-plus')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('clients.index') }}">Clients</a></li>
    <li class="breadcrumb-item active">Add Client</li>
@endsection

@section('content')

<form action="{{ route('clients.store') }}" method="POST" autocomplete="off">
    @csrf

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
                                   value="{{ old('name') }}" placeholder="e.g. Delhi Laser Clinic">
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Phone <span class="text-danger">*</span></label>
                            <input type="text" name="phone"
                                   class="form-control @error('phone') is-invalid @enderror"
                                   value="{{ old('phone') }}" placeholder="+91 98765 43210">
                            @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Email <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="mdi mdi-at"></i></span>
                                <input type="email" name="email"
                                       class="form-control @error('email') is-invalid @enderror"
                                       value="{{ old('email') }}" placeholder="clinic@example.com">
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
                                   value="{{ old('slug') }}" placeholder="e.g. delhi-laser-clinic">
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
                                <option value="" disabled {{ old('industry') ? '' : 'selected' }}>Choose…</option>
                                <option value="dermatologist" {{ old('industry') == 'dermatologist' ? 'selected' : '' }}>Dermatologist</option>
                                <option value="ivf"           {{ old('industry') == 'ivf'           ? 'selected' : '' }}>IVF</option>
                                <option value="other"         {{ old('industry') == 'other'         ? 'selected' : '' }}>Other</option>
                            </select>
                            @error('industry')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">City</label>
                            <select name="city" class="form-select @error('city') is-invalid @enderror">
                                <option value="">— Select city —</option>
                                @foreach(['Delhi','Mumbai','Bangalore','Chennai','Hyderabad','Pune','Other'] as $city)
                                    <option value="{{ $city }}" {{ old('city') == $city ? 'selected' : '' }}>{{ $city }}</option>
                                @endforeach
                            </select>
                            @error('city')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Zip</label>
                            <input type="text" name="zip"
                                   class="form-control @error('zip') is-invalid @enderror"
                                   value="{{ old('zip') }}" placeholder="110001">
                            @error('zip')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">
                                Assigned User <span class="text-danger">*</span>
                                <small class="text-muted fw-normal ms-1">(team auto-assigned)</small>
                            </label>
                            <select name="user_id" class="form-select @error('user_id') is-invalid @enderror">
                                <option value="" disabled {{ old('user_id') ? '' : 'selected' }}>Select user…</option>
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}" {{ old('user_id') == $user->id ? 'selected' : '' }}>
                                        {{ $user->name }}
                                        @if($user->team) — {{ $user->team->name }} @endif
                                    </option>
                                @endforeach
                            </select>
                            @error('user_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

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
                                   value="{{ old('brand_tone') }}" placeholder="e.g. Warm, professional, reassuring">
                            @error('brand_tone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label">Brand voice / do's &amp; don'ts</label>
                            <textarea name="brand_voice" rows="4"
                                      class="form-control @error('brand_voice') is-invalid @enderror"
                                      placeholder="Audience, words to use/avoid, emoji style, CTA preferences, claims to never make…">{{ old('brand_voice') }}</textarea>
                            @error('brand_voice')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card mb-0 h-100">
                <div class="card-body">
                    <h6 class="card-title mb-1">Status</h6>
                    <p class="card-subtitle mb-3">Account availability</p>
                    <div class="d-flex gap-3">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="status" id="status_active"
                                   value="active" {{ old('status', 'active') == 'active' ? 'checked' : '' }}>
                            <label class="form-check-label fw-medium" for="status_active">Active</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="status" id="status_inactive"
                                   value="inactive" {{ old('status') == 'inactive' ? 'checked' : '' }}>
                            <label class="form-check-label fw-medium" for="status_inactive">Inactive</label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="mdi mdi-check me-1"></i> Create Client
                </button>
                <a href="{{ route('clients.index') }}" class="btn btn-light">
                    <i class="mdi mdi-arrow-left me-1"></i> Back
                </a>
            </div>
        </div>

    </div>
</form>

@endsection