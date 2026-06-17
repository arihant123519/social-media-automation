@extends('layouts.app')

@section('title', 'Viral Probability Predictor')
@section('page_header', 'Viral Probability Predictor')
@section('page_icon', 'mdi mdi-rocket-launch-outline')

@section('breadcrumb')
    <li class="breadcrumb-item active">Viral Predictor</li>
@endsection

@push('styles')
<style>
    .ai-card { background:#fff; border:1px solid rgba(0,0,0,.07); border-radius:.9rem; box-shadow:0 1px 2px rgba(0,0,0,.04); }
    .btn-ai { background:linear-gradient(135deg,#3b82f6,#6366f1); border:none; color:#fff; font-weight:600; box-shadow:0 8px 18px -8px rgba(59,130,246,.7); }
    .btn-ai:hover { color:#fff; filter:brightness(1.05); } .btn-ai:disabled { opacity:.8; }
    .lbl { font-size:.68rem; font-weight:700; text-transform:uppercase; letter-spacing:.4px; color:#94a3b8; }
    .gauge { width:150px; height:150px; border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto; }
    .gauge span { font-size:2.2rem; font-weight:800; color:#0f172a; }
    .bar { height:8px; border-radius:999px; background:#e2e8f0; overflow:hidden; }
    .bar > div { height:100%; background:linear-gradient(90deg,#6366f1,#3b82f6); }
    .verdict { font-size:.8rem; font-weight:700; border-radius:2rem; padding:.3rem .9rem; display:inline-flex; align-items:center; gap:.35rem; }
    .verdict.go { background:rgba(16,185,129,.14); color:#059669; }
    .verdict.improve { background:rgba(245,158,11,.16); color:#b45309; }
    .verdict.no_go { background:rgba(239,68,68,.14); color:#dc2626; }
    .kv { border-left:3px solid #818cf8; background:#f8fafc; border-radius:.5rem; padding:.6rem .85rem; }
    #ai-overlay { position:fixed; inset:0; z-index:1090; background:rgba(15,23,42,.55); backdrop-filter:blur(3px); display:none; align-items:center; justify-content:center; }
    #ai-overlay.show { display:flex; }
    .ai-spinner { width:56px; height:56px; border-radius:50%; border:5px solid #e0e7ff; border-top-color:#6366f1; margin:0 auto 1.1rem; animation:spin .8s linear infinite; }
    @keyframes spin { to { transform:rotate(360deg); } }
</style>
@endpush

@section('content')
<div class="row g-4">
    {{-- ─── Input ─── --}}
    <div class="col-12 col-xl-5">
        <div class="ai-card p-3 p-md-4 h-100">
            <h6 class="fw-bold mb-1"><i class="mdi mdi-flask-outline text-primary me-1"></i> Test a planned post</h6>
            <p class="text-muted small mb-3">Score a draft against this client's real top-performers before you publish.</p>

            <div id="err" class="alert alert-danger d-none py-2 small"></div>

            <div class="mb-3">
                <label class="lbl d-block mb-1">Client</label>
                <select id="client_id" class="form-select form-select-sm">
                    @foreach($clients as $c)
                        <option value="{{ $c->id }}">{{ $c->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="mb-3">
                <label class="lbl d-block mb-1">Format</label>
                <select id="format" class="form-select form-select-sm">
                    @foreach($formats as $key => $meta)
                        <option value="{{ $key }}">{{ $meta['label'] }}</option>
                    @endforeach
                </select>
            </div>

            <div class="mb-3">
                <label class="lbl d-block mb-1">Topic</label>
                <input id="topic" type="text" class="form-control form-control-sm" placeholder="e.g. 5 myths about teeth whitening">
            </div>

            <div class="mb-3">
                <label class="lbl d-block mb-1">Hook (first line / first 3 seconds)</label>
                <textarea id="hook" class="form-control form-control-sm" rows="2" placeholder="The opening line that stops the scroll…"></textarea>
            </div>

            <div class="mb-3">
                <label class="lbl d-block mb-1">Caption <span class="text-muted text-lowercase fw-normal">(optional)</span></label>
                <textarea id="caption" class="form-control form-control-sm" rows="4" placeholder="Full caption with CTA & hashtags…"></textarea>
            </div>

            <div class="mb-3">
                <label class="lbl d-block mb-1">Full script / content <span class="text-muted text-lowercase fw-normal">(optional)</span></label>
                <textarea id="script" class="form-control form-control-sm" rows="6" placeholder="Paste the entire video script, post copy or prompt here — we'll judge the whole thing for viral potential…"></textarea>
            </div>

            <button id="go" class="btn btn-ai w-100"><i class="mdi mdi-rocket-launch me-1"></i> Predict virality</button>
        </div>
    </div>

    {{-- ─── Result ─── --}}
    <div class="col-12 col-xl-7">
        <div id="empty" class="ai-card p-5 text-center text-muted h-100 d-flex flex-column justify-content-center">
            <i class="mdi mdi-chart-bell-curve-cumulative" style="font-size:3rem;color:#cbd5e1"></i>
            <p class="mt-2 mb-0">Fill in the draft and hit <strong>Predict virality</strong> to see its match score, predicted reach and a go / no-go call.</p>
        </div>

        <div id="result" class="d-none">
            <div class="ai-card p-3 p-md-4 mb-3">
                <div class="row align-items-center">
                    <div class="col-12 col-md-5 text-center mb-3 mb-md-0">
                        <div id="gauge" class="gauge"><span id="scoreNum">0</span></div>
                        <div class="mt-2"><span id="verdict" class="verdict"></span></div>
                    </div>
                    <div class="col-12 col-md-7">
                        <p id="verdictLine" class="fw-medium mb-3"></p>
                        <div class="kv mb-3">
                            <span class="lbl d-block mb-1"><i class="mdi mdi-eye-outline me-1"></i>Predicted reach</span>
                            <span id="reachRange" class="fs-5 fw-bold text-primary"></span>
                            <div id="reachBasis" class="small text-muted mt-1"></div>
                        </div>
                        <button id="publishBtn" class="btn btn-ai btn-sm"><i class="mdi mdi-cloud-upload-outline me-1"></i>Publish / upload video</button>
                    </div>
                </div>
            </div>

            <div class="ai-card p-3 p-md-4 mb-3">
                <h6 class="fw-bold mb-3"><i class="mdi mdi-format-list-checks text-primary me-1"></i> Score breakdown</h6>
                <div id="breakdown"></div>
            </div>

            <div class="ai-card p-3 p-md-4">
                <h6 class="fw-bold mb-2"><i class="mdi mdi-lightbulb-on-outline text-warning me-1"></i> Tweaks to raise the score</h6>
                <ul id="tweaks" class="mb-3 ps-3"></ul>
                <div id="rewriteWrap" class="kv d-none">
                    <span class="lbl d-block mb-1">Stronger hook suggestion</span>
                    <span id="rewrite"></span>
                </div>
                <p id="histNote" class="text-muted small mt-3 mb-0"></p>
            </div>
        </div>
    </div>
</div>

{{-- Loading overlay --}}
<div id="ai-overlay">
    <div class="text-center text-white">
        <div class="ai-spinner"></div>
        <div class="fw-semibold">Matching your draft to this client's viral DNA…</div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const CSRF = document.querySelector('meta[name="csrf-token"]').content;
    const $ = s => document.querySelector(s);
    const esc = s => (s==null?'':String(s)).replace(/[&<>"]/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]));

    // Prefill from a script handed off by the AI Video Script Generator.
    (function prefillFromScript(){
        let draft;
        try { draft = JSON.parse(sessionStorage.getItem('viralDraft') || 'null'); } catch(e){ return; }
        if(!draft) return;
        sessionStorage.removeItem('viralDraft');
        const setSel = (id, val) => { const el = $(id); if(el && val && [...el.options].some(o => o.value == val)) el.value = val; };
        const setVal = (id, val) => { const el = $(id); if(el && val != null) el.value = val; };
        setSel('#client_id', draft.client_id);
        setSel('#format', draft.format);
        setVal('#topic', draft.topic);
        setVal('#hook', draft.hook);
        setVal('#caption', draft.caption);
        setVal('#script', draft.script);
    })();

    $('#go')?.addEventListener('click', async () => {
        $('#err')?.classList.add('d-none');
        const topic = $('#topic')?.value.trim() ?? '';
        const hook  = $('#hook')?.value.trim() ?? '';
        if(!topic || !hook){
            const e = $('#err');
            if(e){ e.textContent='Topic and hook are required.'; e.classList.remove('d-none'); }
            return;
        }
        if(!topic || !hook){ $('#err').textContent='Topic and hook are required.'; $('#err').classList.remove('d-none'); return; }

        $('#ai-overlay').classList.add('show'); $('#go').disabled = true;
        try {
            const fd = new FormData();
            fd.append('client_id', $('#client_id').value);
            fd.append('format', $('#format').value);
            fd.append('topic', topic);
            fd.append('hook', hook);
            fd.append('caption', $('#caption').value.trim());
            fd.append('script', $('#script').value.trim());

            const res = await fetch('{{ route('ai.viral.predict') }}', {
                method:'POST', headers:{'X-CSRF-TOKEN':CSRF,'Accept':'application/json'}, body: fd,
            });
            const j = await res.json();
            if(!j.success){ throw new Error(j.error || 'Prediction failed.'); }
            render(j.data);
        } catch(e){ 
            console.error(e);
            $('#err').textContent=e.message; 
            $('#err').classList.remove('d-none'); 
        }
        finally { $('#ai-overlay').classList.remove('show'); $('#go').disabled=false; }
    });

    // Hand the evaluated draft off to the Post Creator to upload / publish the video.
    function publishToPost(){
        const draft = {
            source:    'viral',
            client_id: $('#client_id').value || null,
            post_type: ($('#format').value === 'short_video' ? 'short_video' : 'reels'),
            keyword:   $('#topic').value.trim(),
            caption:   $('#caption').value.trim() || $('#script').value.trim(),
            hashtags:  '',
        };
        try { sessionStorage.setItem('aiPublishDraft', JSON.stringify(draft)); } catch(e){}
        window.open('{{ route('Post.index') }}?from=ai', '_blank');
    }
    $('#publishBtn')?.addEventListener('click', publishToPost);

    function render(d){
        $('#empty').classList.add('d-none');
        $('#result').classList.remove('d-none');

        const score = Math.max(0, Math.min(100, parseInt(d.match_score) || 0));
        // colour gauge by score
        const col = score >= 70 ? '#10b981' : (score >= 45 ? '#f59e0b' : '#ef4444');
        const gauge = $('#gauge');
        gauge.style.background = `conic-gradient(${col} ${score*3.6}deg, #e2e8f0 0deg)`;
        gauge.style.padding = '10px';
        // Rebuild the inner gauge but KEEP id="scoreNum" so subsequent predictions
        // can find it again (previously it was dropped, so the 2nd run threw and
        // left the old result on screen).
        gauge.innerHTML = `<div style="background:#fff;width:100%;height:100%;border-radius:50%;display:flex;align-items:center;justify-content:center"><span id="scoreNum">${score}<small style="font-size:.9rem;color:#94a3b8">%</small></span></div>`;

        const verdict = (d.verdict || 'improve').toLowerCase();
        const vLabel = {go:'GO',improve:'IMPROVE',no_go:'NO-GO'}[verdict] || 'IMPROVE';
        const vIcon  = {go:'mdi-check-bold',improve:'mdi-tune',no_go:'mdi-close-thick'}[verdict] || 'mdi-tune';
        const vEl = $('#verdict'); vEl.className = 'verdict ' + verdict;
        vEl.innerHTML = `<i class="mdi ${vIcon}"></i>${vLabel}`;
        $('#verdictLine').textContent = d.verdict_line || '';

        const pr = d.predicted_reach || {};
        $('#reachRange').textContent = (pr.low!=null && pr.high!=null)
            ? `${Number(pr.low).toLocaleString()} – ${Number(pr.high).toLocaleString()}`
            : '—';
        $('#reachBasis').textContent = pr.basis || '';

        $('#breakdown').innerHTML = (d.breakdown||[]).map(b => {
            const pct = b.max ? Math.round((b.score/b.max)*100) : 0;
            return `<div class="mb-3">
                <div class="d-flex justify-content-between small mb-1">
                    <span class="fw-medium">${esc(b.label)}</span>
                    <span class="text-muted">${esc(b.score)}/${esc(b.max)}</span>
                </div>
                <div class="bar"><div style="width:${pct}%"></div></div>
                <div class="small text-muted mt-1">${esc(b.note)}</div>
            </div>`;
        }).join('');

        $('#tweaks').innerHTML = (d.tweaks||[]).map(t => `<li class="mb-1">${esc(t)}</li>`).join('') || '<li class="text-muted">No tweaks suggested.</li>';

        if(d.rewritten_hook){ $('#rewriteWrap').classList.remove('d-none'); $('#rewrite').textContent = d.rewritten_hook; }
        else { $('#rewriteWrap').classList.add('d-none'); }

        $('#histNote').innerHTML = d.has_history
            ? '<i class="mdi mdi-database-check me-1"></i>Scored against this client\'s real published history' + (d.live_reach ? ' (live reach data).' : ' (AI quality scores).')
            : '<i class="mdi mdi-information-outline me-1"></i>No published history yet — scored on niche best-practice and brand voice. Accuracy improves as this client publishes.';
    }
});
</script>
@endpush
