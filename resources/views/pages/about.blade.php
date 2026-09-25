@extends('layouts.app')

@section('title', $page->meta_title ?: $page->title)
@section('meta_description', $page->meta_description ?: \Illuminate\Support\Str::limit(strip_tags($page->content), 155))

@section('content')
<nav aria-label="breadcrumb">
    <ol class="breadcrumb small">
        <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ $page->title }}</li>
    </ol>
</nav>

{{-- Intro: image + admin-editable story --}}
<article class="card card-soft overflow-hidden">
    <div class="row g-0 align-items-center">
        @if($page->image)
            <div class="col-lg-5 page-poster">
                <img src="{{ $page->image_url }}" alt="{{ $page->title }} - {{ config('app.name') }}" class="img-fluid w-100" fetchpriority="high">
            </div>
        @endif
        <div class="{{ $page->image ? 'col-lg-7' : 'col-12' }}">
            <div class="card-body p-4 p-md-5">
                <span class="text-uppercase small fw-semibold text-gold">Who we are</span>
                <h1 class="h2 mt-1 mb-4">{{ $page->title }}</h1>
                {{-- Content is admin-authored and sanitised on save --}}
                <div class="cms-content">{!! $page->content !!}</div>
                <div class="d-flex flex-wrap gap-2 mt-4">
                    <a href="{{ auth()->check() ? route('matches') : route('register') }}" class="btn btn-primary rounded-pill px-4">{{ auth()->check() ? 'See your matches' : 'Register free' }}</a>
                    <a href="{{ route('contact.create') }}" class="btn btn-outline-primary rounded-pill px-4">Contact us</a>
                </div>
            </div>
        </div>
    </div>
</article>

{{-- Live platform numbers --}}
<section class="row g-3 my-4 text-center">
    @foreach([
        ['bi-people-fill', number_format($stats['members']).'+', 'Approved profiles'],
        ['bi-patch-check-fill', number_format($stats['verified']).'+', 'Verified members'],
        ['bi-diagram-3-fill', number_format($stats['communities']).'+', 'Communities'],
        ['bi-geo-alt-fill', number_format($stats['cities']).'+', 'Cities covered'],
    ] as [$icon, $value, $label])
        <div class="col-6 col-lg-3">
            <div class="card stat-card h-100"><div class="card-body py-4">
                <i class="bi {{ $icon }} fs-3 text-brand"></i>
                <div class="h3 fw-bold mb-0 mt-2">{{ $value }}</div>
                <div class="small text-muted">{{ $label }}</div>
            </div></div>
        </div>
    @endforeach
</section>

{{-- Mission & vision --}}
<section class="row g-4 mb-5">
    <div class="col-md-6">
        <div class="card card-soft h-100"><div class="card-body p-4 d-flex gap-3">
            <div class="about-icon"><i class="bi bi-bullseye"></i></div>
            <div>
                <h2 class="h5">Our mission</h2>
                <p class="text-muted mb-0">To make finding a life partner simple, safe and respectful — by bringing genuine, verified profiles together with tools that put families and individuals in control of every step.</p>
            </div>
        </div></div>
    </div>
    <div class="col-md-6">
        <div class="card card-soft h-100"><div class="card-body p-4 d-flex gap-3">
            <div class="about-icon"><i class="bi bi-eye"></i></div>
            <div>
                <h2 class="h5">Our vision</h2>
                <p class="text-muted mb-0">To be the most trusted matrimony platform for Indian families — where every match begins with honesty, shared values and complete peace of mind.</p>
            </div>
        </div></div>
    </div>
</section>

{{-- Why choose us --}}
<section class="mb-5">
    <div class="text-center mb-4"><h2 class="section-title">Why choose us?</h2></div>
    <div class="row g-4">
        @foreach([
            ['bi-person-vcard', 'Manually screened profiles', 'Every profile is reviewed by our team before it goes live, so you only see genuine members.'],
            ['bi-images', 'Verified photos', 'Photos are checked by our moderation team — no fake, celebrity or group pictures.'],
            ['bi-patch-check', 'Verified badges', 'Members who complete ID verification earn a badge, so you know who you are talking to.'],
            ['bi-shield-lock', 'Your privacy matters', 'Contact details are shared only with members you accept or premium members you allow.'],
            ['bi-stars', 'Smart matching', 'Advanced filters for religion, caste, star, education, location and more, plus matches based on your preferences.'],
            ['bi-credit-card-2-front', 'Secure payments', 'Premium plans are paid through Razorpay with UPI, cards and netbanking — we never store your card details.'],
        ] as [$icon, $title, $text])
            <div class="col-md-6 col-lg-4">
                <div class="card card-soft h-100 feature-card"><div class="card-body p-4">
                    <div class="about-icon mb-3"><i class="bi {{ $icon }}"></i></div>
                    <h3 class="h6 fw-semibold">{{ $title }}</h3>
                    <p class="small text-muted mb-0">{{ $text }}</p>
                </div></div>
            </div>
        @endforeach
    </div>
</section>

{{-- How it works --}}
<section class="card card-soft mb-5">
    <div class="card-body p-4 p-md-5">
        <div class="text-center mb-5"><h2 class="section-title">How it works</h2></div>
        @include('partials.how-it-works')
    </div>
</section>

{{-- Safety commitment --}}
<section class="card card-soft bg-brand-soft border-0 mb-5">
    <div class="card-body p-4 p-md-5 d-md-flex align-items-center gap-4">
        <div class="about-icon about-icon-lg mb-3 mb-md-0"><i class="bi bi-heart-pulse"></i></div>
        <div class="flex-grow-1">
            <h2 class="h5">Our commitment to your safety</h2>
            <p class="text-muted mb-md-0">We actively monitor profiles, act quickly on reports and never share your data with third parties. Blocked or suspicious accounts are removed from search immediately.</p>
        </div>
        <a href="{{ route('pages.show', 'safety-tips') }}" class="btn btn-outline-primary rounded-pill px-4 text-nowrap">Read safety tips</a>
    </div>
</section>

@include('partials.story-cta', ['class' => 'rounded-4 overflow-hidden'])
@endsection
