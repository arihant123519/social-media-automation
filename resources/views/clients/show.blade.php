@extends('layouts.app')

@section('title', $client->name)
@section('page_header', 'Client Profile')
@section('page_icon', 'mdi mdi-account-circle')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('clients.index') }}">Clients</a></li>
    <li class="breadcrumb-item active">{{ $client->name }}</li>
@endsection

@push('styles')
<style>
    .cp-hero { background:linear-gradient(135deg,#4f46e5,#7c3aed); border-radius:14px; color:#fff; padding:24px 28px; }
    .cp-avatar { width:72px; height:72px; border-radius:50%; background:rgba(255,255,255,.2);
        display:flex; align-items:center; justify-content:center; font-size:30px; font-weight:700; flex-shrink:0; }
    .cp-badge { font-size:11px; padding:3px 10px; border-radius:20px; font-weight:600; }
    .cp-badge.active { background:#dcfce7; color:#166534; }
    .cp-badge.inactive { background:#fee2e2; color:#991b1b; }
    .cp-meta { font-size:13px; opacity:.95; }
    .cp-meta i { width:18px; }
    .stat-card { background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:16px 18px; text-align:center; height:100%; }
    .stat-card .v { font-size:26px; font-weight:700; line-height:1; }
    .stat-card .l { font-size:12px; color:#6b7280; margin-top:4px; }
    .sec-card { background:#fff; border:1px solid #e5e7eb; border-radius:12px; }
    .sec-card .sec-head { padding:14px 18px; border-bottom:1px solid #f1f5f9; font-weight:600; font-size:15px; color:#111827; display:flex; align-items:center; gap:8px; }
    .sec-card .sec-body { padding:16px 18px; }
    .conn-pill { font-size:11px; padding:3px 10px; border-radius:20px; font-weight:600; }
    .conn-on { background:#dcfce7; color:#166534; }
    .conn-off { background:#f3f4f6; color:#6b7280; }
    .post-row { display:flex; align-items:center; gap:12px; padding:10px 0; border-bottom:1px solid #f1f5f9; }
    .post-row:last-child { border-bottom:none; }
    .pt-icon { width:34px; height:34px; border-radius:8px; display:flex; align-items:center; justify-content:center; flex-shrink:0; font-size:16px; }
    .st-badge { font-size:10px; padding:2px 8px; border-radius:12px; font-weight:600; white-space:nowrap; }
    .st-published { background:#dcfce7; color:#166534; }
    .st-scheduled { background:#dbeafe; color:#1d4ed8; }
    .st-ready,.st-not_ready { background:#fef9c3; color:#854d0e; }
    .st-failed { background:#fee2e2; color:#991b1b; }
    .st-dry_run { background:#f3e8ff; color:#6b21a8; }
    .scope-chip { display:inline-block; background:#eef2ff; color:#3730a3; font-size:11px; padding:3px 9px; border-radius:6px; margin:2px; }
</style>
@endpush

@section('content')
<div class="container-fluid py-3">

    {{-- ── Hero / Profile header ── --}}
    <div class="cp-hero mb-4">
        <div class="d-flex flex-wrap align-items-center gap-3">
            <div class="cp-avatar">{{ strtoupper(substr($client->name,0,1)) }}</div>
            <div class="flex-grow-1">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <h4 class="mb-0 fw-bold">{{ $client->name }}</h4>
                    <span class="cp-badge {{ $client->status }}">{{ ucfirst($client->status) }}</span>
                </div>
                <div class="cp-meta mt-2 d-flex flex-wrap gap-3">
                    <span><i class="mdi mdi-at"></i> {{ $client->email }}</span>
                    <span><i class="mdi mdi-phone"></i> {{ $client->phone }}</span>
                    <span><i class="mdi mdi-briefcase"></i> {{ ucfirst($client->industry) }}</span>
                    @if($client->city)<span><i class="mdi mdi-map-marker"></i> {{ $client->city }}</span>@endif
                </div>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('calendar.index', ['client_id'=>$client->id]) }}" class="btn btn-light btn-sm">
                    <i class="mdi mdi-calendar"></i> Calendar
                </a>
                <a href="{{ route('clients.edit', $client->id) }}" class="btn btn-light btn-sm">
                    <i class="mdi mdi-pencil"></i> Edit
                </a>
            </div>
        </div>
    </div>

    {{-- ── Stat cards ── --}}
    <div class="row g-3 mb-4">
        @foreach([
            ['Total Posts',$stats['total'],'#111827'],
            ['Published',$stats['published'],'#16a34a'],
            ['Scheduled',$stats['scheduled'],'#2563eb'],
            ['Pending',$stats['pending'],'#854d0e'],
            ['Failed',$stats['failed'],'#dc2626'],
        ] as [$label,$val,$color])
        <div class="col-6 col-md">
            <div class="stat-card">
                <div class="v" style="color:{{ $color }}">{{ $val }}</div>
                <div class="l">{{ $label }}</div>
            </div>
        </div>
        @endforeach
    </div>

    <div class="row g-3">

        {{-- ── Left column ── --}}
        <div class="col-lg-4">

            {{-- Account details --}}
            <div class="sec-card mb-3">
                <div class="sec-head"><i class="mdi mdi-information-outline"></i> Account Details</div>
                <div class="sec-body" style="font-size:13px;color:#374151">
                    <div class="d-flex justify-content-between py-1"><span class="text-muted">Slug</span><span>{{ $client->slug }}</span></div>
                    <div class="d-flex justify-content-between py-1"><span class="text-muted">Assigned to</span><span>{{ $client->user?->name ?? '—' }}</span></div>
                    <div class="d-flex justify-content-between py-1"><span class="text-muted">Team</span><span>{{ $client->team?->name ?? '—' }}</span></div>
                    <div class="d-flex justify-content-between py-1"><span class="text-muted">Created by</span><span>{{ $client->creator?->name ?? '—' }}</span></div>
                    <div class="d-flex justify-content-between py-1"><span class="text-muted">Created on</span><span>{{ $client->created_at?->format('d M Y') }}</span></div>
                </div>
            </div>

            {{-- Connected accounts --}}
            <div class="sec-card mb-3">
                <div class="sec-head"><i class="mdi mdi-link-variant"></i> Connected Accounts</div>
                <div class="sec-body">
                    <div class="d-flex align-items-center justify-content-between py-2">
                        <span><i class="mdi mdi-youtube text-danger"></i> YouTube</span>
                        <span class="conn-pill {{ $client->hasYouTubeConnected() ? 'conn-on' : 'conn-off' }}">
                            {{ $client->hasYouTubeConnected() ? 'Connected' : 'Not connected' }}
                        </span>
                    </div>
                    <div class="d-flex align-items-center justify-content-between py-2">
                        <span><i class="mdi mdi-instagram" style="color:#be185d"></i> Instagram</span>
                        <span class="conn-pill {{ $client->hasInstagramConnected() ? 'conn-on' : 'conn-off' }}">
                            {{ $client->hasInstagramConnected() ? 'Connected' : 'Not connected' }}
                        </span>
                    </div>
                </div>
            </div>

            {{-- Scope plans --}}
            <div class="sec-card mb-3">
                <div class="sec-head"><i class="mdi mdi-calendar-text"></i> Content Scope Plans</div>
                <div class="sec-body">
                    @forelse($scopes as $s)
                        @php
                            $isActive = is_null($s->end_date);
                            $platform = $s->scope == 0 ? 'YouTube' : 'Instagram';
                        @endphp
                        <div class="py-2 {{ !$loop->last ? 'border-bottom' : '' }}">
                            <div class="d-flex justify-content-between align-items-center">
                                <strong style="font-size:13px">{{ $platform }}</strong>
                                <span class="st-badge {{ $isActive ? 'st-published' : 'st-not_ready' }}">
                                    {{ $isActive ? 'Active' : 'Ended' }}
                                </span>
                            </div>
                            <div style="font-size:12px;color:#6b7280;margin:3px 0">
                                {{ \Carbon\Carbon::parse($s->start_date)->format('d M Y') }}
                                → {{ $s->end_date ? \Carbon\Carbon::parse($s->end_date)->format('d M Y') : 'ongoing' }}
                            </div>
                            <div>
                                @if($s->scope == 0)
                                    @if($s->long_video)<span class="scope-chip">{{ $s->long_video }} Long Video</span>@endif
                                    @if($s->short_video)<span class="scope-chip">{{ $s->short_video }} Short</span>@endif
                                @else
                                    @if($s->reels)<span class="scope-chip">{{ $s->reels }} Reels</span>@endif
                                    @if($s->story)<span class="scope-chip">{{ $s->story }} Story</span>@endif
                                    @if($s->photo)<span class="scope-chip">{{ $s->photo }} Photo</span>@endif
                                @endif
                                <span class="text-muted" style="font-size:11px">/month</span>
                            </div>
                        </div>
                    @empty
                        <p class="text-muted mb-0" style="font-size:13px">No scope plan set yet.</p>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- ── Right column: posts scope-wise ── --}}
        <div class="col-lg-8">
            @foreach([['YouTube',$ytPosts,'mdi-youtube','#dc2626'],['Instagram',$igPosts,'mdi-instagram','#be185d']] as [$plat,$plist,$icon,$pcolor])
            <div class="sec-card mb-3">
                <div class="sec-head">
                    <i class="mdi {{ $icon }}" style="color:{{ $pcolor }}"></i> {{ $plat }} Posts
                    <span class="text-muted ms-auto" style="font-size:12px;font-weight:400">{{ $plist->count() }} total</span>
                </div>
                <div class="sec-body">
                    @forelse($plist as $p)
                        @php
                            $typeLabel = [
                                'long_video'=>'Long Video','short_video'=>'Short','reels'=>'Reel','photo'=>'Photo','story'=>'Story',
                            ][$p->post_type] ?? ucfirst($p->post_type);
                            $ps = $p->publish_status ?: 'not_ready';
                        @endphp
                        <div class="post-row">
                            <div class="pt-icon" style="background:{{ $pcolor }}1a;color:{{ $pcolor }}">
                                <i class="mdi {{ $p->scope==0 ? 'mdi-play-circle' : ($p->post_type=='photo' ? 'mdi-image' : 'mdi-movie-open') }}"></i>
                            </div>
                            <div class="flex-grow-1" style="min-width:0">
                                <div style="font-size:13px;font-weight:600;color:#111827">{{ $typeLabel }}
                                    @if($p->keyword)<span class="text-muted fw-normal">· {{ \Illuminate\Support\Str::limit($p->keyword,30) }}</span>@endif
                                </div>
                                <div style="font-size:11px;color:#6b7280">
                                    @if($p->scheduled_date) {{ \Carbon\Carbon::parse($p->scheduled_date)->format('d M Y') }} @endif
                                    @if($p->best_score) · Score {{ $p->best_score }}/100 @endif
                                    @if($p->published_at) · {{ $ps === 'dry_run' ? 'Dry-run (not live)' : 'Published' }} {{ $p->published_at->format('d M, g:i A') }} @endif
                                </div>
                            </div>
                            <span class="st-badge st-{{ $ps }}">{{ str_replace('_',' ',ucfirst($ps)) }}</span>
                            @if($p->external_url)
                                <a href="{{ $p->external_url }}" target="_blank" rel="noopener" class="text-success" title="View live post">
                                    <i class="mdi mdi-open-in-new"></i>
                                </a>
                            @endif
                        </div>
                    @empty
                        <p class="text-muted mb-0" style="font-size:13px">No {{ $plat }} posts yet.</p>
                    @endforelse
                </div>
            </div>
            @endforeach
        </div>
    </div>

</div>
@endsection
