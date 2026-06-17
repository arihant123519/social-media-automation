@extends('layouts.app')

@section('title', 'Edit Caption')
@section('page_header', 'Edit Caption & Hashtags')
@section('page_icon', 'mdi mdi-pencil')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('posts.drafts') }}">My Posts</a></li>
    <li class="breadcrumb-item active">Edit caption</li>
@endsection

@section('content')

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body">

                <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
                    <div>
                        <h5 class="mb-1">{{ $post->client->name ?? '—' }} · {{ ucfirst(str_replace('_', ' ', $post->post_type)) }}</h5>
                        <div class="text-muted small">
                            <i class="mdi {{ $post->scope === 0 ? 'mdi-youtube text-danger' : 'mdi-instagram' }}"></i>
                            {{ $post->scope === 0 ? 'YouTube' : 'Instagram' }} ·
                            Winning attempt: <strong>V{{ $winner->attempt_number }}</strong> ({{ $winner->score }}/100)
                        </div>
                    </div>
                </div>

                <div class="alert alert-info py-2 px-3 small mb-3">
                    <i class="mdi mdi-information-outline me-1"></i>
                    Edit the caption/hashtags that will be used when this post publishes.
                    The AI score and media file stay unchanged.
                </div>

                <form method="POST" action="{{ route('posts.updateCaption', $post) }}">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label">Caption</label>
                        <textarea name="caption" class="form-control" rows="6" maxlength="2500">{{ old('caption', $winner->caption) }}</textarea>
                        <div class="form-text">Max 2500 characters</div>
                        @error('caption') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Hashtags</label>
                        <textarea name="hashtags" class="form-control" rows="2" maxlength="600">{{ old('hashtags', $winner->hashtags) }}</textarea>
                        <div class="form-text">Space-separated, e.g. <code>#tag1 #tag2 #tag3</code></div>
                        @error('hashtags') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="mdi mdi-content-save me-1"></i> Save changes
                    </button>
                    <a href="{{ route('posts.drafts') }}" class="btn btn-light">Cancel</a>
                </form>

            </div>
        </div>
    </div>
</div>

@endsection
