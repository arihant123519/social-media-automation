@extends('layouts.app')

@section('title', 'Reel Analyzer')
@section('page_header', 'Reel Analyzer')
@section('page_icon', 'mdi mdi-magnify-scan')

@section('breadcrumb')
    <li class="breadcrumb-item active">Reel Analyzer</li>
@endsection

@push('styles')
<style>
    .ai-card { background:#fff; border:1px solid rgba(0,0,0,.07); border-radius:.9rem; box-shadow:0 1px 2px rgba(0,0,0,.04); }
    .btn-ai { background:linear-gradient(135deg,#3b82f6,#6366f1); border:none; color:#fff; font-weight:600; box-shadow:0 8px 18px -8px rgba(59,130,246,.7); }
    .btn-ai:hover { color:#fff; filter:brightness(1.05); } .btn-ai:disabled { opacity:.8; }
    .lbl { font-size:.68rem; font-weight:700; text-transform:uppercase; letter-spacing:.4px; color:#94a3b8; }
    .drop { border:2px dashed #cbd5e1; border-radius:.8rem; padding:1.1rem; text-align:center; cursor:pointer; transition:.15s; }
    .drop:hover, .drop.drag { border-color:#6366f1; background:#eef2ff; }
    .kv { border-left:3px solid #818cf8; background:#f8fafc; border-radius:.5rem; padding:.6rem .85rem; }
    .gauge { width:140px; height:140px; border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto; }
    .gauge span { font-size:2rem; font-weight:800; color:#0f172a; }
    .bar { height:8px; border-radius:999px; background:#e2e8f0; overflow:hidden; }
    .bar > div { height:100%; background:linear-gradient(90deg,#6366f1,#3b82f6); }
    .tag { display:inline-block; background:#eef2ff; color:#4f46e5; font-size:.74rem; font-weight:600; padding:.18rem .55rem; border-radius:999px; margin:.12rem; }
    #ai-overlay { position:fixed; inset:0; z-index:1090; background:rgba(15,23,42,.55); backdrop-filter:blur(3px); display:none; align-items:center; justify-content:center; }
    #ai-overlay.show { display:flex; }
    .ai-spinner { width:56px; height:56px; border-radius:50%; border:5px solid #e0e7ff; border-top-color:#6366f1; margin:0 auto 1.1rem; animation:spin .8s linear infinite; }
    @keyframes spin { to { transform:rotate(360deg); } }
</style>
@endpush

@section('content')
<div class="row g-4">
    <div class="col-12 col-xl-4">
        <div class="ai-card p-3 p-md-4 h-100">
            <h6 class="fw-bold mb-1"><i class="mdi mdi-link-variant text-primary me-1"></i> Reference reel</h6>
            <p class="text-muted small mb-3">Analyze a reel's strategy, then optionally upload your own video to score the match.</p>

            <div class="mb-3">
                <label class="lbl d-block mb-1">Client (brand voice)</label>
                <select id="client_id" class="form-select form-select-sm">
                    <option value="">— No client —</option>
                    @foreach($clients as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach
                </select>
            </div>
            <div class="mb-3">
                <label class="lbl d-block mb-1">Reel link</label>
                <input id="reel_url" class="form-control form-control-sm" placeholder="https://www.instagram.com/reel/...">
            </div>
            <div class="mb-3">
                <label class="lbl d-block mb-1">Notes (optional)</label>
                <textarea id="notes" class="form-control form-control-sm" rows="2" placeholder="What caught your eye? Any context..."></textarea>
            </div>
            <div class="mb-3">
                <label class="lbl d-block mb-1">Your video (optional — to get a match score)</label>
                <div class="drop" id="drop">
                    <i class="mdi mdi-cloud-upload-outline" style="font-size:1.8rem; color:#6366f1;"></i>
                    <div class="small mt-1" id="dropText">Click or drop a video (≤200MB)</div>
                </div>
                <input type="file" id="video" accept="video/*" class="d-none">
            </div>
            <button id="go" class="btn btn-ai w-100"><i class="mdi mdi-magnify-scan me-1"></i> Analyze</button>
            <div id="err" class="alert alert-danger alert-permanent mt-3 mb-0 py-2 small d-none"></div>
        </div>
    </div>

    <div class="col-12 col-xl-8">
        <div id="empty" class="ai-card p-5 text-center text-muted">
            <i class="mdi mdi-chart-timeline-variant" style="font-size:3rem; color:#cbd5e1;"></i>
            <p class="mb-0 mt-2">Strategy breakdown, match score and optimized content will appear here.</p>
        </div>
        <div id="result" class="d-none"></div>
    </div>
</div>

<div id="ai-overlay"><div class="ai-card text-center p-4" style="max-width:360px;">
    <div class="ai-spinner"></div><h6 class="fw-bold mb-1">Analyzing reel…</h6>
    <p class="text-muted small mb-0" id="ovText">Reverse-engineering the winning strategy.</p>
</div></div>
@endsection

@push('scripts')
<script>
const CSRF = document.querySelector('meta[name="csrf-token"]').content;
const $ = s => document.querySelector(s);
let videoFile = null;
function esc(s){ return (s??'').toString().replace(/[&<>"]/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c])); }
function toast(msg){ const t=document.createElement('div'); t.className='position-fixed bottom-0 end-0 m-3 px-3 py-2 rounded text-white shadow'; t.style.cssText+='z-index:1100;background:#198754;'; t.textContent=msg; document.body.appendChild(t); setTimeout(()=>t.remove(),1600); }
function copy(text){ navigator.clipboard.writeText(text).then(()=>toast('Copied')); }

// Hand off the optimized caption + hashtags to the Post Creator. The analyzed
// video isn't retained, so the user re-attaches it on the post page.
function publishToPost(draft){
    try { sessionStorage.setItem('aiPublishDraft', JSON.stringify(draft)); } catch(e){}
    window.location.href = '{{ route('Post.index') }}?from=ai';
}
function publishReel(){
    const d = window._reelData || {};
    publishToPost({
        source:    'reel',
        client_id: $('#client_id').value || null,
        post_type: 'reels',
        keyword:   (d.reference && d.reference.topic) || 'AI reel post',
        caption:   (d.optimized && d.optimized.captions && d.optimized.captions[0]) || '',
        hashtags:  window._reelAllTags || '',
    });
}

const drop = $('#drop'), fileIn = $('#video');
drop.addEventListener('click', () => fileIn.click());
['dragover','dragenter'].forEach(e=>drop.addEventListener(e, ev=>{ev.preventDefault();drop.classList.add('drag');}));
['dragleave','drop'].forEach(e=>drop.addEventListener(e, ev=>{ev.preventDefault();drop.classList.remove('drag');}));
drop.addEventListener('drop', ev=>{ if(ev.dataTransfer.files[0]) setFile(ev.dataTransfer.files[0]); });
fileIn.addEventListener('change', ()=>{ if(fileIn.files[0]) setFile(fileIn.files[0]); });
function setFile(f){ videoFile=f; $('#dropText').innerHTML = `<i class="mdi mdi-check-circle text-success me-1"></i>${esc(f.name)}`; }

$('#go').addEventListener('click', async () => {
    const url = $('#reel_url').value.trim();
    $('#err').classList.add('d-none');
    if(!url){ $('#err').textContent='Reel link is required.'; $('#err').classList.remove('d-none'); return; }

    $('#ovText').textContent = videoFile ? 'Analyzing your video & scoring the match (this can take a minute)…' : 'Reverse-engineering the winning strategy.';
    $('#ai-overlay').classList.add('show'); $('#go').disabled = true;
    try {
        const fd = new FormData();
        fd.append('reel_url', url);
        if($('#client_id').value) fd.append('client_id', $('#client_id').value);
        if($('#notes').value.trim()) fd.append('notes', $('#notes').value.trim());
        if(videoFile) fd.append('video', videoFile);
        const res = await fetch('{{ route('ai.reel.analyze') }}', {
            method:'POST', headers:{'X-CSRF-TOKEN':CSRF,'Accept':'application/json'}, body: fd,
        });
        const j = await res.json();
        if(!j.success){ throw new Error(j.error || 'Analysis failed.'); }
        render(j.data, j.had_video);
    } catch(e){ $('#err').textContent=e.message; $('#err').classList.remove('d-none'); }
    finally { $('#ai-overlay').classList.remove('show'); $('#go').disabled=false; }
});

function kv(label, val){ return val ? `<div class="kv mb-2"><span class="lbl d-block mb-1">${esc(label)}</span>${esc(val)}</div>` : ''; }

function render(d, hadVideo){
    $('#empty').classList.add('d-none');
    const r = $('#result'); r.classList.remove('d-none');
    const ref = d.reference || {};

    // Match score block
    let matchHtml = '';
    if(d.match){
        const score = d.match.score ?? 0;
        const hue = score>=70?'#16a34a':score>=45?'#d97706':'#dc2626';
        const rows = (d.match.breakdown||[]).map(b=>{
            const pct = Math.round((b.score/(b.max||25))*100);
            return `<div class="mb-2"><div class="d-flex justify-content-between small"><span>${esc(b.label)}</span><span class="fw-bold">${esc(b.score)}/${esc(b.max)}</span></div>
                <div class="bar mt-1"><div style="width:${pct}%"></div></div>
                ${b.note?`<div class="text-muted" style="font-size:.72rem">${esc(b.note)}</div>`:''}</div>`;
        }).join('');
        matchHtml = `<div class="ai-card p-3 p-md-4 mb-3">
            <h6 class="fw-bold mb-3"><i class="mdi mdi-target text-primary me-1"></i>Audience match score</h6>
            <div class="row align-items-center g-3">
                <div class="col-12 col-md-4 text-center">
                    <div class="gauge" style="background:conic-gradient(${hue} ${score*3.6}deg,#e2e8f0 0)">
                        <div style="width:104px;height:104px;border-radius:50%;background:#fff;display:flex;align-items:center;justify-content:center"><span>${esc(score)}</span></div>
                    </div>
                    <div class="small text-muted mt-2">${esc(d.match.verdict||'')}</div>
                </div>
                <div class="col-12 col-md-8">${rows}</div>
            </div>
        </div>`;
    }

    // Your video block
    let yourHtml = '';
    if(d.your_video){
        const strengths = (d.your_video.strengths||[]).map(s=>`<li>${esc(s)}</li>`).join('');
        const gaps = (d.your_video.gaps_vs_reference||[]).map(s=>`<li>${esc(s)}</li>`).join('');
        yourHtml = `<div class="ai-card p-3 p-md-4 mb-3">
            <h6 class="fw-bold mb-2"><i class="mdi mdi-video-account text-primary me-1"></i>Your video</h6>
            ${kv('Topic', d.your_video.observed_topic)}${kv('Opening hook', d.your_video.observed_hook)}
            ${strengths?`<span class="lbl d-block mt-2 mb-1">Strengths</span><ul class="small mb-2">${strengths}</ul>`:''}
            ${gaps?`<span class="lbl d-block mb-1">Gaps vs the reference</span><ul class="small mb-0">${gaps}</ul>`:''}
        </div>`;
    }

    const opt = d.optimized || {};
    const caps = (opt.captions||[]).map(c=>`<div class="border rounded p-2 mb-2 small" style="white-space:pre-wrap">${esc(c)}<div class="mt-1"><button class="btn btn-sm btn-light border" onclick='copy(${JSON.stringify(c)})'><i class="mdi mdi-content-copy"></i></button></div></div>`).join('');
    const hooks = (opt.hooks||[]).map(h=>`<li>${esc(h)}</li>`).join('');
    const actions = (opt.action_items||[]).map(a=>`<li>${esc(a)}</li>`).join('');
    const tagSet = t => (t||[]).map(x=>`<span class="tag">${esc(x)}</span>`).join('');
    const allTags = [...(opt.hashtags?.trending||[]),...(opt.hashtags?.niche||[]),...(opt.hashtags?.branded||[])].join(' ');
    window._reelData = d; window._reelAllTags = allTags;

    r.innerHTML = `
    ${!hadVideo?'<div class="alert alert-info py-2 small"><i class="mdi mdi-information-outline me-1"></i>Upload your own video to get an audience-match score.</div>':''}
    <div class="ai-card p-3 p-md-4 mb-3">
        <h6 class="fw-bold mb-2"><i class="mdi mdi-trophy-outline text-primary me-1"></i>Reference reel — what makes it work</h6>
        ${kv('Topic', ref.topic)}${kv('Target audience', ref.target_audience)}${kv('Hook', ref.hook)}
        ${kv('Caption style', ref.caption_style)}${kv('Hashtag strategy', ref.hashtag_strategy)}${kv('Format', ref.format)}
        ${ref.why_it_works?`<div class="alert alert-light border mt-2 mb-0 small"><i class="mdi mdi-lightbulb-on-outline me-1 text-warning"></i>${esc(ref.why_it_works)}</div>`:''}
    </div>
    ${matchHtml}
    ${yourHtml}
    <div class="ai-card p-3 p-md-4">
        <h6 class="fw-bold mb-3"><i class="mdi mdi-rocket-launch-outline text-primary me-1"></i>Optimized for your audience</h6>
        ${caps?`<span class="lbl d-block mb-1">Captions</span>${caps}`:''}
        ${hooks?`<span class="lbl d-block mt-2 mb-1">Stronger hooks</span><ul class="small">${hooks}</ul>`:''}
        <span class="lbl d-block mt-2 mb-1">Recommended hashtags</span>
        <div>${tagSet(opt.hashtags?.trending)}${tagSet(opt.hashtags?.niche)}${tagSet(opt.hashtags?.branded)}</div>
        ${allTags?`<button class="btn btn-sm btn-light border mt-2" onclick='copy(${JSON.stringify(allTags)})'><i class="mdi mdi-content-copy me-1"></i>Copy all hashtags</button>`:''}
        ${actions?`<span class="lbl d-block mt-3 mb-1">Action items</span><ul class="small mb-0">${actions}</ul>`:''}
        ${hadVideo?`<div class="mt-3 pt-3 border-top"><button class="btn btn-ai" onclick="publishReel()"><i class="mdi mdi-send-check me-1"></i>Publish to Post Creator</button><span class="text-muted small ms-2">Re-attach your video on the next page.</span></div>`:''}
    </div>`;
    r.scrollIntoView({behavior:'smooth', block:'start'});
}
</script>
@endpush
