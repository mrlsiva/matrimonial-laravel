@extends('layouts.app')

@section('title', 'Find Your Perfect Life Partner')
@section('meta_description', 'Join '.config('app.name').' - India\'s trusted matrimony site with admin-verified bride and groom profiles, advanced search and secure chat.')

@section('fullwidth')
@php($heroLink = auth()->check() ? route('matches') : route('register'))
<section class="hero-banner">
    <h1 class="visually-hidden">{{ config('app.name') }} - find your life partner</h1>
    <div id="heroCarousel" class="carousel slide carousel-fade" data-bs-ride="carousel" data-bs-interval="6000">
        <div class="carousel-inner">
            @forelse($sliders as $slide)
                <div class="carousel-item {{ $loop->first ? 'active' : '' }}">
                    <a href="{{ $slide->link_url ? route('banners.click', $slide) : $heroLink }}">
                        <img src="{{ $slide->image_url }}" class="d-block w-100" alt="{{ $slide->title }}" @if($loop->first) fetchpriority="high" @else loading="lazy" @endif>
                    </a>
                </div>
            @empty
                {{-- Default banner when no home slider is active in Admin > Banners & Ads --}}
                <div class="carousel-item active">
                    <a href="{{ $heroLink }}">
                        <picture>
                            <source media="(max-width: 767.98px)" srcset="{{ asset('images/banner-sm.webp') }}">
                            <img src="{{ asset('images/banner.webp') }}" class="d-block w-100" width="2048" height="768" alt="{{ config('app.name') }} - verified profiles, compatible matches, safe and trusted" fetchpriority="high">
                        </picture>
                    </a>
                </div>
            @endforelse
        </div>
        @if($sliders->count() > 1)
            <button class="carousel-control-prev" type="button" data-bs-target="#heroCarousel" data-bs-slide="prev"><span class="carousel-control-prev-icon" aria-hidden="true"></span><span class="visually-hidden">Previous</span></button>
            <button class="carousel-control-next" type="button" data-bs-target="#heroCarousel" data-bs-slide="next"><span class="carousel-control-next-icon" aria-hidden="true"></span><span class="visually-hidden">Next</span></button>
        @endif
    </div>
</section>

<section class="container search-band">
    <form action="{{ route('search') }}" method="GET" class="hero-search p-3 p-md-4">
        <div class="row g-2 g-md-3 align-items-end">
            <div class="col-12 col-lg-2">
                <h2 class="h6 mb-0 text-brand">Search your match</h2>
                <small class="text-muted">{{ number_format($stats['members']) }}+ profiles · {{ number_format($stats['verified']) }}+ verified</small>
            </div>
            <div class="col-6 col-lg-2">
                <label class="form-label small mb-1" for="hs-gender">Looking for</label>
                <select name="gender" id="hs-gender" class="form-select">
                    <option value="female">Bride</option>
                    <option value="male">Groom</option>
                </select>
            </div>
            <div class="col-3 col-lg-1">
                <label class="form-label small mb-1" for="hs-min">Age from</label>
                <select name="age_min" id="hs-min" class="form-select">@for($a = 18; $a <= 60; $a++)<option @selected($a == 21)>{{ $a }}</option>@endfor</select>
            </div>
            <div class="col-3 col-lg-1">
                <label class="form-label small mb-1" for="hs-max">to</label>
                <select name="age_max" id="hs-max" class="form-select">@for($a = 18; $a <= 70; $a++)<option @selected($a == 32)>{{ $a }}</option>@endfor</select>
            </div>
            <div class="col-12 col-lg-3">
                <label class="form-label small mb-1" for="hs-religion">Religion</label>
                <select name="religion_id" id="hs-religion" class="form-select">
                    <option value="">Any religion</option>
                    @foreach($religions as $r)<option value="{{ $r->id }}">{{ $r->name }}</option>@endforeach
                </select>
            </div>
            <div class="col-12 col-lg-3">
                <button class="btn btn-primary w-100 py-2"><i class="bi bi-search me-2"></i>Let's begin</button>
            </div>
        </div>
        @guest<p class="small text-muted text-center mt-2 mb-0">Free registration required to view profiles.</p>@endguest
    </form>
</section>

<section class="py-5 bg-white">
    <div class="container">
        <div class="text-center mb-5"><h2 class="section-title">How it works</h2></div>
        <div class="row g-4 text-center">
            @foreach([
                ['bi-person-plus', 'Register', 'Create your profile in minutes with photos, education, family and horoscope details.'],
                ['bi-shield-check', 'Get verified', 'Our team reviews every profile and photo before it goes live.'],
                ['bi-search-heart', 'Find matches', 'Use advanced filters or let us suggest matches based on your preferences.'],
                ['bi-chat-heart', 'Connect', 'Send interests, chat securely and take the next step with your family.'],
            ] as [$icon, $title, $text])
                <div class="col-6 col-lg-3">
                    <div class="rounded-circle bg-brand-soft d-inline-flex align-items-center justify-content-center mb-3" style="width:72px;height:72px">
                        <i class="bi {{ $icon }} fs-2 text-brand"></i>
                    </div>
                    <h3 class="h6 fw-semibold">{{ $title }}</h3>
                    <p class="small text-muted">{{ $text }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>

@if($featured->isNotEmpty())
<section class="py-5">
    <div class="container">
        <div class="text-center mb-5"><h2 class="section-title">Featured profiles</h2></div>
        <div class="row g-3 g-md-4">
            @foreach($featured as $p)
                <div class="col-6 col-md-4 col-lg-3">
                    @auth
                        @include('partials.profile-card', ['p' => $p])
                    @else
                        {{-- Guests see a teaser card that leads to registration --}}
                        <div class="card profile-card h-100">
                            <img src="{{ $p->photo_url }}" class="photo" alt="Featured {{ $p->gender === 'female' ? 'bride' : 'groom' }}" loading="lazy" style="filter:blur(2px)">
                            <div class="card-body p-3">
                                <h6 class="mb-1">{{ $p->profile_code }} @if($p->is_verified)<i class="bi bi-patch-check-fill verified-badge"></i>@endif</h6>
                                <div class="small text-muted">{{ $p->age }} yrs • {{ $p->occupation?->name }}<br><i class="bi bi-geo-alt"></i> {{ $p->location }}</div>
                            </div>
                            <div class="card-footer bg-white border-0 pt-0 pb-3 px-3">
                                <a href="{{ route('register') }}" class="btn btn-sm btn-outline-primary w-100 rounded-pill">Register to view</a>
                            </div>
                        </div>
                    @endauth
                </div>
            @endforeach
        </div>
    </div>
</section>
@endif

@if($middleAds->isNotEmpty())
<section class="container pb-4">
    <div class="row g-3">
        @foreach($middleAds as $ad)
            <div class="col-md-{{ 12 / min(3, $middleAds->count()) }} ad-slot">
                <a href="{{ $ad->link_url ? route('banners.click', $ad) : '#' }}" rel="sponsored noopener" @if($ad->link_url) target="_blank" @endif>
                    <img src="{{ $ad->image_url }}" alt="{{ $ad->title }}" loading="lazy">
                </a>
            </div>
        @endforeach
    </div>
</section>
@endif

@if($plans->isNotEmpty())
<section class="py-5 bg-white">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="section-title">Membership plans</h2>
            <p class="text-muted mt-3">Upgrade to chat instantly and view verified contact details.</p>
        </div>
        @include('membership._plans', ['plans' => $plans, 'subscription' => null])
    </div>
</section>
@endif

<section class="story-banner text-white text-center d-flex align-items-center">
    <div class="container">
        <h2 class="display-6 mb-3">Your story starts here</h2>
        <p class="lead mb-4">Thousands of families trust us to find the right match.</p>
        <a href="{{ auth()->check() ? route('matches') : route('register') }}" class="btn btn-gold btn-lg rounded-pill px-5">{{ auth()->check() ? 'See your matches' : 'Register free' }}</a>
    </div>
</section>
@endsection
