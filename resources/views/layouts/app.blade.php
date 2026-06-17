<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name', 'Admin Panel'))</title>

    {{-- Google Fonts --}}
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">

    {{-- Bootstrap 5 --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    {{-- Material Design Icons --}}
    <link href="https://cdnjs.cloudflare.com/ajax/libs/MaterialDesign-Webfont/7.2.96/css/materialdesignicons.min.css" rel="stylesheet">

    {{-- Font Awesome --}}
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

    {{-- Main Admin CSS --}}
    <link href="{{ asset('css/style.css') }}?v={{ file_exists(public_path('css/style.css')) ? filemtime(public_path('css/style.css')) : '1' }}" rel="stylesheet">

    <style>
        /* ── FIX: keep all navbar items on ONE row, never wrap ── */
        .navbar-menu-wrapper {
            flex-wrap: nowrap !important;
            /* NOTE: no overflow:hidden here — that clips dropdown menus */
        }
        .navbar-nav-right {
            flex-wrap: nowrap !important;
            flex-shrink: 0;
        }
        .navbar-nav-right .nav-item {
            flex-shrink: 0;
        }
        /* Search shrinks gracefully so icons are never pushed off-screen */
        .search-field {
            flex: 1 1 auto;
            min-width: 0;
        }
        .search-field .input-group {
            flex-wrap: nowrap;
        }
        .search-field .form-control {
            min-width: 0;
        }

        /* ── Mobile sidebar slide-in + backdrop ── */
        #sidebar-backdrop {
            position: fixed; inset: 0; background: rgba(0,0,0,.45);
            z-index: 1029; opacity: 0; visibility: hidden; transition: opacity .25s;
        }
        body.sidebar-open #sidebar-backdrop { opacity: 1; visibility: visible; }

        @media (max-width: 991.98px) {
            .sidebar.sidebar-offcanvas {
                position: fixed; top: 0; bottom: 0; left: 0;
                transform: translateX(-100%); transition: transform .28s ease;
                z-index: 1030; max-width: 84vw; overflow-y: auto;
            }
            .sidebar.sidebar-offcanvas.active { transform: translateX(0); }
            .content-wrapper { padding: 1rem !important; }
            .page-title { font-size: 1.1rem; }
            /* Comfortable tap targets on mobile */
            .btn, .form-control, .form-select { min-height: 40px; }
            .table-responsive { -webkit-overflow-scrolling: touch; }
        }

        /* Smooth, modern card feel app-wide */
        .card { border: 1px solid rgba(0,0,0,.06); border-radius: .85rem; }
        .btn { border-radius: .55rem; }

        /* ════════ Minimal light sidebar ════════ */
        :root { --sb-accent: #2563eb; --sb-accent-soft: #eff4ff; }

        #sidebar.sidebar {
            background: #ffffff;
            border-right: 1px solid #e8edf3;
            padding: 16px 14px 26px;
        }
        #sidebar .nav { gap: 2px; }

        /* Neutralize the theme's dark nav-item bg/margins so the light theme holds.
           The active item's dark background was bleeding onto the open submenu. */
        #sidebar .nav .nav-item { margin: 0; border-radius: 10px; background: transparent; }
        #sidebar .nav .nav-item:hover,
        #sidebar .nav .nav-item.active { background: transparent; }

        /* Optional section label between groups (add <li class="nav-section">Label</li>) */
        #sidebar .nav-section {
            list-style: none; padding: 14px 12px 6px; margin: 0;
            font-size: 11px; font-weight: 700; letter-spacing: .8px;
            text-transform: uppercase; color: #94a3b8;
        }

        /* Top-level link */
        #sidebar .nav-item > .nav-link {
            display: flex !important; align-items: center; gap: 12px;
            padding: 10px 12px; border-radius: 10px;
            color: #475569; font-weight: 500; font-size: 14px; letter-spacing: .1px;
            position: relative; transition: background .15s ease, color .15s ease;
            white-space: nowrap; overflow: hidden;
        }
        #sidebar .nav-item > .nav-link:hover { background: #f1f5f9; color: #0f172a; }
        #sidebar .nav-item > .nav-link:hover .menu-icon { color: #334155; }
        #sidebar .nav-item > .nav-link:hover .menu-arrow { color: #64748b; }

        #sidebar .menu-title { order: 0; flex: 1 1 auto; }

        /* Expand/collapse arrow — clean chevron that points down, flips up when open */
        #sidebar .menu-arrow {
            order: 2; margin-left: auto; float: none !important;
            font-family: 'Material Design Icons'; font-size: 16px; line-height: 1;
            color: #94a3b8; transition: transform .25s ease, color .15s ease;
        }
        #sidebar .menu-arrow::before { content: "\F0140"; }            /* chevron-down */
        #sidebar .nav-link[aria-expanded="true"] .menu-arrow { transform: rotate(180deg); }
        #sidebar .nav-link[aria-expanded="true"] .menu-arrow + .menu-icon { margin-left: 0; }

        #sidebar .menu-icon {
            order: -1; margin: 0 !important; float: none !important;
            width: 22px; flex-shrink: 0;
            display: flex; align-items: center; justify-content: center;
            font-size: 19px; color: #64748b; transition: color .15s ease;
        }

        /* Active / current top-level — soft blue tint + accent bar, no glow */
        #sidebar .nav-item.active > .nav-link {
            background: var(--sb-accent-soft);
            color: var(--sb-accent); font-weight: 600;
        }
        #sidebar .nav-item.active > .nav-link::before {
            content: ''; position: absolute; left: 0; top: 50%; transform: translateY(-50%);
            width: 3px; height: 20px; border-radius: 0 3px 3px 0; background: var(--sb-accent);
        }
        #sidebar .nav-item.active > .nav-link .menu-icon { color: var(--sb-accent); }
        #sidebar .nav-item.active > .nav-link .menu-arrow { color: var(--sb-accent); }

        /* Sub-menu */
        #sidebar .sub-menu { margin: 1px 0 4px; padding: 2px 0 2px 46px; position: relative; }
        #sidebar .sub-menu::before {
            content: ''; position: absolute; left: 22px; top: 4px; bottom: 4px;
            width: 1.5px; background: #e2e8f0; border-radius: 2px;
        }
        #sidebar .sub-menu .nav-item > .nav-link {
            display: flex !important; align-items: center;
            padding: 8px 12px; border-radius: 8px; font-size: 13px; font-weight: 500;
            color: #64748b; transition: background .15s ease, color .15s ease;
        }
        #sidebar .sub-menu .nav-item > .nav-link::before {
            content: ''; position: static; width: 5px; height: 5px; border-radius: 50%;
            background: #cbd5e1; margin-right: 12px; flex-shrink: 0; transform: none;
            transition: background .15s ease, transform .15s ease;
        }
        #sidebar .sub-menu .nav-item > .nav-link:hover { background: #f1f5f9; color: #0f172a; }
        #sidebar .sub-menu .nav-item > .nav-link:hover::before { background: #94a3b8; }
        #sidebar .sub-menu .nav-item > .nav-link.active {
            color: var(--sb-accent); background: var(--sb-accent-soft); font-weight: 600;
        }
        #sidebar .sub-menu .nav-item > .nav-link.active::before {
            background: var(--sb-accent); transform: scale(1.3);
        }

        /* Slim scrollbar */
        #sidebar { scrollbar-width: thin; scrollbar-color: #d8e0ea transparent; }
        #sidebar::-webkit-scrollbar { width: 6px; }
        #sidebar::-webkit-scrollbar-thumb { background: #dbe3ec; border-radius: 3px; }

        /* ── Brand header: light, matches sidebar ── */
        .navbar-brand-wrapper {
            background: #ffffff !important;
            border-right: 1px solid #e8edf3;
            border-bottom: 1px solid #e8edf3;
        }
        .navbar-brand-wrapper::after { display: none; }
        .brand-icon, .brand-icon-mini {
            background: linear-gradient(135deg, #2563eb, #4f46e5) !important;
            border: none !important;
            box-shadow: 0 4px 10px -3px rgba(37,99,235,.5);
        }
        .brand-name { color: #0f172a !important; line-height: 1.15; }
        .brand-name small {
            display: block; font-size: .67rem; font-weight: 600;
            letter-spacing: .5px; color: #94a3b8; text-transform: uppercase;
        }
    </style>

    @stack('styles')
</head>
<body>

<div id="sidebar-backdrop"></div>

<div class="container-scroller">

    {{-- ===== NAVBAR ===== --}}
    <nav class="navbar default-layout-navbar col-12 p-0 fixed-top d-flex flex-row">

        {{-- Brand Wrapper --}}
        <div class="navbar-brand-wrapper d-flex align-items-center justify-content-start flex-shrink-0">
    
    <a class="navbar-brand brand-logo" href="{{ route('dashboard') }}">
        <span class="brand-text">
            <span class="brand-icon">{{ strtoupper(substr(Auth::user()?->name ?? 'U', 0, 1)) }}</span>

            <span class="brand-name">
                {{ Auth::user()?->name ?? 'User' }}
                <small>Administrator</small>
            </span>

        </span>
    </a>

    <a class="navbar-brand brand-logo-mini" href="{{ route('dashboard') }}">
        <span class="brand-icon-mini">{{ strtoupper(substr(Auth::user()?->name ?? 'U', 0, 1)) }}</span>
    </a>

</div>

        {{-- Menu Wrapper — flex:1 fills remaining space, NO overflow:hidden --}}
        <div class="navbar-menu-wrapper d-flex align-items-stretch flex-nowrap" style="flex: 1; min-width: 0;">

            {{-- Sidebar Toggle --}}
            <button class="navbar-toggler navbar-toggler align-self-center flex-shrink-0" type="button" data-toggle="minimize">
                <span class="mdi mdi-menu"></span>
            </button>

            {{-- Search --}}
            <div class="search-field d-none d-md-flex align-items-center" style="flex: 1 1 auto; min-width: 0;">
                <div class="input-group search-input-group" style="flex-wrap: nowrap;">
                    <span class="input-group-text bg-transparent border-0">
                        <i class="mdi mdi-magnify search-icon"></i>
                    </span>
                    <input type="text" class="form-control bg-transparent border-0" placeholder="Search anything...">
                </div>
            </div>

            {{-- Right Nav Items --}}
            <ul class="navbar-nav navbar-nav-right ms-auto d-flex flex-row flex-nowrap align-items-center flex-shrink-0">

                {{-- Notifications --}}
                {{-- <li class="nav-item dropdown">
                    <a class="nav-link count-indicator dropdown-toggle" id="notificationDropdown" href="#" data-bs-toggle="dropdown">
                        <i class="mdi mdi-bell-outline nav-icon"></i>
                        <span class="count-symbol bg-danger"></span>
                    </a>
                    <div class="dropdown-menu dropdown-menu-end navbar-dropdown preview-list" aria-labelledby="notificationDropdown">
                        <h6 class="dropdown-header-title p-3 mb-0">Notifications</h6>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item preview-item">
                            <div class="preview-thumbnail">
                                <div class="preview-icon bg-primary-soft">
                                    <i class="mdi mdi-calendar text-primary"></i>
                                </div>
                            </div>
                            <div class="preview-item-content">
                                <h6 class="preview-subject mb-1">Event today</h6>
                                <p class="text-muted small mb-0">Just a reminder that you have an event</p>
                            </div>
                        </a>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item preview-item">
                            <div class="preview-thumbnail">
                                <div class="preview-icon bg-success-soft">
                                    <i class="mdi mdi-cog text-success"></i>
                                </div>
                            </div>
                            <div class="preview-item-content">
                                <h6 class="preview-subject mb-1">Settings updated</h6>
                                <p class="text-muted small mb-0">Update dashboard settings</p>
                            </div>
                        </a>
                        <div class="dropdown-divider"></div>
                        <p class="p-3 mb-0 text-center text-primary small fw-semibold">See all notifications</p>
                    </div>
                </li> --}}

                {{-- Messages --}}
                {{-- <li class="nav-item dropdown">
                    <a class="nav-link count-indicator dropdown-toggle" id="messageDropdown" href="#" data-bs-toggle="dropdown">
                        <i class="mdi mdi-email-outline nav-icon"></i>
                        <span class="count-symbol bg-warning"></span>
                    </a>
                    <div class="dropdown-menu dropdown-menu-end navbar-dropdown preview-list" aria-labelledby="messageDropdown">
                        <h6 class="dropdown-header-title p-3 mb-0">Messages</h6>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item preview-item">
                            <div class="preview-thumbnail">
                                <img src="https://ui-avatars.com/api/?name=John+Doe&background=1A4A7A&color=fff&size=36" alt="avatar" class="profile-pic rounded-circle">
                            </div>
                            <div class="preview-item-content">
                                <h6 class="preview-subject mb-1">John sent you a message</h6>
                                <p class="text-muted small mb-0">2 minutes ago</p>
                            </div>
                        </a>
                        <div class="dropdown-divider"></div>
                        <p class="p-3 mb-0 text-center text-primary small fw-semibold">See all messages</p>
                    </div>
                </li> --}}

                {{-- Profile --}}
                <li class="nav-item dropdown nav-profile">
                    <a class="nav-link dropdown-toggle d-flex align-items-center" id="profileDropdown" href="#" data-bs-toggle="dropdown">
                        <div class="nav-profile-img me-2 flex-shrink-0">
                            <img src="https://ui-avatars.com/api/?name={{ urlencode(Auth::user()->name ?? 'Admin User') }}&background=1A4A7A&color=fff&size=36" alt="profile" class="rounded-circle">
                            <span class="availability-status online"></span>
                        </div>
                        <div class="nav-profile-text d-none d-md-block">
                            <p class="mb-0 fw-medium">{{ Auth::user()->name ?? 'Admin User' }}</p>
                        </div>
                    </a>
                    <div class="dropdown-menu dropdown-menu-end navbar-dropdown" aria-labelledby="profileDropdown">
                        <a class="dropdown-item" href="#">
                            <i class="mdi mdi-account-outline me-2 text-primary"></i> Profile
                        </a>
                        <a class="dropdown-item" href="#">
                            <i class="mdi mdi-cog-outline me-2 text-primary"></i> Settings
                        </a>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item" href="{{ route('logout') }}"
                           onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                            <i class="mdi mdi-logout me-2 text-danger"></i> Sign out
                        </a>
                        <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                            @csrf
                        </form>
                    </div>
                </li>

            </ul>

            {{-- Mobile Toggler --}}
            <button class="navbar-toggler navbar-toggler-right d-lg-none align-self-center flex-shrink-0" type="button" data-toggle="offcanvas">
                <span class="mdi mdi-menu"></span>
            </button>
        </div>
    </nav>
    {{-- End Navbar --}}

    <div class="container-fluid page-body-wrapper">

        {{-- ===== SIDEBAR ===== --}}
        <nav class="sidebar sidebar-offcanvas" id="sidebar">
            <ul class="nav">

                {{-- Dashboard --}}
                <li class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    <a class="nav-link" href="{{ route('dashboard') }}">
                        <span class="menu-title">Dashboard</span>
                        <i class="mdi mdi-view-dashboard-outline menu-icon"></i>
                    </a>
                </li>

                {{-- Manage Clients --}}
                <li class="nav-item {{ request()->routeIs('clients.*') || request()->routeIs('calendar.*') ? 'active' : '' }}">
                    <a class="nav-link" data-bs-toggle="collapse" href="#clients-menu"
                    aria-expanded="{{ request()->routeIs('clients.*') || request()->routeIs('calendar.*') ? 'true' : 'false' }}">
                        <span class="menu-title">Manage Clients</span>
                        <i class="menu-arrow"></i>
                        <i class="mdi mdi-account-multiple menu-icon"></i>
                    </a>

                    <div class="collapse {{ request()->routeIs('clients.*') || request()->routeIs('calendar.*') ? 'show' : '' }}" id="clients-menu">
                        <ul class="nav flex-column sub-menu">
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('clients.index') ? 'active' : '' }}"
                                href="{{ route('clients.index') }}">
                                    All Clients
                                </a>
                            </li>

                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('clients.scope') ? 'active' : '' }}"
                                href="{{ route('clients.scope') }}">
                                    Add Scope
                                </a>
                            </li>
                             <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('calendar.index') ? 'active' : '' }}"
                                href="{{ route('calendar.index') }}">
                                    View Calendar
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>
                {{-- Posts --}}
                <li class="nav-item {{ request()->routeIs('Post.*') || request()->routeIs('posts.*') ? 'active' : '' }}">
                   <a class="nav-link" data-bs-toggle="collapse" href="#posts-menu"
                    aria-expanded="{{ request()->routeIs('Post.*') || request()->routeIs('posts.*') ? 'true' : 'false' }}">
                        <span class="menu-title">Posts</span>
                        <i class="menu-arrow"></i>
                        <i class="mdi mdi-image-multiple-outline menu-icon"></i>
                    </a>

                    <div class="collapse {{ request()->routeIs('Post.*') || request()->routeIs('posts.*') ? 'show' : '' }}" id="posts-menu">
                        <ul class="nav flex-column sub-menu">
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('Post.index') ? 'active' : '' }}"
                                href="{{ route('Post.index') }}">
                                    Create Posts
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('posts.drafts') || request()->routeIs('posts.resume') || request()->routeIs('posts.editCaption') ? 'active' : '' }}"
                                href="{{ route('posts.drafts') }}">
                                    My Posts
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>

                {{-- AI Studio (Gemini-powered tools — none stored in DB) --}}
                <li class="nav-item {{ request()->routeIs('ai.*') ? 'active' : '' }}">
                    <a class="nav-link" data-bs-toggle="collapse" href="#ai-menu"
                       aria-expanded="{{ request()->routeIs('ai.*') ? 'true' : 'false' }}">
                        <span class="menu-title">AI Studio</span>
                        <i class="menu-arrow"></i>
                        <i class="mdi mdi-robot-excited-outline menu-icon"></i>
                    </a>
                    <div class="collapse {{ request()->routeIs('ai.*') ? 'show' : '' }}" id="ai-menu">
                        <ul class="nav flex-column sub-menu">
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('ai.script') ? 'active' : '' }}" href="{{ route('ai.script') }}">
                                    Video Script Generator
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('ai.captions') ? 'active' : '' }}" href="{{ route('ai.captions') }}">
                                    Multi-Format Captions
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('ai.reel') ? 'active' : '' }}" href="{{ route('ai.reel') }}">
                                    Reel Analyzer
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('ai.auditor') ? 'active' : '' }}" href="{{ route('ai.auditor') }}">
                                    Profile Auditor
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('ai.competitors') ? 'active' : '' }}" href="{{ route('ai.competitors') }}">
                                    Competitor Feed
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>

                {{-- Content Tools (#12 caption drafts, #13 hashtag bank) --}}
                <li class="nav-item {{ request()->routeIs('captions.*') || request()->routeIs('hashtags.*') ? 'active' : '' }}">
                    <a class="nav-link" data-bs-toggle="collapse" href="#tools-menu"
                       aria-expanded="{{ request()->routeIs('captions.*') || request()->routeIs('hashtags.*') ? 'true' : 'false' }}">
                        <span class="menu-title">Content Tools</span>
                        <i class="menu-arrow"></i>
                        <i class="mdi mdi-toolbox-outline menu-icon"></i>
                    </a>
                    <div class="collapse {{ request()->routeIs('captions.*') || request()->routeIs('hashtags.*') ? 'show' : '' }}" id="tools-menu">
                        <ul class="nav flex-column sub-menu">
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('captions.*') ? 'active' : '' }}" href="{{ route('captions.index') }}">
                                    Caption Drafts
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('hashtags.*') ? 'active' : '' }}" href="{{ route('hashtags.index') }}">
                                    Hashtag Bank
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>

                {{-- Reports (#15 analytics) --}}
                <li class="nav-item {{ request()->routeIs('reports.*') ? 'active' : '' }}">
                    <a class="nav-link" href="{{ route('reports.analytics') }}">
                        <span class="menu-title">Analytics</span>
                        <i class="mdi mdi-chart-box-outline menu-icon"></i>
                    </a>
                </li>

                {{-- Growth Intelligence (per-client cross-platform tools) --}}
                @php $growthActive = request()->routeIs('growth.*') || request()->routeIs('ai.viral*'); @endphp
                <li class="nav-item {{ $growthActive ? 'active' : '' }}">
                    <a class="nav-link" data-bs-toggle="collapse" href="#growth-menu"
                       aria-expanded="{{ $growthActive ? 'true' : 'false' }}">
                        <span class="menu-title">Growth Intelligence</span>
                        <i class="menu-arrow"></i>
                        <i class="mdi mdi-chart-line menu-icon"></i>
                    </a>
                    <div class="collapse {{ $growthActive ? 'show' : '' }}" id="growth-menu">
                        <ul class="nav flex-column sub-menu">
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('growth.scorecard') ? 'active' : '' }}" href="{{ route('growth.scorecard') }}">
                                    Health Scorecard
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('growth.command') ? 'active' : '' }}" href="{{ route('growth.command') }}">
                                    Command Center
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('growth.besttime') ? 'active' : '' }}" href="{{ route('growth.besttime') }}">
                                    Best Time To Post
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('ai.viral*') ? 'active' : '' }}" href="{{ route('ai.viral') }}">
                                    Viral Predictor
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>

                <li class="nav-item {{ request()->routeIs('prompts.*') ? 'active' : '' }}">
                    <a class="nav-link" href="{{ route('prompts.index') }}">
                        <span class="menu-title">Prompts</span>
                        <i class="mdi mdi-message-text-outline menu-icon"></i>
                    </a>
                </li>

                <li class="nav-item {{ request()->routeIs('settings.*') ? 'active' : '' }}">
                    <a class="nav-link" href="{{ route('settings.index') }}">
                        <span class="menu-title">Settings</span>
                        <i class="mdi mdi-cog menu-icon"></i>
                    </a>
                </li>

            </ul>
        </nav>
        {{-- End Sidebar --}}

        {{-- ===== MAIN PANEL ===== --}}
        <div class="main-panel">
            <div class="content-wrapper">

                {{-- Page Header --}}
                @hasSection('page_header')
                <div class="page-header">
                    <h3 class="page-title">
                        <span class="page-title-icon">
                            <i class="@yield('page_icon', 'mdi mdi-home')"></i>
                        </span>
                        @yield('page_header')
                    </h3>
                    <nav aria-label="breadcrumb">
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                            @yield('breadcrumb')
                        </ul>
                    </nav>
                </div>
                @endif

                {{-- Flash Messages --}}
                @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="mdi mdi-check-circle me-2"></i>{{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                @endif
                @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="mdi mdi-alert-circle me-2"></i>{{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                @endif

                {{-- Main Content --}}
                @yield('content')

            </div>

            {{-- ===== FOOTER ===== --}}
            <footer class="footer">
                <div class="d-flex justify-content-center">
                    <span class="text-muted">
                        Copyright &copy; {{ date('Y') }}
                        <span class="text-primary">Ichelon Consulting Group</span>.
                        All rights reserved.
                    </span>
                </div>
            </footer>

        </div>

    </div>

</div>

{{-- Bootstrap JS --}}
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

{{-- Admin JS --}}
<script src="{{ asset('js/admin.js') }}?v={{ file_exists(public_path('js/admin.js')) ? filemtime(public_path('js/admin.js')) : '1' }}"></script>

@stack('scripts')
</body>
</html>