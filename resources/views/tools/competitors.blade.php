@extends('layouts.app')

@section('title', 'Competitor Post Intelligence Feed')
@section('page_header', 'Competitor Post Intelligence Feed')
@section('page_icon', 'mdi mdi-radar')

@section('breadcrumb')
    <li class="breadcrumb-item active">Competitor Feed</li>
@endsection

@push('styles')
<style>
    .ai-card { background:#fff; border:1px solid rgba(0,0,0,.07); border-radius:.9rem; box-shadow:0 1px 2px rgba(0,0,0,.04); }
    .btn-ai { background:linear-gradient(135deg,#3b82f6,#6366f1); border:none; color:#fff; font-weight:600; box-shadow:0 8px 18px -8px rgba(59,130,246,.7); }
    .btn-ai:hover { color:#fff; filter:brightness(1.05); } .btn-ai:disabled { opacity:.8; }
    .lbl { font-size:.68rem; font-weight:700; text-transform:uppercase; letter-spacing:.4px; color:#94a3b8; }
    .comp-card { border:1px solid #e2e8f0; border-radius:.8rem; height:100%; }
    .comp-card .head { background:#f8fafc; border-bottom:1px solid #e2e8f0; padding:.6rem .9rem; font-weight:700; }
    .post-row { border-left:3px solid #818cf8; background:#f8fafc; border-radius:.45rem; padding:.5rem .7rem; }
    .fmt { font-size:.66rem; font-weight:700; background:#eef2ff; color:#4f46e5; padding:.1rem .45rem; border-radius:999px; }
    .eng { font-size:.66rem; font-weight:700; background:#dcfce7; color:#15803d; padding:.1rem .45rem; border-radius:999px; }
    .idea { border:1px solid #e2e8f0; border-radius:.7rem; padding:.8rem; height:100%; }
    .handle-chip { display:inline-flex; align-items:center; gap:.3rem; background:#fff; border:1px solid #e2e8f0; border-radius:999px; padding:.2rem .6rem; font-size:.78rem; font-weight:600; }
    #ai-overlay { position:fixed; inset:0; z-index:1090; background:rgba(15,23,42,.55); backdrop-filter:blur(3px); display:none; align-items:center; justify-content:center; }
    #ai-overlay.show { display:flex; }
    .ai-spinner { width:56px; height:56px; border-radius:50%; border:5px solid #e0e7ff; border-top-color:#6366f1; margin:0 auto 1.1rem; animation:spin .8s linear infinite; }
    @keyframes spin { to { transform:rotate(360deg); } }
</style>
@endpush

@section('content')
<div class="ai-card p-3 p-md-4 mb-4">
    <div class="row g-3">
        <div class="col-12 col-md-3">
            <form method="GET" id="clientForm">
                <label class="lbl d-block mb-1">Client</label>
                <select name="client_id" class="form-select form-select-sm" onchange="document.getElementById('clientForm').submit()">
                    @foreach($clients as $c)
                        <option value="{{ $c->id }}" {{ $clientId == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                    @endforeach
                </select>
            </form>
        </div>
        <div class="col-12 col-md-7">
            <label class="lbl d-block mb-1">Competitor Instagram handles (3-5)</label>
            <div class="row g-2" id="handles">
                @for($i = 0; $i < 5; $i++)
                    <div class="col-6 col-lg">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text">@</span>
                            <input class="form-control handle-input" value="{{ $configured[$i] ?? '' }}" placeholder="handle{{ $i+1 }}">
                        </div>
                    </div>
                @endfor
            </div>
        </div>
        <div class="col-12 col-md-2 d-flex align-items-end">
            <button id="go" class="btn btn-ai w-100"><i class="mdi mdi-radar me-1"></i> Run brief</button>
        </div>
    </div>
    <p class="text-muted small mb-0 mt-2"><i class="mdi mdi-information-outline me-1"></i>
        Auto-refreshes every Monday for handles set in <code>config/competitors.php</code>. Run it on demand here anytime. Briefs are cached to the filesystem — never the database.</p>
    <div id="err" class="alert alert-danger alert-permanent mt-3 mb-0 py-2 small d-none"></div>
</div>

<div id="meta" class="text-muted small mb-2 {{ $latest ? '' : 'd-none' }}">
    <i class="mdi mdi-clock-outline me-1"></i>Last generated:
    <span id="metaTime">{{ $latest ? \Carbon\Carbon::parse($latest['generated_at'])->diffForHumans() : 'just now' }}</span>
    @if($latest)· Tracking: {{ implode(', ', array_map(fn($h)=>'@'.$h, $latest['handles'] ?? [])) }}@endif
</div>

<div id="empty" class="ai-card p-5 text-center text-muted {{ $latest ? 'd-none' : '' }}">
    <i class="mdi mdi-radar" style="font-size:3rem; color:#cbd5e1;"></i>
    <p class="mb-0 mt-2">Add competitor handles and run this week's brief.</p>
</div>
<div id="result" class="{{ $latest ? '' : 'd-none' }}"></div>

<div id="ai-overlay"><div class="ai-card text-center p-4" style="max-width:360px;">
    <div class="ai-spinner"></div><h6 class="fw-bold mb-1">Analyzing competitors…</h6>
    <p class="text-muted small mb-0">Finding what's working for them this week.</p>
</div></div>
@endsection

@push('scripts')
<script>
const CSRF = document.querySelector('meta[name="csrf-token"]').content;
const $ = s => document.querySelector(s);
function esc(s){ return (s??'').toString().replace(/[&<>"]/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c])); }
function toast(msg){ const t=document.createElement('div'); t.className='position-fixed bottom-0 end-0 m-3 px-3 py-2 rounded text-white shadow'; t.style.cssText+='z-index:1100;background:#198754;'; t.textContent=msg; document.body.appendChild(t); setTimeout(()=>t.remove(),1600); }
function copy(text){ navigator.clipboard.writeText(text).then(()=>toast('Copied')); }

@if($latest) render(@json($latest['brief'])); @endif

$('#go').addEventListener('click', async () => {
    const handles = [...document.querySelectorAll('.handle-input')].map(i=>i.value.trim().replace(/^@/,'')).filter(Boolean);
    $('#err').classList.add('d-none');
    if(!handles.length){ $('#err').textContent='Add at least one competitor handle.'; $('#err').classList.remove('d-none'); return; }
    $('#ai-overlay').classList.add('show'); $('#go').disabled = true;
    try {
        const res = await fetch('{{ route('ai.competitors.analyze') }}', {
            method:'POST', headers:{'Content-Type':'application/json','X-CSRF-TOKEN':CSRF,'Accept':'application/json'},
            body: JSON.stringify({ client_id:'{{ $clientId }}'||null, handles }),
        });
        const j = await res.json();
        if(!j.success){ throw new Error(j.error || 'Failed.'); }
        $('#meta').classList.remove('d-none'); $('#metaTime').textContent='just now';
        render(j.data);
    } catch(e){ $('#err').textContent=e.message; $('#err').classList.remove('d-none'); }
    finally { $('#ai-overlay').classList.remove('show'); $('#go').disabled=false; }
});

function li(arr){ return (arr||[]).map(x=>`<li>${esc(x)}</li>`).join(''); }

function render(d){
    $('#empty').classList.add('d-none');
    const r = $('#result'); r.classList.remove('d-none');

    const comps = (d.competitors||[]).map(c=>{
        const posts = (c.top_posts||[]).map(p=>`<div class="post-row mb-2">
            <div class="d-flex gap-1 mb-1 flex-wrap"><span class="fmt">${esc(p.format||'')}</span>${p.est_engagement?`<span class="eng">${esc(p.est_engagement)}</span>`:''}</div>
            <div class="small fw-semibold">${esc(p.topic||'')}</div>
            ${p.why_it_worked?`<div class="text-muted" style="font-size:.74rem">${esc(p.why_it_worked)}</div>`:''}
        </div>`).join('');
        return `<div class="col-12 col-lg-6 col-xl-4 mb-3"><div class="comp-card">
            <div class="head"><i class="mdi mdi-instagram me-1" style="color:#e1306c"></i>@${esc(c.handle||'')}</div>
            <div class="p-3">${posts}
                ${c.takeaway?`<div class="alert alert-light border small mb-0 mt-1"><i class="mdi mdi-key-variant me-1 text-warning"></i>${esc(c.takeaway)}</div>`:''}
            </div></div></div>`;
    }).join('');

    const ideas = (d.recommended_posts||[]).map(p=>`<div class="col-12 col-md-4"><div class="idea">
        <span class="fmt">${esc(p.format||'')}</span>
        <div class="fw-bold mt-2 small">${esc(p.title||'')}</div>
        ${p.hook?`<div class="text-muted small mt-1"><b>Hook:</b> ${esc(p.hook)}</div>`:''}
        ${p.rationale?`<div class="text-muted small mt-1"><i class="mdi mdi-source-branch me-1"></i>${esc(p.rationale)}</div>`:''}
    </div></div>`).join('');

    r.innerHTML = `
    <div class="ai-card p-3 p-md-4 mb-3" style="border:1px solid #c7d2fe">
        <h6 class="fw-bold mb-1"><i class="mdi mdi-trending-up text-primary me-1"></i>What's working this week</h6>
        ${d.week_summary?`<div class="alert alert-light border small mb-2">${esc(d.week_summary)}</div>`:''}
        <div class="row">
            <div class="col-12 col-md-6"><span class="lbl d-block mb-1">Patterns winning right now</span><ul class="small mb-0">${li(d.whats_working)}</ul></div>
            <div class="col-12 col-md-6"><span class="lbl d-block mb-1">Content gaps you can own</span><ul class="small mb-0">${li(d.content_gaps)}</ul></div>
        </div>
    </div>

    <h6 class="fw-bold mb-2"><i class="mdi mdi-account-group-outline text-primary me-1"></i>Per-competitor breakdown</h6>
    <div class="row">${comps}</div>

    <div class="ai-card p-3 p-md-4 mt-2">
        <h6 class="fw-bold mb-2"><i class="mdi mdi-lightbulb-on-outline text-primary me-1"></i>Recommended posts for you</h6>
        <div class="row g-2 mb-3">${ideas}</div>
        ${d.recommended_hashtags?`<span class="lbl d-block mb-1">Trending hashtags this week</span>
            <div class="text-primary small" style="white-space:pre-wrap">${esc(d.recommended_hashtags)}</div>
            <button class="btn btn-sm btn-light border mt-2" onclick='copy(${JSON.stringify(d.recommended_hashtags)})'><i class="mdi mdi-content-copy me-1"></i>Copy hashtags</button>`:''}
    </div>`;
}
</script>
@endpush
