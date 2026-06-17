@extends('layouts.app')

@section('title', 'Multi-Format Caption Engine')
@section('page_header', 'Multi-Format Caption Engine')
@section('page_icon', 'mdi mdi-format-list-bulleted-square')

@section('breadcrumb')
    <li class="breadcrumb-item active">Multi-Format Captions</li>
@endsection

@push('styles')
<style>
    .ai-card { background:#fff; border:1px solid rgba(0,0,0,.07); border-radius:.9rem; box-shadow:0 1px 2px rgba(0,0,0,.04); }
    .btn-ai { background:linear-gradient(135deg,#3b82f6,#6366f1); border:none; color:#fff; font-weight:600; box-shadow:0 8px 18px -8px rgba(59,130,246,.7); }
    .btn-ai:hover { color:#fff; filter:brightness(1.05); } .btn-ai:disabled { opacity:.8; }
    .lbl { font-size:.68rem; font-weight:700; text-transform:uppercase; letter-spacing:.4px; color:#94a3b8; }
    .variant { border:1px solid #e2e8f0; border-radius:.8rem; overflow:hidden; transition:.2s; height:100%; }
    .variant:hover { box-shadow:0 .55rem 1.5rem rgba(2,6,23,.10); transform:translateY(-2px); }
    .variant .head { padding:.7rem 1rem; color:#fff; font-weight:700; font-size:.85rem; display:flex; align-items:center; gap:.4rem; }
    .v-long .head  { background:linear-gradient(135deg,#e1306c,#c13584); }
    .v-short .head { background:linear-gradient(135deg,#f59e0b,#ef4444); }
    .v-q .head     { background:linear-gradient(135deg,#3b82f6,#6366f1); }
    .variant .body-txt { white-space:pre-wrap; font-size:.88rem; line-height:1.55; }
    .best-for { font-size:.72rem; color:#64748b; }
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
            <label class="lbl d-block mb-1">Client (brand voice)</label>
            <select id="client_id" class="form-select form-select-sm">
                <option value="">— No client —</option>
                @foreach($clients as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach
            </select>
        </div>
        <div class="col-12 col-md-3">
            <label class="lbl d-block mb-1">Post type</label>
            <select id="post_type" class="form-select form-select-sm">
                <option value="reel">Reel</option>
                <option value="photo">Photo / Feed</option>
                <option value="carousel">Carousel</option>
                <option value="story">Story</option>
            </select>
        </div>
        <div class="col-12 col-md-4">
            <label class="lbl d-block mb-1">Topic / what's the post about?</label>
            <input id="topic" class="form-control form-control-sm" placeholder="e.g. 3 myths about acne">
        </div>
        <div class="col-12 col-md-2">
            <button id="go" class="btn btn-ai w-100"><i class="mdi mdi-creation me-1"></i> Generate</button>
        </div>
    </div>
    <p class="text-muted small mb-0 mt-2"><i class="mdi mdi-information-outline me-1"></i>One Gemini call → three variants. Pick one in 10 seconds.</p>
    <div id="err" class="alert alert-danger alert-permanent mt-3 mb-0 py-2 small d-none"></div>
</div>

<div id="empty" class="ai-card p-5 text-center text-muted">
    <i class="mdi mdi-format-quote-close" style="font-size:3rem; color:#cbd5e1;"></i>
    <p class="mb-0 mt-2">Long-form, short punchy, and question-hook variants will appear here.</p>
</div>

<div id="result" class="d-none">
    <div class="row g-3" id="variants"></div>
    <div class="ai-card p-3 mt-3">
        <label class="lbl d-block mb-1">Shared hashtags</label>
        <div id="hashtags" class="text-primary small" style="white-space:pre-wrap"></div>
        <button class="btn btn-sm btn-light border mt-2" id="copyTags"><i class="mdi mdi-content-copy me-1"></i>Copy hashtags</button>
    </div>
</div>

<div id="ai-overlay"><div class="ai-card text-center p-4" style="max-width:340px;">
    <div class="ai-spinner"></div><h6 class="fw-bold mb-1">Writing 3 variants…</h6>
    <p class="text-muted small mb-0">Long-form, punchy, and a question hook.</p>
</div></div>
@endsection

@push('scripts')
<script>
const CSRF = document.querySelector('meta[name="csrf-token"]').content;
const $ = s => document.querySelector(s);
function esc(s){ return (s??'').toString().replace(/[&<>"]/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c])); }
function toast(msg){ const t=document.createElement('div'); t.className='position-fixed bottom-0 end-0 m-3 px-3 py-2 rounded text-white shadow'; t.style.cssText+='z-index:1100;background:#198754;'; t.textContent=msg; document.body.appendChild(t); setTimeout(()=>t.remove(),1600); }
function copy(text){ navigator.clipboard.writeText(text).then(()=>toast('Copied')); }

// Hand off a chosen caption variant + shared hashtags to the Post Creator.
const CAP_TYPE_MAP = { reel:'reels', photo:'photo', carousel:'photo', story:'story' };
function publishToPost(draft){
    try { sessionStorage.setItem('aiPublishDraft', JSON.stringify(draft)); } catch(e){}
    window.location.href = '{{ route('Post.index') }}?from=ai';
}
function publishCaption(caption){
    publishToPost({
        source:    'captions',
        client_id: $('#client_id').value || null,
        post_type: CAP_TYPE_MAP[$('#post_type').value] || 'reels',
        keyword:   $('#topic').value.trim(),
        caption:   caption,
        hashtags:  (window._capData && window._capData.hashtags) || '',
    });
}

$('#go').addEventListener('click', async () => {
    const topic = $('#topic').value.trim();
    $('#err').classList.add('d-none');
    if(!topic){ $('#err').textContent='Topic is required.'; $('#err').classList.remove('d-none'); return; }
    $('#ai-overlay').classList.add('show'); $('#go').disabled = true;
    try {
        const res = await fetch('{{ route('ai.captions.generate') }}', {
            method:'POST', headers:{'Content-Type':'application/json','X-CSRF-TOKEN':CSRF,'Accept':'application/json'},
            body: JSON.stringify({ client_id:$('#client_id').value||null, topic, post_type:$('#post_type').value }),
        });
        const j = await res.json();
        if(!j.success){ throw new Error(j.error || 'Generation failed.'); }
        render(j.data);
    } catch(e){ $('#err').textContent=e.message; $('#err').classList.remove('d-none'); }
    finally { $('#ai-overlay').classList.remove('show'); $('#go').disabled=false; }
});

function card(cls, icon, v){
    const cap = v?.caption || '';
    return `<div class="col-12 col-lg-4"><div class="variant ${cls}">
        <div class="head"><i class="mdi ${icon}"></i> ${esc(v?.label||'')}</div>
        <div class="p-3 d-flex flex-column" style="height:calc(100% - 44px)">
            <div class="body-txt flex-grow-1">${esc(cap)}</div>
            <div class="best-for mt-2"><i class="mdi mdi-target me-1"></i>${esc(v?.best_for||'')}</div>
            <div class="d-flex gap-2 mt-2">
                <button class="btn btn-sm btn-ai flex-grow-1" onclick='copy(${JSON.stringify(cap)})'><i class="mdi mdi-content-copy me-1"></i>Use this</button>
                <button class="btn btn-sm btn-light border" onclick='publishCaption(${JSON.stringify(cap)})'><i class="mdi mdi-send-check me-1"></i>Publish</button>
            </div>
        </div></div></div>`;
}

function render(d){
    window._capData = d;
    $('#empty').classList.add('d-none'); $('#result').classList.remove('d-none');
    const v = d.variants || {};
    $('#variants').innerHTML =
        card('v-long','mdi-instagram', v.long_form) +
        card('v-short','mdi-lightning-bolt', v.short_punchy) +
        card('v-q','mdi-comment-question-outline', v.question_hook);
    $('#hashtags').textContent = d.hashtags || '';
    $('#copyTags').onclick = () => copy(d.hashtags || '');
    $('#result').scrollIntoView({behavior:'smooth', block:'start'});
}
</script>
@endpush
