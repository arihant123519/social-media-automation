@extends('layouts.app')

@section('title', 'Analytics Report')
@section('page_header', 'Social Analytics Report')
@section('page_icon', 'mdi mdi-chart-box-outline')

@section('breadcrumb')
    <li class="breadcrumb-item active">Analytics</li>
@endsection

@push('styles')
<style>
    .kpi { border-radius:1rem; }
    .kpi .kpi-icon { width:46px; height:46px; border-radius:.8rem; display:flex; align-items:center; justify-content:center; font-size:1.4rem; }
    .kpi h3 { font-weight:700; }
    @media print { .no-print { display:none !important; } .card { box-shadow:none !important; border:1px solid #ddd; } }
    .platform-card { border-radius:1rem; }
</style>
@endpush

@section('content')

<div class="d-flex flex-wrap gap-2 align-items-end justify-content-between mb-3 no-print">
    <form method="GET" class="d-flex flex-wrap gap-2 align-items-end">
        <div>
            <label class="form-label small text-muted mb-1">Client</label>
            <select name="client_id" class="form-select form-select-sm" style="min-width:190px" onchange="this.form.submit()">
                @foreach($clients as $c)
                    <option value="{{ $c->id }}" {{ $clientId == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="form-label small text-muted mb-1">Month</label>
            <input type="month" name="month" value="{{ $month }}" class="form-control form-control-sm" onchange="this.form.submit()">
        </div>
    </form>
    <button class="btn btn-sm btn-outline-secondary" onclick="window.print()"><i class="mdi mdi-printer me-1"></i>Print / PDF</button>
</div>

@if(!$report)
    <div class="alert alert-warning">No active client selected.</div>
@else
    <div class="d-flex align-items-center justify-content-between mb-3">
        <div>
            <h5 class="mb-0">{{ $report['client']['name'] }}</h5>
            <small class="text-muted text-capitalize">{{ $report['client']['industry'] }} · {{ $report['month'] }}</small>
        </div>
        <small class="text-muted d-none d-sm-block">Generated {{ $report['generated'] }}</small>
    </div>

    {{-- KPI ROW --}}
    @php
        $ig = $report['instagram']; $yt = $report['youtube']; $local = $report['local'];
    @endphp
    <div class="row g-3 mb-2">
        <div class="col-6 col-lg-3">
            <div class="card kpi h-100"><div class="card-body d-flex align-items-center gap-3">
                <div class="kpi-icon bg-primary-subtle text-primary"><i class="mdi mdi-send-check"></i></div>
                <div><small class="text-muted d-block">Posts published</small><h3 class="mb-0">{{ $local['published_total'] }}</h3></div>
            </div></div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card kpi h-100"><div class="card-body d-flex align-items-center gap-3">
                <div class="kpi-icon bg-danger-subtle text-danger"><i class="mdi mdi-instagram"></i></div>
                <div><small class="text-muted d-block">IG followers</small><h3 class="mb-0">{{ $ig['connected'] ? number_format($ig['followers']) : '—' }}</h3></div>
            </div></div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card kpi h-100"><div class="card-body d-flex align-items-center gap-3">
                <div class="kpi-icon bg-success-subtle text-success"><i class="mdi mdi-eye-outline"></i></div>
                <div><small class="text-muted d-block">IG reach (28d)</small><h3 class="mb-0">{{ $ig['connected'] ? number_format($ig['reach']) : '—' }}</h3></div>
            </div></div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card kpi h-100"><div class="card-body d-flex align-items-center gap-3">
                <div class="kpi-icon bg-warning-subtle text-warning"><i class="mdi mdi-heart-pulse"></i></div>
                <div><small class="text-muted d-block">Engagement rate</small><h3 class="mb-0">{{ $ig['connected'] ? $ig['engagement_rate'].'%' : '—' }}</h3></div>
            </div></div>
        </div>
    </div>

    <div class="row g-3">
        {{-- Trend chart --}}
        <div class="col-12 col-lg-8">
            <div class="card h-100"><div class="card-body">
                <h6 class="card-title mb-3">Publishing trend (6 months)</h6>
                <canvas id="trendChart" height="110"></canvas>
            </div></div>
        </div>

        {{-- Platform mix --}}
        <div class="col-12 col-lg-4">
            <div class="card h-100"><div class="card-body">
                <h6 class="card-title mb-3">By platform</h6>
                @foreach($local['by_platform'] as $name => $count)
                    @php $total = max(1, $local['published_total']); $pct = round($count/$total*100); @endphp
                    <div class="mb-2">
                        <div class="d-flex justify-content-between small"><span class="text-capitalize">{{ $name }}</span><span class="text-muted">{{ $count }}</span></div>
                        <div class="progress" style="height:6px"><div class="progress-bar" style="width:{{ $pct }}%"></div></div>
                    </div>
                @endforeach
                <hr>
                <div class="d-flex justify-content-between"><span class="text-muted small">Avg quality score</span><span class="fw-semibold">{{ $local['avg_score'] }}/100</span></div>
                @if($yt['connected'])
                    <div class="d-flex justify-content-between mt-1"><span class="text-muted small">YT subscribers</span><span class="fw-semibold">{{ number_format($yt['subscribers']) }}</span></div>
                @endif
            </div></div>
        </div>
    </div>

    {{-- Top posts --}}
    <div class="row g-3 mt-1">
        <div class="col-12 col-lg-6">
            <div class="card h-100"><div class="card-body">
                <h6 class="card-title mb-3"><i class="mdi mdi-instagram text-danger me-1"></i>Top Instagram posts</h6>
                @if(!$ig['connected'])
                    <p class="text-muted small mb-0"><i class="mdi mdi-link-off me-1"></i>{{ $ig['reason'] }}</p>
                @elseif(empty($ig['top_posts']) || count($ig['top_posts']) === 0)
                    <p class="text-muted small mb-0">No Instagram posts in this month.</p>
                @else
                    @foreach($ig['top_posts'] as $p)
                        <div class="d-flex align-items-center justify-content-between border-bottom py-2">
                            <span class="text-truncate me-2" style="max-width:60%">{{ $p['caption'] ?: '(no caption)' }}</span>
                            <span class="small text-muted text-nowrap">
                                <i class="mdi mdi-heart text-danger"></i> {{ number_format($p['likes']) }}
                                <i class="mdi mdi-comment-outline ms-2"></i> {{ number_format($p['comments']) }}
                                @if($p['url'])<a href="{{ $p['url'] }}" target="_blank" class="ms-2"><i class="mdi mdi-open-in-new"></i></a>@endif
                            </span>
                        </div>
                    @endforeach
                @endif
            </div></div>
        </div>

        <div class="col-12 col-lg-6">
            <div class="card h-100"><div class="card-body">
                <h6 class="card-title mb-3"><i class="mdi mdi-trophy-outline text-warning me-1"></i>Top scored posts (all platforms)</h6>
                @forelse($local['top_posts'] as $p)
                    <div class="d-flex align-items-center justify-content-between border-bottom py-2">
                        <span class="text-capitalize">#{{ $p['id'] }} · {{ $p['platform'] }} <span class="text-muted small">({{ $p['date'] }})</span></span>
                        <span class="small">
                            <span class="badge bg-success-subtle text-success">{{ $p['score'] }}/100</span>
                            @if($p['url'])<a href="{{ $p['url'] }}" target="_blank" class="ms-2"><i class="mdi mdi-open-in-new"></i></a>@endif
                        </span>
                    </div>
                @empty
                    <p class="text-muted small mb-0">No published posts this month.</p>
                @endforelse
            </div></div>
        </div>
    </div>
@endif

@endsection

@push('scripts')
@if($report)
<script src="{{ asset('assets/vendors/chart.js/chart.umd.js') }}"></script>
<script>
const TREND = @json($report['local']['trend']);
const ctx = document.getElementById('trendChart');
if (ctx && window.Chart) {
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: TREND.map(t => t.label),
            datasets: [{
                label: 'Posts published',
                data: TREND.map(t => t.count),
                borderColor: '#1A4A7A',
                backgroundColor: 'rgba(26,74,122,.12)',
                fill: true, tension: .35, pointRadius: 4, pointBackgroundColor: '#1A4A7A',
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: true,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
        }
    });
}
</script>
@endif
@endpush
