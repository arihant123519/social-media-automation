@extends('layouts.app')

@section('title', 'Best Time To Post')
@section('page_header', 'Best Time To Post Calculator')
@section('page_icon', 'mdi mdi-clock-time-four-outline')

@section('breadcrumb')
    <li class="breadcrumb-item active">Best Time</li>
@endsection

@push('styles')
<style>
    .heatmap { border-collapse:separate; border-spacing:3px; width:100%; }
    .heatmap th { font-size:.68rem; font-weight:600; color:#64748b; text-align:center; padding:2px; }
    .heatmap td.daylbl { font-size:.72rem; font-weight:600; color:#475569; text-align:right; padding-right:6px; white-space:nowrap; }
    .cell { width:100%; height:22px; border-radius:4px; background:#eef2f7; position:relative; cursor:default; }
    .cell.has { box-shadow:inset 0 0 0 1px rgba(0,0,0,.04); }
    .heat-wrap { overflow-x:auto; }
    .slot-pill { border:1px solid #e2e8f0; border-radius:.7rem; padding:.5rem .75rem; }
    .legend-box { width:16px; height:16px; border-radius:3px; display:inline-block; }
</style>
@endpush

@section('content')

<form method="GET" class="d-flex flex-wrap gap-2 align-items-end mb-3">
    <div>
        <label class="form-label small text-muted mb-1">Client</label>
        <select name="client_id" class="form-select form-select-sm" style="min-width:180px" onchange="this.form.submit()">
            @foreach($clients as $c)
                <option value="{{ $c->id }}" {{ $clientId == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="form-label small text-muted mb-1">Window</label>
        <select name="days" class="form-select form-select-sm" onchange="this.form.submit()">
            @foreach([30=>'30 days',60=>'60 days',90=>'90 days',180=>'6 months'] as $d=>$lbl)
                <option value="{{ $d }}" {{ $days == $d ? 'selected' : '' }}>{{ $lbl }}</option>
            @endforeach
        </select>
    </div>
    @if($data)
    <div>
        <label class="form-label small text-muted mb-1">Platform</label>
        <select name="platform" class="form-select form-select-sm text-capitalize" onchange="this.form.submit()">
            <option value="all" {{ $data['platform']==='all'?'selected':'' }}>All platforms</option>
            @foreach($data['platforms'] as $p)
                <option value="{{ $p }}" {{ $data['platform']===$p?'selected':'' }}>{{ ucfirst($p) }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="form-label small text-muted mb-1">Content type</label>
        <select name="format" class="form-select form-select-sm" onchange="this.form.submit()">
            <option value="all" {{ $data['format']==='all'?'selected':'' }}>All types</option>
            @foreach($data['formats'] as $key=>$lbl)
                <option value="{{ $key }}" {{ $data['format']===$key?'selected':'' }}>{{ $lbl }}</option>
            @endforeach
        </select>
    </div>
    <div class="ms-auto">
        <a href="{{ request()->fullUrlWithQuery(['fresh' => 1]) }}" class="btn btn-sm btn-outline-primary" title="Bypass the 30-min cache and re-pull live data"><i class="mdi mdi-refresh me-1"></i>Refresh data</a>
    </div>
    @endif
</form>

@if(!$data)
    <div class="alert alert-warning">No active client selected.</div>
@elseif($data['total_posts'] === 0)
    <div class="alert alert-info"><i class="mdi mdi-information-outline me-1"></i>No posts in this window/filter to analyse. Try a wider window or publish more content.</div>
@else
    {{-- Takeaways --}}
    <div class="row g-3 mb-3">
        <div class="col-12 col-lg-8">
            <div class="alert alert-primary mb-0 d-flex align-items-center h-100">
                <i class="mdi mdi-lightbulb-on-outline me-2 fs-4"></i>
                <span>
                    Based on <strong>{{ $data['total_posts'] }}</strong> posts, weighted by <strong>{{ $data['weight'] }}</strong>.
                    @if($data['best_day'] && $data['best_hour'])
                        Best day is <strong>{{ $data['best_day']['label'] }}</strong>, strongest hour is <strong>{{ $data['best_hour']['label'] }}</strong>.
                    @endif
                </span>
            </div>
        </div>
        <div class="col-12 col-lg-4">
            <div class="card border h-100"><div class="card-body py-2 d-flex align-items-center justify-content-between">
                <span class="small text-muted">Data source</span>
                <span class="badge {{ $data['live'] ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' }}">
                    <i class="mdi {{ $data['live'] ? 'mdi-access-point' : 'mdi-star-outline' }} me-1"></i>{{ $data['live'] ? 'Live reach' : 'AI quality score' }}
                </span>
            </div></div>
        </div>
    </div>

    <div class="row g-3">
        {{-- Heatmap --}}
        <div class="col-12 col-lg-9">
            <div class="card h-100"><div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="card-title mb-0">Performance heatmap — day × hour</h6>
                    <span class="small text-muted">
                        Low <span class="legend-box ms-1" style="background:#eef2f7"></span>
                        <span class="legend-box" style="background:#9bbcdf"></span>
                        <span class="legend-box" style="background:#1A4A7A"></span> High
                    </span>
                </div>
                <div class="heat-wrap">
                    <table class="heatmap">
                        <thead>
                            <tr>
                                <th></th>
                                @for($h=0;$h<24;$h++)
                                    <th>{{ $h % 3 === 0 ? ($h % 12 === 0 ? 12 : $h % 12) . ($h < 12 ? 'a' : 'p') : '' }}</th>
                                @endfor
                            </tr>
                        </thead>
                        <tbody>
                            @for($d=0;$d<7;$d++)
                                <tr>
                                    <td class="daylbl">{{ $data['days_label'][$d] }}</td>
                                    @for($h=0;$h<24;$h++)
                                        @php
                                            $cell = $data['cells'][$d][$h];
                                            $intensity = $data['max_avg'] > 0 ? $cell['avg'] / $data['max_avg'] : 0;
                                            // blend from #eef2f7 (low) to #1A4A7A (high)
                                            $r = round(238 + (26 - 238) * $intensity);
                                            $g = round(242 + (74 - 242) * $intensity);
                                            $b = round(247 + (122 - 247) * $intensity);
                                            $bg = $cell['count'] ? "rgb($r,$g,$b)" : '#f8fafc';
                                            $tip = $cell['count']
                                                ? sprintf('%s %s — avg %s over %d post%s', $data['days_label'][$d], ($h%12===0?12:$h%12).($h<12?'AM':'PM'), round($cell['avg'],1), $cell['count'], $cell['count']>1?'s':'')
                                                : 'No posts in this slot';
                                        @endphp
                                        <td><div class="cell {{ $cell['count'] ? 'has' : '' }}" style="background:{{ $bg }}" title="{{ $tip }}"></div></td>
                                    @endfor
                                </tr>
                            @endfor
                        </tbody>
                    </table>
                </div>
                <p class="text-muted small mt-3 mb-0"><i class="mdi mdi-information-outline me-1"></i>Hover any cell for the slot average and sample size. Darker = consistently stronger performance.</p>
            </div></div>
        </div>

        {{-- Recommended slots --}}
        <div class="col-12 col-lg-3">
            <div class="card h-100"><div class="card-body">
                <h6 class="card-title mb-3"><i class="mdi mdi-star-circle-outline text-warning me-1"></i>Top time slots</h6>
                @forelse($data['top_slots'] as $i => $slot)
                    <div class="slot-pill mb-2 d-flex align-items-center justify-content-between">
                        <div>
                            <div class="fw-semibold">{{ $slot['label'] }}</div>
                            <div class="small text-muted">{{ $slot['count'] }} post{{ $slot['count']>1?'s':'' }}</div>
                        </div>
                        <span class="badge bg-primary-subtle text-primary">{{ $slot['avg'] }}</span>
                    </div>
                @empty
                    <p class="text-muted small">Not enough data for slot recommendations.</p>
                @endforelse
                <p class="text-muted small mt-2 mb-0">These feed the calendar's suggested publish times and refresh as new data lands.</p>
            </div></div>
        </div>
    </div>
@endif

@endsection
