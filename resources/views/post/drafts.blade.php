@extends('layouts.app')

@section('title', 'My Posts')
@section('page_header', 'My Posts')
@section('page_icon', 'mdi mdi-content-save-edit-outline')

@section('breadcrumb')
    <li class="breadcrumb-item">Posts</li>
    <li class="breadcrumb-item active">Drafts</li>
@endsection

@push('styles')
<style>
    .status-pill { font-size: 11px; padding: 3px 8px; border-radius: 10px; font-weight: 500; }
    .pill-draft  { background:#e9ecef; color:#495057; }
    .pill-low    { background:#fff3cd; color:#856404; }
    .pill-good   { background:#d1ecf1; color:#0c5460; }

    /* Tight icon-only action toolbar — semantic colored buttons in a group */
    .action-group .btn {
        padding: .3rem .55rem;
        line-height: 1;
        color: #fff !important;
        border: none;
        transition: transform .12s, filter .12s, box-shadow .12s;
    }
    .action-group .btn i { font-size: 16px; }
    .action-group .btn:hover {
        filter: brightness(1.08);
        transform: translateY(-1px);
        box-shadow: 0 2px 6px rgba(0,0,0,.15);
        z-index: 2;
        position: relative;
    }
    .action-group .btn:active { transform: translateY(0); }
    /* Subtle vertical divider between connected buttons */
    .action-group .btn + .btn { border-left: 1px solid rgba(255,255,255,.18); }
    /* Highlight a row when arrived via #post-<id> (from the dashboard) */
    @keyframes postRowFlash { 0% { background:#fde68a; } 100% { background:transparent; } }
    tr.js-post-row.is-target > td { animation: postRowFlash 2.4s ease-out; }
    tr.js-post-row.is-target { outline:2px solid #f59e0b; outline-offset:-2px; }
</style>
@endpush

@section('content')

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">

                @if (session('success'))
                    <div class="alert alert-success py-2 px-3 mb-3">{{ session('success') }}</div>
                @endif
                @if (session('error'))
                    <div class="alert alert-danger py-2 px-3 mb-3">{{ session('error') }}</div>
                @endif

                <div class="d-flex justify-content-between align-items-center mb-3">
                    <p class="text-muted mb-0">
                        Posts you started but haven't finished — pick up where you left off.
                    </p>
                    <a href="{{ route('Post.index') }}" class="btn btn-primary btn-sm">
                        <i class="mdi mdi-plus me-1"></i> New Post
                    </a>
                </div>

                @if (! $approved->isEmpty())
                <h6 class="text-success mb-2 mt-1">
                    <i class="mdi mdi-check-circle me-1"></i> Approved (ready to publish or schedule)
                </h6>
                <div class="table-responsive mb-4">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Client</th>
                                <th>Platform</th>
                                <th>Type</th>
                                <th>Keyword</th>
                                <th>Plan date</th>
                                <th class="text-center">Score</th>
                                <th>Last activity</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($approved as $a)
                            @php
                                $aPlan       = $a->scheduled_publish_at ?: $a->scheduled_date;
                                $isScheduled = $a->publish_status === 'scheduled' && $a->scheduled_publish_at;
                            @endphp
                            <tr id="post-{{ $a->id }}" class="js-post-row">
                                <td>{{ $a->client->name ?? '—' }}</td>
                                <td>
                                    <i class="mdi {{ $a->scope === 0 ? 'mdi-youtube text-danger' : 'mdi-instagram' }}"></i>
                                    {{ $a->scope === 0 ? 'YouTube' : 'Instagram' }}
                                </td>
                                <td>
                                    {{ ucfirst(str_replace('_', ' ', $a->post_type)) }}
                                    @if ($isScheduled)
                                        <div class="text-info small mt-1">
                                            <i class="mdi mdi-calendar-clock"></i>
                                            Scheduled: {{ $a->scheduled_publish_at->format('d M Y, h:i A') }}
                                        </div>
                                    @endif
                                </td>
                                <td><span class="text-truncate d-inline-block" style="max-width:200px;">{{ $a->keyword ?? '—' }}</span></td>
                                <td>
                                    @if ($aPlan)
                                        <span class="small">{{ \Carbon\Carbon::parse($aPlan)->format('d M Y') }}</span>
                                    @else
                                        <span class="text-muted small">—</span>
                                    @endif
                                </td>
                                <td class="text-center"><span class="badge bg-success">{{ $a->best_score }}/100</span></td>
                                <td><span class="text-muted small">{{ $a->updated_at?->diffForHumans() }}</span></td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm action-group" role="group" aria-label="Post actions">
                                        <a href="{{ route('posts.editCaption', $a) }}" class="btn btn-primary"
                                           data-bs-toggle="tooltip" title="Edit caption / hashtags">
                                            <i class="mdi mdi-pencil"></i>
                                        </a>
                                        <button type="button" class="btn btn-info" data-action="schedule"
                                                data-post-id="{{ $a->id }}"
                                                data-current="{{ $a->scheduled_publish_at?->format('Y-m-d\TH:i') }}"
                                                data-bs-toggle="tooltip"
                                                title="{{ $isScheduled ? 'Re-schedule for a different date / time' : 'Schedule auto-publish on a calendar date' }}">
                                            <i class="mdi mdi-calendar-clock"></i>
                                        </button>
                                        @if ($isScheduled)
                                            <button type="button" class="btn btn-warning" data-action="unschedule" data-post-id="{{ $a->id }}"
                                                    data-bs-toggle="tooltip" title="Cancel schedule">
                                                <i class="mdi mdi-calendar-remove"></i>
                                            </button>
                                        @endif
                                        <button type="button" class="btn btn-success" data-action="publish-preview" data-post-id="{{ $a->id }}"
                                                data-bs-toggle="tooltip" title="Publish now (with preview)">
                                            <i class="mdi mdi-send"></i>
                                        </button>
                                        <a href="{{ route('posts.download', $a) }}" class="btn btn-secondary"
                                           data-bs-toggle="tooltip" title="Download bundle (media + caption + report)">
                                            <i class="mdi mdi-download"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <h6 class="text-muted mb-2 mt-3">
                    <i class="mdi mdi-content-save-edit-outline me-1"></i> Drafts (in progress)
                </h6>
                @endif

                @if ($drafts->isEmpty() && $approved->isEmpty())
                    <div class="text-center py-5 text-muted">
                        <i class="mdi mdi-content-save-edit-outline" style="font-size:48px;"></i>
                        <p class="mt-2 mb-0">No posts yet. Start a post to see it here.</p>
                    </div>
                @elseif ($drafts->isEmpty())
                    <div class="text-muted small">No active drafts.</div>
                @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Client</th>
                                <th>Platform</th>
                                <th>Type</th>
                                <th>Keyword</th>
                                <th>Plan date</th>
                                <th class="text-center">Attempts</th>
                                <th class="text-center">Best Score</th>
                                <th>Last activity</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($drafts as $d)
                            @php
                                $used  = $d->attempts->count();
                                $score = (int) ($d->best_score ?? 0);
                                $pill  = $used === 0 ? 'pill-draft' : ($score >= \App\Services\PostPublisher::approvalScore() ? 'pill-good' : 'pill-low');
                                $planDate = $d->scheduled_publish_at ?: $d->scheduled_date;
                            @endphp
                            <tr id="post-{{ $d->id }}" class="js-post-row">
                                <td>{{ $d->client->name ?? '—' }}</td>
                                <td>
                                    <i class="mdi {{ $d->scope === 0 ? 'mdi-youtube text-danger' : 'mdi-instagram text-purple' }}"></i>
                                    {{ $d->scope === 0 ? 'YouTube' : 'Instagram' }}
                                </td>
                                <td>{{ ucfirst(str_replace('_', ' ', $d->post_type)) }}</td>
                                <td><span class="text-truncate d-inline-block" style="max-width:220px;">{{ $d->keyword ?? '—' }}</span></td>
                                <td>
                                    @if ($planDate)
                                        <span class="small">{{ \Carbon\Carbon::parse($planDate)->format('d M Y') }}</span>
                                    @else
                                        <span class="text-muted small">—</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <span class="status-pill {{ $pill }}">V{{ $used }}/3</span>
                                </td>
                                <td class="text-center">{{ $score > 0 ? $score : '—' }}</td>
                                <td><span class="text-muted small">{{ $d->updated_at?->diffForHumans() }}</span></td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm action-group" role="group" aria-label="Draft actions">
                                        <a href="{{ route('posts.resume', $d) }}" class="btn btn-primary"
                                           data-bs-toggle="tooltip" title="Continue uploading next attempt">
                                            <i class="mdi mdi-play"></i>
                                        </a>
                                        <button type="button" class="btn btn-danger" data-action="delete-draft" data-post-id="{{ $d->id }}"
                                                data-bs-toggle="tooltip" title="Delete draft (removes uploads too)">
                                            <i class="mdi mdi-trash-can-outline"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endif

            </div>
        </div>
    </div>
</div>

{{-- ─── Schedule modal (Phase 1 #1 — link approved post to a calendar date) ─── --}}
<div class="modal fade" id="scheduleModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="mdi mdi-calendar-clock me-1"></i> Schedule post
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small mb-3">
                    Pick a date + time. The post will appear on the calendar and auto-publish at this moment
                    (scheduler must be running on the server).
                </p>
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

{{-- ─── Pre-publish preview modal (Phase 1 #4) ─── --}}
<div class="modal fade" id="publishPreviewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="mdi mdi-eye-outline me-1"></i> Preview before publishing
                </h5>
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

@push('scripts')
<script>
// Scroll to + flash the post referenced by #post-<id> (deep-link from the
// dashboard's Unscheduled Posts cards).
(function () {
    function focusHashPost() {
        if (!location.hash.startsWith('#post-')) return;
        var row = document.getElementById(location.hash.slice(1));
        if (!row) return;
        row.scrollIntoView({ behavior: 'smooth', block: 'center' });
        row.classList.remove('is-target');
        void row.offsetWidth; // restart the flash if re-triggered
        row.classList.add('is-target');
    }
    document.addEventListener('DOMContentLoaded', focusHashPost);
    window.addEventListener('hashchange', focusHashPost);
})();
</script>
<script>
(function () {
    let currentPostId = null;
    const modalEl   = document.getElementById('publishPreviewModal');
    const body      = document.getElementById('ppmBody');
    const confirmBtn = document.getElementById('ppmConfirmBtn');
    const modal     = new bootstrap.Modal(modalEl);

    document.querySelectorAll('[data-action="publish-preview"]').forEach(btn => {
        btn.addEventListener('click', async function () {
            currentPostId = this.dataset.postId;
            body.innerHTML = '<div class="text-center py-5"><div class="spinner-border text-primary"></div></div>';
            confirmBtn.disabled = true;
            modal.show();

            try {
                const res = await fetch(`/posts/${currentPostId}/preview`, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                });
                const d = await res.json();
                if (!d.success) {
                    body.innerHTML = `<div class="alert alert-danger mb-0">${d.error || 'Failed to load preview'}</div>`;
                    return;
                }

                const platform = d.scope === 0 ? '<i class="mdi mdi-youtube text-danger"></i> YouTube' : '<i class="mdi mdi-instagram"></i> Instagram';
                const mediaTag = (d.mime || '').startsWith('video/')
                    ? `<video controls class="w-100 rounded" style="max-height:420px;" src="${d.media_url}"></video>`
                    : `<img src="${d.media_url}" class="img-fluid rounded" style="max-height:420px;">`;

                body.innerHTML = `
                    <div class="mb-3">
                        <span class="badge bg-light text-dark me-1">${platform}</span>
                        <span class="badge bg-light text-dark me-1">${d.post_type.replace('_', ' ')}</span>
                        <span class="badge bg-success">Score ${d.score}/100 (V${d.attempt})</span>
                        ${d.client_name ? `<span class="badge bg-info ms-1">${d.client_name}</span>` : ''}
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6 text-center bg-light rounded p-2">
                            ${mediaTag}
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small text-muted mb-1">CAPTION</label>
                            <div class="border rounded p-2 mb-3" style="white-space:pre-wrap; font-size:14px;">${escapeHtml(d.caption || '(empty)')}</div>
                            <label class="form-label small text-muted mb-1">HASHTAGS</label>
                            <div class="border rounded p-2 text-primary" style="font-size:13px;">${escapeHtml(d.hashtags || '(none)')}</div>
                        </div>
                    </div>
                    <div class="alert alert-warning small mt-3 mb-0">
                        <i class="mdi mdi-alert-outline me-1"></i>
                        Once published, this content goes live on ${d.scope === 0 ? 'YouTube' : 'Instagram'} and cannot be unpublished from here.
                    </div>
                `;
                confirmBtn.disabled = false;
            } catch (e) {
                body.innerHTML = `<div class="alert alert-danger mb-0">Network error: ${e.message}</div>`;
            }
        });
    });

    confirmBtn.addEventListener('click', async function () {
        if (!currentPostId) return;
        confirmBtn.disabled = true;
        const original = confirmBtn.innerHTML;
        confirmBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Publishing...';

        try {
            const res = await fetch(`/posts/${currentPostId}/publish`, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                },
            });
            const d = await res.json();
            if (d.success) {
                body.insertAdjacentHTML('afterbegin',
                    `<div class="alert alert-success">
                        <i class="mdi mdi-check-circle me-1"></i> Published!
                        ${d.external_url ? `<a href="${d.external_url}" target="_blank" class="ms-2">View live</a>` : ''}
                    </div>`);
                confirmBtn.innerHTML = '<i class="mdi mdi-check"></i> Published';
                setTimeout(() => window.location.reload(), 1500);
            } else {
                body.insertAdjacentHTML('afterbegin',
                    `<div class="alert alert-danger">${d.error || 'Publish failed'}</div>`);
                confirmBtn.innerHTML = original;
                confirmBtn.disabled = false;
            }
        } catch (e) {
            confirmBtn.innerHTML = original;
            confirmBtn.disabled = false;
            alert('Network error: ' + e.message);
        }
    });

    function escapeHtml(s) {
        return String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    }

    // ─── Schedule modal handler ───
    let schedPostId = null;
    const schedModalEl  = document.getElementById('scheduleModal');
    const schedModal    = new bootstrap.Modal(schedModalEl);
    const schedInput    = document.getElementById('schedDtInput');
    const schedMsg      = document.getElementById('schedMsg');
    const schedConfirm  = document.getElementById('schedConfirmBtn');

    document.querySelectorAll('[data-action="schedule"]').forEach(btn => {
        btn.addEventListener('click', function () {
            schedPostId = this.dataset.postId;
            const current = this.dataset.current;
            // Default to current scheduled time or tomorrow 10:00 AM.
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

    // ─── Delete draft handler ───
    document.querySelectorAll('[data-action="delete-draft"]').forEach(btn => {
        btn.addEventListener('click', async function () {
            if (!confirm('Delete this draft? Uploaded files will be removed.')) return;
            const id = this.dataset.postId;
            this.disabled = true;
            try {
                const res = await fetch(`/posts/${id}/draft`, {
                    method: 'DELETE',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                });
                // Controller returns a redirect (success message via session); reload either way.
                if (res.ok || res.redirected || res.status === 302) {
                    window.location.reload();
                } else {
                    alert('Delete failed');
                    this.disabled = false;
                }
            } catch (e) {
                alert('Network error: ' + e.message);
                this.disabled = false;
            }
        });
    });

    // ─── Unschedule handler ───
    document.querySelectorAll('[data-action="unschedule"]').forEach(btn => {
        btn.addEventListener('click', async function () {
            if (!confirm('Cancel this schedule? The post will stay approved but won\'t auto-publish.')) return;
            const id = this.dataset.postId;
            this.disabled = true;
            try {
                const res = await fetch(`/posts/${id}/unschedule`, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                });
                const d = await res.json();
                if (d.success) {
                    window.location.reload();
                } else {
                    alert(d.error || 'Unschedule failed');
                    this.disabled = false;
                }
            } catch (e) {
                alert('Network error: ' + e.message);
                this.disabled = false;
            }
        });
    });

    schedConfirm.addEventListener('click', async function () {
        if (!schedPostId) return;
        if (!schedInput.value) {
            schedMsg.innerHTML = '<div class="alert alert-warning py-2 px-3 mb-0">Pick a date & time.</div>';
            return;
        }

        schedConfirm.disabled = true;
        const original = schedConfirm.innerHTML;
        schedConfirm.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Scheduling...';

        try {
            const fd = new FormData();
            fd.append('scheduled_publish_at', schedInput.value);
            const res = await fetch(`/posts/${schedPostId}/schedule`, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                },
                body: fd,
            });
            const d = await res.json();
            if (d.success) {
                schedMsg.innerHTML = `<div class="alert alert-success py-2 px-3 mb-0">
                    <i class="mdi mdi-check-circle me-1"></i>
                    Scheduled. View it on the <a href="/calendar">calendar</a>.
                </div>`;
                schedConfirm.innerHTML = '<i class="mdi mdi-check"></i> Done';
                setTimeout(() => window.location.reload(), 1500);
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
})();
</script>
@endpush

@endsection
