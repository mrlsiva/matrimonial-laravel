<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@hasSection('title') @yield('title') | @endif {{ config('app.name') }}</title>
    <meta name="description" content="@yield('meta_description', 'Find your perfect life partner on '.config('app.name').' - verified profiles, advanced search and secure chat.')">
    <link rel="canonical" href="{{ url()->current() }}">
    <meta property="og:title" content="@yield('title', config('app.name'))">
    <meta property="og:description" content="@yield('meta_description', 'Trusted matrimony service for brides and grooms.')">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="{{ asset('css/app.css') }}?v=7" rel="stylesheet">
    @stack('styles')
</head>
<body>
<nav class="navbar navbar-expand-lg bg-white shadow-sm sticky-top site-nav">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center gap-2" href="{{ route('home') }}">
            <img src="{{ asset('images/logo.svg') }}" alt="{{ config('app.name') }} logo"><span>{{ config('app.name') }}</span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-controls="mainNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav me-auto ms-lg-4">
                @auth
                    @unless(auth()->user()->isAdmin())
                        <li class="nav-item"><a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="nav-item"><a class="nav-link {{ request()->routeIs('matches') ? 'active' : '' }}" href="{{ route('matches') }}">Matches</a></li>
                        <li class="nav-item"><a class="nav-link {{ request()->routeIs('search') ? 'active' : '' }}" href="{{ route('search') }}">Search</a></li>
                        <li class="nav-item"><a class="nav-link {{ request()->routeIs('interests.*') ? 'active' : '' }}" href="{{ route('interests.index') }}">Interests</a></li>
                        <li class="nav-item">
                            <a class="nav-link position-relative {{ request()->routeIs('chat.*') ? 'active' : '' }}" href="{{ route('chat.index') }}">Messages
                                @if($unreadChats)<span class="badge rounded-pill bg-danger ms-1">{{ $unreadChats }}</span>@endif
                            </a>
                        </li>
                    @endunless
                @endauth
                <li class="nav-item"><a class="nav-link {{ request()->routeIs('membership.*') ? 'active' : '' }}" href="{{ route('membership.index') }}">Membership</a></li>
            </ul>
            <ul class="navbar-nav align-items-lg-center gap-lg-2">
                @guest
                    <li class="nav-item"><a class="nav-link" href="{{ route('login') }}">Login</a></li>
                    <li class="nav-item"><a class="btn btn-primary rounded-pill px-4" href="{{ route('register') }}">Register Free</a></li>
                @else
                    @if(auth()->user()->isAdmin())
                        <li class="nav-item"><a class="btn btn-outline-primary btn-sm" href="{{ route('admin.dashboard') }}">Admin Panel</a></li>
                    @else
                        <li class="nav-item dropdown">
                            <a class="nav-link position-relative" href="#" id="notifBell" role="button" data-bs-toggle="dropdown" aria-expanded="false" data-url="{{ route('notifications.latest') }}" aria-label="Notifications">
                                <i class="bi bi-bell fs-5"></i>
                                @if($unreadNotifications)<span class="badge rounded-pill bg-danger badge-dot">{{ $unreadNotifications > 9 ? '9+' : $unreadNotifications }}</span>@endif
                            </a>
                            <div class="dropdown-menu dropdown-menu-end p-0 notif-menu shadow">
                                <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom bg-light">
                                    <strong class="small">Notifications</strong>
                                    <a href="{{ route('notifications.index') }}" class="small">View all</a>
                                </div>
                                <div id="notifList"><div class="p-4 text-center"><div class="spinner-border spinner-border-sm text-secondary"></div></div></div>
                            </div>
                        </li>
                    @endif
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center gap-2" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-person-circle fs-5"></i><span class="d-lg-none d-xl-inline">{{ \Illuminate\Support\Str::limit(auth()->user()->name, 16) }}</span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow">
                            @unless(auth()->user()->isAdmin())
                                @if(auth()->user()->profile)
                                    <li><a class="dropdown-item" href="{{ route('profiles.show', auth()->user()->profile) }}"><i class="bi bi-eye me-2"></i>View my profile</a></li>
                                @endif
                                <li><a class="dropdown-item" href="{{ route('profile.edit') }}"><i class="bi bi-pencil-square me-2"></i>Edit profile</a></li>
                                <li><a class="dropdown-item" href="{{ route('photos.index') }}"><i class="bi bi-images me-2"></i>My photos</a></li>
                                <li><a class="dropdown-item" href="{{ route('favorites.index') }}"><i class="bi bi-star me-2"></i>Shortlist</a></li>
                                <li><a class="dropdown-item" href="{{ route('payments.index') }}"><i class="bi bi-receipt me-2"></i>Payments &amp; plan</a></li>
                                <li><a class="dropdown-item" href="{{ route('account.edit') }}"><i class="bi bi-gear me-2"></i>Account settings</a></li>
                                <li><hr class="dropdown-divider"></li>
                            @endunless
                            <li>
                                <form method="POST" action="{{ route('logout') }}">@csrf
                                    <button class="dropdown-item text-danger"><i class="bi bi-box-arrow-right me-2"></i>Logout</button>
                                </form>
                            </li>
                        </ul>
                    </li>
                @endguest
            </ul>
        </div>
    </div>
</nav>

<main>
    @hasSection('fullwidth')
        @include('partials.alerts', ['container' => true])
        @yield('fullwidth')
    @else
        <div class="container py-4">
            @include('partials.alerts')
            @yield('content')
        </div>
    @endif
</main>

<footer class="site-footer {{ $__env->hasSection('fullwidth') ? '' : 'mt-5' }} pt-5 pb-3">
    <div class="container">
        <div class="row g-4">
            <div class="col-md-5">
                <h5 class="text-white display-font">{{ config('app.name') }}</h5>
                <p class="small">A trusted matrimony platform helping families find verified, compatible life partners. Every profile is screened by our team before it goes live.</p>
                <p class="small mb-1"><i class="bi bi-envelope me-2"></i>{{ config('matrimony.support_email') }}</p>
                <p class="small"><i class="bi bi-telephone me-2"></i>{{ config('matrimony.support_phone') }}</p>
            </div>
            <div class="col-6 col-md-3">
                <h6 class="text-white">Explore</h6>
                <ul class="list-unstyled small">
                    <li><a href="{{ route('register') }}">Register free</a></li>
                    <li><a href="{{ route('membership.index') }}">Membership plans</a></li>
                    <li><a href="{{ route('contact.create') }}">Contact support</a></li>
                </ul>
            </div>
            <div class="col-6 col-md-4">
                <h6 class="text-white">Information</h6>
                <ul class="list-unstyled small">
                    @foreach($footerPages as $fp)
                        <li><a href="{{ route('pages.show', $fp->slug) }}">{{ $fp->title }}</a></li>
                    @endforeach
                </ul>
            </div>
        </div>
        <hr class="border-secondary">
        <p class="small text-center mb-0">&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="{{ asset('js/app.js') }}?v=2"></script>
@stack('scripts')
</body>
</html>
