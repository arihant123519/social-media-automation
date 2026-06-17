@extends('layouts.app')
@section('title', 'Post Creator')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<style>
:root {
    --yt:#dc2626; --ig:#be185d;
    --ink:#0f172a; --muted:#64748b;
    --border:#e2e8f0; --surface:#f8fafc; --card:#fff;
    --accent:#6366f1; --good:#16a34a; --bad:#ef4444; --warn:#f59e0b;
    --radius:12px;
}
* { box-sizing:border-box; }

.pc-shell { max-width:1280px; margin:0 auto; }

.step-card {
    background:var(--card); border:1px solid var(--border);
    border-radius:var(--radius); padding:20px;
    margin-bottom:16px;
}
.step-head { display:flex; align-items:center; gap:10px; margin-bottom:14px; }
.step-num {
    width:28px; height:28px; border-radius:50%;
    background:var(--accent); color:#fff; font-size:13px; font-weight:700;
    display:flex; align-items:center; justify-content:center;
}
.step-num.done { background:var(--good); }
.step-num.muted { background:#cbd5e1; }
.step-title { font-size:15px; font-weight:600; color:var(--ink); margin:0; }
.step-sub { font-size:12px; color:var(--muted); margin:0; }

.field-row { display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:12px; }
.field label { font-size:12px; font-weight:500; color:var(--ink); margin-bottom:5px; display:block; }
.field .form-control, .field .form-select {
    width:100%; padding:8px 10px; border:1px solid var(--border);
    border-radius:8px; font-size:13px; background:var(--surface);
}
.field .form-control:focus, .field .form-select:focus {
    outline:none; border-color:var(--accent); background:#fff;
}

.btn-primary-pc {
    background:var(--accent); color:#fff; border:none;
    padding:10px 18px; border-radius:9px; font-size:13px; font-weight:600;
    cursor:pointer; display:inline-flex; align-items:center; gap:6px;
}
.btn-primary-pc:hover { background:#4f46e5; }
.btn-primary-pc:disabled { background:#a5b4fc; cursor:not-allowed; }

.btn-soft {
    background:#f1f5f9; color:#475569; border:1px solid var(--border);
    padding:8px 14px; border-radius:8px; font-size:12px; cursor:pointer;
}
.btn-soft:hover { background:#e2e8f0; }

/* ─── Trending grid ─── */
.trend-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(240px,1fr)); gap:12px; }
.trend-card {
    border:2px solid var(--border); border-radius:11px;
    overflow:hidden; cursor:pointer; transition:all .15s;
    background:#fff; position:relative;
}
.trend-card:hover { border-color:var(--accent); transform:translateY(-2px); box-shadow:0 6px 16px rgba(0,0,0,.06); }
.trend-card.selected { border-color:var(--accent); box-shadow:0 0 0 3px rgba(99,102,241,.15); }
.trend-card .thumb {
    width:100%; aspect-ratio:16/9; background:#f1f5f9;
    background-size:cover; background-position:center; position:relative;
}
/* Play icon only on video cards; photos show an image icon instead */
.trend-card .thumb.is-video::after {
    content:'\f4f4'; font-family:'bootstrap-icons';
    position:absolute; left:50%; top:50%; transform:translate(-50%,-50%);
    color:#fff; font-size:32px; opacity:.7; text-shadow:0 2px 8px rgba(0,0,0,.4);
}
.trend-card .thumb.is-image::after {
    content:'\f3fa'; font-family:'bootstrap-icons';  /* image icon */
    position:absolute; left:50%; top:50%; transform:translate(-50%,-50%);
    color:#fff; font-size:30px; opacity:.6; text-shadow:0 2px 8px rgba(0,0,0,.4);
}
.trend-card .check {
    position:absolute; top:8px; right:8px;
    width:24px; height:24px; border-radius:50%;
    background:#fff; border:2px solid var(--border);
    display:flex; align-items:center; justify-content:center;
    z-index:2; transition:all .15s;
}
.trend-card.selected .check {
    background:var(--accent); border-color:var(--accent); color:#fff;
}
.trend-card.selected .check::before {
    content:'\f26b'; font-family:'bootstrap-icons'; font-size:13px;
}
.trend-card .meta { padding:10px 12px; }
.trend-card .t-title { font-size:13px; font-weight:600; color:var(--ink);
    display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical;
    overflow:hidden; line-height:1.35; min-height:34px; }
.trend-card .t-channel { font-size:11px; color:var(--muted); margin-top:4px; }
.trend-card .t-stats { display:flex; gap:10px; font-size:11px; color:var(--muted); margin-top:6px; flex-wrap:wrap; }
.trend-card .t-stats span { display:inline-flex; align-items:center; gap:3px; }
.trend-card .t-velocity {
    display:inline-flex; align-items:center; gap:3px;
    font-size:10px; font-weight:700; padding:2px 7px;
    border-radius:20px; background:#fef3c7; color:#92400e;
    margin-top:6px;
}
.trend-card .t-date {
    font-size:10px; color:var(--muted); margin-top:4px;
    display:inline-flex; align-items:center; gap:3px;
}
.trend-card .t-tags { display:flex; flex-wrap:wrap; gap:4px; margin-top:8px; }
.trend-card .t-tag {
    font-size:10px; padding:2px 7px; border-radius:20px;
    background:#fdf2f8; color:#9d174d;
}
.trend-card.yt .t-tag { background:#fef2f2; color:#991b1b; }

.ai-tag {
    position:absolute; top:8px; left:8px; z-index:2;
    background:#ede9fe; color:#5b21b6;
    font-size:9px; font-weight:700; padding:3px 7px; border-radius:20px;
    text-transform:uppercase; letter-spacing:.05em;
}

/* ─── Upload ─── */
.upload-box {
    border:2px dashed var(--border); border-radius:10px;
    padding:24px; text-align:center; transition:all .15s;
    background:var(--surface); cursor:pointer;
}
.upload-box:hover { border-color:var(--accent); background:#eef2ff; }
.upload-box i { font-size:34px; color:var(--accent); margin-bottom:6px; }
.upload-box .upload-label { font-size:13px; font-weight:500; color:var(--ink); }
.upload-box .upload-sub { font-size:11px; color:var(--muted); margin-top:3px; }
.upload-box.has-file { border-style:solid; border-color:var(--good); background:#f0fdf4; }
.upload-box.has-file i { color:var(--good); }

/* ─── Score Result ─── */
.score-hero {
    display:flex; align-items:center; gap:18px;
    padding:18px; border-radius:11px; margin-bottom:14px;
    background:linear-gradient(135deg,#eef2ff,#f8fafc);
}
.score-ring {
    width:90px; height:90px; border-radius:50%;
    background:conic-gradient(var(--ring-color,var(--accent)) calc(var(--ring-pct,0)*1%), #e2e8f0 0);
    display:flex; align-items:center; justify-content:center;
    position:relative;
}
.score-ring::after {
    content:''; position:absolute; inset:8px;
    background:#fff; border-radius:50%;
}
.score-ring .num {
    position:relative; z-index:1; font-size:26px; font-weight:700; color:var(--ink);
}
.score-ring.good { --ring-color:var(--good); }
.score-ring.mid  { --ring-color:var(--warn); }
.score-ring.bad  { --ring-color:var(--bad); }

.score-summary h6 { font-size:14px; font-weight:600; margin:0 0 4px; color:var(--ink); }
.score-summary p  { font-size:12px; color:var(--muted); margin:0; line-height:1.5; }
.grade-badge {
    display:inline-block; padding:3px 10px; border-radius:20px;
    font-size:11px; font-weight:700; margin-left:8px;
}
.grade-good { background:#dcfce7; color:#166534; }
.grade-mid  { background:#fef3c7; color:#92400e; }
.grade-bad  { background:#fee2e2; color:#991b1b; }

.param-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:10px; }
.param-card {
    border:1px solid var(--border); border-radius:9px; padding:12px;
    background:#fff;
}
.param-card .p-head { display:flex; justify-content:space-between; align-items:center; margin-bottom:6px; }
.param-card .p-label { font-size:12px; font-weight:600; color:var(--ink); }
.param-card .p-score { font-size:12px; font-weight:700; color:var(--accent); }
.param-card .p-score.good { color:var(--good); }
.param-card .p-score.mid  { color:var(--warn); }
.param-card .p-score.bad  { color:var(--bad); }
.param-card .p-bar {
    height:5px; background:#e2e8f0; border-radius:3px; overflow:hidden;
    margin-bottom:7px;
}
.param-card .p-bar .fill { height:100%; background:var(--accent); border-radius:3px; }
.param-card .p-feedback { font-size:11px; color:var(--muted); line-height:1.5; }
.param-card .p-compare {
    display:grid; grid-template-columns:1fr 1fr; gap:8px;
    margin:8px 0; padding:8px; background:var(--surface); border-radius:7px;
    border:1px solid #eef2ff;
}
.param-card .p-compare .side { font-size:10px; line-height:1.45; }
.param-card .p-compare .side .lbl {
    font-size:9px; font-weight:700; text-transform:uppercase;
    letter-spacing:.05em; margin-bottom:3px; display:block;
}
.param-card .p-compare .side.ref .lbl { color:#1d4ed8; }
.param-card .p-compare .side.you .lbl { color:#9d174d; }
.param-card .p-compare .side .body { color:var(--ink); }

/* ─── Big head-to-head compare panel (reference vs user) ─── */
.compare-panel {
    display:grid; grid-template-columns:1fr 32px 1fr; gap:12px; align-items:stretch;
    border:1px solid var(--border); border-radius:11px; padding:14px;
    background:#fff; margin-bottom:14px;
}
.compare-side { display:flex; flex-direction:column; gap:8px; }
.compare-side .side-head {
    font-size:10px; font-weight:700; text-transform:uppercase;
    letter-spacing:.06em; display:flex; align-items:center; gap:6px;
}
.compare-side.ref .side-head { color:#1d4ed8; }
.compare-side.you .side-head { color:#9d174d; }

.compare-side .preview {
    width:100%; aspect-ratio:16/9; border-radius:8px;
    background:#f1f5f9 center/cover no-repeat;
    object-fit:cover; display:block;
}
.compare-side .row-label {
    font-size:10px; font-weight:600; color:var(--muted);
    text-transform:uppercase; letter-spacing:.05em; margin-top:4px;
}
.compare-side .row-val {
    font-size:12px; color:var(--ink); line-height:1.5;
    word-break:break-word;
}
.compare-side .tag-chips { display:flex; flex-wrap:wrap; gap:4px; }
.compare-side .tag-chips .chip {
    font-size:10px; padding:2px 8px; border-radius:20px;
}
.compare-side.ref .tag-chips .chip { background:#dbeafe; color:#1e3a8a; }
.compare-side.you .tag-chips .chip { background:#fce7f3; color:#9d174d; }

.compare-vs {
    display:flex; align-items:center; justify-content:center;
    font-size:11px; font-weight:700; color:var(--muted);
    background:#f8fafc; border-radius:6px;
}

@media (max-width:768px) {
    .compare-panel { grid-template-columns:1fr; }
    .compare-vs { padding:6px; }
    .param-card .p-compare { grid-template-columns:1fr; }
}

/* ─── Triple Comparison (Reference | V1 | V2 | V3) ─── */
.triple-compare {
    display:grid;
    grid-template-columns: 1.15fr 1fr 1fr 1fr;
    gap:10px;
    margin-bottom:14px;
}
.tc-col {
    background:#fff;
    border:1px solid var(--border);
    border-radius:11px;
    padding:12px;
    position:relative;
    display:flex; flex-direction:column; gap:6px;
}
.tc-col.ref     { background:#eff6ff; border-color:#bfdbfe; }
.tc-col.v.good  { border-color:var(--good); box-shadow:0 0 0 2px rgba(34,197,94,.1); }
.tc-col.v.mid   { border-color:var(--warn); }
.tc-col.v.bad   { border-color:var(--bad); }
.tc-col.v-empty { background:#f9fafb; opacity:.7; border-style:dashed; }

.tc-head {
    font-size:11px; font-weight:700; text-transform:uppercase;
    letter-spacing:.06em; color:var(--ink);
    display:flex; align-items:center; gap:5px;
}
.tc-col.ref .tc-head { color:#1d4ed8; }

.tc-preview {
    width:100%; aspect-ratio:9/16; max-height:200px;
    border-radius:7px; background:#f1f5f9 center/cover no-repeat;
    object-fit:cover; display:block;
}
.tc-empty-thumb {
    display:flex; align-items:center; justify-content:center;
    color:var(--muted); font-size:11px; font-style:italic;
}

.tc-title {
    font-size:12px; font-weight:600; color:var(--ink);
    line-height:1.35;
    display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical;
    overflow:hidden;
}
.tc-sub { font-size:10px; color:var(--muted); }
.tc-mini-muted { font-size:10px; color:var(--muted); font-style:italic; }

.tc-tags { display:flex; flex-wrap:wrap; gap:3px; }
.tc-tags .chip {
    font-size:9px; padding:2px 6px; border-radius:20px;
    background:#dbeafe; color:#1e3a8a;
}

.tc-stats {
    font-size:10px; color:var(--muted);
    display:flex; gap:8px; flex-wrap:wrap;
}

.tc-score-big {
    font-size:28px; font-weight:800; line-height:1;
    text-align:center; padding:6px 0;
    color:var(--ink);
}
.tc-score-big.good { color:var(--good); }
.tc-score-big.mid  { color:var(--warn); }
.tc-score-big.bad  { color:var(--bad); }

.tc-deltas {
    display:flex; flex-wrap:wrap; gap:4px; justify-content:center;
    min-height:18px;
}
.tc-delta {
    font-size:9px; font-weight:700;
    padding:2px 7px; border-radius:20px;
    display:inline-flex; align-items:center; gap:3px;
}
.tc-delta.delta-pos { background:#dcfce7; color:#166534; }
.tc-delta.delta-neg { background:#fee2e2; color:#991b1b; }

.tc-spell {
    font-size:10px; font-weight:600;
    padding:4px 8px; border-radius:6px;
    display:flex; align-items:center; gap:4px;
    justify-content:center;
}
.tc-spell.pass { background:#dcfce7; color:#166534; }
.tc-spell.fail { background:#fee2e2; color:#991b1b; }

.tc-issues {
    margin:0; padding-left:14px; list-style:disc;
    font-size:10px; color:#475569; line-height:1.4;
}
.tc-issues li { margin-bottom:2px; }

.tc-winner {
    position:absolute; top:-8px; right:-8px;
    background:linear-gradient(135deg,#facc15,#f59e0b);
    color:#78350f; font-size:9px; font-weight:800;
    padding:3px 8px; border-radius:20px;
    box-shadow:0 2px 8px rgba(245,158,11,.4);
    display:inline-flex; align-items:center; gap:3px;
    z-index:2; text-transform:uppercase; letter-spacing:.05em;
}

.tc-gate {
    padding:10px 14px; border-radius:9px;
    margin-bottom:10px; font-size:12px;
    display:flex; align-items:center; gap:8px;
}
.tc-gate.ok   { background:#dcfce7; color:#166534; border:1px solid #86efac; }
.tc-gate.fail { background:#fef3c7; color:#92400e; border:1px solid #fcd34d; }
.tc-gate i { font-size:18px; }

@media (max-width: 900px) {
    .triple-compare { grid-template-columns:1fr 1fr; }
}
@media (max-width: 540px) {
    .triple-compare { grid-template-columns:1fr; }
}

.suggest-list { list-style:none; padding:0; margin:0; }
.suggest-list li {
    padding:8px 12px; background:#fef3c7; border-left:3px solid var(--warn);
    border-radius:6px; margin-bottom:6px; font-size:12px; color:#78350f;
}

/* ─── Spelling errors + auto-fix ─── */
.spell-errors {
    margin-top:8px; padding:8px 10px;
    background:#fef2f2; border-left:3px solid var(--bad);
    border-radius:6px;
}
.spell-errors .se-head {
    font-size:10px; font-weight:700; text-transform:uppercase;
    color:#991b1b; margin-bottom:4px; letter-spacing:.05em;
}
.spell-errors ul { margin:0; padding-left:16px; }
.spell-errors li { font-size:11px; color:#7f1d1d; line-height:1.5; margin-bottom:2px; }

.auto-fix-box {
    margin-top:8px; padding:10px;
    background:#f0fdf4; border:1px solid #bbf7d0; border-radius:7px;
    position:relative;
}
.auto-fix-box .af-head {
    font-size:10px; font-weight:700; text-transform:uppercase;
    color:#166534; letter-spacing:.05em; margin-bottom:4px;
    display:flex; align-items:center; justify-content:space-between;
}
.auto-fix-box .af-text {
    font-size:12px; color:#14532d; line-height:1.5;
    white-space:pre-wrap; word-break:break-word;
}
.auto-fix-box .copy-btn-sm {
    background:#fff; color:#166534; border:1px solid #bbf7d0;
    border-radius:6px; padding:3px 8px; font-size:10px; cursor:pointer;
    font-weight:600;
}
.auto-fix-box .copy-btn-sm:hover { background:#dcfce7; }

/* ─── Recommendations grid ─── */
.recs-wrap {
    background:linear-gradient(135deg,#eef2ff,#f8fafc);
    border:1px solid #c7d2fe; border-radius:11px;
    padding:16px; margin-top:14px;
}
.recs-wrap > h6 {
    font-size:14px; font-weight:700; color:var(--ink); margin:0 0 12px;
    display:flex; align-items:center; gap:6px;
}
.recs-grid {
    display:grid; grid-template-columns:repeat(auto-fit,minmax(280px,1fr));
    gap:10px;
}
.rec-card {
    background:#fff; border:1px solid var(--border);
    border-radius:9px; padding:12px;
}
.rec-card .r-head {
    font-size:10px; font-weight:700; text-transform:uppercase;
    color:var(--muted); letter-spacing:.06em; margin-bottom:8px;
    display:flex; align-items:center; gap:5px;
}
.hook-rec {
    padding:8px 10px; border:1px solid var(--border); border-radius:7px;
    margin-bottom:6px; font-size:12px; color:var(--ink); line-height:1.4;
    cursor:pointer; transition:all .15s; display:flex; align-items:center; gap:8px;
}
.hook-rec:hover { background:#eef2ff; border-color:var(--accent); }
.hook-rec .h-badge {
    font-size:9px; font-weight:700; text-transform:uppercase;
    padding:2px 6px; border-radius:20px; flex-shrink:0;
    background:#e0e7ff; color:#3730a3;
}
.cap-rec {
    padding:8px 10px; border:1px solid var(--border); border-radius:7px;
    margin-bottom:6px; font-size:12px; color:var(--ink); line-height:1.5;
    cursor:pointer; position:relative;
}
.cap-rec:hover { background:#f8fafc; }
.cap-rec .c-copy {
    position:absolute; top:6px; right:6px;
    background:none; border:none; cursor:pointer; color:var(--muted);
    font-size:11px;
}
.cap-rec .c-copy:hover { color:var(--accent); }

.htag-mix { display:flex; flex-wrap:wrap; gap:5px; margin-top:5px; }
.htag-mix .chip { font-size:10px; padding:3px 8px; border-radius:20px; cursor:pointer; }
.htag-mix .chip.trending { background:#fce7f3; color:#9d174d; }
.htag-mix .chip.niche    { background:#ede9fe; color:#5b21b6; }
.htag-mix .chip.brand    { background:#d1fae5; color:#065f46; }
.htag-mix .chip:hover { opacity:.85; }

.time-box {
    text-align:center; padding:12px;
    background:linear-gradient(135deg,#fef3c7,#fde68a);
    border-radius:8px;
}
.time-box .t-time {
    font-size:16px; font-weight:700; color:#78350f;
}

.idea-row-rec {
    padding:8px 10px; border:1px solid var(--border); border-radius:7px;
    margin-bottom:6px;
}
.idea-row-rec .ir-title { font-size:12px; font-weight:600; color:var(--ink); }
.idea-row-rec .ir-angle { font-size:11px; color:var(--muted); margin-top:2px; }
.idea-row-rec .ir-script {
    font-size:10px; color:#475569; margin-top:4px; line-height:1.4;
    padding:4px 6px; background:var(--surface); border-radius:5px;
}
.idea-row-rec .ir-views {
    font-size:10px; font-weight:700; color:#065f46;
    background:#d1fae5; padding:2px 7px; border-radius:20px;
    display:inline-block; margin-top:4px;
}

/* Section-level "Copy all" buttons */
.copy-section-btn {
    background:var(--accent); color:#fff; border:none;
    border-radius:6px; padding:4px 10px; font-size:10px;
    font-weight:600; cursor:pointer; display:inline-flex;
    align-items:center; gap:4px; transition:all .15s;
}
.copy-section-btn:hover { background:#4f46e5; }
.copy-section-btn:active { transform:scale(.97); }

/* Tiny per-item copy buttons */
.copy-inline-btn {
    background:transparent; border:1px solid var(--border);
    border-radius:5px; padding:2px 6px; font-size:10px;
    color:var(--muted); cursor:pointer; transition:all .12s;
    flex-shrink:0;
}
.copy-inline-btn:hover { background:var(--accent); color:#fff; border-color:var(--accent); }

/* Anything copyable shows pointer cursor */
[data-copy], [data-copy-list], [data-copy-ideas] { cursor:pointer; }

/* ─── Attempts comparison ─── */
.attempts-row { display:grid; grid-template-columns:repeat(3,1fr); gap:10px; margin-bottom:14px; }
.attempt-tile {
    border:1px solid var(--border); border-radius:10px; padding:12px;
    background:#fff; text-align:center; position:relative;
}
.attempt-tile.empty { opacity:.4; background:#f9fafb; }
.attempt-tile .a-num {
    font-size:10px; color:var(--muted); font-weight:600;
    text-transform:uppercase; letter-spacing:.06em;
}
.attempt-tile .a-score {
    font-size:30px; font-weight:700; margin:6px 0;
    color:var(--ink);
}
.attempt-tile.good .a-score { color:var(--good); }
.attempt-tile.mid  .a-score { color:var(--warn); }
.attempt-tile.bad  .a-score { color:var(--bad); }
.attempt-tile .a-arrow {
    position:absolute; right:-7px; top:50%; transform:translateY(-50%);
    color:var(--muted); font-size:16px; z-index:2;
    background:#fff; padding:2px;
}
.attempt-tile:last-child .a-arrow { display:none; }
.attempt-tile .a-preview {
    width:100%; aspect-ratio:16/9; border-radius:6px;
    margin-top:6px; background:#f1f5f9 center/cover no-repeat;
    object-fit:cover; display:block;
}

.approved-banner {
    background:linear-gradient(135deg,#22c55e,#16a34a); color:#fff;
    padding:18px 20px; border-radius:11px; display:flex;
    align-items:center; justify-content:space-between; gap:14px;
    margin-bottom:14px;
}
.approved-banner h6 { font-size:16px; font-weight:700; margin:0; }
.approved-banner p  { font-size:12px; opacity:.95; margin:2px 0 0; }
.download-btn {
    background:#fff; color:var(--good); padding:10px 18px;
    border-radius:9px; font-size:13px; font-weight:600;
    text-decoration:none; display:inline-flex; align-items:center; gap:7px;
    border:none; cursor:pointer;
}

.max-banner {
    background:linear-gradient(135deg,#fef3c7,#fde68a); color:#78350f;
    padding:14px 18px; border-radius:11px; margin-bottom:14px;
    font-size:13px;
}

.skeleton { background:linear-gradient(90deg,#f1f5f9 25%,#e2e8f0 50%,#f1f5f9 75%);
    background-size:200% 100%; animation:shimmer 1.4s infinite; border-radius:8px; }
@keyframes shimmer { 0%{background-position:200% 0;} 100%{background-position:-200% 0;} }

.hidden { display:none !important; }

/* ─── Inspiration Panel (keyword-based AI suggestions, shown after Find Trending) ─── */
.insp-panel {
    background:linear-gradient(135deg,#fef3c7,#fde68a);
    border:1px solid #fcd34d; border-radius:11px;
    padding:14px 16px; margin-top:14px;
}
.insp-panel h6 {
    font-size:13px; font-weight:700; color:#78350f;
    margin:0 0 10px; display:flex; align-items:center; gap:6px;
}
.insp-grid {
    display:grid; grid-template-columns:repeat(auto-fit, minmax(240px, 1fr));
    gap:10px;
}
.insp-block {
    background:#fff; border:1px solid #fcd34d; border-radius:8px;
    padding:10px 12px;
}
.insp-block .ib-head {
    font-size:10px; font-weight:700; text-transform:uppercase;
    color:#92400e; letter-spacing:.05em; margin-bottom:6px;
}
.insp-hook {
    font-size:11px; padding:5px 8px; border-radius:6px;
    background:#fffbeb; margin-bottom:4px; cursor:pointer;
    display:flex; align-items:center; gap:6px; color:#451a03;
}
.insp-hook:hover { background:#fef3c7; }
.insp-hook .ih-badge {
    font-size:8px; font-weight:700; text-transform:uppercase;
    padding:1px 5px; border-radius:20px;
    background:#fde68a; color:#78350f; flex-shrink:0;
}
.insp-tags { display:flex; flex-wrap:wrap; gap:3px; }
.insp-tags .chip {
    font-size:10px; padding:2px 7px; border-radius:20px;
    cursor:pointer;
}
.insp-tags .chip.trending { background:#fce7f3; color:#9d174d; }
.insp-tags .chip.niche    { background:#ede9fe; color:#5b21b6; }
.insp-tags .chip.brand    { background:#d1fae5; color:#065f46; }
.insp-script {
    font-size:11px; color:#451a03; line-height:1.5;
}
.insp-script .step {
    padding:4px 6px; background:#fffbeb; border-left:2px solid #f59e0b;
    border-radius:0 5px 5px 0; margin-bottom:3px;
}
.insp-script .step strong { color:#78350f; }
.insp-time {
    text-align:center; font-size:14px; font-weight:700;
    color:#78350f; padding:6px;
}

/* ─── Trending Detail Modal ─── */
.trend-modal-backdrop {
    position:fixed; inset:0; background:rgba(0,0,0,.55);
    z-index:1100; display:flex; align-items:center; justify-content:center;
    opacity:0; pointer-events:none; transition:opacity .2s;
    padding:16px;
}
.trend-modal-backdrop.open { opacity:1; pointer-events:all; }
.trend-modal {
    background:#fff; border-radius:14px; width:100%; max-width:720px;
    max-height:90vh; overflow-y:auto;
    box-shadow:0 20px 60px rgba(0,0,0,.3);
    transform:translateY(20px); transition:transform .2s;
}
.trend-modal-backdrop.open .trend-modal { transform:none; }
.tm-header {
    padding:14px 18px; border-bottom:1px solid var(--border);
    display:flex; align-items:center; justify-content:space-between;
    gap:10px; position:sticky; top:0; background:#fff; z-index:2;
}
.tm-header h5 { font-size:15px; font-weight:700; margin:0; color:var(--ink); }
.tm-close {
    width:30px; height:30px; border-radius:50%;
    border:none; background:#f1f5f9; cursor:pointer; font-size:16px;
    display:flex; align-items:center; justify-content:center; color:var(--muted);
}
.tm-body { padding:16px 18px; }
.tm-thumb {
    width:100%; aspect-ratio:16/9; border-radius:9px;
    background:#f1f5f9 center/cover no-repeat; margin-bottom:14px;
}
.tm-row { margin-bottom:12px; }
.tm-row .tm-label {
    font-size:10px; font-weight:700; text-transform:uppercase;
    color:var(--muted); letter-spacing:.05em; margin-bottom:4px;
}
.tm-row .tm-val { font-size:13px; color:var(--ink); line-height:1.55; word-break:break-word; }
.tm-stats {
    display:grid; grid-template-columns:repeat(auto-fit, minmax(110px, 1fr));
    gap:10px; margin-bottom:14px;
}
.tm-stat {
    background:var(--surface); border:1px solid var(--border);
    border-radius:8px; padding:8px 10px; text-align:center;
}
.tm-stat .ts-val { font-size:14px; font-weight:700; color:var(--ink); }
.tm-stat .ts-lbl { font-size:10px; color:var(--muted); margin-top:2px; }
.tm-tags { display:flex; flex-wrap:wrap; gap:5px; }
.tm-tags .chip {
    font-size:11px; padding:3px 9px; border-radius:20px;
    background:#fce7f3; color:#9d174d; cursor:pointer;
}
.tm-tags .chip.yt { background:#fef2f2; color:#991b1b; }
.tm-footer {
    padding:12px 18px; border-top:1px solid var(--border);
    display:flex; gap:8px; justify-content:flex-end;
    position:sticky; bottom:0; background:#fff;
}
.tm-link {
    color:var(--accent); text-decoration:none; font-size:11px;
    display:inline-flex; align-items:center; gap:4px;
}
.tm-link:hover { text-decoration:underline; }
</style>
@endpush

@section('content')
<div class="container-fluid py-4 pc-shell">

    <div class="mb-3">
        <h4 class="fw-bold mb-0">Post Creator</h4>
        <p class="text-muted mb-0" style="font-size:13px">Find trending posts → upload yours → get AI score → improve up to 3 times</p>
    </div>

    {{-- ═════════ STEP 1: PICK SCOPE + KEYWORD ═════════ --}}
    <div class="step-card" id="step1">
        <div class="step-head">
            <div class="step-num">1</div>
            <div>
                <h6 class="step-title">Pick client, scope & keyword</h6>
                <p class="step-sub">Choose what kind of post you want to compare against</p>
            </div>
        </div>

        <div class="field-row">
            <div class="field">
                <label>Client</label>
                <select id="clientSel" class="form-select">
                    <option value="">Select client...</option>
                    @foreach($clients as $c)
                        <option value="{{ $c->id }}" data-industry="{{ $c->industry }}">{{ $c->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="field">
                <label>Scope</label>
                <select id="scopeSel" class="form-select">
                    <option value="0">YouTube</option>
                    <option value="1" selected>Instagram</option>
                </select>
            </div>

            <div class="field">
                <label>Post Type</label>
                <select id="typeSel" class="form-select">
                    {{-- populated by JS based on scope --}}
                </select>
            </div>

            <div class="field" style="grid-column:span 2;">
                <label>Keyword / Topic</label>
                <input id="keywordInput" class="form-control" type="text" placeholder="e.g. skin care, gym motivation, recipe...">
            </div>
        </div>

        <div class="mt-3">
            <button class="btn-primary-pc" id="findBtn" onclick="findTrending()">
                <i class="bi bi-search"></i> Find Trending
            </button>
        </div>
    </div>

    {{-- ═════════ STEP 2: TRENDING RESULTS ═════════ --}}
    <div class="step-card hidden" id="step2">
        <div class="step-head">
            <div class="step-num">2</div>
            <div>
                <h6 class="step-title">Pick ONE trending post to compare against <span class="text-muted" style="font-weight:400;font-size:12px">(optional)</span></h6>
                <p class="step-sub" id="trendSub">Click any card to select it</p>
            </div>
            <button class="btn-soft hidden" id="skipCompareBtn" onclick="startDirect()" style="margin-left:auto;white-space:nowrap">
                <i class="bi bi-lightning-charge"></i> Skip comparison &amp; score directly
            </button>
        </div>
        <div id="trendGrid" class="trend-grid"></div>
    </div>

    {{-- ═════════ STEP 3: UPLOAD ═════════ --}}
    <div class="step-card hidden" id="step3">
        <div class="step-head">
            <div class="step-num" id="step3Num">3</div>
            <div>
                <h6 class="step-title" id="step3Title">Upload your version</h6>
                <p class="step-sub" id="step3Sub">Upload your file → AI will compare it with the selected trending post</p>
            </div>
        </div>

        <input type="file" id="fileInput" class="hidden" onchange="handleFile(event)">
        <div class="upload-box" id="uploadBox" onclick="document.getElementById('fileInput').click()">
            <i class="bi bi-cloud-upload"></i>
            <div class="upload-label" id="uploadLabel">Click to upload your file</div>
            <div class="upload-sub" id="uploadSub">mp4/mov for videos, jpg/png for photos · max 200MB</div>
        </div>

        <div class="field-row mt-3">
            <div class="field" style="grid-column:span 2;">
                <label>Caption (paste your post caption)</label>
                <textarea id="captionInput" class="form-control" rows="2" placeholder="Write the caption you plan to use..."></textarea>

                {{-- AI caption suggestions (auto-loaded after step 3 opens) --}}
                <div class="dropdown mt-1" id="captionSuggestWrap" style="display:none;">
                    <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-lightbulb"></i> <span id="captionSuggestCount">0</span> AI caption suggestion(s)
                    </button>
                    <ul class="dropdown-menu shadow-sm" id="captionSuggestMenu" style="min-width:420px; max-width:560px; max-height:340px; overflow-y:auto;"></ul>
                </div>
                <div class="text-muted small mt-1" id="captionSuggestLoading" style="display:none;">
                    <span class="spinner-border spinner-border-sm me-1"></span> Generating AI suggestions…
                </div>
            </div>
            <div class="field" style="grid-column:span 2;">
                <label class="d-flex align-items-center justify-content-between">
                    <span>Hashtags</span>
                    <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2" id="bankBtn" onclick="pullFromBank()" title="Insert top hashtags from this client's specialty bank">
                        <i class="bi bi-bank"></i> Bank
                    </button>
                </label>
                <input id="hashtagsInput" class="form-control" type="text" placeholder="#skincare #beauty #tips">

                {{-- AI hashtag suggestions --}}
                <div class="dropdown mt-1" id="hashtagSuggestWrap" style="display:none;">
                    <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-hash"></i> <span id="hashtagSuggestCount">0</span> AI hashtag suggestion(s)
                    </button>
                    <ul class="dropdown-menu shadow-sm" id="hashtagSuggestMenu" style="min-width:420px; max-width:560px; max-height:340px; overflow-y:auto;"></ul>
                </div>
            </div>
        </div>

        <div class="mt-3 d-flex gap-2">
            <button class="btn-primary-pc" id="submitBtn" onclick="submitUpload()" disabled>
                <i class="bi bi-stars"></i> <span id="submitBtnLabel">Get Health Score</span>
            </button>
            <button class="btn-soft" onclick="resetFlow()">Start Over</button>
        </div>
    </div>

    {{-- ═════════ STEP 4: RESULT ═════════ --}}
    <div class="step-card hidden" id="step4">
        <div class="step-head">
            <div class="step-num done">4</div>
            <div>
                <h6 class="step-title">Health Score & Suggestions</h6>
                <p class="step-sub" id="resultSub"></p>
            </div>
        </div>

        <div id="approvedBanner" class="hidden"></div>
        <div id="maxBanner" class="hidden"></div>

        {{-- Triple comparison: Reference | V1 | V2 | V3 (single panel) --}}
        <div id="compareWrap"></div>

        {{-- current score hero --}}
        <div id="scoreHero"></div>
        <div id="paramGrid" class="param-grid mt-3"></div>

        <div id="suggestionsWrap" class="mt-3"></div>

        {{-- AI Recommendations: alternative hooks, caption variants, hashtags, best time, 5 reel ideas --}}
        <div id="recommendationsWrap" class="mt-3"></div>
    </div>

</div>
{{-- ═════════ TRENDING DETAIL MODAL ═════════ --}}
<div class="trend-modal-backdrop" id="trendModalBackdrop" onclick="closeTrendModalOnBackdrop(event)">
    <div class="trend-modal" role="dialog" aria-modal="true">
        <div class="tm-header">
            <h5 id="tmTitle">Reel Details</h5>
            <button class="tm-close" onclick="closeTrendModal()" aria-label="Close">&times;</button>
        </div>
        <div class="tm-body" id="tmBody"></div>
        <div class="tm-footer">
            <button class="btn-soft" onclick="closeTrendModal()">Cancel</button>
            <button class="btn-primary-pc" onclick="confirmTrendSelection()" id="tmConfirmBtn">
                <i class="bi bi-check2"></i> Use as Comparison Reference
            </button>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
const CSRF = '{{ csrf_token() }}';
const PREFILL = @json($prefill ?? []);
const ROUTES = {
    trending:    @json(route('posts.trending')),
    inspiration: @json(route('posts.inspiration')),
    start:       @json(route('posts.start')),
    upload:      @json(route('posts.upload')),
    download:    (id) => @json(url('/posts')) + '/' + id + '/download',
    publish:     (id) => @json(url('/posts')) + '/' + id + '/publish',
    save:        (id) => @json(url('/posts')) + '/' + id + '/save',
    hashtagSuggest: @json(route('hashtags.suggest')),
};

// #13 — Insert top-rated hashtags from this client's specialty bank.
async function pullFromBank() {
    const sel = document.getElementById('clientSel');
    const opt = sel?.selectedOptions?.[0];
    const specialty = opt?.dataset?.industry;
    if (!specialty) { (window.showToast||alert)('warning', 'Pick a client first.'); return; }

    const btn = document.getElementById('bankBtn');
    const orig = btn ? btn.innerHTML : '';
    if (btn) { btn.disabled = true; btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>'; }
    try {
        const res = await fetch(ROUTES.hashtagSuggest + '?specialty=' + encodeURIComponent(specialty) + '&limit=25', {
            headers: { 'Accept': 'application/json' },
        });
        const j = await res.json();
        if (!j.success || !j.all || !j.all.length) {
            (window.showToast||alert)('info', 'No hashtags in the ' + specialty + ' bank yet. Add some under Content Tools → Hashtag Bank.');
            return;
        }
        const input = document.getElementById('hashtagsInput');
        const existing = (input.value.match(/#\w+/g) || []);
        const merged = [...new Set([...existing, ...j.all])];
        input.value = merged.join(' ');
        if (window.showToast) showToast('success', j.all.length + ' hashtags added from the ' + specialty + ' bank.');
    } catch (e) {
        (window.showToast||alert)('danger', 'Could not load the hashtag bank.');
    } finally {
        if (btn) { btn.disabled = false; btn.innerHTML = orig; }
    }
}

const SCOPE_TYPES = {
    0: [
        { value:'long_video',  label:'YouTube Long Video' },
        { value:'short_video', label:'YouTube Short' },
    ],
    1: [
        { value:'reels', label:'Instagram Reel'  },
        { value:'photo', label:'Instagram Photo' },
        { value:'story', label:'Instagram Story' },
    ],
};

let state = {
    postId: null,
    selectedRef: null,
    pendingRef: null,    // ref currently shown in detail modal, not yet confirmed
    attempts: [],
    finalStatus: null,
    file: null,
    scheduledDate: null,   // from calendar deep-link, if any
    clientScopeId: null,
};

// ─── INIT ───
document.addEventListener('DOMContentLoaded', () => {
    populateTypes();
    document.getElementById('scopeSel').addEventListener('change', populateTypes);

    // Apply prefill from calendar deep-link
    if (PREFILL && (PREFILL.client_id || PREFILL.post_type)) {
        applyPrefill(PREFILL);
    }

    // Apply hand-off from an AI Studio tool (?from=ai + sessionStorage draft)
    applyAiHandoff();
});

// Map AI-tool post types → Post Creator post types
const AI_TYPE_MAP = { reel:'reels', reels:'reels', photo:'photo', carousel:'photo', story:'story', short_video:'short_video', long_video:'long_video' };

// Pre-fill the wizard from a draft stashed by an AI Studio tool, then reveal
// Step 2 with the "Skip comparison" option. Caption/hashtags carry over; the
// trending comparison becomes optional (skipping scores the post standalone).
function applyAiHandoff() {
    const fromAi = new URLSearchParams(location.search).get('from') === 'ai';
    let raw = null;
    try { raw = sessionStorage.getItem('aiPublishDraft'); } catch (e) {}
    if (!fromAi || !raw) return;

    let draft;
    try { draft = JSON.parse(raw); } catch (e) { return; }
    try { sessionStorage.removeItem('aiPublishDraft'); } catch (e) {}

    const postType  = AI_TYPE_MAP[draft.post_type] || 'reels';
    const isYouTube = (postType === 'long_video' || postType === 'short_video');

    // Scope + post types
    document.getElementById('scopeSel').value = isYouTube ? '0' : '1';
    populateTypes();
    const typeSel = document.getElementById('typeSel');
    if ([...typeSel.options].some(o => o.value === postType)) typeSel.value = postType;

    // Client (only when the option exists — "No client" tools leave it empty)
    if (draft.client_id) {
        const clientSel = document.getElementById('clientSel');
        if ([...clientSel.options].some(o => o.value === String(draft.client_id))) {
            clientSel.value = String(draft.client_id);
        }
    }

    // Text fields
    if (draft.keyword)  document.getElementById('keywordInput').value  = draft.keyword;
    if (draft.caption)  document.getElementById('captionInput').value  = draft.caption;
    if (draft.hashtags) document.getElementById('hashtagsInput').value = draft.hashtags;

    // Seed extra caption variants into the Step-3 suggestion dropdown
    if (Array.isArray(draft.caption_variants) && draft.caption_variants.length) {
        v1CaptionItems = dedupeByText(draft.caption_variants
            .filter(c => typeof c === 'string' && c.trim().length > 5)
            .map((c, i) => ({ label: `AI variant ${i + 1}`, text: c.trim() })));
        renderV1Dropdown('captionSuggest', v1CaptionItems, 'captionInput');
    }

    // Info banner above Step 1
    const labelMap = { script:'Video Script', captions:'Caption', reel:'Reel Analyzer' };
    const srcLabel = labelMap[draft.source] || 'AI';
    const step1 = document.getElementById('step1');
    if (step1 && !document.getElementById('aiHandoffBanner')) {
        const b = document.createElement('div');
        b.id = 'aiHandoffBanner';
        b.style.cssText = 'background:#eef2ff;border:1px solid #c7d2fe;border-radius:9px;padding:10px 14px;margin-bottom:12px;font-size:13px;color:#3730a3;display:flex;align-items:center;gap:8px;flex-wrap:wrap';
        b.innerHTML = `<i class="bi bi-magic"></i> Publishing your <strong>${escapeHtml(srcLabel)}</strong> content — caption &amp; hashtags pre-filled. Pick a client, then either choose a trending post to compare, or <strong>skip the comparison</strong> and score directly.`;
        step1.parentNode.insertBefore(b, step1);
    }

    // Reveal Step 2 + the Skip button
    document.getElementById('step2').classList.remove('hidden');
    document.getElementById('trendSub').textContent = 'Optional — pick a trending post to compare, or skip & score directly';
    const skipBtn = document.getElementById('skipCompareBtn');
    if (skipBtn) skipBtn.classList.remove('hidden');
}

// Skip the trending comparison: create the Post with NO reference, then go
// straight to upload. The post is scored STANDALONE — the score≥60 + spelling
// gate still applies before it can be saved/published.
async function startDirect() {
    const clientId = document.getElementById('clientSel').value;
    const keyword  = (document.getElementById('keywordInput').value || '').trim();
    if (!clientId) { alert('Pick a client first.'); document.getElementById('clientSel').focus(); return; }
    if (!keyword)  { alert('Enter a keyword / topic first.'); document.getElementById('keywordInput').focus(); return; }

    const btn = document.getElementById('skipCompareBtn');
    if (btn) { btn.disabled = true; btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Starting…'; }

    try {
        const res = await fetch(ROUTES.start, {
            method:'POST',
            headers: { 'Content-Type':'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept':'application/json' },
            body: JSON.stringify({
                client_id:       clientId,
                scope:           document.getElementById('scopeSel').value,
                post_type:       document.getElementById('typeSel').value,
                keyword,
                scheduled_date:  state.scheduledDate,
                client_scope_id: state.clientScopeId,
                // no trending_ref_id / trending_ref_meta → standalone scoring
            }),
        });
        const json = await res.json();
        if (!json.success) { alert(json.error || json.message || 'Failed to start post.'); return; }

        state.selectedRef = null;
        state.postId      = json.post_id;
        state.attempts    = [];

        document.getElementById('step3').classList.remove('hidden');
        document.getElementById('step3').scrollIntoView({ behavior:'smooth', block:'start' });
        updateStep3Header();
        applyUploadAccept();
        loadV1Suggestions();
    } catch (e) {
        alert(e.message);
    } finally {
        if (btn) { btn.disabled = false; btn.innerHTML = '<i class="bi bi-lightning-charge"></i> Skip comparison &amp; score directly'; }
    }
}

function applyPrefill(p) {
    if (p.client_id) {
        const sel = document.getElementById('clientSel');
        if (sel) sel.value = p.client_id;
    }
    if (p.scope !== null && p.scope !== undefined && p.scope !== '') {
        document.getElementById('scopeSel').value = String(p.scope);
        populateTypes();
    }
    if (p.post_type) {
        document.getElementById('typeSel').value = p.post_type;
    }

    // Banner so user knows which calendar slot this Post is for
    state.scheduledDate  = p.scheduled_date || null;
    state.clientScopeId  = p.client_scope_id ? Number(p.client_scope_id) : null;
    if (p.scheduled_date) {
        const niceDate = new Date(p.scheduled_date).toLocaleDateString('en-IN', { weekday:'long', day:'numeric', month:'long', year:'numeric' });
        const step1 = document.getElementById('step1');
        if (step1 && !document.getElementById('prefillBanner')) {
            const banner = document.createElement('div');
            banner.id = 'prefillBanner';
            banner.style.cssText = 'background:#eef2ff;border:1px solid #c7d2fe;border-radius:9px;padding:10px 14px;margin-bottom:12px;font-size:13px;color:#3730a3;display:flex;align-items:center;gap:8px;';
            banner.innerHTML = `<i class="bi bi-calendar-event"></i> Creating post for calendar slot: <strong>${niceDate}</strong> · client + scope pre-selected from calendar.`;
            step1.parentNode.insertBefore(banner, step1);
        }
        document.getElementById('keywordInput')?.focus();
    }
}

function populateTypes() {
    const scope = document.getElementById('scopeSel').value;
    const sel = document.getElementById('typeSel');
    sel.innerHTML = SCOPE_TYPES[scope]
        .map(t => `<option value="${t.value}">${t.label}</option>`).join('');
}

// ─── STEP 1: find trending ───
async function findTrending() {
    const clientId = document.getElementById('clientSel').value;
    const scope    = document.getElementById('scopeSel').value;
    const postType = document.getElementById('typeSel').value;
    const keyword  = document.getElementById('keywordInput').value.trim();

    if (!clientId) { alert('Select a client first.'); return; }
    if (!keyword)  { alert('Enter a keyword.'); document.getElementById('keywordInput').focus(); return; }

    const btn = document.getElementById('findBtn');
    btn.disabled = true; btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Searching…';

    showSkeletonGrid();
    document.getElementById('step2').classList.remove('hidden');

    try {
        const res = await fetch(ROUTES.trending, {
            method:'POST',
            headers: { 'Content-Type':'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept':'application/json' },
            body: JSON.stringify({ client_id:clientId, scope, post_type:postType, keyword }),
        });
        const json = await res.json();

        if (!json.success) {
            renderTrendError(json.error || 'No results.');
        } else {
            renderTrending(json.items || [], parseInt(scope), !!json.ai_generated);
        }
    } catch (e) {
        renderTrendError(e.message);
    } finally {
        btn.disabled = false; btn.innerHTML = '<i class="bi bi-search"></i> Find Trending';
    }
}


function showSkeletonGrid() {
    document.getElementById('trendGrid').innerHTML = Array(8).fill(0)
        .map(() => `<div class="trend-card"><div class="skeleton" style="aspect-ratio:16/9"></div>
            <div class="meta"><div class="skeleton sk-line" style="height:12px;margin-bottom:6px;"></div>
            <div class="skeleton sk-line" style="height:10px;width:60%"></div></div></div>`).join('');
}

function renderTrendError(msg) {
    document.getElementById('trendGrid').innerHTML =
        `<div style="grid-column:1/-1;padding:30px;text-align:center;color:var(--bad);font-size:13px">
            <i class="bi bi-exclamation-triangle" style="font-size:24px;display:block;margin-bottom:8px"></i>
            ${escapeHtml(msg)}
        </div>`;
}

function renderTrending(items, scope, aiGen) {
    if (!items.length) { renderTrendError('No trending posts found.'); return; }

    const sub = aiGen
        ? `AI-generated trending ideas (real IG hashtag search needs Meta App Review) — pick one`
        : `Top ${items.length} trending posts — click ONE to select`;
    document.getElementById('trendSub').textContent = sub;

    const cls = scope === 0 ? 'yt' : 'ig';

    document.getElementById('trendGrid').innerHTML = items.map((it, idx) => {
        const thumb = it.thumbnail
            ? `style="background-image:url('${escapeAttr(it.thumbnail)}')"` : '';

        const stats = [];
        if (it.views != null && it.views > 0)    stats.push(`<span><i class="bi bi-eye"></i> ${fmtNum(it.views)}</span>`);
        if (it.likes != null && it.likes > 0)    stats.push(`<span><i class="bi bi-heart"></i> ${fmtNum(it.likes)}</span>`);
        if (it.comments != null && it.comments > 0) stats.push(`<span><i class="bi bi-chat"></i> ${fmtNum(it.comments)}</span>`);

        const velocity = it.views_per_day
            ? `<div class="t-velocity"><i class="bi bi-fire"></i> ${fmtNum(it.views_per_day)}/day</div>`
            : '';

        const dateLine = it.published_human
            ? `<div class="t-date"><i class="bi bi-clock"></i> ${escapeHtml(it.published_human)}</div>`
            : (it.published ? `<div class="t-date"><i class="bi bi-clock"></i> ${escapeHtml(it.published.substring(0,10))}</div>` : '');

        const tags = (it.hashtags || []).slice(0,3)
            .map(t => `<span class="t-tag">${escapeHtml(t)}</span>`).join('');
        const aiBadge = aiGen ? `<span class="ai-tag">AI</span>` : '';

        return `
        <div class="trend-card ${cls}" data-idx="${idx}" onclick="openTrendDetail(${idx})">
            ${aiBadge}
            <div class="check"></div>
            <div class="thumb ${it.media_type === 'IMAGE' ? 'is-image' : 'is-video'}" ${thumb}></div>
            <div class="meta">
                <div class="t-title">${escapeHtml(it.title || '(untitled)')}</div>
                ${it.channel ? `<div class="t-channel">${escapeHtml(it.channel)}</div>` : ''}
                <div class="t-stats">${stats.join(' ')}</div>
                ${velocity}
                ${dateLine}
                <div class="t-tags">${tags}</div>
            </div>
        </div>`;
    }).join('');

    window._trendItems = items;
    window._trendScope = scope;
}

// ─── STEP 2A: open detail modal (no commit yet) ───
function openTrendDetail(idx) {
    const item = window._trendItems[idx];
    if (!item) return;

    state.pendingRef = item;
    const scope = parseInt(document.getElementById('scopeSel').value);
    const cls   = scope === 0 ? 'yt' : 'ig';

    const thumb = item.thumbnail
        ? `<div class="tm-thumb" style="background-image:url('${escapeAttr(item.thumbnail)}')"></div>` : '';

    const stats = [];
    if (item.views    != null && item.views > 0)    stats.push(`<div class="tm-stat"><div class="ts-val">${fmtNum(item.views)}</div><div class="ts-lbl">Views</div></div>`);
    if (item.likes    != null && item.likes > 0)    stats.push(`<div class="tm-stat"><div class="ts-val">${fmtNum(item.likes)}</div><div class="ts-lbl">Likes</div></div>`);
    if (item.comments != null && item.comments > 0) stats.push(`<div class="tm-stat"><div class="ts-val">${fmtNum(item.comments)}</div><div class="ts-lbl">Comments</div></div>`);
    if (item.views_per_day)                         stats.push(`<div class="tm-stat" style="background:#fef3c7;border-color:#fcd34d"><div class="ts-val" style="color:#92400e">🔥 ${fmtNum(item.views_per_day)}</div><div class="ts-lbl">Views/day</div></div>`);
    if (item.published_human || item.published)     stats.push(`<div class="tm-stat"><div class="ts-val" style="font-size:11px">${escapeHtml(item.published_human || item.published.substring(0,10))}</div><div class="ts-lbl">Published</div></div>`);
    if (item.duration_sec)                          stats.push(`<div class="tm-stat"><div class="ts-val">${item.duration_sec}s</div><div class="ts-lbl">Duration</div></div>`);

    const tagsHtml = (item.hashtags || []).length
        ? (item.hashtags.map(t => `<span class="chip ${cls}" data-copy="${escapeAttr(t)}">${escapeHtml(t)}</span>`).join(''))
        : `<span style="font-size:11px;color:var(--muted);font-style:italic">No hashtags in description</span>`;

    const description = item.description || item.caption || '';

    document.getElementById('tmTitle').textContent = item.title || (item.media_type === 'IMAGE' ? 'Photo Details' : 'Post Details');
    document.getElementById('tmBody').innerHTML = `
        ${thumb}
        <div class="tm-row">
            <div class="tm-label">Title</div>
            <div class="tm-val" style="font-weight:600">${escapeHtml(item.title || '(no title)')}</div>
        </div>
        ${item.channel ? `<div class="tm-row"><div class="tm-label">Channel / Creator</div><div class="tm-val">${escapeHtml(item.channel)}</div></div>` : ''}
        ${stats.length ? `<div class="tm-stats">${stats.join('')}</div>` : ''}
        <div class="tm-row">
            <div class="tm-label">Hashtags ${item.hashtags?.length ? `(${item.hashtags.length})` : ''}</div>
            <div class="tm-tags">${tagsHtml}</div>
        </div>
        ${description ? `
        <div class="tm-row">
            <div class="tm-label">${item.description ? 'Description' : 'Caption'}</div>
            <div class="tm-val" style="max-height:160px;overflow:auto;font-size:12px;padding:8px;background:var(--surface);border-radius:6px">${escapeHtml(description)}</div>
        </div>` : ''}
        ${item.url ? `
        <div class="tm-row">
            <a class="tm-link" href="${escapeAttr(item.url)}" target="_blank" rel="noopener">
                <i class="bi bi-box-arrow-up-right"></i> Open original on ${scope === 0 ? 'YouTube' : 'Instagram'}
            </a>
        </div>` : ''}
    `;

    document.getElementById('trendModalBackdrop').classList.add('open');
    document.body.style.overflow = 'hidden';
}

function closeTrendModal() {
    document.getElementById('trendModalBackdrop').classList.remove('open');
    document.body.style.overflow = '';
}
function closeTrendModalOnBackdrop(e) {
    if (e.target === document.getElementById('trendModalBackdrop')) closeTrendModal();
}

// ─── STEP 2B: confirm reference → create Post row ───
async function confirmTrendSelection() {
    const item = state.pendingRef;
    if (!item) return;

    const btn = document.getElementById('tmConfirmBtn');
    btn.disabled = true; btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Starting…';

    try {
        const res = await fetch(ROUTES.start, {
            method:'POST',
            headers: { 'Content-Type':'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept':'application/json' },
            body: JSON.stringify({
                client_id:         document.getElementById('clientSel').value,
                scope:             document.getElementById('scopeSel').value,
                post_type:         document.getElementById('typeSel').value,
                keyword:           document.getElementById('keywordInput').value.trim(),
                trending_ref_id:   String(item.ref_id),
                trending_ref_meta: item,
                scheduled_date:    state.scheduledDate,
                client_scope_id:   state.clientScopeId,
            }),
        });
        const json = await res.json();
        if (!json.success) { alert(json.error || json.message || 'Failed to start post.'); return; }

        state.selectedRef = item;
        state.postId      = json.post_id;
        state.attempts    = [];

        // Mark selected card visually
        document.querySelectorAll('.trend-card').forEach(c => c.classList.remove('selected'));
        const card = document.querySelector(`.trend-card[data-idx="${(window._trendItems || []).indexOf(item)}"]`);
        if (card) card.classList.add('selected');

        closeTrendModal();

        // Reveal step 3
        document.getElementById('step3').classList.remove('hidden');
        document.getElementById('step3').scrollIntoView({ behavior:'smooth', block:'start' });
        updateStep3Header();
        applyUploadAccept();
        // Kick off AI caption/hashtag suggestions in the background — they'll
        // be ready by the time the user fills the inputs.
        loadV1Suggestions();
    } catch (e) {
        alert(e.message);
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-check2"></i> Use as Comparison Reference';
    }
}

// Escape key closes modals
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') closeTrendModal();
});

// ─── STEP 3: upload ───
// What media kind does the current post type accept?
function expectedKind() {
    switch (document.getElementById('typeSel').value) {
        case 'photo':       return 'image';
        case 'story':       return 'both';   // IG stories allow image or video
        default:            return 'video';  // reels, long_video, short_video
    }
}

// Restrict the file picker + hint to the right media kind for this post type.
function applyUploadAccept() {
    const kind = expectedKind();
    const input = document.getElementById('fileInput');
    const sub = document.getElementById('uploadSub');
    if (kind === 'image') {
        input.setAttribute('accept', 'image/*');
        sub.textContent = 'jpg/png/webp only (this is a photo post) · max 200MB';
    } else if (kind === 'video') {
        input.setAttribute('accept', 'video/*');
        sub.textContent = 'mp4/mov/webm only (this is a video post) · max 200MB';
    } else {
        input.setAttribute('accept', 'image/*,video/*');
        sub.textContent = 'image or video (story) · max 200MB';
    }
}

function handleFile(e) {
    const f = e.target.files[0] || null;
    const box = document.getElementById('uploadBox');

    // Reject a file that doesn't match the post type
    if (f) {
        const kind = expectedKind();
        const isImg = (f.type || '').startsWith('image/');
        const isVid = (f.type || '').startsWith('video/');
        const ok = (kind === 'image' && isImg) || (kind === 'video' && isVid) || (kind === 'both' && (isImg || isVid));
        if (!ok) {
            const want = kind === 'image' ? 'an image (jpg/png/webp)' : (kind === 'video' ? 'a video (mp4/mov/webm)' : 'an image or video');
            alert(`This is a ${document.getElementById('typeSel').value} post — please upload ${want}. You selected: ${f.type || 'unknown'}.`);
            e.target.value = '';
            state.file = null;
            box.classList.remove('has-file');
            document.getElementById('submitBtn').disabled = true;
            return;
        }
    }

    state.file = f;
    if (state.file) {
        box.classList.add('has-file');
        document.getElementById('uploadLabel').textContent = state.file.name;
        document.getElementById('uploadSub').textContent =
            (state.file.size / 1024 / 1024).toFixed(1) + ' MB · ' + (state.file.type || 'unknown');
        document.getElementById('submitBtn').disabled = false;
    } else {
        box.classList.remove('has-file');
        document.getElementById('submitBtn').disabled = true;
    }
}

async function submitUpload() {
    if (!state.postId || !state.file) return;

    const btn = document.getElementById('submitBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Analyzing…';

    const fd = new FormData();
    fd.append('post_id',  state.postId);
    fd.append('file',     state.file);
    fd.append('caption',  document.getElementById('captionInput').value);
    fd.append('hashtags', document.getElementById('hashtagsInput').value);
    fd.append('_token',   CSRF);

    try {
        const res = await fetch(ROUTES.upload, {
            method:'POST',
            headers: { 'Accept':'application/json', 'X-CSRF-TOKEN': CSRF },
            body: fd,
        });

        // Surface non-2xx errors clearly
        if (!res.ok) {
            let errMsg = `Upload failed (HTTP ${res.status})`;
            try {
                const j = await res.json();
                if (j.errors) {
                    errMsg = Object.values(j.errors).flat().join('\n');
                } else if (j.message) {
                    errMsg = j.message;
                } else if (j.error) {
                    errMsg = j.error;
                }
            } catch {}
            if (res.status === 413) errMsg = 'File too big for the server. Raise upload_max_filesize in php.ini and restart Apache.';
            alert(errMsg);
            return;
        }

        const json = await res.json();

        // Server returned 200 but flagged failure
        if (json.success === false && !json.feedback) {
            alert(json.error || 'Scoring failed.');
            return;
        }

        state.attempts    = json.all_attempts || [];
        state.finalStatus = json.final_status;

        renderResult(json);
    } catch (e) {
        alert('Network/JS error: ' + e.message);
    } finally {
        btn.disabled = (state.finalStatus === 'approved' || state.finalStatus === 'max_attempts');
        btn.innerHTML = '<i class="bi bi-stars"></i> <span id="submitBtnLabel">Re-upload (Try Again)</span>';
        updateStep3Header();
        // reset file for next attempt
        document.getElementById('fileInput').value = '';
        document.getElementById('uploadBox').classList.remove('has-file');
        document.getElementById('uploadLabel').textContent = 'Click to upload your improved version';
        document.getElementById('uploadSub').textContent = 'Re-upload to try again';
        state.file = null;
    }
}

function updateStep3Header() {
    const used = state.attempts.length;
    const left = 3 - used;
    const titleEl = document.getElementById('step3Title');
    const subEl   = document.getElementById('step3Sub');

    if (state.finalStatus === 'approved') {
        titleEl.textContent = 'Approved!';
        subEl.textContent   = 'You hit the approval threshold. Save the post from the panel below, then schedule it from the calendar.';
        document.getElementById('submitBtn').disabled = true;
    } else if (state.finalStatus === 'max_attempts') {
        titleEl.textContent = '3 attempts used';
        subEl.textContent   = 'No more re-uploads allowed for this post. Start a new one.';
        document.getElementById('submitBtn').disabled = true;
    } else if (used === 0) {
        titleEl.textContent = 'Upload your version';
        subEl.textContent   = 'Upload your file → AI will compare with the selected trending post';
    } else {
        titleEl.textContent = `Attempt ${used + 1} of 3`;
        subEl.textContent   = `${left} ${left === 1 ? 'attempt' : 'attempts'} remaining. Apply the suggestions and re-upload.`;
    }
}

// ─── STEP 4: render result ───
function renderResult(j) {
    document.getElementById('step4').classList.remove('hidden');

    const fb = j.feedback || {};
    const score = j.score || 0;
    const grade = fb.grade || gradeOf(score);
    const summary = fb.summary || (j.error || '');

    document.getElementById('resultSub').textContent =
        `Attempt ${j.attempt_number} of 3 · Best score: ${j.best_score}/100`;

    // approved / max banners
    document.getElementById('approvedBanner').classList.add('hidden');
    document.getElementById('maxBanner').classList.add('hidden');

    // Score gate passed but spelling gate failed → user must re-upload
    if (!j.approved && j.score_gate_pass === true && j.spell_gate_pass === false && j.final_status === 'in_progress') {
        const errs = j.spell_errors_count || 0;
        document.getElementById('approvedBanner').classList.remove('hidden');
        document.getElementById('approvedBanner').innerHTML = `
            <div class="max-banner" style="background:linear-gradient(135deg,#fef3c7,#fde68a)">
                <strong><i class="bi bi-exclamation-triangle-fill"></i> Almost there — spelling blocks publish</strong><br>
                Score <strong>${j.score}/100 ✅</strong> passes the threshold, but <strong>${errs} spelling error${errs > 1 ? 's' : ''}</strong> were detected.
                Fix the caption (see auto-fixed version on the Spelling parameter card) and re-upload V${j.attempt_number + 1} — Publish will unlock once spelling is clean.
            </div>`;
    }

    if (j.approved) {
        const platform = parseInt(document.getElementById('scopeSel').value) === 0 ? 'YouTube' : 'Instagram';
        document.getElementById('approvedBanner').classList.remove('hidden');
        document.getElementById('approvedBanner').innerHTML = `
            <div class="approved-banner">
                <div style="flex:1">
                    <h6><i class="bi bi-check-circle-fill"></i> Approved — Score ${j.best_score}/100</h6>
                    <p>Great work! Threshold passed. Save this post — it'll appear on the calendar where you can set a date & time to auto-publish.</p>
                </div>
                <div style="display:flex;gap:8px;flex-shrink:0;flex-wrap:wrap">
                    <button class="download-btn" id="savePostBtn" onclick="savePost(${j.post_id})" style="background:var(--accent);color:#fff">
                        <i class="bi bi-calendar-check"></i> Save Post
                    </button>
                    <button class="download-btn" id="publishBtn" onclick="publishPost(${j.post_id})" style="background:#16a34a;color:#fff">
                        <i class="bi bi-rocket-takeoff"></i> Publish Now
                    </button>
                </div>
            </div>
            <div id="publishResult" style="margin-top:10px"></div>`;
    } else if (j.final_status === 'max_attempts') {
        // Figure out why max_attempts (score, spelling, or both)
        const bestAttempt = (j.all_attempts || []).reduce((b, a) => a.score > (b?.score ?? -1) ? a : b, null);
        const bestSpell   = bestAttempt?.spell_errors_count ?? 0;
        const scoreFailed = j.best_score < 60;
        const spellFailed = bestSpell > 0;

        let reasons = [];
        if (scoreFailed) reasons.push(`score ${j.best_score}/100 below 60`);
        if (spellFailed) reasons.push(`${bestSpell} spelling error${bestSpell > 1 ? 's' : ''} on best attempt`);
        const reasonText = reasons.length ? reasons.join(' · ') : 'one or both gates not met';

        document.getElementById('maxBanner').classList.remove('hidden');
        document.getElementById('maxBanner').innerHTML = `
            <div class="max-banner">
                <strong>3 attempts used — cannot publish.</strong><br>
                Reason: ${reasonText}.<br>
                Both <strong>score ≥ 60</strong> AND <strong>zero spelling errors</strong> required. Start a new post to try again.
            </div>`;
    }

    // Unified triple comparison: Reference | V1 | V2 | V3 with deltas + spell gate.
    // When the comparison was skipped (standalone score) there's no reference —
    // pass a placeholder so the V1/V2/V3 columns still render.
    const refForCompare = state.selectedRef || { title:'Standalone — no trending comparison', channel:'AI Studio post', hashtags:[], thumbnail:null };
    renderTripleCompare(refForCompare, j.all_attempts || []);

    // current score hero
    document.getElementById('scoreHero').innerHTML = `
        <div class="score-hero">
            <div class="score-ring ${ringClass(score)}" style="--ring-pct:${score}">
                <div class="num">${score}</div>
            </div>
            <div class="score-summary">
                <h6>This attempt: ${score}/100
                    <span class="grade-badge ${gradeClass(score)}">${grade}</span>
                </h6>
                <p>${escapeHtml(summary)}</p>
            </div>
        </div>`;

    // params grid — now shows reference vs your_post per parameter
    const params = fb.parameters || {};
    document.getElementById('paramGrid').innerHTML = Object.keys(params).map(k => {
        const p = params[k];
        const pct = Math.round((p.score / p.max) * 100);
        const refSays = p.reference_says || '';
        const youSays = p.your_post_says || '';
        const hasCompare = refSays || youSays;
        const compareBlock = hasCompare ? `
            <div class="p-compare">
                <div class="side ref">
                    <span class="lbl"><i class="bi bi-star-fill"></i> Reference</span>
                    <span class="body">${escapeHtml(refSays || '—')}</span>
                </div>
                <div class="side you">
                    <span class="lbl"><i class="bi bi-person-fill"></i> Your Post</span>
                    <span class="body">${escapeHtml(youSays || '—')}</span>
                </div>
            </div>` : '';
        const sugList = Array.isArray(p.suggestions) && p.suggestions.length
            ? `<ul style="margin:6px 0 0;padding-left:16px;font-size:11px;color:var(--ink);line-height:1.5">
                  ${p.suggestions.slice(0,3).map(s => `<li>${escapeHtml(s)}</li>`).join('')}
               </ul>` : '';

        // Spelling/Grammar special blocks: errors list + auto-fix copy box
        let errorsBlock = '';
        if (Array.isArray(p.errors) && p.errors.length) {
            errorsBlock = `
              <div class="spell-errors">
                  <div class="se-head"><i class="bi bi-exclamation-triangle-fill"></i> ${p.errors.length} error${p.errors.length>1?'s':''} detected</div>
                  <ul>${p.errors.slice(0,8).map(e => `<li>${escapeHtml(e)}</li>`).join('')}</ul>
              </div>`;
        }
        let autoFixBlock = '';
        if (p.auto_fix && String(p.auto_fix).trim()) {
            const txt = String(p.auto_fix);
            autoFixBlock = `
              <div class="auto-fix-box">
                  <div class="af-head">
                      <span><i class="bi bi-magic"></i> Auto-fixed version</span>
                      <button class="copy-btn-sm" data-copy="${escapeAttr(txt)}">
                          <i class="bi bi-clipboard"></i> Copy
                      </button>
                  </div>
                  <div class="af-text">${escapeHtml(txt)}</div>
              </div>`;
        }

        return `
        <div class="param-card">
            <div class="p-head">
                <span class="p-label">${escapeHtml(p.label)}</span>
                <span class="p-score ${pct >= 60 ? 'good' : (pct >= 45 ? 'mid' : 'bad')}">${p.score}/${p.max}</span>
            </div>
            <div class="p-bar"><div class="fill" style="width:${pct}%;background:${pct >= 60 ? 'var(--good)' : (pct >= 45 ? 'var(--warn)' : 'var(--bad)')}"></div></div>
            ${compareBlock}
            <div class="p-feedback"><strong>Feedback:</strong> ${escapeHtml(p.feedback || '')}</div>
            ${errorsBlock}
            ${autoFixBlock}
            ${sugList}
        </div>`;
    }).join('');

    // Unified Recommendations section: quick wins + 3 hooks + best time + 5 ideas
    // (caption_variants and suggested_hashtags now go into the Step 3 dropdowns instead)
    renderRecommendations(fb.recommendations || {}, j.suggestions || []);

    // Merge this attempt's AI variants into the Step 3 caption + hashtag dropdowns,
    // so the next attempt's inputs have richer suggestions inline.
    appendAttemptSuggestionsToDropdowns(fb.recommendations || {}, j.attempt_number);

    // Quick wins are merged into the Recommendations panel — clear the old separate wrap
    document.getElementById('suggestionsWrap').innerHTML = '';

    document.getElementById('step4').scrollIntoView({ behavior:'smooth', block:'start' });
}

function renderRecommendations(recs, quickWins = []) {
    const wrap = document.getElementById('recommendationsWrap');
    if (!wrap) return;
    const hasRecs = recs && Object.keys(recs).length > 0;
    if (!hasRecs && !quickWins.length) { wrap.innerHTML = ''; return; }

    recs = recs || {};
    const hooks    = recs.alternative_hooks   || [];
    const captions = recs.caption_variants    || [];
    const tags     = recs.suggested_hashtags  || null;
    const time     = recs.best_time_to_post   || '';
    const ideas    = recs.new_reel_ideas      || [];

    // ── Section: Quick Wins (merged from old separate yellow box) ──
    const qwCard = quickWins.length ? `
      <div class="rec-card" style="grid-column:1/-1; background:#fef3c7; border-color:#fcd34d">
        <div class="r-head" style="display:flex;justify-content:space-between;align-items:center">
          <span><i class="bi bi-lightning-charge-fill" style="color:#f59e0b"></i> Top Suggestions to Improve (${quickWins.length})</span>
          <button class="copy-section-btn" data-copy-list='${escapeAttr(JSON.stringify(quickWins))}' data-copy-label="suggestions">
            <i class="bi bi-clipboard"></i> Copy all
          </button>
        </div>
        <ul class="suggest-list" style="margin-top:8px">
          ${quickWins.slice(0,8).map(s => `
            <li style="display:flex;justify-content:space-between;align-items:flex-start;gap:8px">
              <span style="flex:1">${escapeHtml(s)}</span>
              <button class="copy-inline-btn" data-copy="${escapeAttr(s)}" title="Copy">
                <i class="bi bi-clipboard"></i>
              </button>
            </li>`).join('')}
        </ul>
      </div>` : '';

    // ── Section: Hooks ──
    const hookCard = hooks.length ? `
      <div class="rec-card">
        <div class="r-head" style="display:flex;justify-content:space-between;align-items:center">
          <span><i class="bi bi-fire"></i> Alternative Hooks (${hooks.length})</span>
          <button class="copy-section-btn" data-copy-list='${escapeAttr(JSON.stringify(hooks.map(h => h.text || '')))}' data-copy-label="hooks">
            <i class="bi bi-clipboard"></i> Copy all
          </button>
        </div>
        ${hooks.map(h => `
          <div class="hook-rec" data-copy="${escapeAttr(h.text || '')}">
            <span class="h-badge">${escapeHtml(h.type || 'idea')}</span>
            <span style="flex:1">${escapeHtml(h.text || '')}</span>
            <i class="bi bi-clipboard" style="color:var(--muted);font-size:11px"></i>
          </div>`).join('')}
      </div>` : '';

    // ── Section: Caption Variants ──
    const capCard = captions.length ? `
      <div class="rec-card">
        <div class="r-head" style="display:flex;justify-content:space-between;align-items:center">
          <span><i class="bi bi-chat-text"></i> Caption Variants (${captions.length})</span>
          <button class="copy-section-btn" data-copy-list='${escapeAttr(JSON.stringify(captions))}' data-copy-label="captions">
            <i class="bi bi-clipboard"></i> Copy all
          </button>
        </div>
        ${captions.map(c => `
          <div class="cap-rec" data-copy="${escapeAttr(c)}">
            <button class="c-copy">
              <i class="bi bi-clipboard"></i>
            </button>
            ${escapeHtml(c)}
          </div>`).join('')}
      </div>` : '';

    // ── Section: Hashtag Mix ──
    let tagCard = '';
    if (tags) {
        const renderSet = (arr, cls) => (arr || []).map(t =>
            `<span class="chip ${cls}" data-copy="${escapeAttr(t)}">${escapeHtml(t)}</span>`
        ).join('');
        const totalTags = (tags.trending?.length||0) + (tags.niche?.length||0) + (tags.brand?.length||0);
        const allTags = [...(tags.trending||[]), ...(tags.niche||[]), ...(tags.brand||[])].join(' ');
        tagCard = `
          <div class="rec-card">
            <div class="r-head" style="display:flex;justify-content:space-between;align-items:center">
              <span><i class="bi bi-hash"></i> Hashtag Mix (${totalTags})</span>
              <button class="copy-section-btn" data-copy="${escapeAttr(allTags)}">
                <i class="bi bi-clipboard"></i> Copy all
              </button>
            </div>
            ${tags.trending?.length ? `<div style="font-size:10px;color:var(--muted);margin-top:6px;font-weight:600">TRENDING</div><div class="htag-mix">${renderSet(tags.trending,'trending')}</div>` : ''}
            ${tags.niche?.length    ? `<div style="font-size:10px;color:var(--muted);margin-top:8px;font-weight:600">NICHE</div><div class="htag-mix">${renderSet(tags.niche,'niche')}</div>` : ''}
            ${tags.brand?.length    ? `<div style="font-size:10px;color:var(--muted);margin-top:8px;font-weight:600">BRAND</div><div class="htag-mix">${renderSet(tags.brand,'brand')}</div>` : ''}
          </div>`;
    }

    // ── Section: Best Time ──
    const timeCard = time ? `
      <div class="rec-card">
        <div class="r-head" style="display:flex;justify-content:space-between;align-items:center">
          <span><i class="bi bi-clock"></i> Best Time to Post</span>
          <button class="copy-section-btn" data-copy="${escapeAttr(time)}">
            <i class="bi bi-clipboard"></i> Copy
          </button>
        </div>
        <div class="time-box"><div class="t-time">${escapeHtml(time)}</div></div>
      </div>` : '';

    // ── Section: 5 New Reel Ideas (with per-idea copy) ──
    const ideasCard = ideas.length ? `
      <div class="rec-card" style="grid-column:1/-1">
        <div class="r-head" style="display:flex;justify-content:space-between;align-items:center">
          <span><i class="bi bi-stars"></i> ${ideas.length} New Reel Ideas</span>
          <button class="copy-section-btn" data-copy-ideas='${escapeAttr(JSON.stringify(ideas))}'>
            <i class="bi bi-clipboard"></i> Copy all
          </button>
        </div>
        ${ideas.slice(0,5).map((i, idx) => {
          const ideaText = `${i.title || ''}\n${i.angle || ''}\n${i.script_outline || ''}`.trim();
          return `
          <div class="idea-row-rec" style="position:relative">
            <button class="copy-inline-btn" style="position:absolute;top:8px;right:8px" data-copy="${escapeAttr(ideaText)}" title="Copy this idea">
              <i class="bi bi-clipboard"></i>
            </button>
            <div class="ir-title">${idx+1}. ${escapeHtml(i.title || '(no title)')}</div>
            ${i.angle ? `<div class="ir-angle">${escapeHtml(i.angle)}</div>` : ''}
            ${i.script_outline ? `<div class="ir-script"><strong>Script:</strong> ${escapeHtml(i.script_outline)}</div>` : ''}
            ${i.estimated_views ? `<span class="ir-views">${escapeHtml(i.estimated_views)}</span>` : ''}
          </div>`;
        }).join('')}
      </div>` : '';

    // Caption Variants + Hashtag Mix are now surfaced as dropdowns under each
    // Step-3 input via appendAttemptSuggestionsToDropdowns() — not repeated here.
    wrap.innerHTML = `
      <div class="recs-wrap">
        <h6><i class="bi bi-lightbulb-fill" style="color:var(--accent)"></i> AI Suggestions & Recommendations</h6>
        <div class="recs-grid">
          ${qwCard}
          ${hookCard}
          ${timeCard}
          ${ideasCard}
        </div>
      </div>`;
}

async function savePost(postId) {
    const btn    = document.getElementById('savePostBtn');
    const result = document.getElementById('publishResult');
    if (!btn || !result) return;

    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Saving…';

    try {
        const res = await fetch(ROUTES.save(postId), {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
            body: JSON.stringify({ _token: CSRF }),
        });
        const json = await res.json();

        if (!json.success) {
            result.innerHTML = `<div class="publish-fail" style="background:#fef2f2;border:1px solid #fecaca;border-radius:8px;padding:10px;color:#991b1b">
                <strong><i class="bi bi-x-circle"></i> Could not save:</strong> ${escapeHtml(json.error || 'Unknown error')}</div>`;
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-calendar-check"></i> Save Post';
            return;
        }

        const dateLine = json.scheduled_date
            ? `It's on the calendar for <strong>${escapeHtml(json.scheduled_date)}</strong>.`
            : `It's saved to the calendar.`;

        result.innerHTML = `
            <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:14px;color:#166534">
                <strong><i class="bi bi-check-circle-fill"></i> Post saved!</strong><br>
                ${dateLine} Open the calendar, click this slot, and set a date & time to auto-publish.
                <div style="margin-top:10px">
                    <a href="${json.calendar_url}" style="display:inline-flex;align-items:center;gap:6px;background:#16a34a;color:#fff;text-decoration:none;font-size:13px;font-weight:600;padding:9px 16px;border-radius:8px">
                        <i class="bi bi-calendar3"></i> Go to Calendar
                    </a>
                </div>
            </div>`;
        btn.innerHTML = '<i class="bi bi-check2"></i> Saved';
    } catch (e) {
        result.innerHTML = `<div style="background:#fef2f2;border:1px solid #fecaca;border-radius:8px;padding:10px;color:#991b1b"><i class="bi bi-wifi-off"></i> Network error. Please try again.</div>`;
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-calendar-check"></i> Save Post';
    }
}

async function publishPost(postId) {
    const btn    = document.getElementById('publishBtn');
    const result = document.getElementById('publishResult');
    if (!btn || !result) return;

    if (!confirm('Publish this post live now? This will send it to the platform.')) return;

    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Publishing…';
    result.innerHTML = '';

    try {
        const res = await fetch(ROUTES.publish(postId), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': CSRF,
                'Accept': 'application/json',
            },
        });
        const json = await res.json();

        if (!res.ok || !json.success) {
            result.innerHTML = `
              <div style="background:#fee2e2;color:#991b1b;padding:10px 14px;border-radius:8px;font-size:13px">
                <strong><i class="bi bi-x-circle"></i> Publish failed:</strong> ${escapeHtml(json.error || 'Unknown error')}
              </div>`;
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-rocket-takeoff"></i> Retry Publish';
            return;
        }

        // Reusable calendar info chip
        const calChip = (cal) => cal ? `
            <div style="margin-top:8px;padding:8px 12px;background:#eef2ff;border-left:3px solid #6366f1;border-radius:7px;font-size:12px;color:#3730a3">
                <i class="bi bi-calendar-check"></i>
                Calendar slot for <strong>${escapeHtml(cal.slot_date_human)}</strong>
                (${escapeHtml(cal.post_type)}) updated to <strong>${escapeHtml(cal.status)}</strong>.
                <a href="${escapeAttr(cal.view_url)}" style="color:#3730a3;text-decoration:underline;margin-left:6px">
                    <i class="bi bi-arrow-up-right-square"></i> View on Calendar
                </a>
            </div>` : '';

        // Reusable "what got published" chip — shows which attempt's data went out
        const attemptChip = (pa) => pa ? `
            <div style="margin-top:8px;padding:10px 12px;background:#f0fdf4;border-left:3px solid #16a34a;border-radius:7px;font-size:12px;color:#166534">
                <i class="bi bi-check2-square"></i>
                Published <strong>V${pa.number}</strong> data (score ${pa.score}/100)
                <div style="margin-top:5px;font-size:11px;color:#14532d">
                    <strong>Caption:</strong> <em>${escapeHtml(pa.caption_preview || '(empty)')}${pa.caption_preview && pa.caption_preview.length >= 140 ? '…' : ''}</em>
                </div>
                ${pa.hashtags ? `<div style="font-size:11px;color:#14532d;margin-top:3px"><strong>Hashtags:</strong> ${escapeHtml(pa.hashtags)}</div>` : ''}
            </div>` : '';

        if (json.dry_run) {
            result.innerHTML = `
              <div style="background:#fef3c7;color:#92400e;padding:10px 14px;border-radius:8px;font-size:13px">
                <strong><i class="bi bi-flag"></i> Dry run:</strong> ${escapeHtml(json.message || '')}<br>
                <small>Add platform tokens in .env or on the client record to publish for real.</small>
              </div>
              ${attemptChip(json.published_attempt)}
              ${calChip(json.calendar)}`;
            btn.innerHTML = '<i class="bi bi-check2"></i> Dry-run complete';
            return;
        }

        // Real publish success
        result.innerHTML = `
          <div style="background:#dcfce7;color:#166534;padding:12px 14px;border-radius:8px;font-size:13px">
            <strong><i class="bi bi-check-circle-fill"></i> Published successfully!</strong>
            ${json.external_url ? `<br><a href="${json.external_url}" target="_blank" rel="noopener" style="color:#166534;text-decoration:underline">→ View live post</a>` : ''}
            ${json.external_post_id ? `<br><small>Platform post ID: ${escapeHtml(json.external_post_id)}</small>` : ''}
          </div>
          ${attemptChip(json.published_attempt)}
          ${calChip(json.calendar)}`;
        btn.innerHTML = '<i class="bi bi-check2"></i> Published';
    } catch (e) {
        result.innerHTML = `
          <div style="background:#fee2e2;color:#991b1b;padding:10px 14px;border-radius:8px;font-size:13px">
            Network/JS error: ${escapeHtml(e.message)}
          </div>`;
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-rocket-takeoff"></i> Retry Publish';
    }
}

// ─── Unified copy handler — works for ALL copy buttons regardless of content ───
//
// HTML pattern: any element with data-copy="..." gets clicked and copies that text.
//   data-copy="single text"           → copies that string
//   data-copy-list='["a","b"]'        → copies "1. a\n2. b" (numbered list)
//   data-copy-ideas='[{title,...}]'   → copies formatted reel ideas
//
// Using data-attributes avoids ALL onclick string-escaping issues with apostrophes,
// quotes, newlines, etc.
document.addEventListener('click', function (e) {
    const el = e.target.closest('[data-copy], [data-copy-list], [data-copy-ideas]');
    if (!el) return;

    let text = '';
    let msg  = 'Copied';

    if (el.dataset.copy !== undefined) {
        text = el.dataset.copy;
        msg  = 'Copied';
    } else if (el.dataset.copyList !== undefined) {
        try {
            const items = JSON.parse(el.dataset.copyList);
            const label = el.dataset.copyLabel || 'items';
            text = items.map((s, i) => `${i+1}. ${s}`).join('\n');
            msg  = `${items.length} ${label} copied`;
        } catch { return; }
    } else if (el.dataset.copyIdeas !== undefined) {
        try {
            const ideas = JSON.parse(el.dataset.copyIdeas);
            text = ideas.map((i, idx) =>
                `${idx+1}. ${i.title || ''}\n   Angle: ${i.angle || ''}\n   Script: ${i.script_outline || ''}\n   Views: ${i.estimated_views || ''}`
            ).join('\n\n');
            msg  = `${ideas.length} ideas copied`;
        } catch { return; }
    }

    if (!text) return;

    // Stop propagation so parent containers with their own data-copy don't also fire
    e.stopPropagation();

    // Try clipboard API first, fall back to textarea-select for older browsers / non-HTTPS
    copyToClipboard(text).then(() => flashCopied(el, msg));
});

function copyToClipboard(text) {
    if (navigator.clipboard && window.isSecureContext) {
        return navigator.clipboard.writeText(text);
    }
    // Fallback: invisible textarea + execCommand
    return new Promise((resolve, reject) => {
        const ta = document.createElement('textarea');
        ta.value = text;
        ta.style.position = 'fixed';
        ta.style.opacity = '0';
        document.body.appendChild(ta);
        ta.select();
        try { document.execCommand('copy'); resolve(); }
        catch (e) { reject(e); }
        finally { document.body.removeChild(ta); }
    });
}

function flashCopied(btn, msg) {
    if (!btn) return;
    const orig = btn.innerHTML;
    // For chips/cards, change the icon if present; for buttons, replace whole content
    const isButton = btn.tagName === 'BUTTON';
    const target = isButton ? btn : (btn.querySelector('.bi-clipboard') || btn);
    const targetOrig = target.innerHTML;
    target.innerHTML = isButton ? `<i class="bi bi-check2"></i> ${msg}` : '<i class="bi bi-check2"></i>';
    if (isButton) {
        btn.style.background = 'var(--good)';
        btn.style.color = '#fff';
    }
    setTimeout(() => {
        target.innerHTML = targetOrig;
        if (isButton) {
            btn.style.background = '';
            btn.style.color = '';
        }
    }, 1800);
}

function renderTripleCompare(ref, attempts) {
    const wrap = document.getElementById('compareWrap');
    if (!wrap || !ref) return;

    // Sort attempts asc, find best score
    const sorted = [...attempts].sort((a,b) => a.attempt_number - b.attempt_number);
    const bestScore = sorted.length ? Math.max(...sorted.map(a => a.score)) : 0;
    const v1Score   = sorted.find(a => a.attempt_number === 1)?.score ?? null;

    // ── Reference column ──
    const refThumb = ref.thumbnail
        ? `<div class="tc-preview" style="background-image:url('${escapeAttr(ref.thumbnail)}')"></div>`
        : `<div class="tc-preview tc-empty-thumb">(no thumbnail)</div>`;

    const refTags = (ref.hashtags || []).slice(0, 6).map(t =>
        `<span class="chip ref">${escapeHtml(t)}</span>`).join('');

    const refStats = [];
    if (ref.views != null && ref.views > 0)  refStats.push(`<i class="bi bi-eye"></i> ${fmtNum(ref.views)}`);
    if (ref.views_per_day)                   refStats.push(`<i class="bi bi-fire"></i> ${fmtNum(ref.views_per_day)}/day`);

    const refCol = `
      <div class="tc-col ref">
        <div class="tc-head"><i class="bi bi-star-fill"></i> Reference</div>
        ${refThumb}
        <div class="tc-title">${escapeHtml(ref.title || '(untitled)')}</div>
        ${ref.channel ? `<div class="tc-sub">${escapeHtml(ref.channel)}</div>` : ''}
        <div class="tc-tags">${refTags || '<span class="tc-mini-muted">no hashtags</span>'}</div>
        ${refStats.length ? `<div class="tc-stats">${refStats.join(' · ')}</div>` : ''}
      </div>`;

    // ── V1/V2/V3 columns ──
    const userCols = [1, 2, 3].map(n => {
        const a = sorted.find(x => x.attempt_number === n);

        if (!a) {
            return `
              <div class="tc-col v-empty">
                <div class="tc-head">V${n}</div>
                <div class="tc-preview tc-empty-thumb">— not yet —</div>
                <div class="tc-score-big" style="color:#cbd5e1">—</div>
                <div class="tc-sub" style="text-align:center;font-style:italic">awaiting upload</div>
              </div>`;
        }

        const cls    = a.score >= 60 ? 'good' : (a.score >= 45 ? 'mid' : 'bad');
        const isWin  = a.score === bestScore && a.score > 0;
        const isImg  = (a.mime || '').startsWith('image/');
        const preview = isImg
            ? `<img class="tc-preview" src="${a.file_url}" alt="V${n}">`
            : `<video class="tc-preview" src="${a.file_url}" muted controls></video>`;

        // Delta vs previous attempt
        const prev = sorted.find(x => x.attempt_number === n - 1);
        let deltaChip = '';
        if (prev && a.score !== prev.score) {
            const diff   = a.score - prev.score;
            const dCls   = diff > 0 ? 'delta-pos' : 'delta-neg';
            const dArrow = diff > 0 ? '↑' : '↓';
            deltaChip = `<span class="tc-delta ${dCls}">${dArrow} ${diff > 0 ? '+' : ''}${diff} vs V${n - 1}</span>`;
        }

        // % improvement over V1 (skip V1 itself)
        let v1Chip = '';
        if (n > 1 && v1Score && v1Score > 0) {
            const pct = Math.round(((a.score - v1Score) / v1Score) * 100);
            v1Chip = `<span class="tc-delta ${pct >= 0 ? 'delta-pos' : 'delta-neg'}">${pct >= 0 ? '+' : ''}${pct}% over V1</span>`;
        }

        // Spelling gate badge
        const errCount = a.spell_errors_count ?? 0;
        const spellBadge = errCount === 0
            ? `<div class="tc-spell pass"><i class="bi bi-check-circle-fill"></i> Spelling clean</div>`
            : `<div class="tc-spell fail"><i class="bi bi-x-circle-fill"></i> ${errCount} spell error${errCount > 1 ? 's' : ''}</div>`;

        // Top issues
        const topIssues = Array.isArray(a.top_issues) && a.top_issues.length
            ? `<ul class="tc-issues">${a.top_issues.slice(0,2).map(s => `<li>${escapeHtml(s)}</li>`).join('')}</ul>`
            : '';

        const winnerBadge = isWin
            ? `<div class="tc-winner"><i class="bi bi-trophy-fill"></i> Best</div>`
            : '';

        return `
          <div class="tc-col v ${cls}">
            ${winnerBadge}
            <div class="tc-head">V${n}</div>
            ${preview}
            <div class="tc-score-big ${cls}">${a.score}<span style="font-size:14px;color:var(--muted)">/100</span></div>
            <div class="tc-deltas">${deltaChip}${v1Chip}</div>
            ${spellBadge}
            ${topIssues}
          </div>`;
    }).join('');

    // Auto-publish readiness banner
    const winner = sorted.find(a => a.score === bestScore && a.score > 0);
    let publishGate = '';
    if (winner) {
        const okScore = winner.score >= 60;
        const okSpell = (winner.spell_errors_count ?? 0) === 0;
        if (okScore && okSpell) {
            publishGate = `
              <div class="tc-gate ok">
                <i class="bi bi-check-circle-fill"></i>
                <span><strong>Auto-publish ready:</strong> V${winner.attempt_number} hit ${winner.score}/100 with 0 spelling errors.</span>
              </div>`;
        } else if (sorted.length >= 3 || (winner && winner.score >= 60 && !okSpell)) {
            const reasons = [];
            if (!okScore) reasons.push(`score ${winner.score} < 60`);
            if (!okSpell) reasons.push(`${winner.spell_errors_count} spelling error${winner.spell_errors_count > 1 ? 's' : ''}`);
            publishGate = `
              <div class="tc-gate fail">
                <i class="bi bi-exclamation-circle-fill"></i>
                <span><strong>Not auto-publish ready:</strong> ${reasons.join(' · ')}</span>
              </div>`;
        }
    }

    wrap.innerHTML = `
      <h6 style="font-size:13px;font-weight:600;color:var(--ink);margin:0 0 8px">
        <i class="bi bi-bar-chart-line-fill"></i> Triple Comparison — Reference vs V1 vs V2 vs V3
      </h6>
      ${publishGate}
      <div class="triple-compare">
        ${refCol}
        ${userCols}
      </div>
    `;
}

// ─── helpers ───
function ringClass(s) { return s >= 60 ? 'good' : (s >= 45 ? 'mid' : 'bad'); }
function gradeClass(s) { return s >= 60 ? 'grade-good' : (s >= 45 ? 'grade-mid' : 'grade-bad'); }
function gradeOf(s) {
    if (s >= 90) return 'A+'; if (s >= 80) return 'A';
    if (s >= 70) return 'B+'; if (s >= 60) return 'B';
    if (s >= 50) return 'C';  if (s >= 40) return 'D';
    return 'F';
}
function fmtNum(n) {
    if (n >= 1_000_000) return (n/1_000_000).toFixed(1) + 'M';
    if (n >= 1_000)     return (n/1_000).toFixed(1) + 'K';
    return String(n);
}
function escapeHtml(s) {
    return String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;')
        .replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;');
}
function escapeAttr(s) { return escapeHtml(s); }

// ── V1 AI caption + hashtag suggestions ─────────────────────────────────
// Auto-fired when Step 3 opens. Reuses the existing inspiration endpoint
// (text-only Gemini call). After each attempt's scoring runs, that attempt's
// AI variants are appended to the same dropdowns (so the bottom "AI
// Recommendations" cards for caption / hashtag don't need to repeat).
let v1SuggestionsLoaded = false;
let v1CaptionItems = [];
let v1HashtagItems = [];
function dedupeByText(items) {
    const seen = new Set();
    return items.filter(it => {
        const k = (it.text || '').trim();
        if (!k || seen.has(k)) return false;
        seen.add(k); return true;
    });
}
async function loadV1Suggestions() {
    if (v1SuggestionsLoaded) return;

    const scope    = document.getElementById('scopeSel').value;
    const postType = document.getElementById('typeSel').value;
    const keyword  = (document.getElementById('keywordInput').value || '').trim();
    const clientId = document.getElementById('clientSel')?.value || null;
    if (!scope || !postType || !keyword) return;

    const loadingEl = document.getElementById('captionSuggestLoading');
    if (loadingEl) loadingEl.style.display = '';

    try {
        const res = await fetch(ROUTES.inspiration, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
            body: JSON.stringify({ scope, post_type: postType, keyword, client_id: clientId }),
        });
        const json = await res.json();
        if (!json.success || !json.data) { v1SuggestionsLoaded = true; return; }
        const d = json.data;

        // Caption suggestions: sample_caption + each hook
        const captionItems = [];
        if (d.sample_caption && d.sample_caption.length > 5) {
            captionItems.push({ label: 'Sample caption', text: d.sample_caption });
        }
        (d.hooks || []).forEach((h, i) => {
            const text = (typeof h === 'string') ? h : (h.text || '');
            if (text && text.length > 5) {
                const type = (typeof h === 'object' && h.type) ? h.type : 'hook';
                captionItems.push({ label: `Hook ${i + 1} (${type})`, text });
            }
        });

        // Hashtag suggestions: each group + a combined "all"
        const hashtagItems = [];
        const groups = (d.hashtags && typeof d.hashtags === 'object') ? d.hashtags : {};
        const allFlat = [];
        for (const [name, tags] of Object.entries(groups)) {
            if (Array.isArray(tags) && tags.length) {
                hashtagItems.push({ label: name, text: tags.join(' ') });
                allFlat.push(...tags);
            }
        }
        if (allFlat.length) {
            hashtagItems.unshift({ label: 'All combined', text: allFlat.join(' ') });
        }

        // Merge (don't overwrite) so AI-handoff seeded variants survive
        v1CaptionItems = dedupeByText([...v1CaptionItems, ...captionItems]);
        v1HashtagItems = dedupeByText([...v1HashtagItems, ...hashtagItems]);
        renderV1Dropdown('captionSuggest', v1CaptionItems, 'captionInput');
        renderV1Dropdown('hashtagSuggest', v1HashtagItems, 'hashtagsInput');
        v1SuggestionsLoaded = true;
    } catch (e) {
        // silent fail — suggestions are optional
    } finally {
        if (loadingEl) loadingEl.style.display = 'none';
    }
}

// Merge a scored attempt's AI variants into the V1 dropdowns. Called from
// renderResult after each upload — keeps caption/hashtag suggestions in one
// place so the bottom Step 4 cards for these don't repeat.
function appendAttemptSuggestionsToDropdowns(recs, attemptNumber) {
    if (!recs || typeof recs !== 'object') return;

    // Caption variants
    (recs.caption_variants || []).forEach((c, i) => {
        if (typeof c === 'string' && c.trim().length > 5) {
            v1CaptionItems.push({ label: `V${attemptNumber} variant ${i + 1}`, text: c.trim() });
        }
    });

    // Hashtag mix: trending / niche / brand + combined
    const tags = recs.suggested_hashtags;
    if (tags && typeof tags === 'object') {
        const allFlat = [];
        for (const [name, arr] of Object.entries(tags)) {
            if (Array.isArray(arr) && arr.length) {
                v1HashtagItems.push({ label: `V${attemptNumber} ${name}`, text: arr.join(' ') });
                allFlat.push(...arr);
            }
        }
        if (allFlat.length) {
            v1HashtagItems.push({ label: `V${attemptNumber} all combined`, text: allFlat.join(' ') });
        }
    }

    v1CaptionItems = dedupeByText(v1CaptionItems);
    v1HashtagItems = dedupeByText(v1HashtagItems);
    renderV1Dropdown('captionSuggest', v1CaptionItems, 'captionInput');
    renderV1Dropdown('hashtagSuggest', v1HashtagItems, 'hashtagsInput');
}

function renderV1Dropdown(prefix, items, targetId) {
    const wrap  = document.getElementById(prefix + 'Wrap');
    const menu  = document.getElementById(prefix + 'Menu');
    const count = document.getElementById(prefix + 'Count');
    if (!wrap || !menu) return;
    if (!items.length) { wrap.style.display = 'none'; return; }

    count.textContent = items.length;
    menu.innerHTML = '<li><h6 class="dropdown-header small mb-0">Click any item to paste</h6></li>'
        + '<li><hr class="dropdown-divider my-1"></li>'
        + items.map(it => `
            <li>
                <a class="dropdown-item small py-2" href="#"
                   data-suggest-target="${targetId}"
                   data-suggest-text="${escapeAttr(it.text)}"
                   style="white-space:normal; line-height:1.4;">
                    <span class="badge bg-info">AI · ${escapeHtml(it.label)}</span>
                    <div class="mt-1 text-dark">${escapeHtml(it.text)}</div>
                </a>
            </li>`).join('');
    wrap.style.display = '';

    menu.querySelectorAll('[data-suggest-target]').forEach(el => {
        el.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.getElementById(this.dataset.suggestTarget);
            if (!target) return;
            target.value = this.dataset.suggestText || '';
            target.focus();
            target.dispatchEvent(new Event('input', { bubbles: true }));
        });
    });
}

function resetFlow() {
    if (state.attempts.length && !confirm('Reset and start over? Current attempts will stay saved in DB.')) return;
    // Preserve the calendar-slot context so a started-over post still links to its slot
    state = {
        postId:null, selectedRef:null, attempts:[], finalStatus:null, file:null,
        scheduledDate: state.scheduledDate || null,
        clientScopeId: state.clientScopeId || null,
    };
    // Allow AI suggestions to refetch for the new keyword/scope/type
    v1SuggestionsLoaded = false;
    v1CaptionItems = [];
    v1HashtagItems = [];
    document.getElementById('captionSuggestWrap').style.display = 'none';
    document.getElementById('hashtagSuggestWrap').style.display = 'none';
    document.getElementById('step2').classList.add('hidden');
    document.getElementById('step3').classList.add('hidden');
    document.getElementById('step4').classList.add('hidden');
    document.getElementById('trendGrid').innerHTML = '';
    document.getElementById('keywordInput').value = '';
    document.getElementById('captionInput').value = '';
    document.getElementById('hashtagsInput').value = '';
    document.getElementById('fileInput').value = '';
    document.getElementById('uploadBox').classList.remove('has-file');
    document.getElementById('uploadLabel').textContent = 'Click to upload your file';
    document.getElementById('uploadSub').textContent = 'mp4/mov for videos, jpg/png for photos · max 200MB';
    document.getElementById('submitBtn').disabled = true;
    document.getElementById('submitBtnLabel') && (document.getElementById('submitBtnLabel').textContent = 'Get Health Score');
    window.scrollTo({ top:0, behavior:'smooth' });
}
</script>
@endpush
