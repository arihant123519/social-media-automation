@extends('layouts.app')

@section('title', 'Content Health Scorecard')
@section('page_header', 'Content Health Scorecard')
@section('page_icon', 'mdi mdi-clipboard-pulse-outline')

@section('breadcrumb')
    <li class="breadcrumb-item active">Scorecard</li>
@endsection

@push('styles')
<style>
    .sc-kpi { border-radius:1rem; height:100%; }
    .sc-kpi .kpi-icon { width:44px; height:44px; border-radius:.75rem; display:flex; align-items:center; justify-content:center; font-size:1.35rem; }
    .sc-val { font-weight:700; font-size:1.6rem; line-height:1.1; }
    .trend { font-weight:600; font-size:.8rem; border-radius:2rem; padding:.15rem .55rem; display:inline-flex; align-items:center; gap:.2rem; }
    .trend.up   { background:rgba(16,185,129,.12); color:#059669; }
    .trend.down { background:rgba(239,68,68,.12); color:#dc2626; }
    .trend.flat { background:rgba(100,116,139,.12); color:#475569; }
    .trend.bad  { background:rgba(239,68,68,.12); color:#dc2626; }
    .sc-comment { font-size:.82rem; color:#475569; min-height:2.4em; }
    @media print { .no-print { display:none !important; } .card { box-shadow:none !important; border:1px solid #ddd; } body { background:#fff; } }
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
    <div class="d-flex gap-2">
        <a href="{{ request()->fullUrlWithQuery(['fresh' => 1]) }}" class="btn btn-sm btn-outline-primary" title="Bypass the 30-min cache and re-pull live data"><i class="mdi mdi-refresh me-1"></i>Refresh data</a>
        <button class="btn btn-sm btn-outline-secondary" onclick="window.print()"><i class="mdi mdi-printer me-1"></i>Print / PDF</button>
    </div>
</div>

@if(!$card)
    <div class="alert alert-warning">No active client selected.</div>
@else
    <div class="card">
        <div class="card-body">
            <div class="d-flex align-items-start justify-content-between flex-wrap gap-2 mb-1">
                <div>
                    <h4 class="mb-0">{{ $card['client']['name'] }}</h4>
                    <small class="text-muted text-capitalize">{{ $card['client']['industry'] ?: 'Client' }} · Content Health · {{ $card['month'] }}</small>
                </div>
                <div class="text-end">
                    <div class="small text-muted">Generated {{ $card['generated'] }}</div>
                    <div class="mt-1">
                        @foreach($card['live'] as $plat => $on)
                            <span class="badge {{ $on ? 'bg-success-subtle text-success' : 'bg-light text-muted' }} text-capitalize">
                                <i class="mdi {{ $on ? 'mdi-check-circle' : 'mdi-link-off' }} me-1"></i>{{ $plat }}
                            </span>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="alert alert-primary py-2 px-3 mt-2 mb-3 d-flex align-items-center">
                <i class="mdi mdi-lightbulb-on-outline me-2 fs-5"></i>
                <span class="fw-medium">{{ $card['summary'] }}</span>
            </div>

            <div class="row g-3">
                @foreach($card['kpis'] as $k)
                    @php
                        $dir = $k['direction'];
                        $cls = $dir === 'flat' ? 'flat' : ($k['good'] === false ? 'bad' : $dir);
                        $arrow = $dir === 'up' ? 'mdi-arrow-up-bold' : ($dir === 'down' ? 'mdi-arrow-down-bold' : 'mdi-minus');
                    @endphp
                    <div class="col-12 col-sm-6 col-lg-4">
                        <div class="card sc-kpi border">
                            <div class="card-body">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="kpi-icon bg-primary-subtle text-primary"><i class="mdi {{ $k['icon'] }}"></i></div>
                                        <span class="text-muted small">{{ $k['label'] }}</span>
                                    </div>
                                    @if($k['available'] && $k['delta'] !== null)
                                        <span class="trend {{ $cls }}">
                                            <i class="mdi {{ $arrow }}"></i>{{ ($k['delta'] >= 0 ? '+' : '') . $k['delta'] }}%
                                        </span>
                                    @elseif($k['available'])
                                        <span class="trend flat"><i class="mdi mdi-minus"></i>new</span>
                                    @endif
                                </div>
                                <div class="sc-val">
                                    @if($k['available'])
                                        {{ is_float($k['current']) ? rtrim(rtrim(number_format($k['current'],1),'0'),'.') : number_format((int)$k['current']) }}{{ $k['suffix'] }}
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </div>
                                @if($k['available'])
                                    <div class="text-muted small mb-2">vs {{ is_float($k['previous']) ? rtrim(rtrim(number_format($k['previous'],1),'0'),'.') : number_format((int)$k['previous']) }}{{ $k['suffix'] }} last month</div>
                                @else
                                    <div class="text-muted small mb-2">Not tracked yet</div>
                                @endif
                                <div class="sc-comment border-top pt-2">
                                    <i class="mdi mdi-robot-happy-outline text-primary me-1"></i>{{ $k['comment'] }}
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <p class="text-muted small mt-3 mb-0">
                <i class="mdi mdi-information-outline me-1"></i>
                Reach / Views / Saves / Shares populate live once Instagram is connected for this client. Quality score and cadence always reflect published activity.
            </p>
        </div>
    </div>

    {{-- ─── Per-post breakdown for the month ─── --}}
    <div class="card mt-3">
        <div class="card-body pb-0">
            <h6 class="card-title mb-0">
                <i class="mdi mdi-format-list-bulleted-square text-primary me-1"></i>
                Posts this month <span class="text-muted fw-normal">({{ count($card['posts']) }})</span>
            </h6>
        </div>
        @if(empty($card['posts']))
            <div class="card-body pt-2"><p class="text-muted small mb-0">No posts published in {{ $card['month'] }}.</p></div>
        @else
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Content</th>
                        <th>Format</th>
                        <th class="text-end">Reach</th>
                        <th class="text-end">Views</th>
                        <th class="text-end">Saves</th>
                        <th class="text-end">Shares</th>
                        <th class="text-end">Likes</th>
                        <th class="text-end">Comments</th>
                        <th class="text-end">Eng.</th>
                        <th class="text-end">Score</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($card['posts'] as $p)
                        @php
                            $platIco = ['instagram'=>['mdi-instagram','#e1306c'],'youtube'=>['mdi-youtube','#ff0000'],'facebook'=>['mdi-facebook','#1877f2'],'linkedin'=>['mdi-linkedin','#0a66c2']][$p['platform']] ?? ['mdi-web','#64748b'];
                        @endphp
                        <tr>
                            <td style="min-width:230px">
                                <div class="d-flex align-items-center gap-2">
                                    <span style="width:28px;height:28px;border-radius:.5rem;display:inline-flex;align-items:center;justify-content:center;background:{{ $platIco[1] }}1a;color:{{ $platIco[1] }}"><i class="mdi {{ $platIco[0] }}"></i></span>
                                    <div class="text-truncate" style="max-width:240px">
                                        <div class="fw-medium text-truncate">{{ $p['title'] }}</div>
                                        <div class="small text-muted">{{ $p['published_at']->format('d M Y') }} · {{ ucfirst($p['platform']) }} {{ $p['live'] ? '' : '· local' }}</div>
                                    </div>
                                </div>
                            </td>
                            <td><span class="badge" style="background:{{ $p['format_color'] }};color:#fff">{{ $p['format_label'] }}</span></td>
                            <td class="text-end">{{ is_numeric($p['reach']) ? number_format($p['reach']) : '—' }}</td>
                            <td class="text-end">{{ is_numeric($p['views']) ? number_format($p['views']) : '—' }}</td>
                            <td class="text-end">{{ is_numeric($p['saves']) ? number_format($p['saves']) : '—' }}</td>
                            <td class="text-end">{{ is_numeric($p['shares']) ? number_format($p['shares']) : '—' }}</td>
                            <td class="text-end">{{ is_numeric($p['likes']) ? number_format($p['likes']) : '—' }}</td>
                            <td class="text-end">{{ is_numeric($p['comments']) ? number_format($p['comments']) : '—' }}</td>
                            <td class="text-end">{{ is_numeric($p['engagement']) ? number_format($p['engagement']) : '—' }}</td>
                            <td class="text-end">@if($p['score'] !== null)<span class="badge bg-success-subtle text-success">{{ $p['score'] }}</span>@else — @endif</td>
                            <td class="text-end">@if($p['url'])<a href="{{ $p['url'] }}" target="_blank" class="text-muted"><i class="mdi mdi-open-in-new"></i></a>@endif</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
@endif

@endsection