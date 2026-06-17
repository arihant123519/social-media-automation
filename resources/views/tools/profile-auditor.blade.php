@extends('layouts.app')

@section('title', 'Competitor Profile Auditor')
@section('page_header', 'Competitor Profile Auditor')
@section('page_icon', 'mdi mdi-account-search-outline')

@section('breadcrumb')
    <li class="breadcrumb-item active">Profile Auditor</li>
@endsection

@push('styles')
<style>
    .ai-card { background:#fff; border:1px solid rgba(0,0,0,.07); border-radius:.9rem; box-shadow:0 1px 2px rgba(0,0,0,.04); }
    .btn-ai { background:linear-gradient(135deg,#3b82f6,#6366f1); border:none; color:#fff; font-weight:600; box-shadow:0 8px 18px -8px rgba(59,130,246,.7); }
    .btn-ai:hover { color:#fff; filter:brightness(1.05); } .btn-ai:disabled { opacity:.8; }
    .lbl { font-size:.68rem; font-weight:700; text-transform:uppercase; letter-spacing:.4px; color:#94a3b8; }
    .kv { border-left:3px solid #818cf8; background:#f8fafc; border-radius:.5rem; padding:.6rem .85rem; }
    .tag { display:inline-block; background:#eef2ff; color:#4f46e5; font-size:.74rem; font-weight:600; padding:.18rem .55rem; border-radius:999px; margin:.12rem; }
    .mix-row { display:flex; align-items:center; gap:.6rem; }
    .mix-row .share { font-weight:700; color:#4f46e5; min-width:48px; }
    .idea { border:1px solid #e2e8f0; border-radius:.7rem; padding:.8rem; height:100%; }
    .idea .fmt { font-size:.68rem; font-weight:700; background:#dcfce7; color:#15803d; padding:.12rem .5rem; border-radius:999px; }
    #ai-overlay { position:fixed; inset:0; z-index:1090; background:rgba(15,23,42,.55); backdrop-filter:blur(3px); display:none; align-items:center; justify-content:center; }
    #ai-overlay.show { display:flex; }
    .ai-spinner { width:56px; height:56px; border-radius:50%; border:5px solid #e0e7ff; border-top-color:#6366f1; margin:0 auto 1.1rem; animation:spin .8s linear infinite; }
    @keyframes spin { to { transform:rotate(360deg); } }
</style>
@endpush

@section('content')
<div class="ai-card p-3 p-md-4 mb-4">
    <div class="row g-3 align-items-end">
        <div class="col-12 col-md-3">
            <label class="lbl d-block mb-1">Your client (brand voice)</label>
            <select id="client_id" class="form-select form-select-sm">
                <option value="">— No client —</option>
                @foreach($clients as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach
            </select>
        </div>
        <div class="col-12 col-md-3">
            <label class="lbl d-block mb-1">Competitor handle</label>
            <div class="input-group input-group-sm">
                <span class="input-group-text">@</span>
                <input id="handle" class="form-control" placeholder="competitor_handle">
            </div>
        </div>
        <div class="col-12 col-md-4">
            <label class="lbl d-block mb-1">Niche (optional)</label>
            <input id="niche" class="form-control form-control-sm" placeholder="e.g. dermatology clinic, Mumbai">
        </div>
        <div class="col-12 col-md-2">
            <button id="go" class="btn btn-ai w-100"><i class="mdi mdi-magnify me-1"></i> Audit</button>
        </div>
    </div>
    <p class="text-muted small mb-0 mt-2"><i class="mdi mdi-information-outline me-1"></i>Analyzes their recent content patterns and hands you a "steal their playbook" brief.</p>
    <div id="err" class="alert alert-danger alert-permanent mt-3 mb-0 py-2 small d-none"></div>
</div>

<div id="empty" class="ai-card p-5 text-center text-muted">
    <i class="mdi mdi-clipboard-search-outline" style="font-size:3rem; color:#cbd5e1;"></i>
    <p class="mb-0 mt-2">The competitor's playbook will appear here.</p>
</div>
<div id="result" class="d-none"></div>

<div id="ai-overlay"><div class="ai-card text-center p-4" style="max-width:340px;">
    <div class="ai-spinner"></div><h6 class="fw-bold mb-1">Auditing profile…</h6>
    <p class="text-muted small mb-0">Decoding what wins on their feed.</p>
</div></div>
@endsection

@push('scripts')
<script>
const CSRF = document.querySelector('meta[name="csrf-token"]').content;
const $ = s => document.querySelector(s);
function esc(s){ return (s??'').toString().replace(/[&<>"]/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c])); }
function toast(msg){ const t=document.createElement('div'); t.className='position-fixed bottom-0 end-0 m-3 px-3 py-2 rounded text-white shadow'; t.style.cssText+='z-index:1100;background:#198754;'; t.textContent=msg; document.body.appendChild(t); setTimeout(()=>t.remove(),1600); }
function copy(text){ navigator.clipboard.writeText(text).then(()=>toast('Copied')); }

$('#go').addEventListener('click', async () => {
    const handle = $('#handle').value.trim().replace(/^@/,'');
    $('#err').classList.add('d-none');
    if(!handle){ $('#err').textContent='Competitor handle is required.'; $('#err').classList.remove('d-none'); return; }
    $('#ai-overlay').classList.add('show'); $('#go').disabled = true;
    try {
        const res = await fetch('{{ route('ai.auditor.audit') }}', {
            method:'POST', headers:{'Content-Type':'application/json','X-CSRF-TOKEN':CSRF,'Accept':'application/json'},
            body: JSON.stringify({ client_id:$('#client_id').value||null, handle, niche:$('#niche').value.trim()||null }),
        });
        const j = await res.json();
        if(!j.success){ throw new Error(j.error || 'Audit failed.'); }
        render(j.data);
    } catch(e){ $('#err').textContent=e.message; $('#err').classList.remove('d-none'); }
    finally { $('#ai-overlay').classList.remove('show'); $('#go').disabled=false; }
});

function kv(label, val){ return val ? `<div class="kv mb-2"><span class="lbl d-block mb-1">${esc(label)}</span>${esc(val)}</div>` : ''; }
function li(arr){ return (arr||[]).map(x=>`<li>${esc(x)}</li>`).join(''); }

function render(d){
    $('#empty').classList.add('d-none');
    const r = $('#result'); r.classList.remove('d-none');
    const s = d.snapshot||{}, cs = d.caption_style||{}, hs = d.hashtag_strategy||{}, pb = d.playbook||{};
    const mix = (s.content_mix||[]).map(m=>`<div class="mix-row mb-2"><span class="share">${esc(m.share||'')}</span><div><b>${esc(m.format||'')}</b> <span class="text-muted small">${esc(m.note||'')}</span></div></div>`).join('');
    const capEx = (cs.examples||[]).map(e=>`<div class="border rounded p-2 mb-1 small fst-italic">"${esc(e)}"</div>`).join('');
    const tags = (hs.common_tags||[]).map(t=>`<span class="tag">${esc(t)}</span>`).join('');
    const ideas = (pb.post_ideas||[]).map(p=>`<div class="col-12 col-md-4"><div class="idea">
        <span class="fmt">${esc(p.format||'')}</span>
        <div class="fw-bold mt-2 small">${esc(p.title||'')}</div>
        ${p.hook?`<div class="text-muted small mt-1"><b>Hook:</b> ${esc(p.hook)}</div>`:''}
        ${p.why?`<div class="text-muted small mt-1"><i class="mdi mdi-lightbulb-on-outline me-1"></i>${esc(p.why)}</div>`:''}
    </div></div>`).join('');

    r.innerHTML = `
    <div class="ai-card p-3 p-md-4 mb-3">
        <h6 class="fw-bold mb-3"><i class="mdi mdi-instagram me-1" style="color:#e1306c"></i>@${esc(d.handle||'')} — snapshot</h6>
        <div class="row g-3">
            <div class="col-12 col-md-6">
                ${kv('Niche', s.estimated_niche)}${kv('Posting frequency', s.posting_frequency)}${kv('Winning format', s.winning_format)}
            </div>
            <div class="col-12 col-md-6">
                <span class="lbl d-block mb-2">Content mix</span>${mix}
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-12 col-md-6"><div class="ai-card p-3 p-md-4 h-100">
            <h6 class="fw-bold mb-2"><i class="mdi mdi-format-quote-close text-primary me-1"></i>Caption style</h6>
            ${kv('Tone', cs.tone)}${kv('Length', cs.length)}${kv('Structure', cs.structure)}
            ${capEx?`<span class="lbl d-block mt-2 mb-1">Examples</span>${capEx}`:''}
        </div></div>
        <div class="col-12 col-md-6"><div class="ai-card p-3 p-md-4 h-100">
            <h6 class="fw-bold mb-2"><i class="mdi mdi-pound text-primary me-1"></i>Hashtag strategy</h6>
            ${kv('Approach', hs.approach)}
            ${tags?`<span class="lbl d-block mt-2 mb-1">Common tags</span><div>${tags}</div>`:''}
            <div class="row mt-3">
                <div class="col-6"><span class="lbl d-block mb-1">What works</span><ul class="small mb-0">${li(d.what_works)}</ul></div>
                <div class="col-6"><span class="lbl d-block mb-1">Gaps to exploit</span><ul class="small mb-0">${li(d.gaps_to_exploit)}</ul></div>
            </div>
        </div></div>
    </div>

    <div class="ai-card p-3 p-md-4" style="border:1px solid #c7d2fe">
        <h6 class="fw-bold mb-2"><i class="mdi mdi-flag-checkered text-primary me-1"></i>Steal their playbook</h6>
        ${pb.summary?`<div class="alert alert-light border small">${esc(pb.summary)}</div>`:''}
        <span class="lbl d-block mb-2">Post ideas for you</span>
        <div class="row g-2 mb-3">${ideas}</div>
        ${kv('Recommended cadence', pb.recommended_cadence)}
        ${pb.recommended_hashtags?`<span class="lbl d-block mt-2 mb-1">Recommended hashtags</span>
            <div class="text-primary small" style="white-space:pre-wrap">${esc(pb.recommended_hashtags)}</div>
            <button class="btn btn-sm btn-light border mt-2" onclick='copy(${JSON.stringify(pb.recommended_hashtags)})'><i class="mdi mdi-content-copy me-1"></i>Copy hashtags</button>`:''}
    </div>`;
    r.scrollIntoView({behavior:'smooth', block:'start'});
}
</script>
@endpush
