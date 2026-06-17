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
    <link href="{{ asset('css/style.css') }}" rel="stylesheet">

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
    </style>

    @stack('styles')
</head>
<body>

<div class="container-scroller">

    {{-- ===== NAVBAR ===== --}}
    <nav class="navbar default-layout-navbar col-12 p-0 fixed-top d-flex flex-row">

        {{-- Brand Wrapper --}}
        <div class="navbar-brand-wrapper d-flex align-items-center justify-content-start flex-shrink-0">
    
    <a class="navbar-brand brand-logo" href="{{ route('dashboard') }}">
        <span class="brand-text">
            <span class="brand-icon">G</span>

            {{-- FIXED --}}
            <span class="brand-name">
                {{ $user['name'] ?? 'Guest' }}
            </span>

        </span>
    </a>

    <a class="navbar-brand brand-logo-mini" href="{{ route('dashboard') }}">
        <span class="brand-icon-mini">G</span>
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
                <li class="nav-item {{ request()->routeIs('clients.*') ? 'active' : '' }}">
                    <a class="nav-link" data-bs-toggle="collapse" href="#clients-menu"
                    aria-expanded="{{ request()->routeIs('clients.*') ? 'true' : 'false' }}">
                        <span class="menu-title">Manage Clients</span>
                        <i class="menu-arrow"></i>
                        <i class="mdi mdi-account-multiple menu-icon"></i>
                    </a>

                    <div class="collapse {{ request()->routeIs('clients.*') ? 'show' : '' }}" id="clients-menu">
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
                                    View Calender
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>
                {{-- post for Clients --}}
                <li class="nav-item {{ request()->routeIs('clients.*') ? 'active' : '' }}">
                   <a class="nav-link" data-bs-toggle="collapse" href="#posts-menu"
                    aria-expanded="{{ request()->routeIs('clients.*') ? 'true' : 'false' }}">
                        <span class="menu-title">Posts</span>
                        <i class="menu-arrow"></i>
                        <i class="mdi mdi-account-multiple menu-icon"></i>
                    </a>

                    <div class="collapse {{ request()->routeIs('posts.*') ? 'show' : '' }}" id="posts-menu">
                        <ul class="nav flex-column sub-menu">
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('Post.index') ? 'active' : '' }}"
                                href="{{ route('Post.index') }}">
                                    Create Posts
                                </a>
                            </li>
                        </ul>
                    </div>
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
<script src="{{ asset('js/admin.js') }}"></script>

@stack('scripts')
</body>
</html>