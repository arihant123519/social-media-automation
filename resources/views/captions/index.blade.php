@extends('layouts.app')

@section('title', 'Caption Drafts')
@section('page_header', 'Weekly Caption Drafts')
@section('page_icon', 'mdi mdi-format-quote-close')

@section('breadcrumb')
    <li class="breadcrumb-item active">Caption Drafts</li>
@endsection

@php
    // Platform visuals keyed off CaptionDraft::platformLabel()
    $platformMeta = [
        'YouTube'   => ['icon' => 'mdi-youtube',        'color' => '#ff0000'],
        'Instagram' => ['icon' => 'mdi-instagram',      'color' => '#e1306c'],
        'Facebook'  => ['icon' => 'mdi-facebook',       'color' => '#1877f2'],
        'LinkedIn'  => ['icon' => 'mdi-linkedin',       'color' => '#0a66c2'],
        'Social'    => ['icon' => 'mdi-share-variant',  'color' => '#64748b'],
    ];
@endphp

@push('styles')
<style>
    /* ── Toolbar ── */
    .caption-toolbar {
        background: #fff;
        border: 1px solid rgba(0,0,0,.06);
        border-radius: .9rem;
        padding: .85rem 1.1rem;
        box-shadow: 0 1px 2px rgba(0,0,0,.04);
    }
    .caption-toolbar .client-select { min-width: 210px; }
    .btn-generate {
        background: linear-gradient(135deg, #3b82f6, #6366f1);
        border: none; color: #fff; font-weight: 600;
        box-shadow: 0 8px 18px -8px rgba(59,130,246,.7);
    }
    .btn-generate:hover { color: #fff; filter: brightness(1.05); }
    .btn-generate:disabled { opacity: .85; }

    /* ── Day header ── */
    .day-header {
        position: sticky; top: 64px; z-index: 3;
        background: #fff;
        border: 1px solid rgba(0,0,0,.06);
        border-left: 4px solid #6366f1;
        border-radius: .7rem;
        padding: .55rem 1rem;
        box-shadow: 0 1px 2px rgba(0,0,0,.04);
    }
    .day-header .day-count {
        font-size: .72rem; font-weight: 600;
        background: #eef2ff; color: #4f46e5;
        padding: .2rem .6rem; border-radius: 999px;
    }

    /* ── Draft card ── */
    .draft-card {
        background: #fff;
        border: 1px solid rgba(0,0,0,.07);
        border-radius: .9rem;
        transition: box-shadow .2s, transform .2s, border-color .2s;
        overflow: hidden;
    }
    .draft-card:hover {
        box-shadow: 0 .55rem 1.5rem rgba(2,6,23,.10);
        transform: translateY(-2px);
        border-color: rgba(99,102,241,.35);
    }
    .draft-card .card-accent { height: 4px; }

    .platform-pill {
        display: inline-flex; align-items: center; gap: .35rem;
        font-size: .72rem; font-weight: 600;
        padding: .25rem .6rem; border-radius: 999px;
        background: #f1f5f9; color: #475569;
    }
    .platform-pill i { font-size: .95rem; }
    .theme-chip {
        font-size: .68rem; letter-spacing: .4px; font-weight: 700;
        text-transform: uppercase;
        padding: .28rem .55rem; border-radius: 999px;
        background: #eef2ff; color: #4f46e5;
    }
    .status-pill {
        font-size: .68rem; font-weight: 700; text-transform: uppercase;
        padding: .28rem .6rem; border-radius: 999px;
    }
    .status-draft  { background: #fef3c7; color: #b45309; }
    .status-edited { background: #dcfce7; color: #15803d; }

    .caption-label {
        font-size: .68rem; font-weight: 600; text-transform: uppercase;
        letter-spacing: .4px; color: #94a3b8; margin-bottom: .25rem;
    }
    .caption-box, .hashtag-box {
        border: 1px solid #e2e8f0; border-radius: .6rem;
        resize: none; overflow: hidden;
        font-size: .85rem; line-height: 1.5;
        transition: border-color .15s, box-shadow .15s;
    }
    .caption-box:focus, .hashtag-box:focus {
        border-color: #818cf8;
        box-shadow: 0 0 0 .18rem rgba(99,102,241,.15);
    }
    .hashtag-box { color: #4f46e5; font-weight: 500; }
    .char-count { font-size: .68rem; color: #94a3b8; }

    .draft-actions .btn { border-radius: .55rem; }

    /* ── Empty state ── */
    .empty-state {
        background: #fff; border: 1px dashed #cbd5e1; border-radius: 1rem;
    }
    .empty-state .empty-icon {
        width: 84px; height: 84px; border-radius: 50%;
        display: inline-flex; align-items: center; justify-content: center;
        background: linear-gradient(135deg, #eef2ff, #e0e7ff);
        color: #6366f1; font-size: 2.4rem;
    }

    /* ── Generating overlay ── */
    #generating-overlay {
        position: fixed; inset: 0; z-index: 1090;
        background: rgba(15,23,42,.55); backdrop-filter: blur(3px);
        display: none; align-items: center; justify-content: center;
    }
    #generating-overlay.show { display: flex; }
    .gen-card {
        background: #fff; border-radius: 1rem; padding: 2rem 2.4rem;
        text-align: center; max-width: 360px;
        box-shadow: 0 20px 50px -12px rgba(0,0,0,.4);
    }
    .gen-spinner {
        width: 56px; height: 56px; border-radius: 50%;
        border: 5px solid #e0e7ff; border-top-color: #6366f1;
        margin: 0 auto 1.1rem; animation: gen-spin .8s linear infinite;
    }
    @keyframes gen-spin { to { transform: rotate(360deg); } }
    .gen-dots::after {
        content: ''; animation: gen-dots 1.4s steps(4,end) infinite;
    }
    @keyframes gen-dots {
        0% { content: ''; } 25% { content: '.'; }
        50% { content: '..'; } 75% { content: '...'; }
    }

    @media (max-width: 575.98px){
        .toolbar-stack { flex-direction: column; align-items: stretch !important; gap: .6rem; }
        .toolbar-stack > * { width: 100%; }
        .toolbar-stack .client-select { min-width: 0; width: 100%; }
        .day-header { top: 56px; }
    }
</style>
@endpush

@section('content')

<div class="caption-toolbar d-flex flex-wrap align-items-center justify-content-between gap-2 mb-4 toolbar-stack">
    <form method="GET" class="d-flex align-items-center gap-2">
        <label class="text-muted small mb-0 fw-semibold d-none d-sm-block">Client</label>
        <select name="client_id" class="form-select form-select-sm client-select" onchange="this.form.submit()">
            @foreach($clients as $c)
                <option value="{{ $c->id }}" {{ $clientId == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
            @endforeach
        </select>
    </form>

    <form method="POST" action="{{ route('captions.generate') }}" class="d-flex" id="generate-form">
        @csrf
        <input type="hidden" name="client_id" value="{{ $clientId }}">
        <button class="btn btn-generate btn-sm px-3" type="submit" id="generate-btn">
            <i class="mdi mdi-robot-happy-outline me-1"></i> Generate next 7 days
        </button>
    </form>
</div>

@if($drafts->isEmpty())
    <div class="empty-state text-center py-5 px-3">
        <div class="empty-icon mb-3"><i class="mdi mdi-calendar-text-outline"></i></div>
        <h5 class="fw-bold mb-1">No drafts yet for {{ $selectedClient->name ?? 'this client' }}</h5>
        <p class="text-muted mb-4">Captions auto-generate every Sunday for the coming week — or generate them right now.</p>
        <button class="btn btn-generate" type="submit" form="generate-form">
            <i class="mdi mdi-robot-happy-outline me-1"></i> Generate next 7 days
        </button>
    </div>
@else
    @foreach($drafts as $date => $items)
        @php $d = \Carbon\Carbon::parse($date); @endphp
        <div class="day-header mb-3 d-flex align-items-center justify-content-between">
            <span class="fw-semibold text-dark">
                <i class="mdi mdi-calendar-blank-outline me-1 text-primary"></i>{{ $d->format('l, j M Y') }}
            </span>
            <span class="day-count">{{ $items->count() }} post{{ $items->count() > 1 ? 's' : '' }}</span>
        </div>

        <div class="row g-3 mb-4">
            @foreach($items as $draft)
                @php
                    $label = $draft->platformLabel();
                    $pm = $platformMeta[$label] ?? $platformMeta['Social'];
                @endphp
                <div class="col-12 col-md-6 col-xxl-4" id="draft-{{ $draft->id }}">
                    <div class="draft-card h-100 d-flex flex-column">
                        <div class="card-accent" style="background: {{ $pm['color'] }}"></div>
                        <div class="p-3 d-flex flex-column flex-grow-1">
                            <div class="d-flex align-items-center justify-content-between mb-3 gap-2">
                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                    <span class="platform-pill">
                                        <i class="mdi {{ $pm['icon'] }}" style="color: {{ $pm['color'] }}"></i>
                                        {{ $label }}
                                    </span>
                                    <span class="theme-chip">{{ $draft->theme ?? 'General' }}</span>
                                </div>
                                <span class="status-pill {{ $draft->status === 'edited' ? 'status-edited' : 'status-draft' }}" data-status>
                                    {{ ucfirst($draft->status) }}
                                </span>
                            </div>

                            <div class="text-muted small mb-2">
                                <i class="mdi mdi-shape-outline me-1"></i>{{ ucfirst(str_replace('_', ' ', $draft->post_type)) }}
                            </div>

                            <div class="caption-label">Caption</div>
                            <textarea class="form-control caption-box mb-2" rows="4" data-field="caption" oninput="autoGrow(this)">{{ $draft->caption }}</textarea>

                            <div class="d-flex align-items-center justify-content-between">
                                <div class="caption-label mb-0">Hashtags</div>
                            </div>
                            <textarea class="form-control hashtag-box mb-3 mt-1" rows="2" data-field="hashtags" oninput="autoGrow(this)">{{ $draft->hashtags }}</textarea>

                            <div class="mt-auto draft-actions d-flex gap-2">
                                <button class="btn btn-sm btn-primary flex-fill" onclick="saveDraft({{ $draft->id }})">
                                    <i class="mdi mdi-content-save-outline me-1"></i>Save
                                </button>
                                <button class="btn btn-sm btn-light border" onclick="copyDraft({{ $draft->id }})" title="Copy caption + hashtags">
                                    <i class="mdi mdi-content-copy"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-danger" onclick="discardDraft({{ $draft->id }})" title="Discard">
                                    <i class="mdi mdi-delete-outline"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endforeach
@endif

{{-- Generating overlay --}}
<div id="generating-overlay">
    <div class="gen-card">
        <div class="gen-spinner"></div>
        <h6 class="fw-bold mb-1">Generating captions<span class="gen-dots"></span></h6>
        <p class="text-muted small mb-0">Crafting 7 days of posts with AI.<br>This can take up to a minute — please don’t close this tab.</p>
    </div>
</div>

@endsection

@push('scripts')
<script>
const CSRF = document.querySelector('meta[name="csrf-token"]').content;

/* Auto-grow textareas so captions are never clipped */
function autoGrow(el) {
    el.style.height = 'auto';
    el.style.height = (el.scrollHeight + 2) + 'px';
}
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.caption-box, .hashtag-box').forEach(autoGrow);
});

/* Show overlay + disable button while the 7-day generation runs */
const genForm = document.getElementById('generate-form');
if (genForm) {
    genForm.addEventListener('submit', () => {
        document.getElementById('generating-overlay').classList.add('show');
        const btn = document.getElementById('generate-btn');
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Generating…';
        }
    });
}

function fields(id) {
    const root = document.getElementById('draft-' + id);
    return {
        caption: root.querySelector('[data-field="caption"]').value,
        hashtags: root.querySelector('[data-field="hashtags"]').value,
        statusEl: root.querySelector('[data-status]'),
    };
}

async function saveDraft(id) {
    const f = fields(id);
    const res = await fetch(`/caption-drafts/${id}`, {
        method: 'POST',
        headers: { 'Content-Type':'application/json', 'X-CSRF-TOKEN':CSRF, 'Accept':'application/json' },
        body: JSON.stringify({ caption: f.caption, hashtags: f.hashtags }),
    });
    const j = await res.json().catch(() => ({}));
    if (j.success) {
        f.statusEl.textContent = 'Edited';
        f.statusEl.className = 'status-pill status-edited';
        f.statusEl.setAttribute('data-status', '');
        toast('Saved');
    } else { toast('Save failed', true); }
}

function copyDraft(id) {
    const f = fields(id);
    navigator.clipboard.writeText((f.caption + '\n\n' + f.hashtags).trim()).then(() => toast('Copied to clipboard'));
}

async function discardDraft(id) {
    if (!confirm('Discard this draft?')) return;
    const res = await fetch(`/caption-drafts/${id}`, {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN':CSRF, 'Accept':'application/json' },
    });
    if ((await res.json().catch(()=>({}))).success) {
        document.getElementById('draft-' + id)?.remove();
        toast('Discarded');
    }
}

function toast(msg, danger=false) {
    const t = document.createElement('div');
    t.className = 'position-fixed bottom-0 end-0 m-3 px-3 py-2 rounded text-white shadow';
    t.style.zIndex = 1100;
    t.style.background = danger ? '#dc3545' : '#198754';
    t.textContent = msg;
    document.body.appendChild(t);
    setTimeout(() => t.remove(), 1800);
}
</script>
@endpush
