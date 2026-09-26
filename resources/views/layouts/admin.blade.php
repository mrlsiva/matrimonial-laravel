<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title', 'Dashboard') | Admin - {{ config('app.name') }}</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="{{ asset('css/app.css') }}?v=7" rel="stylesheet">
    <style>
        body { background: #f4f5f9; }
        .admin-sidebar { width: 250px; min-height: 100vh; background: #1f0a13; }
        .admin-sidebar .nav-link { color: #d9c3cc; border-radius: .5rem; padding: .55rem .9rem; font-size: .92rem; }
        .admin-sidebar .nav-link:hover { background: rgba(255,255,255,.06); color: #fff; }
        .admin-sidebar .nav-link.active { background: var(--brand); color: #fff; }
        .admin-sidebar .section { color: #8c6b78; font-size: .7rem; text-transform: uppercase; letter-spacing: .08em; margin: 1rem .9rem .35rem; }
        .admin-main { min-width: 0; }
        .table > :not(caption) > * > * { vertical-align: middle; }
        @media (max-width: 991.98px) { .admin-sidebar { width: 100%; min-height: 0; } }
    </style>
    @stack('styles')
</head>
<body>
@php
    $nav = [
        ['Dashboard', 'admin.dashboard', 'bi-speedometer2', 'admin.dashboard'],
        'Members',
        ['Users', 'admin.users.index', 'bi-people', 'admin.users.*'],
        ['Profile Approval', 'admin.profiles.index', 'bi-person-check', 'admin.profiles.*'],
        ['Photo Verification', 'admin.photos.index', 'bi-images', 'admin.photos.*'],
        'Revenue',
        ['Membership Plans', 'admin.plans.index', 'bi-gem', 'admin.plans.*'],
        ['Payments', 'admin.payments.index', 'bi-credit-card', 'admin.payments.*'],
        ['Subscriptions', 'admin.subscriptions.index', 'bi-calendar-check', 'admin.subscriptions.*'],
        'Content',
        ['CMS Pages', 'admin.pages.index', 'bi-file-earmark-text', 'admin.pages.*'],
        ['Banners & Ads', 'admin.banners.index', 'bi-megaphone', 'admin.banners.*'],
        ['Support Messages', 'admin.messages.index', 'bi-envelope', 'admin.messages.*'],
    ];
    $masters = ['religions' => 'Religions', 'castes' => 'Castes', 'education-levels' => 'Education', 'occupations' => 'Occupations', 'states' => 'States', 'cities' => 'Cities'];
@endphp
<div class="d-lg-flex">
    <aside class="admin-sidebar p-3 flex-shrink-0">
        <div class="d-flex justify-content-between align-items-center">
            <a href="{{ route('admin.dashboard') }}" class="d-flex align-items-center gap-2 text-white text-decoration-none mb-2">
                <img src="{{ asset('images/logo.svg') }}" alt="" height="32"><strong>Admin Panel</strong>
            </a>
            <button class="btn btn-sm btn-outline-light d-lg-none" data-bs-toggle="collapse" data-bs-target="#adminNav" aria-label="Toggle menu"><i class="bi bi-list"></i></button>
        </div>
        <nav class="collapse d-lg-block" id="adminNav">
            <ul class="nav flex-column gap-1">
                @foreach($nav as $item)
                    @if(is_string($item))
                        <li class="section">{{ $item }}</li>
                    @else
                        <li><a class="nav-link {{ request()->routeIs($item[3]) ? 'active' : '' }}" href="{{ route($item[1]) }}"><i class="bi {{ $item[2] }} me-2"></i>{{ $item[0] }}</a></li>
                    @endif
                @endforeach
                <li class="section">Master Data</li>
                @foreach($masters as $type => $label)
                    <li><a class="nav-link {{ request()->routeIs('admin.masters.*') && request()->route('type') === $type ? 'active' : '' }}" href="{{ route('admin.masters.index', $type) }}"><i class="bi bi-list-ul me-2"></i>{{ $label }}</a></li>
                @endforeach
                <li class="section">Account</li>
                <li><a class="nav-link {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}" href="{{ route('admin.settings.edit') }}"><i class="bi bi-gear me-2"></i>Settings</a></li>
                <li><a class="nav-link" href="{{ route('home') }}" target="_blank"><i class="bi bi-box-arrow-up-right me-2"></i>View site</a></li>
            </ul>
        </nav>
    </aside>
    <div class="admin-main flex-grow-1">
        <header class="bg-white border-bottom px-4 py-3 d-flex justify-content-between align-items-center">
            <h1 class="h5 mb-0" style="font-family:Poppins,sans-serif">@yield('title', 'Dashboard')</h1>
            <div class="d-flex align-items-center gap-3">
                <span class="small text-muted d-none d-sm-inline"><i class="bi bi-person-circle me-1"></i>{{ auth()->user()->name }}</span>
                <form method="POST" action="{{ route('logout') }}">@csrf<button class="btn btn-sm btn-outline-danger"><i class="bi bi-box-arrow-right"></i> Logout</button></form>
            </div>
        </header>
        <div class="p-3 p-md-4">
            @include('partials.alerts')
            @yield('content')
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="{{ asset('js/app.js') }}?v=2"></script>
@stack('scripts')
</body>
</html>
