@extends('layouts.app')

@section('title', 'AI Video Script Generator')
@section('page_header', 'AI Video Script Generator')
@section('page_icon', 'mdi mdi-movie-open-play-outline')

@section('breadcrumb')
    <li class="breadcrumb-item active">Video Script Generator</li>
@endsection

@push('styles')
<style>
    .ai-card { background:#fff; border:1px solid rgba(0,0,0,.07); border-radius:.9rem; box-shadow:0 1px 2px rgba(0,0,0,.04); }
    .btn-ai { background:linear-gradient(135deg,#3b82f6,#6366f1); border:none; color:#fff; font-weight:600; box-shadow:0 8px 18px -8px rgba(59,130,246,.7); }
    .btn-ai:hover { color:#fff; filter:brightness(1.05); }
    .btn-ai:disabled { opacity:.8; }
    .dur-toggle input { display:none; }
    .dur-toggle label { cursor:pointer; border:1.5px solid #e2e8f0; border-radius:.6rem; padding:.5rem 1.1rem; font-weight:600; color:#475569; transition:.15s; }
    .dur-toggle input:checked + label { border-color:#6366f1; background:#eef2ff; color:#4f46e5; }
    .script-label { font-size:.68rem; font-weight:700; text-transform:uppercase; letter-spacing:.4px; color:#94a3b8; }
    .beat-row { border-left:3px solid #818cf8; background:#f8fafc; border-radius:.5rem; padding:.65rem .85rem; }
    .copy-chip { cursor:pointer; }
    .hook-box { background:linear-gradient(135deg,#fef3c7,#fde68a33); border:1px solid #fcd34d; border-radius:.7rem; }
    .cta-box { background:linear-gradient(135deg,#dcfce7,#bbf7d033); border:1px solid #86efac; border-radius:.7rem; }
    .pill { font-size:.7rem; font-weight:700; padding:.2rem .6rem; border-radius:999px; background:#eef2ff; color:#4f46e5; }
    #ai-overlay { position:fixed; inset:0; z-index:1090; background:rgba(15,23,42,.55); backdrop-filter:blur(3px); display:none; align-items:center; justify-content:center; }
    #ai-overlay.show { display:flex; }
    .ai-spinner { width:56px; height:56px; border-radius:50%; border:5px solid #e0e7ff; border-top-color:#6366f1; margin:0 auto 1.1rem; animation:spin .8s linear infinite; }
    @keyframes spin { to { transform:rotate(360deg); } }
</style>
@endpush

@section('content')
<div class="row g-4">
    {{-- Input --}}
    <div class="col-12 col-xl-4">
        <div class="ai-card p-3 p-md-4 h-100">
            <h6 class="fw-bold mb-1"><i class="mdi mdi-pencil-ruler text-primary me-1"></i> Brief</h6>
            <p class="text-muted small mb-3">Script ready in ~90 seconds — no briefing call needed.</p>

            <div class="mb-3">
                <label class="script-label d-block mb-1">Client (brand voice)</label>
                <select id="client_id" class="form-select form-select-sm">
                    <option value="">— No client (generic) —</option>
                    @foreach($clients as $c)
                        <option value="{{ $c->id }}">{{ $c->name }}{{ $c->industry ? ' · '.$c->industry : '' }}</option>
                    @endforeach
                </select>
            </div>

            <div class="mb-3">
                <label class="script-label d-block mb-1">Doctor's specialty</label>
                <input id="specialty" class="form-control form-control-sm" placeholder="e.g. Dermatologist, Cardiologist">
            </div>

            <div class="mb-3">
                <label class="script-label d-block mb-1">Topic</label>
                <textarea id="topic" class="form-control form-control-sm" rows="3" placeholder="e.g. Why your sunscreen isn't working"></textarea>
            </div>

            <div class="mb-3">
                <label class="script-label d-block mb-1">Duration</label>
                <div class="input-group input-group-sm">
                    <input id="duration_value" type="number" class="form-control" value="30" min="1" step="1" placeholder="e.g. 5">
                    <select id="duration_unit" class="form-select" style="max-width:7rem">
                        <option value="sec" selected>seconds</option>
                        <option value="min">minutes</option>
                    </select>
                </div>
                <div class="text-muted small mt-1">Type any length (up to 20 min). Longer videos are structured into chapters/segments.</div>
            </div>

            <div class="mb-3">
                <label class="script-label d-block mb-1">Platform</label>
                <select id="platform" class="form-select form-select-sm">
                    <option value="Instagram">Instagram Reel</option>
                    <option value="YouTube">YouTube Short</option>
                </select>
            </div>

            <button id="go" class="btn btn-ai w-100"><i class="mdi mdi-creation me-1"></i> Generate Script</button>
            <div id="err" class="alert alert-danger alert-permanent mt-3 mb-0 py-2 small d-none"></div>
        </div>
    </div>

    {{-- Output --}}
    <div class="col-12 col-xl-8">
        <div id="empty" class="ai-card p-5 text-center text-muted">
            <i class="mdi mdi-movie-roll" style="font-size:3rem; color:#cbd5e1;"></i>
            <p class="mb-0 mt-2">Your generated script will appear here.</p>
        </div>
        <div id="result" class="d-none"></div>
    </div>
</div>

<div id="ai-overlay"><div class="ai-card text-center p-4" style="max-width:340px;">
    <div class="ai-spinner"></div>
    <h6 class="fw-bold mb-1">Writing your script…</h6>
    <p class="text-muted small mb-0">Gemini is crafting a scroll-stopping hook, body and CTA.</p>
</div></div>
@endsection

@push('scripts')
<script>
const CSRF = document.querySelector('meta[name="csrf-token"]').content;
const $ = s => document.querySelector(s);

// Resolve the typed length + unit into whole seconds, clamped to a sane range.
function durationSeconds(){
    let v = parseFloat($('#duration_value').value) || 0;
    if($('#duration_unit').value === 'min') v *= 60;
    return Math.max(15, Math.min(1200, Math.round(v)));
}
function fmtDur(s){ s = parseInt(s)||0; if(s < 120) return s+'s'; const m = s/60; return (Number.isInteger(m)?m:m.toFixed(1))+' min'; }
function esc(s){ return (s??'').toString().replace(/[&<>"]/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c])); }
function toast(msg){ const t=document.createElement('div'); t.className='position-fixed bottom-0 end-0 m-3 px-3 py-2 rounded text-white shadow'; t.style.cssText+='z-index:1100;background:#198754;'; t.textContent=msg; document.body.appendChild(t); setTimeout(()=>t.remove(),1600); }
function copy(text){ navigator.clipboard.writeText(text).then(()=>toast('Copied')); }

// Hand off the generated script's caption + hashtags to the Post Creator.
function publishToPost(draft){
    try { sessionStorage.setItem('aiPublishDraft', JSON.stringify(draft)); } catch(e){}
    window.open('{{ route('Post.index') }}?from=ai', '_blank');
}
// Map the chosen platform + duration to a content format the rest of the app understands.
// YouTube clips of 3 min+ are long-form videos, not Shorts; Instagram stays Reels.
function videoFormat(){
    const secs = durationSeconds();
    if($('#platform').value === 'YouTube') return secs >= 180 ? 'long_video' : 'short_video';
    return 'reels';
}

function publishScript(){
    const d = window._scriptData || {};
    publishToPost({
        source:    'script',
        client_id: $('#client_id').value || null,
        post_type: videoFormat(),
        keyword:   $('#topic').value.trim(),
        caption:   d.caption || buildPlain(d),
        hashtags:  d.hashtags || '',
    });
}

$('#go').addEventListener('click', async () => {
    const topic = $('#topic').value.trim(), specialty = $('#specialty').value.trim();
    $('#err').classList.add('d-none');
    if(!topic || !specialty){ $('#err').textContent='Topic and specialty are required.'; $('#err').classList.remove('d-none'); return; }

    $('#ai-overlay').classList.add('show'); $('#go').disabled = true;
    try {
        const res = await fetch('{{ route('ai.script.generate') }}', {
            method:'POST',
            headers:{'Content-Type':'application/json','X-CSRF-TOKEN':CSRF,'Accept':'application/json'},
            body: JSON.stringify({
                client_id: $('#client_id').value || null,
                topic, specialty,
                duration: durationSeconds(),
                platform: $('#platform').value,
            }),
        });
        const j = await res.json();
        if(!j.success){ throw new Error(j.error || 'Generation failed.'); }
        render(j.data);
    } catch(e) {
        $('#err').textContent = e.message; $('#err').classList.remove('d-none');
    } finally {
        $('#ai-overlay').classList.remove('show'); $('#go').disabled = false;
    }
});

function render(d){
    window._scriptData = d;
    $('#empty').classList.add('d-none');
    const r = $('#result'); r.classList.remove('d-none');
    const body = (d.body||[]).map(b => `
        <div class="beat-row mb-2">
            <div class="d-flex justify-content-between"><span class="pill">Beat ${esc(b.beat)}</span></div>
            <div class="mt-2"><span class="script-label">Spoken</span><div>${esc(b.spoken)}</div></div>
            ${b.on_screen_text?`<div class="mt-1"><span class="script-label">On-screen</span><div class="text-primary">${esc(b.on_screen_text)}</div></div>`:''}
            ${b.b_roll?`<div class="mt-1"><span class="script-label">B-roll</span><div class="text-muted small">${esc(b.b_roll)}</div></div>`:''}
        </div>`).join('');
    const shots = (d.shot_list||[]).map(s=>`<li>${esc(s)}</li>`).join('');

    const fullText = buildPlain(d);

    r.innerHTML = `
    <div class="ai-card p-3 p-md-4">
        <div class="d-flex justify-content-between align-items-start mb-3 gap-2 flex-wrap">
            <div>
                <h5 class="fw-bold mb-1">${esc(d.title||'Untitled script')}</h5>
                <span class="pill">${esc(fmtDur(d.duration_seconds))}</span>
                <span class="pill">${esc(d.platform||'')}</span>
            </div>
            <div class="d-flex gap-2 flex-shrink-0 flex-wrap">
                <button class="btn btn-sm btn-ai" data-copy="all"><i class="mdi mdi-content-copy me-1"></i>Copy all</button>
                <button class="btn btn-sm btn-light border" id="checkViralBtn"><i class="mdi mdi-rocket-launch-outline me-1"></i>Check viral predictor</button>
                <button class="btn btn-sm btn-light border" id="publishBtn"><i class="mdi mdi-send-check me-1"></i>Publish</button>
            </div>
        </div>

        <div class="hook-box p-3 mb-3">
            <span class="script-label">🪝 Hook (first 3 sec)</span>
            <div class="fw-semibold mt-1">${esc(d.hook?.spoken)}</div>
            ${d.hook?.on_screen_text?`<div class="text-primary mt-1">“${esc(d.hook.on_screen_text)}”</div>`:''}
            ${d.hook?.why_it_works?`<div class="text-muted small mt-1"><i class="mdi mdi-lightbulb-on-outline me-1"></i>${esc(d.hook.why_it_works)}</div>`:''}
        </div>

        <span class="script-label">Body</span>
        <div class="mt-2 mb-3">${body}</div>

        <div class="cta-box p-3 mb-3">
            <span class="script-label">📣 Call to action</span>
            <div class="fw-semibold mt-1">${esc(d.cta?.spoken)}</div>
            ${d.cta?.on_screen_text?`<div class="text-success mt-1">“${esc(d.cta.on_screen_text)}”</div>`:''}
        </div>

        <div class="row g-3">
            <div class="col-12 col-md-6">
                <span class="script-label">Caption</span>
                <div class="border rounded p-2 mt-1 small" style="white-space:pre-wrap">${esc(d.caption)}</div>
                <button class="btn btn-sm btn-light border mt-2" data-copy="caption"><i class="mdi mdi-content-copy me-1"></i>Copy caption</button>
            </div>
            <div class="col-12 col-md-6">
                <span class="script-label">Hashtags</span>
                <div class="border rounded p-2 mt-1 small text-primary" style="white-space:pre-wrap">${esc(d.hashtags)}</div>
                <button class="btn btn-sm btn-light border mt-2" data-copy="hashtags"><i class="mdi mdi-content-copy me-1"></i>Copy hashtags</button>
            </div>
        </div>

        ${d.suggested_audio?`<div class="mt-3"><span class="script-label">🎵 Suggested audio</span><div class="small">${esc(d.suggested_audio)}</div></div>`:''}
        ${shots?`<div class="mt-3"><span class="script-label">🎬 Shot list</span><ul class="small mb-0 mt-1">${shots}</ul></div>`:''}
    </div>`;

    // Wire up buttons via listeners (inline onclick broke whenever the script
    // text contained an apostrophe, which silently killed the copy buttons).
    const copyMap = { all: fullText, caption: d.caption || '', hashtags: d.hashtags || '' };
    r.querySelectorAll('[data-copy]').forEach(btn => {
        btn.addEventListener('click', () => copy(copyMap[btn.dataset.copy] || ''));
    });
    r.querySelector('#publishBtn')?.addEventListener('click', publishScript);
    r.querySelector('#checkViralBtn')?.addEventListener('click', () => checkViral(d));

    r.scrollIntoView({behavior:'smooth', block:'start'});
}

// Send the generated draft to the Viral Probability Predictor to score its virality.
function checkViral(d){
    try {
        sessionStorage.setItem('viralDraft', JSON.stringify({
            client_id: $('#client_id').value || null,
            format:    videoFormat(),
            topic:     $('#topic').value.trim(),
            hook:      d.hook?.spoken || '',
            caption:   d.caption || '',
            script:    buildPlain(d),
        }));
    } catch(e){}
    window.open('{{ route('ai.viral') }}?from=script', '_blank');
}

function buildPlain(d){
    let t = `${d.title||''} (${d.duration_seconds||''}s · ${d.platform||''})\n\n`;
    t += `HOOK: ${d.hook?.spoken||''}\n`;
    (d.body||[]).forEach(b => t += `BEAT ${b.beat}: ${b.spoken||''}\n`);
    t += `CTA: ${d.cta?.spoken||''}\n\n`;
    t += `CAPTION:\n${d.caption||''}\n\n${d.hashtags||''}`;
    return t;
}
</script>
@endpush
