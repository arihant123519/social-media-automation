@extends('layouts.app')

@section('title', 'Hashtag Bank')
@section('page_header', 'Hashtag Bank')
@section('page_icon', 'mdi mdi-pound')

@section('breadcrumb')
    <li class="breadcrumb-item active">Hashtag Bank</li>
@endsection

@push('styles')
<style>
    :root {
        --hb-trending: #6366f1;
        --hb-niche:    #0ea5e9;
        --hb-brand:    #ec4899;
        --hb-high:     #16a34a;
        --hb-medium:   #f59e0b;
        --hb-low:      #94a3b8;
    }

    /* ── Specialty switcher ── */
    .hb-specs { display:flex; gap:.5rem; overflow-x:auto; padding-bottom:.35rem; -webkit-overflow-scrolling:touch; }
    .hb-specs::-webkit-scrollbar { height:5px; }
    .hb-specs::-webkit-scrollbar-thumb { background:rgba(0,0,0,.15); border-radius:3px; }
    .hb-spec {
        display:inline-flex; align-items:center; gap:.5rem; white-space:nowrap;
        padding:.55rem 1rem; border-radius:999px; font-weight:600; font-size:.86rem;
        color:#475569; background:#fff; border:1px solid #e2e8f0; text-decoration:none;
        transition:all .15s ease; flex-shrink:0;
    }
    .hb-spec:hover { border-color:#c7d2fe; color:#4338ca; transform:translateY(-1px); box-shadow:0 6px 16px -10px rgba(79,70,229,.5); }
    .hb-spec.is-active {
        background:linear-gradient(135deg,#4f46e5,#6366f1); color:#fff; border-color:transparent;
        box-shadow:0 10px 22px -10px rgba(79,70,229,.7);
    }
    .hb-spec .hb-spec-count {
        font-size:.72rem; font-weight:700; min-width:1.4rem; text-align:center;
        padding:.05rem .4rem; border-radius:999px; background:#eef2ff; color:#4338ca;
    }
    .hb-spec.is-active .hb-spec-count { background:rgba(255,255,255,.22); color:#fff; }

    /* ── Cards ── */
    .hb-card { border:1px solid #eef0f4; border-radius:1rem; box-shadow:0 1px 2px rgba(16,24,40,.04); }
    .hb-add-card { position:sticky; top:90px; }
    .hb-card-head { display:flex; align-items:center; gap:.6rem; font-weight:700; color:#0f172a; }
    .hb-card-head .ic {
        width:34px; height:34px; border-radius:9px; display:flex; align-items:center; justify-content:center;
        background:linear-gradient(135deg,#4f46e5,#6366f1); color:#fff; font-size:1.1rem;
    }

    /* ── Form ── */
    .hb-label { font-size:.72rem; font-weight:700; letter-spacing:.04em; text-transform:uppercase; color:#94a3b8; }
    .hb-add-card textarea, .hb-add-card .form-select {
        border-radius:.65rem; border-color:#e2e8f0; font-size:.9rem;
    }
    .hb-add-card textarea:focus, .hb-add-card .form-select:focus {
        border-color:#a5b4fc; box-shadow:0 0 0 .2rem rgba(99,102,241,.15);
    }
    .hb-save-btn {
        border-radius:.65rem; font-weight:600; padding:.6rem; border:none;
        background:linear-gradient(135deg,#4f46e5,#6366f1); box-shadow:0 10px 20px -10px rgba(79,70,229,.7);
    }
    .hb-save-btn:hover { background:linear-gradient(135deg,#4338ca,#4f46e5); }
    .hb-hint { font-size:.78rem; color:#94a3b8; }

    /* ── Toolbar ── */
    .hb-toolbar { display:flex; flex-wrap:wrap; gap:.6rem; align-items:center; }
    .hb-search { position:relative; flex:1 1 200px; min-width:160px; }
    .hb-search .mdi { position:absolute; left:.7rem; top:50%; transform:translateY(-50%); color:#94a3b8; }
    .hb-search input { border-radius:.65rem; border:1px solid #e2e8f0; padding:.5rem .8rem .5rem 2.1rem; width:100%; font-size:.88rem; }
    .hb-search input:focus { outline:none; border-color:#a5b4fc; box-shadow:0 0 0 .2rem rgba(99,102,241,.12); }
    .hb-filter {
        display:inline-flex; align-items:center; gap:.35rem; padding:.42rem .8rem; border-radius:999px;
        font-size:.8rem; font-weight:600; color:#64748b; background:#f1f5f9; border:1px solid transparent;
        cursor:pointer; transition:all .12s ease;
    }
    .hb-filter:hover { background:#e2e8f0; }
    .hb-filter.is-active { background:#1e293b; color:#fff; }
    .hb-filter .dot { width:8px; height:8px; border-radius:50%; }
    .hb-copy-btn {
        display:inline-flex; align-items:center; gap:.4rem; padding:.45rem .85rem; border-radius:.65rem;
        font-size:.82rem; font-weight:600; color:#4338ca; background:#eef2ff; border:1px solid #e0e7ff; cursor:pointer;
        transition:all .12s ease;
    }
    .hb-copy-btn:hover { background:#e0e7ff; }

    /* ── Tag chips ── */
    .hb-tags { display:flex; flex-wrap:wrap; gap:.55rem; }
    .hb-chip {
        position:relative; display:inline-flex; align-items:center; gap:.5rem;
        padding:.5rem .8rem; border-radius:.7rem; cursor:pointer; user-select:none;
        background:#fff; border:1px solid #e8ecf3; font-size:.88rem; color:#1e293b; font-weight:600;
        transition:transform .1s ease, box-shadow .15s ease, border-color .15s ease;
    }
    .hb-chip:hover { transform:translateY(-2px); box-shadow:0 8px 18px -10px rgba(16,24,40,.35); border-color:#cbd5e1; }
    .hb-chip:active { transform:translateY(0) scale(.98); }
    .hb-chip .perf-dot { width:9px; height:9px; border-radius:50%; flex-shrink:0; }
    .hb-chip .hb-cat { font-size:.68rem; font-weight:700; letter-spacing:.02em; text-transform:uppercase; padding:.1rem .45rem; border-radius:999px; }
    .hb-chip.cat-trending .hb-cat { background:#eef2ff; color:var(--hb-trending); }
    .hb-chip.cat-niche    .hb-cat { background:#e0f2fe; color:var(--hb-niche); }
    .hb-chip.cat-brand    .hb-cat { background:#fce7f3; color:var(--hb-brand); }
    .hb-chip .hb-del {
        display:flex; align-items:center; justify-content:center; width:20px; height:20px; border-radius:50%;
        border:none; background:transparent; color:#cbd5e1; font-size:1rem; padding:0; line-height:1;
        transition:all .12s ease;
    }
    .hb-chip .hb-del:hover { background:#fee2e2; color:#ef4444; }
    .hb-chip.is-copied { border-color:#16a34a; background:#f0fdf4; box-shadow:0 0 0 .2rem rgba(22,163,74,.12); }

    /* ── Legend ── */
    .hb-legend { display:flex; flex-wrap:wrap; gap:1rem; font-size:.76rem; color:#94a3b8; }
    .hb-legend span { display:inline-flex; align-items:center; gap:.4rem; }
    .hb-legend .perf-dot { width:9px; height:9px; border-radius:50%; }

    /* ── Empty / no-results ── */
    .hb-empty { text-align:center; padding:3.5rem 1rem; color:#94a3b8; }
    .hb-empty .mdi { font-size:3rem; opacity:.5; }

    /* ── Toast ── */
    .hb-toast {
        position:fixed; bottom:24px; left:50%; transform:translateX(-50%) translateY(20px);
        background:#0f172a; color:#fff; padding:.65rem 1.1rem; border-radius:.7rem; font-size:.85rem; font-weight:600;
        box-shadow:0 14px 30px -10px rgba(0,0,0,.5); opacity:0; pointer-events:none; transition:all .25s ease; z-index:2000;
        display:inline-flex; align-items:center; gap:.5rem;
    }
    .hb-toast.show { opacity:1; transform:translateX(-50%) translateY(0); }

    @media (max-width:991.98px){ .hb-add-card { position:static; } }
</style>
@endpush

@section('content')

{{-- Specialty switcher --}}
<div class="hb-specs mb-3">
    @foreach($specialties as $s)
        <a href="{{ route('hashtags.index', ['specialty' => $s]) }}"
           class="hb-spec {{ $specialty === $s ? 'is-active' : '' }}">
            {{ ucfirst($s) }}
            <span class="hb-spec-count">{{ $counts[$s] ?? 0 }}</span>
        </a>
    @endforeach
</div>

<div class="row g-3">
    {{-- Add form --}}
    <div class="col-12 col-lg-4">
        <div class="card hb-card hb-add-card">
            <div class="card-body p-4">
                <div class="hb-card-head mb-3">
                    <span class="ic"><i class="mdi mdi-plus"></i></span>
                    <span>Add hashtags</span>
                </div>
                <form method="POST" action="{{ route('hashtags.store') }}">
                    @csrf
                    <input type="hidden" name="specialty" value="{{ $specialty }}">
                    <div class="mb-3">
                        <label class="hb-label d-block mb-1">Tags (space or comma separated)</label>
                        <textarea name="tags" rows="3" class="form-control" placeholder="#skincare #dermatology glowingskin" required></textarea>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="hb-label d-block mb-1">Category</label>
                            <select name="category" class="form-select form-select-sm">
                                <option value="niche">Niche</option>
                                <option value="trending">Trending</option>
                                <option value="brand">Brand</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="hb-label d-block mb-1">Performance</label>
                            <select name="performance" class="form-select form-select-sm">
                                <option value="high">High</option>
                                <option value="medium" selected>Medium</option>
                                <option value="low">Low</option>
                            </select>
                        </div>
                    </div>
                    <button class="btn btn-primary w-100 hb-save-btn text-white">
                        <i class="mdi mdi-content-save-outline me-1"></i>Save to bank
                    </button>
                </form>
                <p class="hb-hint mb-0 mt-3">
                    <i class="mdi mdi-information-outline me-1"></i>Refresh ratings monthly using Instagram analytics for best reach.
                </p>

                <hr class="my-3" style="border-color:#eef0f4;">
                <div class="hb-legend">
                    <span><i class="perf-dot" style="background:var(--hb-high)"></i> High</span>
                    <span><i class="perf-dot" style="background:var(--hb-medium)"></i> Medium</span>
                    <span><i class="perf-dot" style="background:var(--hb-low)"></i> Low</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Tag list --}}
    <div class="col-12 col-lg-8">
        <div class="card hb-card">
            <div class="card-body p-4">
                <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
                    <h6 class="mb-0 fw-bold text-capitalize" style="color:#0f172a;">
                        {{ $specialty }} hashtags
                        <span class="text-muted fw-normal">({{ $tags->count() }})</span>
                    </h6>
                    <button class="hb-copy-btn" onclick="copyAll()" type="button">
                        <i class="mdi mdi-content-copy"></i>Copy all
                    </button>
                </div>

                @if($tags->isEmpty())
                    <div class="hb-empty">
                        <i class="mdi mdi-pound"></i>
                        <p class="mt-2 mb-0">No hashtags yet — add 50–100 for this specialty.</p>
                    </div>
                @else
                    {{-- Toolbar: search + category filters --}}
                    <div class="hb-toolbar mb-3">
                        <div class="hb-search">
                            <i class="mdi mdi-magnify"></i>
                            <input type="text" id="hbSearch" placeholder="Search hashtags…" oninput="filterTags()">
                        </div>
                        <div class="d-flex gap-2 flex-wrap">
                            <button class="hb-filter is-active" data-cat="all" onclick="setFilter(this)">All</button>
                            <button class="hb-filter" data-cat="trending" onclick="setFilter(this)"><span class="dot" style="background:var(--hb-trending)"></span>Trending</button>
                            <button class="hb-filter" data-cat="niche" onclick="setFilter(this)"><span class="dot" style="background:var(--hb-niche)"></span>Niche</button>
                            <button class="hb-filter" data-cat="brand" onclick="setFilter(this)"><span class="dot" style="background:var(--hb-brand)"></span>Brand</button>
                        </div>
                    </div>

                    <div class="hb-tags" id="tagWrap">
                        @foreach($tags as $t)
                            <div class="hb-chip cat-{{ $t->category }}"
                                 data-tag="{{ $t->tag }}" data-cat="{{ $t->category }}"
                                 onclick="copyTag(this)" title="Click to copy">
                                <span class="perf-dot" style="background:var(--hb-{{ $t->performance }})" title="{{ ucfirst($t->performance) }} performance"></span>
                                <span>{{ $t->tag }}</span>
                                <span class="hb-cat">{{ ucfirst($t->category) }}</span>
                                <form method="POST" action="{{ route('hashtags.destroy', $t) }}" class="d-inline" onsubmit="return confirm('Remove {{ $t->tag }}?')">
                                    @csrf @method('DELETE')
                                    <button class="hb-del" type="submit" onclick="event.stopPropagation()" title="Remove">
                                        <i class="mdi mdi-close"></i>
                                    </button>
                                </form>
                            </div>
                        @endforeach
                    </div>

                    <div class="hb-empty d-none" id="hbNoResults">
                        <i class="mdi mdi-magnify-close"></i>
                        <p class="mt-2 mb-0">No hashtags match your search.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="hb-toast" id="hbToast"><i class="mdi mdi-check-circle"></i><span>Copied!</span></div>

@endsection

@push('scripts')
<script>
let hbFilter = 'all';

function copyTag(el) {
    navigator.clipboard.writeText(el.dataset.tag).then(() => {
        el.classList.add('is-copied');
        setTimeout(() => el.classList.remove('is-copied'), 600);
        hbShowToast('Copied ' + el.dataset.tag);
    });
}

function copyAll() {
    const tags = [...document.querySelectorAll('#tagWrap .hb-chip:not(.d-none)')].map(e => e.dataset.tag);
    if (!tags.length) return;
    navigator.clipboard.writeText(tags.join(' '));
    hbShowToast(tags.length + ' hashtags copied');
}

function setFilter(btn) {
    document.querySelectorAll('.hb-filter').forEach(b => b.classList.remove('is-active'));
    btn.classList.add('is-active');
    hbFilter = btn.dataset.cat;
    filterTags();
}

function filterTags() {
    const q = (document.getElementById('hbSearch')?.value || '').toLowerCase().trim();
    let visible = 0;
    document.querySelectorAll('#tagWrap .hb-chip').forEach(chip => {
        const matchCat = hbFilter === 'all' || chip.dataset.cat === hbFilter;
        const matchText = !q || chip.dataset.tag.toLowerCase().includes(q);
        const show = matchCat && matchText;
        chip.classList.toggle('d-none', !show);
        if (show) visible++;
    });
    document.getElementById('hbNoResults')?.classList.toggle('d-none', visible > 0);
}

let toastTimer;
function hbShowToast(msg) {
    const t = document.getElementById('hbToast');
    t.querySelector('span').textContent = msg;
    t.classList.add('show');
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => t.classList.remove('show'), 1600);
}
</script>
@endpush
