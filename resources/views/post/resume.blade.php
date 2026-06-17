@extends('layouts.app')

@section('title', 'Continue Draft')
@section('page_header', 'Continue Uploading')
@section('page_icon', 'mdi mdi-cloud-upload-outline')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('posts.drafts') }}">Drafts</a></li>
    <li class="breadcrumb-item active">{{ $post->keyword ?? 'Resume' }}</li>
@endsection

@push('styles')
<style>
    .suggest-panel { border:1px solid #e5e7eb; border-radius:8px; background:#f9fafb; padding:10px 12px; }
    .suggest-head  { font-size:12px; color:#6b7280; margin-bottom:8px; }
    .suggest-list  { display:flex; flex-direction:column; gap:6px; max-height:240px; overflow-y:auto; padding-right:4px; }
    .suggest-item  {
        display:flex; align-items:flex-start; justify-content:space-between; gap:8px;
        background:#fff; border:1px solid #e5e7eb; border-radius:6px; padding:8px 10px;
    }
    .suggest-text  { flex:1; font-size:13px; line-height:1.4; white-space:pre-wrap; word-break:break-word; }
    .copy-btn      { flex-shrink:0; padding:.2rem .5rem; font-size:12px; }
</style>
@endpush

@php
    $used     = $post->attempts->count();
    $left     = $maxAttempts - $used;
    $best     = (int) ($post->best_score ?? 0);
    $approval = \App\Services\PostPublisher::approvalScore();
@endphp

@section('content')

<div class="row">
    <div class="col-lg-8">

        <div class="card mb-3">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between mb-3 flex-wrap gap-2">
                    <div>
                        <h5 class="mb-1">{{ $post->client->name ?? '—' }} · {{ ucfirst(str_replace('_', ' ', $post->post_type)) }}</h5>
                        <div class="text-muted small">
                            <i class="mdi {{ $post->scope === 0 ? 'mdi-youtube text-danger' : 'mdi-instagram' }}"></i>
                            {{ $post->scope === 0 ? 'YouTube' : 'Instagram' }} · keyword: <strong>{{ $post->keyword }}</strong>
                        </div>
                    </div>
                    <div class="text-end">
                        <div class="text-muted small">Attempts used</div>
                        <div class="fs-4 fw-bold">{{ $used }}/{{ $maxAttempts }}</div>
                    </div>
                </div>

                @if ($used > 0)
                <h6 class="text-muted mt-3 mb-2">
                    Previous attempts
                    <span class="small text-muted">— hover/play to verify you don't re-upload the same file</span>
                </h6>
                <div class="row g-2 mb-3">
                    @foreach ($post->attempts as $a)
                    @php
                        $err     = is_array($a->ai_feedback) ? count($a->ai_feedback['parameters']['spelling_grammar']['errors'] ?? []) : 0;
                        $isVideo = str_starts_with((string) $a->mime, 'video/');
                        // Relative URL — resolves against the request host, works on
                        // localhost AND on the live server. Avoids APP_URL pointing to
                        // a stale/unreachable tunnel.
                        $url     = '/storage/' . ltrim((string) $a->file_path, '/');
                    @endphp
                    <div class="col-md-4">
                        <div class="card h-100" style="border:1px solid #e5e7eb;">
                            <div class="position-relative" style="background:#000; aspect-ratio: 9/16; max-height:230px; overflow:hidden;">
                                @if ($isVideo)
                                    <video src="{{ $url }}" controls preload="metadata"
                                           style="width:100%; height:100%; object-fit:contain;"></video>
                                @else
                                    <img src="{{ $url }}" alt="V{{ $a->attempt_number }} preview"
                                         style="width:100%; height:100%; object-fit:contain;">
                                @endif
                                <span class="badge bg-dark position-absolute top-0 start-0 m-1">
                                    V{{ $a->attempt_number }}
                                </span>
                                <span class="badge {{ $a->score >= $approval ? 'bg-success' : 'bg-warning text-dark' }} position-absolute top-0 end-0 m-1">
                                    {{ $a->score }}/100
                                </span>
                            </div>
                            <div class="card-body p-2">
                                <div class="small text-muted">
                                    {{ $a->mime }} · {{ number_format(($a->file_size ?? 0) / 1024 / 1024, 1) }} MB
                                </div>
                                @if ($err > 0)
                                    <span class="badge bg-danger mt-1">
                                        <i class="mdi mdi-alert-circle-outline"></i> {{ $err }} spelling error(s)
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
                @endif

                @if ($post->final_status === 'approved')
                    <hr>
                    <div class="alert alert-success mb-0">
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                            <div>
                                <i class="mdi mdi-check-circle-outline fs-4"></i>
                                <strong>Post approved!</strong>
                                Best score <span class="badge bg-success">{{ $best }}/100</span>
                                <div class="small text-muted mt-1">
                                    You can publish now, schedule for later, edit the caption, or download the bundle.
                                </div>
                            </div>
                            <div class="btn-group btn-group-sm action-group" role="group" aria-label="Approved post actions">
                                <a href="{{ route('posts.editCaption', $post) }}" class="btn btn-primary"
                                   data-bs-toggle="tooltip" title="Edit caption / hashtags">
                                    <i class="mdi mdi-pencil"></i>
                                </a>
                                <button type="button" class="btn btn-info" data-action="schedule"
                                        data-post-id="{{ $post->id }}"
                                        data-current="{{ $post->scheduled_publish_at?->format('Y-m-d\TH:i') }}"
                                        data-bs-toggle="tooltip" title="Schedule auto-publish">
                                    <i class="mdi mdi-calendar-clock"></i>
                                </button>
                                <button type="button" class="btn btn-success" data-action="publish-preview"
                                        data-post-id="{{ $post->id }}"
                                        data-bs-toggle="tooltip" title="Publish now (with preview)">
                                    <i class="mdi mdi-send"></i>
                                </button>
                                <a href="{{ route('posts.download', $post) }}" class="btn btn-secondary"
                                   data-bs-toggle="tooltip" title="Download bundle">
                                    <i class="mdi mdi-download"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                @elseif ($left <= 0)
                    <div class="alert alert-warning mb-0">
                        <i class="mdi mdi-alert-circle-outline me-1"></i>
                        Maximum 3 attempts reached. No more uploads allowed for this post.
                    </div>
                @else
                @php
                    // Build caption + hashtag suggestions from previous attempts
                    // (your own previous text + each attempt's AI variants).
                    $captionSuggestions = [];
                    $hashtagSuggestions = [];
                    foreach ($post->attempts as $a) {
                        if (trim((string) $a->caption) !== '') {
                            $captionSuggestions[] = ['label' => "V{$a->attempt_number} · You", 'kind' => 'user', 'text' => (string) $a->caption];
                        }
                        if (trim((string) $a->hashtags) !== '') {
                            $hashtagSuggestions[] = ['label' => "V{$a->attempt_number} · You", 'kind' => 'user', 'text' => (string) $a->hashtags];
                        }
                        $fb = is_array($a->ai_feedback) ? $a->ai_feedback : [];
                        // AI caption variants
                        foreach (($fb['recommendations']['caption_variants'] ?? []) as $i => $v) {
                            if (is_string($v) && strlen(trim($v)) > 5) {
                                $captionSuggestions[] = ['label' => "V{$a->attempt_number} · AI suggestion " . ($i + 1), 'kind' => 'ai', 'text' => trim($v)];
                            }
                        }
                        // AI suggested hashtag mix → flatten
                        $aiTags = $fb['recommendations']['suggested_hashtags'] ?? null;
                        if (is_array($aiTags)) {
                            $flat = [];
                            array_walk_recursive($aiTags, function ($t) use (&$flat) { if (is_string($t)) $flat[] = $t; });
                            if (! empty($flat)) {
                                $hashtagSuggestions[] = ['label' => "V{$a->attempt_number} · AI suggestion", 'kind' => 'ai', 'text' => implode(' ', $flat)];
                            }
                        }
                    }
                @endphp

                <hr>
                <h6 class="mb-3">Upload attempt V{{ $used + 1 }}</h6>

                <form id="resumeUploadForm" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="post_id" value="{{ $post->id }}">

                    <div class="mb-3">
                        <label class="form-label">Media file <span class="text-danger">*</span></label>
                        <input type="file" name="file" class="form-control" required
                            accept="{{ in_array($post->post_type, ['photo']) ? 'image/*' : (in_array($post->post_type, ['story']) ? 'image/*,video/*' : 'video/*') }}">
                        <div class="form-text">Max 200 MB</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Caption</label>
                        <textarea name="caption" id="captionInput" class="form-control" rows="3" maxlength="2500" placeholder="Your caption..."></textarea>

                        @if (count($captionSuggestions))
                        <div class="dropdown mt-2">
                            <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button"
                                    data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="mdi mdi-lightbulb-on-outline"></i>
                                {{ count($captionSuggestions) }} suggestion{{ count($captionSuggestions) > 1 ? 's' : '' }} from previous attempts
                            </button>
                            <ul class="dropdown-menu shadow-sm" style="min-width:420px; max-width:560px; max-height:340px; overflow-y:auto;">
                                <li><h6 class="dropdown-header small mb-0">Click any item to paste into the caption box</h6></li>
                                <li><hr class="dropdown-divider my-1"></li>
                                @foreach ($captionSuggestions as $s)
                                <li>
                                    <a class="dropdown-item small py-2" href="#"
                                       data-suggest-target="captionInput"
                                       data-suggest-text="{{ $s['text'] }}"
                                       style="white-space:normal; line-height:1.4;">
                                        <span class="badge {{ $s['kind'] === 'ai' ? 'bg-info' : 'bg-secondary' }}">{{ $s['label'] }}</span>
                                        <div class="mt-1 text-dark">{{ $s['text'] }}</div>
                                    </a>
                                </li>
                                @endforeach
                            </ul>
                        </div>
                        @endif
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Hashtags</label>
                        <input type="text" name="hashtags" id="hashtagsInput" class="form-control" maxlength="600" placeholder="#tag1 #tag2 #tag3">

                        @if (count($hashtagSuggestions))
                        <div class="dropdown mt-2">
                            <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button"
                                    data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="mdi mdi-pound"></i>
                                {{ count($hashtagSuggestions) }} hashtag suggestion{{ count($hashtagSuggestions) > 1 ? 's' : '' }}
                            </button>
                            <ul class="dropdown-menu shadow-sm" style="min-width:420px; max-width:560px; max-height:340px; overflow-y:auto;">
                                <li><h6 class="dropdown-header small mb-0">Click any item to paste into the hashtags box</h6></li>
                                <li><hr class="dropdown-divider my-1"></li>
                                @foreach ($hashtagSuggestions as $s)
                                <li>
                                    <a class="dropdown-item small py-2" href="#"
                                       data-suggest-target="hashtagsInput"
                                       data-suggest-text="{{ $s['text'] }}"
                                       style="white-space:normal; line-height:1.4;">
                                        <span class="badge {{ $s['kind'] === 'ai' ? 'bg-info' : 'bg-secondary' }}">{{ $s['label'] }}</span>
                                        <div class="mt-1 text-primary">{{ $s['text'] }}</div>
                                    </a>
                                </li>
                                @endforeach
                            </ul>
                        </div>
                        @endif
                    </div>

                    <button type="submit" class="btn btn-primary" id="resumeUploadBtn">
                        <i class="mdi mdi-cloud-upload me-1"></i> Upload & Score
                    </button>
                    <a href="{{ route('posts.drafts') }}" class="btn btn-light">Back to drafts</a>
                </form>
                @endif

            </div>
        </div>

        <div id="scoreResult" class="card d-none">
            <div class="card-body">
                <h6 class="card-title mb-3">Result</h6>
                <div id="scoreBody"></div>
                <div class="small text-muted mt-2">Refreshing this page to show the updated state…</div>
            </div>
        </div>

    </div>
</div>

{{-- ─── Schedule modal (same pattern as drafts page) ─── --}}
<div class="modal fade" id="scheduleModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="mdi mdi-calendar-clock me-1"></i> Schedule post</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small mb-3">Pick a date + time. Auto-publish will trigger at this moment (scheduler must be running).</p>
                <div class="mb-3">
                    <label class="form-label">Scheduled date &amp; time</label>
                    <input type="datetime-local" id="schedDtInput" class="form-control" required>
                </div>
                <div id="schedMsg"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-info text-white" id="schedConfirmBtn">
                    <i class="mdi mdi-check me-1"></i> Confirm schedule
                </button>
            </div>
        </div>
    </div>
</div>

{{-- ─── Pre-publish preview modal ─── --}}
<div class="modal fade" id="publishPreviewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="mdi mdi-eye-outline me-1"></i> Preview before publishing</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="ppmBody">
                <div class="text-center py-5"><div class="spinner-border text-primary"></div></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" id="ppmConfirmBtn" disabled>
                    <i class="mdi mdi-send me-1"></i> Confirm & Publish
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
(function () {
    const csrf = document.querySelector('meta[name="csrf-token"]').content;

    // ─── Suggestion dropdown click handler — paste into target input ───
    document.querySelectorAll('[data-suggest-target]').forEach(el => {
        el.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.getElementById(this.dataset.suggestTarget);
            if (!target) return;
            target.value = this.dataset.suggestText || '';
            target.focus();
            target.dispatchEvent(new Event('input', { bubbles: true }));
        });
    });

    // ─── Schedule modal handler ───
    const schedModalEl = document.getElementById('scheduleModal');
    if (schedModalEl) {
        const schedModal   = new bootstrap.Modal(schedModalEl);
        const schedInput   = document.getElementById('schedDtInput');
        const schedMsg     = document.getElementById('schedMsg');
        const schedConfirm = document.getElementById('schedConfirmBtn');

        document.querySelectorAll('[data-action="schedule"]').forEach(btn => {
            btn.addEventListener('click', function () {
                const current = this.dataset.current;
                if (current) {
                    schedInput.value = current;
                } else {
                    const t = new Date(); t.setDate(t.getDate() + 1); t.setHours(10, 0, 0, 0);
                    const pad = n => String(n).padStart(2, '0');
                    schedInput.value = `${t.getFullYear()}-${pad(t.getMonth()+1)}-${pad(t.getDate())}T${pad(t.getHours())}:${pad(t.getMinutes())}`;
                }
                schedMsg.innerHTML = '';
                schedConfirm.disabled = false;
                schedModal.show();
            });
        });

        schedConfirm.addEventListener('click', async function () {
            if (!schedInput.value) { schedMsg.innerHTML = '<div class="alert alert-warning py-2 px-3 mb-0">Pick a date & time.</div>'; return; }
            schedConfirm.disabled = true;
            const original = schedConfirm.innerHTML;
            schedConfirm.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Scheduling...';
            try {
                const fd = new FormData();
                fd.append('scheduled_publish_at', schedInput.value);
                const res = await fetch(`/posts/{{ $post->id }}/schedule`, {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                    body: fd,
                });
                const d = await res.json();
                if (d.success) {
                    schedMsg.innerHTML = `<div class="alert alert-success py-2 px-3 mb-0">Scheduled. See it on the <a href="/calendar">calendar</a>.</div>`;
                    schedConfirm.innerHTML = '<i class="mdi mdi-check"></i> Done';
                    setTimeout(() => window.location.reload(), 1300);
                } else {
                    schedMsg.innerHTML = `<div class="alert alert-danger py-2 px-3 mb-0">${d.error || 'Schedule failed'}</div>`;
                    schedConfirm.innerHTML = original;
                    schedConfirm.disabled = false;
                }
            } catch (e) {
                schedMsg.innerHTML = `<div class="alert alert-danger py-2 px-3 mb-0">Network error: ${e.message}</div>`;
                schedConfirm.innerHTML = original;
                schedConfirm.disabled = false;
            }
        });
    }

    // ─── Pre-publish preview modal handler ───
    const ppmModalEl = document.getElementById('publishPreviewModal');
    if (ppmModalEl) {
        const ppmModal   = new bootstrap.Modal(ppmModalEl);
        const ppmBody    = document.getElementById('ppmBody');
        const ppmConfirm = document.getElementById('ppmConfirmBtn');
        const escapeHtml = s => String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));

        document.querySelectorAll('[data-action="publish-preview"]').forEach(btn => {
            btn.addEventListener('click', async function () {
                ppmBody.innerHTML = '<div class="text-center py-5"><div class="spinner-border text-primary"></div></div>';
                ppmConfirm.disabled = true;
                ppmModal.show();
                try {
                    const res = await fetch(`/posts/{{ $post->id }}/preview`, {
                        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                    });
                    const d = await res.json();
                    if (!d.success) {
                        ppmBody.innerHTML = `<div class="alert alert-danger mb-0">${d.error || 'Failed to load preview'}</div>`;
                        return;
                    }
                    const platform = d.scope === 0 ? '<i class="mdi mdi-youtube text-danger"></i> YouTube' : '<i class="mdi mdi-instagram"></i> Instagram';
                    const mediaTag = (d.mime || '').startsWith('video/')
                        ? `<video controls class="w-100 rounded" style="max-height:420px;" src="${d.media_url}"></video>`
                        : `<img src="${d.media_url}" class="img-fluid rounded" style="max-height:420px;">`;
                    ppmBody.innerHTML = `
                        <div class="mb-3">
                            <span class="badge bg-light text-dark me-1">${platform}</span>
                            <span class="badge bg-light text-dark me-1">${d.post_type.replace('_',' ')}</span>
                            <span class="badge bg-success">Score ${d.score}/100 (V${d.attempt})</span>
                            ${d.client_name ? `<span class="badge bg-info ms-1">${d.client_name}</span>` : ''}
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6 text-center bg-light rounded p-2">${mediaTag}</div>
                            <div class="col-md-6">
                                <label class="form-label small text-muted mb-1">CAPTION</label>
                                <div class="border rounded p-2 mb-3" style="white-space:pre-wrap; font-size:14px;">${escapeHtml(d.caption || '(empty)')}</div>
                                <label class="form-label small text-muted mb-1">HASHTAGS</label>
                                <div class="border rounded p-2 text-primary" style="font-size:13px;">${escapeHtml(d.hashtags || '(none)')}</div>
                            </div>
                        </div>
                        <div class="alert alert-warning small mt-3 mb-0">
                            <i class="mdi mdi-alert-outline me-1"></i>
                            Once published, this content goes live on ${d.scope === 0 ? 'YouTube' : 'Instagram'}.
                        </div>`;
                    ppmConfirm.disabled = false;
                } catch (e) {
                    ppmBody.innerHTML = `<div class="alert alert-danger mb-0">Network error: ${e.message}</div>`;
                }
            });
        });

        ppmConfirm.addEventListener('click', async function () {
            ppmConfirm.disabled = true;
            const original = ppmConfirm.innerHTML;
            ppmConfirm.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Publishing...';
            try {
                const res = await fetch(`/posts/{{ $post->id }}/publish`, {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                });
                const d = await res.json();
                if (d.success) {
                    ppmBody.insertAdjacentHTML('afterbegin',
                        `<div class="alert alert-success"><i class="mdi mdi-check-circle me-1"></i> Published!
                            ${d.external_url ? `<a href="${d.external_url}" target="_blank" class="ms-2">View live</a>` : ''}
                        </div>`);
                    ppmConfirm.innerHTML = '<i class="mdi mdi-check"></i> Done';
                    setTimeout(() => window.location.href = '{{ route('posts.drafts') }}', 1500);
                } else {
                    ppmBody.insertAdjacentHTML('afterbegin', `<div class="alert alert-danger">${d.error || 'Publish failed'}</div>`);
                    ppmConfirm.innerHTML = original;
                    ppmConfirm.disabled = false;
                }
            } catch (e) {
                ppmConfirm.innerHTML = original;
                ppmConfirm.disabled = false;
                alert('Network error: ' + e.message);
            }
        });
    }

    // ─── Upload-and-score handler — reloads page on success so new attempt's
    // preview + current status (approved buttons or remaining attempts) show up.
    const form = document.getElementById('resumeUploadForm');
    if (!form) return;

    form.addEventListener('submit', async function (e) {
        e.preventDefault();
        const btn = document.getElementById('resumeUploadBtn');
        const original = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Scoring...';

        try {
            const res = await fetch('{{ route('posts.upload') }}', {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                body: new FormData(form),
            });
            const data = await res.json();

            const result = document.getElementById('scoreResult');
            const body   = document.getElementById('scoreBody');
            result.classList.remove('d-none');
            result.scrollIntoView({ behavior: 'smooth', block: 'center' });

            if (data.success === false) {
                body.innerHTML = `<div class="alert alert-danger mb-0">${data.error || 'Upload failed'}</div>`;
                btn.disabled = false;
                btn.innerHTML = original;
                return;
            }

            const score = data.score ?? '?';
            const cls   = data.approved ? 'success' : 'warning';
            const gate  = data.approved ? '✓ Approved — saved' : 'Needs another attempt';
            body.innerHTML = `
                <div class="alert alert-${cls} mb-0">
                    <div class="d-flex justify-content-between align-items-center">
                        <strong>Score: ${score}/100</strong>
                        <span><strong>${gate}</strong></span>
                    </div>
                </div>`;
            // Reload page so new V's preview shows in the grid + Publish/Schedule
            // buttons appear if approved.
            setTimeout(() => window.location.reload(), 1500);
        } catch (err) {
            alert('Network error: ' + err.message);
            btn.disabled = false;
            btn.innerHTML = original;
        }
    });
})();
</script>
@endpush
