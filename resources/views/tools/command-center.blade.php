@extends('layouts.app')

@section('title', 'Content Command Center')
@section('page_header', 'All-Platform Command Center')
@section('page_icon', 'mdi mdi-view-grid-plus-outline')

@section('breadcrumb')
    <li class="breadcrumb-item active">Command Center</li>
@endsection

@push('styles')
<style>
    .fmt-pill { font-size:.72rem; font-weight:600; color:#fff; border-radius:2rem; padding:.18rem .6rem; white-space:nowrap; }
    .plat-ico { width:30px; height:30px; border-radius:.55rem; display:inline-flex; align-items:center; justify-content:center; font-size:1.05rem; }
    .win-badge { font-size:.68rem; font-weight:700; background:linear-gradient(135deg,#f59e0b,#f97316); color:#fff; border-radius:2rem; padding:.12rem .5rem; }
    .cc-table td, .cc-table th { vertical-align:middle; }
    .cc-table th.sortable a { color:inherit; text-decoration:none; }
    .cc-table th.active-sort { color:#1A4A7A; }
    .metric-num { font-variant-numeric:tabular-nums; }
    .rank-cell { font-weight:700; color:#94a3b8; width:36px; }
</style>
@endpush

@section('content')

<form method="GET" class="d-flex flex-wrap gap-2 align-items-end mb-3">
    <div>
        <label class="form-label small text-muted mb-1">Client</label>
        <select name="client_id" class="form-select form-select-sm" style="min-width:190px" onchange="this.form.submit()">
            @foreach($clients as $c)
                <option value="{{ $c->id }}" {{ $clientId == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="form-label small text-muted mb-1">Window</label>
        <select name="days" class="form-select form-select-sm" onchange="this.form.submit()">
            @foreach([30=>'Last 30 days',60=>'Last 60 days',90=>'Last 90 days',180=>'Last 6 months'] as $d=>$lbl)
                <option value="{{ $d }}" {{ $days == $d ? 'selected' : '' }}>{{ $lbl }}</option>
            @endforeach
        </select>
    </div>
    @if($data)
        <input type="hidden" name="sort" value="{{ $data['sort'] }}">
    @endif
    <div class="ms-auto">
        <a href="{{ request()->fullUrlWithQuery(['fresh' => 1]) }}" class="btn btn-sm btn-outline-primary" title="Bypass the 30-min cache and re-pull live data"><i class="mdi mdi-refresh me-1"></i>Refresh data</a>
    </div>
</form>

@if(!$data)
    <div class="alert alert-warning">No active client selected.</div>
@elseif(empty($data['items']))
    <div class="alert alert-info"><i class="mdi mdi-information-outline me-1"></i>No published content in this window yet. Publish posts (or connect a platform) to populate the command center.</div>
@else
    {{-- Connection + AI insight strip --}}
    <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
        @foreach($data['live'] as $plat => $on)
            <span class="badge {{ $on ? 'bg-success-subtle text-success' : 'bg-light text-muted' }} text-capitalize">
                <i class="mdi {{ $on ? 'mdi-check-circle' : 'mdi-link-off' }} me-1"></i>{{ $plat }} {{ $on ? 'live' : 'offline' }}
            </span>
        @endforeach
        <span class="ms-auto small text-muted">{{ $data['totals']['posts'] }} posts ranked by <strong>{{ $data['metrics'][$data['sort']] }}</strong></span>
    </div>

    @if($data['insight'])
        <div class="alert alert-primary d-flex align-items-center py-2 px-3">
            <i class="mdi mdi-robot-happy-outline me-2 fs-5"></i><span class="fw-medium">{{ $data['insight'] }}</span>
        </div>
    @endif

    {{-- Totals row --}}
    <div class="row g-2 mb-3">
        @php $tiles = [['Reach','reach','mdi-eye-outline'],['Saves','saves','mdi-bookmark-outline'],['Shares','shares','mdi-share-variant'],['Plays','plays','mdi-play-circle-outline'],['Engagement','engagement','mdi-heart-pulse']]; @endphp
        @foreach($tiles as [$lbl,$key,$ic])
            <div class="col">
                <div class="card border h-100"><div class="card-body py-2 px-3">
                    <div class="small text-muted"><i class="mdi {{ $ic }} me-1"></i>{{ $lbl }}</div>
                    <div class="fw-bold metric-num">{{ $data['totals'][$key] !== null ? number_format($data['totals'][$key]) : '—' }}</div>
                </div></div>
            </div>
        @endforeach
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover cc-table mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th class="rank-cell">#</th>
                        <th>Content</th>
                        <th>Format</th>
                        @php
                            $cols = ['reach'=>'Reach','saves'=>'Saves','shares'=>'Shares','plays'=>'Plays','engagement'=>'Eng.','score'=>'Score'];
                        @endphp
                        @foreach($cols as $key => $lbl)
                            <th class="text-end sortable {{ $data['sort']===$key ? 'active-sort' : '' }}">
                                <a href="{{ request()->fullUrlWithQuery(['sort'=>$key]) }}">
                                    {{ $lbl }}
                                    @if($data['sort']===$key)<i class="mdi mdi-sort-descending"></i>@endif
                                </a>
                            </th>
                        @endforeach
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($data['items'] as $idx => $it)
                        @php
                            $platIco = ['instagram'=>['mdi-instagram','#e1306c'],'youtube'=>['mdi-youtube','#ff0000'],'facebook'=>['mdi-facebook','#1877f2'],'linkedin'=>['mdi-linkedin','#0a66c2']][$it['platform']] ?? ['mdi-web','#64748b'];
                            $isWinner = $data['winning_format'] && $it['format'] === $data['winning_format'];
                        @endphp
                        <tr>
                            <td class="rank-cell">{{ $idx + 1 }}</td>
                            <td style="min-width:240px">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="plat-ico" style="background:{{ $platIco[1] }}1a;color:{{ $platIco[1] }}"><i class="mdi {{ $platIco[0] }}"></i></span>
                                    <div class="text-truncate" style="max-width:260px">
                                        <div class="fw-medium text-truncate">{{ $it['title'] }}</div>
                                        <div class="small text-muted text-capitalize">{{ $it['platform'] }} · {{ $it['published_at']->format('d M Y') }} {{ $it['live'] ? '' : '· local' }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="fmt-pill" style="background:{{ $it['format_color'] }}">{{ $it['format_label'] }}</span>
                            </td>
                            <td class="text-end metric-num">{{ is_numeric($it['reach']) ? number_format($it['reach']) : '—' }}</td>
                            <td class="text-end metric-num">{{ is_numeric($it['saves']) ? number_format($it['saves']) : '—' }}</td>
                            <td class="text-end metric-num">{{ is_numeric($it['shares']) ? number_format($it['shares']) : '—' }}</td>
                            <td class="text-end metric-num">{{ is_numeric($it['plays']) ? number_format($it['plays']) : '—' }}</td>
                            <td class="text-end metric-num">{{ is_numeric($it['engagement']) ? number_format($it['engagement']) : '—' }}</td>
                            <td class="text-end metric-num">
                                @if($it['score'] !== null)
                                    <span class="badge bg-success-subtle text-success">{{ $it['score'] }}</span>
                                @else — @endif
                            </td>
                            <td class="text-nowrap">
                                @if($isWinner)<span class="win-badge" title="Best-performing format for this client on {{ $data['metrics'][$data['sort']] }}"><i class="mdi mdi-trophy"></i> Wins for you</span>@endif
                                @if($it['url'])<a href="{{ $it['url'] }}" target="_blank" class="ms-1 text-muted"><i class="mdi mdi-open-in-new"></i></a>@endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    <p class="text-muted small mt-2">
        <i class="mdi mdi-information-outline me-1"></i>
        Dashes mean that metric isn't available yet for this item — live reach/saves/shares populate once the platform is connected. Click a column to re-rank.
    </p>
@endif

@endsection
