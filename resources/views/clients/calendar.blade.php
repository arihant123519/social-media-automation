@extends('layouts.app')

@section('title', 'Content Calendar')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<style>
/* ════════════════════════════════════════════════
   Content Calendar — modern, interactive theme
   ════════════════════════════════════════════════ */
.cal-page { --cal-yt:#dc2626; --cal-ig:#be185d; --cal-blue:#2563eb; --cal-indigo:#6366f1; }

/* ── Page header ─────────────────────────────── */
.cal-hero {
    position: relative;
    border-radius: 18px;
    padding: 22px 26px;
    margin-bottom: 22px;
    background: linear-gradient(120deg, #1b4e82 0%, #2b6cb0 60%, #3182ce 100%);
    box-shadow: 0 18px 40px -22px rgba(27,78,130,.7);
    overflow: hidden;
    color: #fff;
}
.cal-hero::after {
    content: ''; position: absolute; right: -40px; top: -60px;
    width: 240px; height: 240px; border-radius: 50%;
    background: radial-gradient(circle, rgba(255,255,255,.18), transparent 70%);
    pointer-events: none;
}
.cal-hero h4 { font-family:'Space Grotesk',sans-serif; font-weight:700; letter-spacing:-.5px; }
.cal-hero .hero-sub { color: rgba(255,255,255,.82); font-size: 13px; }
.cal-client-select {
    background: rgba(255,255,255,.16) !important;
    border: 1px solid rgba(255,255,255,.3) !important;
    color: #fff !important; font-weight: 600;
    backdrop-filter: blur(6px); border-radius: 10px;
}
.cal-client-select option { color:#1a202c; }
.cal-client-select:focus { box-shadow: 0 0 0 3px rgba(255,255,255,.25) !important; }

/* ── Layout ─────────────────────────────────── */
.cal-wrapper {
    background: #fff;
    border-radius: 16px;
    border: 1px solid #e8edf3;
    overflow: hidden;
    box-shadow: 0 10px 30px -18px rgba(26,74,122,.35);
}

/* ── Toolbar ─────────────────────────────────── */
.cal-toolbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px 20px;
    border-bottom: 1px solid #eef2f6;
    flex-wrap: wrap;
    gap: 12px;
    background: linear-gradient(180deg,#fbfcfe,#fff);
}
.cal-month-nav { display: flex; align-items: center; gap: 10px; }
.cal-month-nav .month-label {
    font-family:'Space Grotesk',sans-serif;
    font-size: 18px; font-weight: 700;
    min-width: 168px; text-align: center; color: #142c44;
}
.cal-nav-btn {
    width: 36px; height: 36px;
    border: 1px solid #e2e8f0; background: #fff;
    border-radius: 10px; display: flex;
    align-items: center; justify-content: center;
    color: #2b6cb0; text-decoration: none; font-size: 15px;
    transition: transform .15s, background .15s, box-shadow .15s, color .15s;
}
.cal-nav-btn:hover { background: #1b4e82; color: #fff; transform: translateY(-1px); box-shadow: 0 8px 16px -8px rgba(27,78,130,.6); }
.cal-nav-btn:active { transform: translateY(0); }
.cal-today-btn {
    border: 1px solid #bee3f8; background: #ebf4ff; color: #1b4e82;
    font-size: 12px; font-weight: 600; border-radius: 10px;
    padding: 7px 14px; text-decoration: none; transition: all .15s;
    display: inline-flex; align-items: center; gap: 5px;
}
.cal-today-btn:hover { background: #1b4e82; color: #fff; }

/* ── Scope Tabs (segmented) ─────────────────── */
.scope-seg { display:flex; gap:4px; background:#f1f5f9; padding:4px; border-radius:12px; }
.scope-badge {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 7px 15px; border-radius: 9px;
    font-size: 12.5px; font-weight: 600;
    border: 1px solid transparent; text-decoration: none;
    color:#64748b; transition: all .18s; cursor: pointer;
}
.scope-badge:hover { color:#1e293b; background: rgba(255,255,255,.7); }
.scope-all.active       { background:#fff; color:#1d4ed8; box-shadow:0 4px 10px -4px rgba(29,78,216,.5); }
.scope-youtube.active   { background:#fff; color:#dc2626; box-shadow:0 4px 10px -4px rgba(220,38,38,.5); }
.scope-instagram.active { background:#fff; color:#be185d; box-shadow:0 4px 10px -4px rgba(190,24,93,.5); }

/* ── Summary Cards ───────────────────────────── */
.summary-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 14px; margin-bottom: 22px;
}
.summary-card {
    position: relative;
    background:#fff; border:1px solid #e8edf3;
    border-radius:14px; padding:16px 18px;
    overflow: hidden;
    box-shadow: 0 4px 14px -10px rgba(26,74,122,.4);
    transition: transform .2s ease, box-shadow .2s ease;
    cursor: default;
}
.summary-card::before {
    content:''; position:absolute; left:0; top:0; bottom:0; width:4px;
    background: var(--card-accent, #cbd5e1);
}
.summary-card:hover { transform: translateY(-4px); box-shadow: 0 16px 30px -16px rgba(26,74,122,.5); }
.summary-card .s-head { display:flex; align-items:center; justify-content:space-between; }
.summary-card .s-label { font-size:11.5px; color:#64748b; margin-bottom:6px; font-weight:600; text-transform:uppercase; letter-spacing:.04em; }
.summary-card .s-value { font-family:'Space Grotesk',sans-serif; font-size:30px; font-weight:700; color:#111827; line-height:1; }
.summary-card .s-sub   { font-size:11px; color:#9ca3af; margin-top:5px; }
.summary-card .s-icon {
    width:36px; height:36px; border-radius:10px; flex-shrink:0;
    display:flex; align-items:center; justify-content:center; font-size:17px;
    background: var(--icon-bg,#eef2f6); color: var(--icon-fg,#64748b);
}

/* ── Grid Header ─────────────────────────────── */
.cal-grid-header {
    display: grid; grid-template-columns: repeat(7, 1fr);
    background: #f8fafc; border-bottom: 1px solid #eef2f6;
}
.cal-grid-header div {
    padding: 11px 6px; font-size: 11px; font-weight: 700;
    text-transform: uppercase; letter-spacing: .06em;
    color: #94a3b8; text-align: center;
}
.cal-grid-header div.dow-weekend { color:#cbd5e1; }

/* ── Calendar Grid ───────────────────────────── */
.cal-grid { display: grid; grid-template-columns: repeat(7, 1fr); }
.cal-cell {
    min-height: 116px; padding: 7px;
    border-right: 1px solid #eef2f6;
    border-bottom: 1px solid #eef2f6;
    background: #fff;
    transition: background .15s, box-shadow .15s;
    position: relative;
    /* staggered entrance */
    opacity: 0;
    animation: cellIn .4s ease forwards;
}
@keyframes cellIn { from { opacity:0; transform: translateY(8px); } to { opacity:1; transform:none; } }
.cal-cell:nth-child(7n) { border-right: none; }
.cal-cell.empty         { background: #fafbfc; }
.cal-cell.weekend:not(.empty):not(.today) { background:#fcfdfe; }
.cal-cell:not(.empty):hover { background:#f6f9ff; box-shadow: inset 0 0 0 1px #dbeafe; z-index:2; }
.cal-cell.today {
    background: linear-gradient(180deg,#eff6ff,#fff);
    box-shadow: inset 0 0 0 2px #2563eb;
}

.day-num {
    font-size: 12.5px; font-weight: 700; color: #94a3b8;
    width: 26px; height: 26px; border-radius: 9px;
    display: flex; align-items: center; justify-content: center;
    margin-bottom: 5px; transition: all .15s;
}
.cal-cell:not(.empty):hover .day-num { color:#1e293b; }
.cal-cell.today .day-num {
    background: linear-gradient(135deg,#3b82f6,#2563eb); color: #fff;
    box-shadow: 0 6px 12px -5px rgba(37,99,235,.7);
}

/* ── Post Pills ──────────────────────────────── */
.post-pill {
    display: flex; align-items: center; gap: 5px;
    font-size: 10.5px; font-weight: 600;
    padding: 3px 7px 3px 6px; border-radius: 8px;
    margin-bottom: 4px; cursor: pointer;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    transition: transform .12s ease, box-shadow .12s ease, filter .12s;
    position: relative;
    border: 1px solid transparent;
}
.post-pill:hover {
    transform: translateX(2px);
    box-shadow: 0 6px 14px -6px rgba(0,0,0,.3);
    filter: none;
}

/* Scope colours */
.post-pill.yt { background:#fef2f2; color:#991b1b; border-color:#fecaca; }
.post-pill.ig { background:#fdf2f8; color:#9d174d; border-color:#f9d3e6; }

/* Status overlays */
.post-pill.status-completed { background:#dcfce7 !important; color:#166534 !important; border-color:#bbf7d0 !important; }
.post-pill.status-missed    { background:#fee2e2 !important; color:#991b1b !important; border-color:#fecaca !important; }

.status-dot {
    width: 7px; height: 7px; border-radius: 50%;
    flex-shrink: 0; margin-right: 1px;
}
.dot-pending   { background: #f59e0b; box-shadow:0 0 0 2px rgba(245,158,11,.18); }
.dot-completed { background: #22c55e; box-shadow:0 0 0 2px rgba(34,197,94,.18); }
.dot-missed    { background: #ef4444; box-shadow:0 0 0 2px rgba(239,68,68,.18); }

.more-badge {
    font-size:10px; color:#2563eb; padding:2px 6px; font-weight:600;
    background:#eff6ff; border-radius:6px; display:inline-block; cursor:pointer;
    transition: background .15s;
}
.more-badge:hover { background:#dbeafe; }

/* #14 — weekday content theme tag */
.theme-tag {
    font-size: 8.5px; font-weight: 700; letter-spacing:.2px;
    background: rgba(99,102,241,.12); color:#4338ca;
    padding: 1px 5px; border-radius: 6px; margin-left: 2px;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 80px;
}

/* ── Stage indicator icon (right side of pill) ── */
.stage-icon {
    margin-left: auto;
    font-size: 10px;
    flex-shrink: 0;
}
.stage-published .stage-icon { color:#16a34a; }   /* green check */
.stage-scheduled .stage-icon { color:#2563eb; }   /* blue clock  */
.stage-ready     .stage-icon { color:#6366f1; }   /* indigo bookmark */
.stage-draft     .stage-icon { color:#9ca3af; }   /* grey pencil */
.stage-failed    .stage-icon { color:#ef4444; }   /* red warning */

/* Subtle left border accent so saved/scheduled pills stand out */
.post-pill.stage-scheduled { box-shadow: inset 3px 0 0 #2563eb; }
.post-pill.stage-ready     { box-shadow: inset 3px 0 0 #6366f1; }

/* ── Legend ──────────────────────────────────── */
.legend-row  { display:flex; gap:16px; flex-wrap:wrap; margin-bottom:10px; }
.legend-item { display:flex; align-items:center; gap:6px; font-size:12px; color:#6b7280; }
.legend-dot  { width:9px; height:9px; border-radius:50%; }

/* ── Empty state for a month with no posts ──── */
.cal-empty-state {
    text-align:center; padding:40px 20px; color:#94a3b8;
}
.cal-empty-state i { font-size:40px; color:#cbd5e1; }

/* ── Status Modal ────────────────────────────── */
.status-modal-backdrop {
    position: fixed; inset: 0;
    background: rgba(0,0,0,.45); z-index: 1050;
    display: flex; align-items: center; justify-content: center;
    opacity: 0; pointer-events: none;
    transition: opacity .2s;
}
.status-modal-backdrop.open { opacity: 1; pointer-events: all; }
.status-modal {
    background: #fff; border-radius: 14px;
    width: 100%; max-width: 400px; margin: 16px;
    box-shadow: 0 20px 60px rgba(0,0,0,.2);
    transform: translateY(16px);
    transition: transform .2s;
}
.status-modal-backdrop.open .status-modal { transform: none; }
.modal-header {
    display: flex; align-items: center; justify-content: space-between;
    padding: 16px 20px; border-bottom: 1px solid #e5e7eb;
}
.modal-header h6 { font-size:15px; font-weight:600; color:#111827; margin:0; }
.modal-close {
    width:28px; height:28px; border:none; background:#f3f4f6;
    border-radius:50%; cursor:pointer; font-size:14px;
    display:flex; align-items:center; justify-content:center;
    color:#6b7280;
}
.modal-body  { padding: 18px 20px; }
.modal-footer { padding: 14px 20px; border-top:1px solid #e5e7eb; display:flex; gap:8px; justify-content:flex-end; }

.status-btn-group { display:flex; gap:8px; margin-bottom:16px; }
.status-opt {
    flex:1; padding:9px 6px; border-radius:9px;
    border:2px solid #e5e7eb; background:#fff;
    font-size:12px; font-weight:600; cursor:pointer;
    text-align:center; transition: all .15s;
    display:flex; flex-direction:column; align-items:center; gap:4px;
}
.status-opt i { font-size:18px; }
.status-opt.sel-pending   { border-color:#f59e0b; background:#fffbeb; color:#92400e; }
.status-opt.sel-completed { border-color:#22c55e; background:#f0fdf4; color:#166534; }
.status-opt.sel-missed    { border-color:#ef4444; background:#fef2f2; color:#991b1b; }

.note-area {
    width:100%; border:1px solid #d1d5db; border-radius:8px;
    padding:8px 10px; font-size:13px; resize:none;
    font-family:inherit; color:#374151;
}
.note-area:focus { outline:none; border-color:#2563eb; }

.btn-save {
    background:#2563eb; color:#fff; border:none;
    border-radius:8px; padding:8px 20px;
    font-size:13px; font-weight:500; cursor:pointer;
    transition: background .15s;
}
.btn-save:hover { background:#1d4ed8; }
.btn-cancel {
    background:#f3f4f6; color:#374151; border:none;
    border-radius:8px; padding:8px 16px;
    font-size:13px; cursor:pointer;
}

@media (max-width:640px) {
    .cal-cell { min-height:64px; }
    .post-pill span.pill-label { display:none; }
}
</style>
@endpush

@section('content')
<div class="container-fluid py-4 cal-page">

    {{-- ── Page Header (hero) ───────────────────────── --}}
    <div class="cal-hero d-flex align-items-center justify-content-between flex-wrap gap-3">
        <div>
            <h4 class="mb-1">
                <i class="bi bi-calendar3 me-2"></i>Content Calendar
            </h4>
            <p class="hero-sub mb-0">
                {{ $selectedClient?->name }} — post schedule by scope
            </p>
        </div>

        {{-- Client Selector --}}
        <form method="GET" action="{{ route('calendar.index') }}" id="clientForm" class="d-flex align-items-center gap-2">
            <input type="hidden" name="month" value="{{ $month }}">
            <input type="hidden" name="year"  value="{{ $year }}">
            <input type="hidden" name="scope" value="{{ request('scope','all') }}">
            <label class="text-white-50 small mb-0 d-none d-sm-block" style="font-weight:600">Client</label>
            <select name="client_id" class="form-select form-select-sm cal-client-select"
                    style="min-width:200px" onchange="this.form.submit()">
                @foreach($clients as $c)
                    <option value="{{ $c->id }}" {{ $c->id == $selectedClientId ? 'selected' : '' }}>
                        {{ $c->name }}
                    </option>
                @endforeach
            </select>
        </form>
    </div>

    {{-- ── Summary Cards ────────────────────────────── --}}
    @php
        $allPosts   = collect($calendarData)->flatten(1);
        $totalPosts = $allPosts->count();
        $ytPosts    = $allPosts->where('scope', 0)->count();
        $igPosts    = $allPosts->where('scope', 1)->count();
        $activeDays = collect($calendarData)->filter(fn($d) => count($d) > 0)->count();
        $completed  = $allPosts->where('status','completed')->count();
        $missed     = $allPosts->where('status','missed')->count();
    @endphp

    @php
        $completionRate = $totalPosts > 0 ? round($completed / $totalPosts * 100) : 0;
    @endphp
    <div class="summary-row">
        <div class="summary-card" style="--card-accent:#2563eb">
            <div class="s-head">
                <div>
                    <div class="s-label">Total Posts</div>
                    <div class="s-value" data-count="{{ $totalPosts }}">0</div>
                </div>
                <div class="s-icon" style="--icon-bg:#eff6ff;--icon-fg:#2563eb"><i class="bi bi-collection"></i></div>
            </div>
            <div class="s-sub">this month</div>
        </div>
        <div class="summary-card" style="--card-accent:#dc2626">
            <div class="s-head">
                <div>
                    <div class="s-label" style="color:#991b1b">YouTube</div>
                    <div class="s-value" style="color:#dc2626" data-count="{{ $ytPosts }}">0</div>
                </div>
                <div class="s-icon" style="--icon-bg:#fef2f2;--icon-fg:#dc2626"><i class="bi bi-youtube"></i></div>
            </div>
            <div class="s-sub">posts scheduled</div>
        </div>
        <div class="summary-card" style="--card-accent:#be185d">
            <div class="s-head">
                <div>
                    <div class="s-label" style="color:#9d174d">Instagram</div>
                    <div class="s-value" style="color:#be185d" data-count="{{ $igPosts }}">0</div>
                </div>
                <div class="s-icon" style="--icon-bg:#fdf2f8;--icon-fg:#be185d"><i class="bi bi-instagram"></i></div>
            </div>
            <div class="s-sub">posts scheduled</div>
        </div>
        <div class="summary-card" style="--card-accent:#22c55e">
            <div class="s-head">
                <div>
                    <div class="s-label" style="color:#166534">Completed</div>
                    <div class="s-value" style="color:#22c55e" data-count="{{ $completed }}">0</div>
                </div>
                <div class="s-icon" style="--icon-bg:#f0fdf4;--icon-fg:#16a34a"><i class="bi bi-check2-circle"></i></div>
            </div>
            <div class="s-sub">{{ $completionRate }}% done this month</div>
        </div>
        <div class="summary-card" style="--card-accent:#ef4444">
            <div class="s-head">
                <div>
                    <div class="s-label" style="color:#991b1b">Missed</div>
                    <div class="s-value" style="color:#ef4444" data-count="{{ $missed }}">0</div>
                </div>
                <div class="s-icon" style="--icon-bg:#fef2f2;--icon-fg:#ef4444"><i class="bi bi-exclamation-octagon"></i></div>
            </div>
            <div class="s-sub">not posted</div>
        </div>
    </div>

    {{-- ── Calendar Box ──────────────────────────────── --}}
    <div class="cal-wrapper">

        {{-- Toolbar --}}
        <div class="cal-toolbar">
            @php
                $prevM = $month == 1  ? 12 : $month - 1;
                $prevY = $month == 1  ? $year - 1 : $year;
                $nextM = $month == 12 ? 1  : $month + 1;
                $nextY = $month == 12 ? $year + 1 : $year;
            @endphp

            <div class="cal-month-nav">
                <a href="{{ route('calendar.index', ['client_id'=>$selectedClientId,'month'=>$prevM,'year'=>$prevY,'scope'=>request('scope','all')]) }}"
                   class="cal-nav-btn" title="Previous month"><i class="bi bi-chevron-left"></i></a>
                <span class="month-label">{{ \Carbon\Carbon::create($year,$month)->format('F Y') }}</span>
                <a href="{{ route('calendar.index', ['client_id'=>$selectedClientId,'month'=>$nextM,'year'=>$nextY,'scope'=>request('scope','all')]) }}"
                   class="cal-nav-btn" title="Next month"><i class="bi bi-chevron-right"></i></a>
                @if(!($year == now()->year && $month == now()->month))
                    <a href="{{ route('calendar.index', ['client_id'=>$selectedClientId,'month'=>now()->month,'year'=>now()->year,'scope'=>request('scope','all')]) }}"
                       class="cal-today-btn"><i class="bi bi-dot"></i> Today</a>
                @endif
            </div>

            {{-- Scope Filter (segmented) --}}
            <div class="scope-seg">
                @foreach([
                    ['all','All','bi-grid-fill','scope-all'],
                    ['0','YouTube','bi-youtube','scope-youtube'],
                    ['1','Instagram','bi-instagram','scope-instagram'],
                ] as [$val,$lbl,$ico,$cls])
                <a href="{{ route('calendar.index', ['client_id'=>$selectedClientId,'month'=>$month,'year'=>$year,'scope'=>$val]) }}"
                   class="scope-badge {{ $cls }} {{ request('scope','all') == $val ? 'active' : '' }}">
                    <i class="bi {{ $ico }}"></i> {{ $lbl }}
                </a>
                @endforeach
            </div>
        </div>

        {{-- Legend (consolidated) --}}
        <div class="px-4 pt-3 pb-2" style="border-bottom:1px solid #f1f5f9; background:#fbfcfe;">
            <div class="legend-row mb-1">
                <span class="text-muted" style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;align-self:center">Status</span>
                <div class="legend-item"><div class="legend-dot" style="background:#f59e0b"></div> Pending</div>
                <div class="legend-item"><div class="legend-dot" style="background:#22c55e"></div> Completed</div>
                <div class="legend-item"><div class="legend-dot" style="background:#ef4444"></div> Missed</div>
                <div class="legend-item"><div class="legend-dot" style="background:#dc2626"></div> YouTube</div>
                <div class="legend-item"><div class="legend-dot" style="background:#be185d"></div> Instagram</div>
            </div>
            <div class="legend-row" style="margin-bottom:2px">
                <span class="text-muted" style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;align-self:center">Stage</span>
                <div class="legend-item"><i class="bi bi-pencil-fill" style="color:#9ca3af"></i> Draft</div>
                <div class="legend-item"><i class="bi bi-bookmark-check-fill" style="color:#6366f1"></i> Ready</div>
                <div class="legend-item"><i class="bi bi-clock-fill" style="color:#2563eb"></i> Scheduled</div>
                <div class="legend-item"><i class="bi bi-check-circle-fill" style="color:#16a34a"></i> Published</div>
                <div class="legend-item"><i class="bi bi-exclamation-triangle-fill" style="color:#ef4444"></i> Failed</div>
            </div>
        </div>

        {{-- Day Headers --}}
        <div class="cal-grid-header">
            @foreach(['Sun','Mon','Tue','Wed','Thu','Fri','Sat'] as $idx => $dn)
                <div class="{{ in_array($idx, [0,6]) ? 'dow-weekend' : '' }}">{{ $dn }}</div>
            @endforeach
        </div>

        {{-- Calendar Grid --}}
        @php
            $firstDow    = \Carbon\Carbon::create($year, $month, 1)->dayOfWeek;
            $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
            $today       = now();
            $activeScope = request('scope','all');
        @endphp

        <div class="cal-grid">
            @for($i = 0; $i < $firstDow; $i++)
                <div class="cal-cell empty" style="animation-delay:{{ $i * 12 }}ms"></div>
            @endfor

            @for($d = 1; $d <= $daysInMonth; $d++)
                @php
                    $isToday  = $today->year==$year && $today->month==$month && $today->day==$d;
                    $cellDow  = ($firstDow + $d - 1) % 7;
                    $isWeekend = in_array($cellDow, [0, 6]);
                    $posts   = $calendarData[$d] ?? [];
                    if ($activeScope !== 'all') {
                        $posts = array_values(array_filter($posts, fn($p) => (string)$p['scope'] === $activeScope));
                    }
                    $maxShow = 3;
                    $extra   = max(0, count($posts) - $maxShow);
                @endphp

                <div class="cal-cell {{ $isToday ? 'today' : '' }} {{ $isWeekend ? 'weekend' : '' }}"
                     style="animation-delay:{{ ($firstDow + $d - 1) * 12 }}ms">
                    <div class="day-num">{{ $d }}</div>

                    @foreach($posts as $post)
                        @php
                            // Stage indicator based on the linked post's publish lifecycle
                            $ps    = $post['publish_status'] ?? null;
                            $hasPost = !empty($post['post_id']);
                            [$stageIcon, $stageClass, $stageTip] = match (true) {
                                $ps === 'published'                          => ['bi-check-circle-fill', 'stage-published', 'Published'],
                                $ps === 'scheduled'                          => ['bi-clock-fill',        'stage-scheduled', 'Scheduled for auto-publish'],
                                $ps === 'failed'                             => ['bi-exclamation-triangle-fill', 'stage-failed', 'Publish failed'],
                                $hasPost && ($post['final_status'] ?? '') === 'approved' => ['bi-bookmark-check-fill', 'stage-ready', 'Post saved — ready to schedule'],
                                $hasPost                                     => ['bi-pencil-fill',       'stage-draft', 'Draft in progress'],
                                default                                      => [null, '', ''],
                            };
                            $tip = $post['label'] . ' — ' . ucfirst($post['status']);
                            if (!empty($post['theme'])) { $tip .= ' · Theme: ' . $post['theme']; }
                            if ($stageTip) {
                                $tip .= ' · ' . $stageTip;
                                if ($ps === 'scheduled' && !empty($post['scheduled_publish_at'])) {
                                    $tip .= ' (' . \Carbon\Carbon::parse($post['scheduled_publish_at'])->format('j M, g:i A') . ')';
                                }
                            }
                        @endphp
                        <div class="post-pill {{ $post['scope']==0 ? 'yt' : 'ig' }} status-{{ $post['status'] }} {{ $stageClass }} {{ $loop->index >= $maxShow ? 'pill-hidden d-none' : '' }}"
                             onclick="openStatusModal({{ json_encode($post) }}, {{ $selectedClientId }})"
                             title="{{ $tip }}">
                            <span class="status-dot dot-{{ $post['status'] }}"></span>
                            <i class="bi {{ $post['icon'] }}" style="font-size:9px"></i>
                            <span class="pill-label">{{ $post['label'] }}</span>
                            @if(!empty($post['theme']))
                                <span class="theme-tag">{{ $post['theme'] }}</span>
                            @endif
                            @if($stageIcon)
                                <i class="bi {{ $stageIcon }} stage-icon" aria-hidden="true"></i>
                            @endif
                        </div>
                    @endforeach

                    @if($extra > 0)
                        <div class="more-badge" onclick="toggleMore(this)" data-count="{{ $extra }}">+{{ $extra }} more</div>
                    @endif
                </div>
            @endfor

            @php
                $total    = $firstDow + $daysInMonth;
                $trailing = $total % 7 === 0 ? 0 : 7 - ($total % 7);
            @endphp
            @for($i = 0; $i < $trailing; $i++)
                <div class="cal-cell empty" style="animation-delay:{{ ($total + $i) * 12 }}ms"></div>
            @endfor
        </div>

    </div>{{-- /cal-wrapper --}}

</div>{{-- /container --}}

{{-- ── Status Update Modal ───────────────────────────── --}}
<div class="status-modal-backdrop" id="statusBackdrop" onclick="closeModalOnBackdrop(event)">
    <div class="status-modal" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
        <div class="modal-header">
            <h6 id="modalTitle">Update Post Status</h6>
            <button class="modal-close" onclick="closeStatusModal()" aria-label="Close">&times;</button>
        </div>
        <div class="modal-body">
            {{-- Post info --}}
            <div class="d-flex align-items-center gap-2 mb-3 p-2"
                 style="background:#f9fafb; border-radius:8px; border:1px solid #e5e7eb">
                <i class="bi" id="modalIcon" style="font-size:20px"></i>
                <div>
                    <div id="modalPostLabel" style="font-size:13px;font-weight:600;color:#111827"></div>
                    <div id="modalPostDate"  style="font-size:11px;color:#6b7280"></div>
                </div>
            </div>

            {{-- Editable section (hidden once a post is published) --}}
            <div id="editSection">
                {{-- Status buttons --}}
                <div class="mb-1" style="font-size:12px;color:#6b7280;font-weight:500;">Status</div>
                <div class="status-btn-group" id="statusBtnGroup">
                    <button class="status-opt" data-status="pending"
                            onclick="selectStatus('pending')">
                        <i class="bi bi-clock text-warning"></i> Pending
                    </button>
                    <button class="status-opt" data-status="completed"
                            onclick="selectStatus('completed')">
                        <i class="bi bi-check-circle text-success"></i> Completed
                    </button>
                    <button class="status-opt" data-status="missed"
                            onclick="selectStatus('missed')">
                        <i class="bi bi-x-circle text-danger"></i> Missed
                    </button>
                </div>

                {{-- Note --}}
                <div class="mb-1 mt-3" style="font-size:12px;color:#6b7280;font-weight:500;">Note (optional)</div>
                <textarea class="note-area" id="modalNote" rows="2"
                          placeholder="Add a note..."></textarea>
            </div>

            {{-- Published summary (shown only when post is published) --}}
            <div id="publishedSummary" style="display:none"></div>
        </div>
        {{-- Quick "Create Post" / "View Live Post" link --}}
        <div id="postAction" class="modal-body" style="padding-top:0; border-top:1px solid #f1f5f9"></div>

        {{-- Auto-publish scheduling (only for approved posts) --}}
        <div id="scheduleAction" class="modal-body" style="padding-top:0;"></div>

        <div class="modal-footer">
            <button class="btn-cancel" onclick="closeStatusModal()">Cancel</button>
            <button class="btn-save"   onclick="saveStatus()" id="saveBtn">
                <i class="bi bi-check2"></i> Save
            </button>
        </div>
    </div>
</div>

@push('scripts')
<script>
let currentPost   = null;
let currentClientId = null;
let selectedStatus  = null;

const scopeNames = { 0: 'YouTube', 1: 'Instagram' };

function openStatusModal(post, clientId) {
    currentPost     = post;
    currentClientId = clientId;
    selectedStatus  = post.status || 'pending';

    // Fill info
    document.getElementById('modalIcon').className      = 'bi ' + post.icon;
    document.getElementById('modalIcon').style.color    = post.scope === 0 ? '#dc2626' : '#be185d';
    document.getElementById('modalPostLabel').textContent =
        scopeNames[post.scope] + ' · ' + post.label;
    document.getElementById('modalPostDate').textContent =
        formatDate(post.scheduled_date);
    document.getElementById('modalNote').value = post.note || '';

    // ── Completed / published posts: show a simple "done" view only ──
    // Covers real publish, dry-run publish, and manually-marked-completed slots.
    const isDone = post.publish_status === 'published'
        || post.publish_status === 'dry_run'
        || post.status === 'completed';
    const editSection = document.getElementById('editSection');
    const publishedSummary = document.getElementById('publishedSummary');
    const saveBtn = document.getElementById('saveBtn');

    if (isDone) {
        editSection.style.display = 'none';
        saveBtn.style.display = 'none';
        document.getElementById('postAction').innerHTML = '';
        document.getElementById('scheduleAction').innerHTML = '';

        const liveLink = post.external_url
            ? `<a href="${post.external_url}" target="_blank" rel="noopener"
                  style="display:inline-flex;align-items:center;gap:6px;color:#16a34a;text-decoration:none;font-size:13px;font-weight:600;padding:9px 16px;background:#dcfce7;border-radius:8px;margin-top:10px">
                 <i class="bi bi-box-arrow-up-right"></i> View Live Post
               </a>`
            : '';
        const dryNote = post.publish_status === 'dry_run'
            ? `<div style="font-size:11px;color:#a16207;margin-top:6px;">(Dry-run — no real post; configure platform tokens to publish live.)</div>`
            : '';

        // Real live publish can't be reopened; dry-run / manual-completed can.
        const isRealLive = post.publish_status === 'published' && post.external_url;
        const reopenBtn = isRealLive ? '' : `
                <div style="margin-top:10px">
                    <button onclick="reopenSlot()" style="background:transparent;border:none;color:#6b7280;font-size:11px;text-decoration:underline;cursor:pointer">
                        <i class="bi bi-arrow-counterclockwise"></i> Reopen this slot (reschedule)
                    </button>
                </div>`;

        publishedSummary.style.display = 'block';
        publishedSummary.innerHTML = `
            <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:10px;padding:14px;text-align:center;color:#166534">
                <i class="bi bi-check-circle-fill" style="font-size:28px;color:#16a34a"></i>
                <div style="font-size:15px;font-weight:700;margin-top:6px">${isRealLive ? 'Published &amp; Completed' : 'Completed'}</div>
                <div style="font-size:12px;color:#15803d;margin-top:2px">This ${post.label} for ${scopeNames[post.scope]} is done.</div>
                ${liveLink}
                ${dryNote}
                ${reopenBtn}
            </div>`;

        document.getElementById('statusBackdrop').classList.add('open');
        document.body.style.overflow = 'hidden';
        return;
    }

    // Not done — normal editable modal
    editSection.style.display = 'block';
    saveBtn.style.display = '';
    publishedSummary.style.display = 'none';
    publishedSummary.innerHTML = '';

    selectStatus(selectedStatus, false);

    // ── Build "Create / View Post" action ──
    const postUrl = @json(route('Post.index')) +
        '?client_id='        + encodeURIComponent(currentClientId) +
        '&post_type='        + encodeURIComponent(post.type) +
        '&scope='            + encodeURIComponent(post.scope) +
        '&scheduled_date='   + encodeURIComponent(post.scheduled_date) +
        '&client_scope_id='  + encodeURIComponent(post.client_scope_id);

    // Normal modal only reaches pending / missed slots (completed handled above).
    // Offer "Create Post" for both — missed slots can still be posted late.
    const lateNote = post.status === 'missed'
        ? `<div style="font-size:11px;color:#991b1b;margin-bottom:6px"><i class="bi bi-exclamation-triangle"></i> This slot's date has passed (missed). You can still create & post it late.</div>`
        : '';
    const actionHtml = lateNote + `
          <a href="${postUrl}"
             style="display:inline-flex;align-items:center;gap:6px;background:#6366f1;color:#fff;text-decoration:none;font-size:13px;font-weight:600;padding:9px 16px;border-radius:8px;margin-top:4px">
            <i class="bi bi-stars"></i> Create Post for this Slot
          </a>`;
    document.getElementById('postAction').innerHTML = actionHtml;

    buildScheduleAction(post);

    document.getElementById('statusBackdrop').classList.add('open');
    document.body.style.overflow = 'hidden';
}

// Build the auto-publish scheduling UI for approved posts.
function buildScheduleAction(post) {
    const box = document.getElementById('scheduleAction');

    // Only meaningful once a post exists AND is approved
    if (!post.post_id || post.final_status !== 'approved') {
        box.innerHTML = '';
        return;
    }

    // Already published — nothing to schedule
    if (post.publish_status === 'published') {
        box.innerHTML = '';
        return;
    }

    // Already scheduled — show the time + cancel option
    if (post.publish_status === 'scheduled' && post.scheduled_publish_at) {
        const human = formatDateTime(post.scheduled_publish_at);
        box.innerHTML = `
          <div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:8px;padding:10px 12px;font-size:13px;color:#1d4ed8;">
            <i class="bi bi-clock-history"></i> Auto-publish scheduled for<br>
            <strong>${human}</strong>
          </div>
          <div style="display:flex;gap:8px;margin-top:8px;">
            <button onclick="cancelSchedule(${post.post_id})"
                    style="flex:1;background:#fef2f2;color:#991b1b;border:1px solid #fecaca;border-radius:8px;padding:8px;font-size:12px;font-weight:600;cursor:pointer;">
              <i class="bi bi-x-circle"></i> Cancel Schedule
            </button>
            <button onclick="publishNow(${post.post_id})"
                    style="flex:1;background:#16a34a;color:#fff;border:none;border-radius:8px;padding:8px;font-size:12px;font-weight:600;cursor:pointer;">
              <i class="bi bi-send"></i> Publish Now
            </button>
          </div>`;
        return;
    }

    // Approved & ready (or previously failed) — show scheduler.
    // Schedule date must be today/future. If the slot date is in the past (missed),
    // default to tomorrow 6 PM so the post can still go out late.
    const slotDate = post.scheduled_date ? new Date(post.scheduled_date + 'T18:00') : null;
    const isPastSlot = slotDate && slotDate < new Date();
    let defaultDt;
    if (isPastSlot || !slotDate) {
        const t = new Date(); t.setDate(t.getDate() + 1); t.setHours(18, 0, 0, 0);
        const pad = n => String(n).padStart(2, '0');
        defaultDt = `${t.getFullYear()}-${pad(t.getMonth()+1)}-${pad(t.getDate())}T18:00`;
    } else {
        defaultDt = (post.scheduled_date || '') + 'T18:00';
    }
    const pastNote = isPastSlot
        ? `<div style="font-size:11px;color:#92400e;margin-bottom:6px;"><i class="bi bi-info-circle"></i> This slot's date has passed — schedule it for today or a future time to publish late.</div>`
        : '';
    const failedNote = post.publish_status === 'failed'
        ? `<div style="font-size:11px;color:#991b1b;margin-bottom:6px;"><i class="bi bi-exclamation-triangle"></i> Previous publish failed — reschedule or publish now.</div>`
        : '';

    box.innerHTML = `
      <div style="border-top:1px solid #f1f5f9;padding-top:12px;">
        ${pastNote}
        ${failedNote}
        <div style="font-size:12px;color:#6b7280;font-weight:500;margin-bottom:6px;">
          <i class="bi bi-calendar-check"></i> Schedule auto-publish
        </div>
        <input type="datetime-local" id="schedDateTime" value="${defaultDt}"
               style="width:100%;border:1px solid #d1d5db;border-radius:8px;padding:8px 10px;font-size:13px;color:#374151;margin-bottom:8px;">
        <div style="display:flex;gap:8px;">
          <button onclick="scheduleAutoPublish(${post.post_id})"
                  style="flex:1;background:#2563eb;color:#fff;border:none;border-radius:8px;padding:9px;font-size:12px;font-weight:600;cursor:pointer;">
            <i class="bi bi-clock"></i> Schedule
          </button>
          <button onclick="publishNow(${post.post_id})"
                  style="flex:1;background:#16a34a;color:#fff;border:none;border-radius:8px;padding:9px;font-size:12px;font-weight:600;cursor:pointer;">
            <i class="bi bi-send"></i> Publish Now
          </button>
        </div>
      </div>`;
}

const POST_BASE = @json(url('/posts'));

async function scheduleAutoPublish(postId) {
    const dt = document.getElementById('schedDateTime').value;
    if (!dt) { alert('Please pick a date and time.'); return; }
    if (new Date(dt) <= new Date()) { alert('Pick a future date and time.'); return; }

    await postJson(`${POST_BASE}/${postId}/schedule`, { scheduled_publish_at: dt.replace('T', ' ') },
        (data) => {
            alert('Scheduled for ' + data.when_human + '. You\'ll get a reminder email one day before.');
            window.location.reload();
        });
}

async function cancelSchedule(postId) {
    if (!confirm('Cancel the scheduled auto-publish?')) return;
    await postJson(`${POST_BASE}/${postId}/unschedule`, {}, () => window.location.reload());
}

async function publishNow(postId) {
    if (!confirm('Publish this post now?')) return;
    await postJson(`${POST_BASE}/${postId}/publish`, {}, (data) => {
        if (data.dry_run) {
            alert('Dry-run: tokens not configured, no real post made.');
        } else {
            alert('Published! ' + (data.external_url || ''));
        }
        window.location.reload();
    });
}

async function postJson(url, payload, onSuccess) {
    try {
        const res = await fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            body: JSON.stringify({ ...payload, _token: '{{ csrf_token() }}' }),
        });
        const data = await res.json();
        if (data.success) {
            onSuccess(data);
        } else {
            alert(data.error || 'Something went wrong.');
        }
    } catch (e) {
        alert('Network error. Please try again.');
    }
}

function formatDateTime(dtStr) {
    const d = new Date(dtStr);
    return d.toLocaleString('en-IN', { weekday:'long', day:'numeric', month:'long', year:'numeric', hour:'numeric', minute:'2-digit' });
}

function closeStatusModal() {
    document.getElementById('statusBackdrop').classList.remove('open');
    document.body.style.overflow = '';
}

function closeModalOnBackdrop(e) {
    if (e.target === document.getElementById('statusBackdrop')) closeStatusModal();
}

function selectStatus(status, updateVar = true) {
    if (updateVar) selectedStatus = status;
    document.querySelectorAll('.status-opt').forEach(btn => {
        btn.className = 'status-opt';
        if (btn.dataset.status === (updateVar ? status : selectedStatus)) {
            btn.className = 'status-opt sel-' + (updateVar ? status : selectedStatus);
        }
    });
}

async function saveStatus() {
    if (!currentPost || !selectedStatus) return;

    const btn = document.getElementById('saveBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Saving…';

    const payload = {
        client_scope_id: currentPost.client_scope_id,
        client_id:       currentClientId,
        scope:           currentPost.scope,
        post_type:       currentPost.type,
        scheduled_date:  currentPost.scheduled_date,
        status:          selectedStatus,
        note:            document.getElementById('modalNote').value,
        _token:          '{{ csrf_token() }}',
    };

    try {
        const res = await fetch('{{ route("calendar.updateStatus") }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify(payload),
        });
        const data = await res.json();

        if (data.success) {
            closeStatusModal();
            // Reload to reflect changes
            window.location.reload();
        } else {
            alert('Something went wrong. Please try again.');
        }
    } catch (e) {
        alert('Network error. Please try again.');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-check2"></i> Save';
    }
}

async function reopenSlot() {
    if (!currentPost) return;
    if (!confirm('Reopen this slot? It will go back to pending so you can schedule/publish again.')) return;

    // If a post exists, reset it (clears dry-run/schedule + sets slot pending) via the post endpoint.
    if (currentPost.post_id) {
        await postJson(`${POST_BASE}/${currentPost.post_id}/reopen`, {}, () => window.location.reload());
        return;
    }

    // No post yet — just revert the calendar slot to pending.
    try {
        const res = await fetch('{{ route("calendar.updateStatus") }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({
                client_scope_id: currentPost.client_scope_id,
                client_id:       currentClientId,
                scope:           currentPost.scope,
                post_type:       currentPost.type,
                scheduled_date:  currentPost.scheduled_date,
                status:          'pending',
                note:            currentPost.note || '',
                _token:          '{{ csrf_token() }}',
            }),
        });
        const data = await res.json();
        if (data.success) { closeStatusModal(); window.location.reload(); }
        else alert('Could not reopen the slot.');
    } catch (e) { alert('Network error.'); }
}

function formatDate(dateStr) {
    const d = new Date(dateStr);
    return d.toLocaleDateString('en-IN', { weekday:'long', day:'numeric', month:'long', year:'numeric' });
}

// Keyboard close
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeStatusModal(); });

// ── Expand / collapse the hidden post pills in a day cell ──
function toggleMore(badge) {
    const cell = badge.closest('.cal-cell');
    if (!cell) return;
    const hidden = cell.querySelectorAll('.pill-hidden');
    const isCollapsed = hidden.length && hidden[0].classList.contains('d-none');
    hidden.forEach(p => p.classList.toggle('d-none', !isCollapsed));
    badge.textContent = isCollapsed ? 'Show less' : ('+' + badge.dataset.count + ' more');
}

// ── Animated count-up for the summary cards ──
(function () {
    const prefersReduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    document.querySelectorAll('.s-value[data-count]').forEach(el => {
        const target = parseInt(el.dataset.count, 10) || 0;
        if (prefersReduced || target === 0) { el.textContent = target; return; }
        const duration = 700, start = performance.now();
        const tick = now => {
            const p = Math.min((now - start) / duration, 1);
            const eased = 1 - Math.pow(1 - p, 3);          // easeOutCubic
            el.textContent = Math.round(eased * target);
            if (p < 1) requestAnimationFrame(tick);
        };
        requestAnimationFrame(tick);
    });
})();
</script>
@endpush
@endsection