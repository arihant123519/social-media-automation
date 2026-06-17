@extends('layouts.app')

@section('title', 'Dashboard')
@section('page_header', 'Dashboard')
@section('page_icon', 'mdi mdi-view-dashboard-outline')

@section('breadcrumb')
    <li class="breadcrumb-item active" aria-current="page">Overview</li>
@endsection

@push('styles')
<style>
    :root { --r:16px; }
    .dash { --gap:16px; }

    /* KPI cards */
    .kpi { background:#fff; border:1px solid #eef0f4; border-radius:18px; padding:18px 20px; position:relative; overflow:hidden; height:100%; box-shadow:0 1px 3px rgba(15,23,42,.04); transition:transform .18s ease, box-shadow .18s ease; }
    .kpi:hover { transform:translateY(-3px); box-shadow:0 14px 30px -12px rgba(15,23,42,.20); }
    .kpi::after { content:''; position:absolute; right:-28px; top:-28px; width:96px; height:96px; border-radius:50%; background:linear-gradient(135deg,var(--c1,#4f46e5),var(--c2,#7c3aed)); opacity:.07; }
    .kpi-head { display:flex; align-items:flex-start; justify-content:space-between; gap:10px; margin-bottom:12px; }
    .kpi .ic { width:46px; height:46px; border-radius:13px; display:flex; align-items:center; justify-content:center; font-size:22px; color:#fff; flex-shrink:0; background:linear-gradient(135deg,var(--c1,#4f46e5),var(--c2,#7c3aed)); box-shadow:0 8px 18px -6px var(--c2,#7c3aed); }
    .kpi .l { font-size:11.5px; color:#64748b; font-weight:700; text-transform:uppercase; letter-spacing:.05em; padding-top:3px; }
    .kpi .v { font-size:34px; font-weight:800; line-height:1; color:#0f172a; letter-spacing:-1px; position:relative; z-index:1; }
    .kpi .sub { font-size:11.5px; color:#94a3b8; margin-top:8px; font-weight:500; }

    /* Section cards */
    .panel { background:#fff; border:1px solid #eef0f4; border-radius:var(--r); box-shadow:0 1px 3px rgba(15,23,42,.04); }
    .panel-h { padding:15px 20px; border-bottom:1px solid #f1f5f9; font-weight:700; font-size:14px; color:#0f172a; display:flex; align-items:center; gap:8px; }
    .panel-b { padding:18px 20px; }
    .mini { display:flex; align-items:center; gap:10px; padding:9px 0; }
    .mini:not(:last-child){ border-bottom:1px dashed #eef0f4; }
    .mini .dot { width:10px; height:10px; border-radius:3px; flex-shrink:0; }
    .mini .mn { font-size:13px; color:#475569; }
    .mini .mv { margin-left:auto; font-weight:700; font-size:14px; color:#0f172a; }

    /* Platforms pill */
    .plat-pill { display:inline-flex; align-items:center; gap:6px; background:#f8fafc; border:1px solid #eef0f4; border-radius:10px; padding:7px 12px; font-size:13px; font-weight:600; color:#334155; margin:3px; }

    /* Schedule sidebar */
    .sched { background:#fff; border:1px solid #eef0f4; border-radius:var(--r); position:sticky; top:16px; overflow:hidden; box-shadow:0 1px 3px rgba(15,23,42,.04); }
    .sched-h { padding:16px 18px; background:linear-gradient(135deg,#4f46e5,#7c3aed); color:#fff; }
    .sched-h h6 { margin:0; font-size:14px; font-weight:700; display:flex; align-items:center; gap:7px; }
    .sched-h .d { font-size:12px; margin-top:2px; color:#bbf7d0; font-weight:600; }
    .sched-b { padding:6px 0; max-height:540px; overflow-y:auto; }
    .sched-c { padding:11px 18px; }
    .sched-c:not(:last-child){ border-bottom:1px solid #f6f7f9; }
    .sched-cn { display:flex; align-items:center; gap:8px; margin-bottom:7px; }
    .sched-av { width:26px; height:26px; border-radius:8px; background:#eef2ff; color:#4f46e5; display:inline-flex; align-items:center; justify-content:center; font-size:12px; font-weight:800; }
    .sched-p { display:flex; align-items:center; gap:7px; padding:4px 0 4px 34px; font-size:12.5px; color:#475569; }
    .sbadge { margin-left:auto; font-size:9px; padding:2px 8px; border-radius:10px; font-weight:700; white-space:nowrap; }
    .sched-empty { text-align:center; color:#94a3b8; padding:38px 16px; font-size:13px; }

    .eyebrow { font-size:11px; font-weight:700; color:#94a3b8; text-transform:uppercase; letter-spacing:.06em; margin:2px 0 10px; }

    /* ── Unscheduled Posts (client-tabbed) ── */
    .us { background:#fff; border:1px solid #eef0f4; border-radius:var(--r); overflow:hidden; box-shadow:0 1px 3px rgba(15,23,42,.04); }
    .us-h { padding:16px 20px; display:flex; align-items:center; gap:10px; flex-wrap:wrap; border-bottom:1px solid #f1f5f9; }
    .us-h .t { font-weight:700; font-size:14px; color:#0f172a; display:flex; align-items:center; gap:8px; }
    .us-h .badge-tot { font-size:11px; font-weight:800; padding:2px 9px; border-radius:10px; background:#fef3c7; color:#92400e; }
    .us-h .sub { font-size:12px; color:#94a3b8; font-weight:500; }

    /* Client tabs */
    .us-tabs { display:flex; gap:8px; padding:13px 16px; overflow-x:auto; background:#fafbfc; border-bottom:1px solid #f1f5f9; scrollbar-width:thin; }
    .us-tabs::-webkit-scrollbar { height:6px; }
    .us-tabs::-webkit-scrollbar-thumb { background:#e2e8f0; border-radius:3px; }
    .us-tab { flex:0 0 auto; display:flex; align-items:center; gap:9px; padding:7px 14px 7px 7px; border:1px solid #e7eaf0; border-radius:999px; background:#fff; cursor:pointer; transition:all .15s; }
    .us-tab:hover { border-color:#c7d2fe; background:#f5f7ff; }
    .us-tab.active { border-color:#4f46e5; background:#eef2ff; box-shadow:0 2px 8px rgba(79,70,229,.15); }
    .us-tab .av { width:30px; height:30px; border-radius:50%; background:linear-gradient(135deg,#6366f1,#8b5cf6); color:#fff; display:flex; align-items:center; justify-content:center; font-size:13px; font-weight:800; flex-shrink:0; }
    .us-tab .nm { font-size:13px; font-weight:700; color:#334155; white-space:nowrap; }
    .us-tab.active .nm { color:#4338ca; }
    .us-tab .ct { font-size:10.5px; font-weight:800; min-width:20px; text-align:center; padding:1px 7px; border-radius:9px; background:#eef2ff; color:#6366f1; }
    .us-tab.active .ct { background:#4f46e5; color:#fff; }

    /* Cards */
    .us-body { padding:16px 20px; }
    .us-pane { display:none; animation:usFade .25s ease; }
    .us-pane.active { display:block; }
    @keyframes usFade { from { opacity:0; transform:translateY(4px); } to { opacity:1; transform:none; } }
    .us-cards { display:grid; grid-template-columns:repeat(auto-fill,minmax(250px,1fr)); gap:12px; }
    .us-card { display:flex; align-items:center; gap:11px; padding:11px 13px; border:1px solid #eef0f4; border-radius:12px; background:#fff; text-decoration:none; transition:all .15s; position:relative; overflow:hidden; }
    .us-card::before { content:''; position:absolute; left:0; top:0; bottom:0; width:3px; background:var(--pc,#94a3b8); }
    .us-card:hover { border-color:#c7d2fe; box-shadow:0 8px 20px rgba(15,23,42,.09); transform:translateY(-2px); }
    .us-card .ic { width:36px; height:36px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:18px; flex-shrink:0; background:var(--icbg,#f1f5f9); color:var(--pc,#475569); }
    .us-card .main { min-width:0; flex:1; }
    .us-card .ty { font-size:13px; font-weight:700; color:#0f172a; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .us-card .kw { font-size:11.5px; color:#94a3b8; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .us-card .st { font-size:9.5px; font-weight:700; padding:3px 9px; border-radius:8px; white-space:nowrap; flex-shrink:0; }
    .us-card .chev { color:#cbd5e1; font-size:18px; flex-shrink:0; transition:.15s; }
    .us-card:hover .chev { color:#6366f1; transform:translateX(2px); }
    .us-empty { text-align:center; color:#94a3b8; padding:40px 16px; font-size:13px; }
</style>
@endpush

@section('content')
<div class="container-fluid py-2 dash">

    {{-- ── KPI strip ── --}}
    <div class="row g-3 mb-1">
        @foreach([
            ['Total Clients', $stats['clients_total'], 'mdi-account-group', '#6366f1', '#8b5cf6', $stats['clients_active'].' active · '.$stats['clients_inactive'].' inactive'],
            ['Total Posts', $stats['posts_total'], 'mdi-file-document-multiple', '#0891b2', '#06b6d4', 'across all clients'],
            ['Published', $stats['posts_published'], 'mdi-check-decagram', '#16a34a', '#22c55e', 'live posts'],
            ['Scheduled', $stats['posts_scheduled'], 'mdi-clock-time-four', '#d97706', '#f59e0b', 'queued to publish'],
        ] as [$label,$val,$icon,$c1,$c2,$sub])
        <div class="col-6 col-xl-3">
            <div class="kpi" style="--c1:{{ $c1 }};--c2:{{ $c2 }}">
                <div class="kpi-head">
                    <span class="l">{{ $label }}</span>
                    <span class="ic"><i class="mdi {{ $icon }}"></i></span>
                </div>
                <div class="v">{{ $val }}</div>
                <div class="sub">{{ $sub }}</div>
            </div>
        </div>
        @endforeach
    </div>

    <div class="row g-3 mt-1">

        {{-- ── Left: charts + breakdown ── --}}
        <div class="col-lg-8">
            <div class="row g-3">

                {{-- Posts donut --}}
                <div class="col-md-6">
                    <div class="panel h-100">
                        <div class="panel-h"><i class="mdi mdi-chart-donut text-primary"></i> Posts by Status</div>
                        <div class="panel-b">
                            <div style="position:relative;height:210px"><canvas id="postsDonut"></canvas></div>
                        </div>
                    </div>
                </div>

                {{-- Clients donut --}}
                <div class="col-md-6">
                    <div class="panel h-100">
                        <div class="panel-h"><i class="mdi mdi-chart-donut" style="color:#7c3aed"></i> Clients</div>
                        <div class="panel-b">
                            <div style="position:relative;height:210px"><canvas id="clientsDonut"></canvas></div>
                        </div>
                    </div>
                </div>

                {{-- Posts breakdown list --}}
                <div class="col-md-6">
                    <div class="panel h-100">
                        <div class="panel-h"><i class="mdi mdi-format-list-bulleted"></i> Posts Breakdown</div>
                        <div class="panel-b">
                            @php
                                $failed = max(0, $stats['posts_total'] - $stats['posts_published'] - $stats['posts_scheduled'] - $stats['posts_pending']);
                            @endphp
                            @foreach([
                                ['Published', $stats['posts_published'], '#16a34a'],
                                ['Scheduled', $stats['posts_scheduled'], '#2563eb'],
                                ['Pending', $stats['posts_pending'], '#eab308'],
                                ['Failed', $failed, '#dc2626'],
                            ] as [$n,$v,$c])
                            <div class="mini">
                                <span class="dot" style="background:{{ $c }}"></span>
                                <span class="mn">{{ $n }}</span>
                                <span class="mv">{{ $v }}</span>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- Platforms / scopes --}}
                <div class="col-md-6">
                    <div class="panel h-100">
                        <div class="panel-h"><i class="mdi mdi-share-variant" style="color:#0891b2"></i> Scopes (Platforms)</div>
                        <div class="panel-b">
                            <div class="eyebrow">{{ $stats['platforms_count'] }} active platform{{ $stats['platforms_count']>1?'s':'' }}</div>
                            <div>
                                @foreach($stats['platforms'] as $plat)
                                    <span class="plat-pill">
                                        <i class="mdi {{ $plat==='YouTube'?'mdi-youtube text-danger':'mdi-instagram' }}" style="{{ $plat==='Instagram'?'color:#be185d':'' }}"></i>
                                        {{ $plat }}
                                    </span>
                                @endforeach
                                <span class="plat-pill" style="border-style:dashed;color:#94a3b8">
                                    <i class="mdi mdi-plus"></i> More soon
                                </span>
                            </div>
                            <div class="text-muted mt-3" style="font-size:12px">
                                <i class="mdi mdi-information-outline"></i> LinkedIn &amp; others can be added later.
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        {{-- ── Right: Today's Schedule ── --}}
        <div class="col-lg-4">
            <div class="sched">
                <div class="sched-h">
                    <h6><i class="mdi mdi-calendar-clock"></i> Today's Schedule</h6>
                    <div class="d">{{ now()->format('l, d M Y') }}</div>
                </div>
                <div class="sched-b">
                    @forelse($todaysPosts as $clientName => $posts)
                        <div class="sched-c">
                            <div class="sched-cn">
                                <span class="sched-av">{{ strtoupper(substr($clientName,0,1)) }}</span>
                                <strong style="font-size:13px;color:#0f172a">{{ $clientName }}</strong>
                                <span class="text-muted" style="font-size:11px;margin-left:auto">{{ $posts->count() }} post{{ $posts->count()>1?'s':'' }}</span>
                            </div>
                            @foreach($posts as $p)
                                @php
                                    $typeLabel = ['long_video'=>'Long Video','short_video'=>'Short','reels'=>'Reel','photo'=>'Photo','story'=>'Story'][$p->post_type] ?? ucfirst($p->post_type);
                                    $ps = $p->publish_status ?: 'scheduled';
                                    $pmap = ['published'=>['#dcfce7','#166534','Published'],'scheduled'=>['#dbeafe','#1d4ed8','Scheduled'],'failed'=>['#fee2e2','#991b1b','Failed'],'dry_run'=>['#f3e8ff','#6b21a8','Dry-run']];
                                    [$bg,$fg,$lbl] = $pmap[$ps] ?? ['#fef9c3','#854d0e',ucfirst($ps)];
                                @endphp
                                <div class="sched-p">
                                    <i class="mdi {{ $p->scope==0 ? 'mdi-youtube text-danger' : 'mdi-instagram' }}" style="{{ $p->scope==1?'color:#be185d':'' }}"></i>
                                    <span style="font-weight:600;color:#334155">{{ $typeLabel }}</span>
                                    <span class="text-muted">{{ optional($p->scheduled_publish_at)->format('g:i A') }}</span>
                                    <span class="sbadge" style="background:{{ $bg }};color:{{ $fg }}">{{ $lbl }}</span>
                                    @if($p->external_url)
                                        <a href="{{ $p->external_url }}" target="_blank" rel="noopener" class="text-success"><i class="mdi mdi-open-in-new"></i></a>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @empty
                        <div class="sched-empty">
                            <i class="mdi mdi-calendar-blank" style="font-size:32px;display:block;margin-bottom:8px;opacity:.4"></i>
                            No posts scheduled<br>for today.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

    </div>

    {{-- ── Unscheduled Posts (client-tabbed) ── --}}
    @php $unCount = $unscheduledPosts->flatten()->count(); @endphp
    <div class="row g-3 mt-1">
        <div class="col-12">
            <div class="us">
                <div class="us-h">
                    <span class="t"><i class="mdi mdi-calendar-question" style="color:#d97706"></i> Unscheduled Posts</span>
                    <span class="badge-tot">{{ $unCount }}</span>
                    <span class="sub d-none d-sm-inline">in the pipeline — not yet given a publish time</span>
                </div>

                @if($unCount)
                    {{-- Dynamic per-client tabs --}}
                    <div class="us-tabs" role="tablist">
                        @foreach($unscheduledPosts as $clientName => $posts)
                            <button type="button" class="us-tab {{ $loop->first ? 'active' : '' }}" data-tab="{{ $loop->index }}" role="tab">
                                <span class="av">{{ strtoupper(substr($clientName, 0, 1)) }}</span>
                                <span class="nm">{{ $clientName }}</span>
                                <span class="ct">{{ $posts->count() }}</span>
                            </button>
                        @endforeach
                    </div>

                    <div class="us-body">
                        @foreach($unscheduledPosts as $clientName => $posts)
                            <div class="us-pane {{ $loop->first ? 'active' : '' }}" data-pane="{{ $loop->index }}" role="tabpanel">
                                <div class="us-cards">
                                    @foreach($posts as $p)
                                        @php
                                            $typeLabel = ['long_video'=>'Long Video','short_video'=>'Short','reels'=>'Reel','photo'=>'Photo','story'=>'Story'][$p->post_type] ?? ucfirst(str_replace('_', ' ', (string) $p->post_type));
                                            $isYt = (int) $p->scope === 0;
                                            $pc   = $isYt ? '#dc2626' : '#be185d';
                                            $icbg = $isYt ? '#fef2f2' : '#fdf2f8';
                                            $ready = $p->publish_status === 'ready';
                                            [$sbg, $sfg, $slbl] = $ready ? ['#dcfce7', '#166534', 'Ready'] : ['#fef9c3', '#854d0e', 'Not ready'];
                                        @endphp
                                        <a class="us-card" style="--pc:{{ $pc }};--icbg:{{ $icbg }}"
                                           href="{{ route('posts.drafts') }}#post-{{ $p->id }}"
                                           title="Open “{{ $typeLabel }}” in Drafts">
                                            <span class="ic"><i class="mdi {{ $isYt ? 'mdi-youtube' : 'mdi-instagram' }}"></i></span>
                                            <span class="main">
                                                <div class="ty">{{ $typeLabel }}</div>
                                                <div class="kw">{{ $p->keyword ?: '—' }}</div>
                                            </span>
                                            <span class="st" style="background:{{ $sbg }};color:{{ $sfg }}">{{ $slbl }}</span>
                                            <i class="mdi mdi-chevron-right chev"></i>
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="us-empty">
                        <i class="mdi mdi-calendar-check" style="font-size:34px;display:block;margin-bottom:10px;opacity:.4"></i>
                        Nothing waiting to be scheduled. All caught up!
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Unscheduled Posts — dynamic client tab switching
    document.querySelectorAll('.us-tab').forEach(function (tab) {
        tab.addEventListener('click', function () {
            var i = tab.dataset.tab;
            document.querySelectorAll('.us-tab').forEach(function (t) {
                t.classList.toggle('active', t.dataset.tab === i);
            });
            document.querySelectorAll('.us-pane').forEach(function (p) {
                p.classList.toggle('active', p.dataset.pane === i);
            });
        });
    });

    if (typeof Chart === 'undefined') return;

    // Plugin: draw total in the middle of a doughnut
    const centerText = {
        id: 'centerText',
        afterDraw(chart) {
            const { ctx } = chart;
            const total = chart.data.datasets[0].data.reduce((a, b) => a + b, 0);
            const meta = chart.getDatasetMeta(0);
            if (!meta.data.length) return;
            const { x, y } = meta.data[0];
            ctx.save();
            ctx.textAlign = 'center'; ctx.textBaseline = 'middle';
            ctx.font = '700 26px sans-serif'; ctx.fillStyle = '#0f172a';
            ctx.fillText(total, x, y - 6);
            ctx.font = '500 11px sans-serif'; ctx.fillStyle = '#94a3b8';
            ctx.fillText('Total', x, y + 14);
            ctx.restore();
        }
    };

    const opts = {
        responsive: true, maintainAspectRatio: false, cutout: '68%',
        plugins: { legend: { position: 'bottom', labels: { boxWidth: 11, font: { size: 11 }, padding: 12, usePointStyle: true } } }
    };

    const pCtx = document.getElementById('postsDonut');
    if (pCtx) new Chart(pCtx, {
        type: 'doughnut',
        data: {
            labels: ['Published', 'Scheduled', 'Pending', 'Failed'],
            datasets: [{
                data: [{{ $stats['posts_published'] }}, {{ $stats['posts_scheduled'] }}, {{ $stats['posts_pending'] }}, {{ max(0, $stats['posts_total'] - $stats['posts_published'] - $stats['posts_scheduled'] - $stats['posts_pending']) }}],
                backgroundColor: ['#16a34a', '#2563eb', '#eab308', '#dc2626'], borderWidth: 3, borderColor: '#fff'
            }]
        },
        options: opts, plugins: [centerText]
    });

    const cCtx = document.getElementById('clientsDonut');
    if (cCtx) new Chart(cCtx, {
        type: 'doughnut',
        data: {
            labels: ['Active', 'Inactive'],
            datasets: [{
                data: [{{ $stats['clients_active'] }}, {{ $stats['clients_inactive'] }}],
                backgroundColor: ['#4f46e5', '#cbd5e1'], borderWidth: 3, borderColor: '#fff'
            }]
        },
        options: opts, plugins: [centerText]
    });
});
</script>
@endpush
