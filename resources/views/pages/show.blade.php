@extends('layouts.app')

@section('title', $page->meta_title ?: $page->title)
@section('meta_description', $page->meta_description ?: \Illuminate\Support\Str::limit(strip_tags($page->content), 155))

@section('content')
<div class="row justify-content-center">
    <div class="{{ $page->image ? 'col-xl-11' : 'col-lg-9' }}">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb small">
                <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                <li class="breadcrumb-item active" aria-current="page">{{ $page->title }}</li>
            </ol>
        </nav>
        <article class="card card-soft overflow-hidden">
            <div class="row g-0">
                @if($page->image)
                    <div class="col-lg-5 page-poster">
                        <img src="{{ $page->image_url }}" alt="{{ $page->title }} - {{ config('app.name') }}" class="img-fluid w-100" fetchpriority="high">
                    </div>
                @endif
                <div class="{{ $page->image ? 'col-lg-7' : 'col-12' }}">
                    <div class="card-body p-4 p-md-5">
                        <h1 class="h2 mb-4">{{ $page->title }}</h1>
                        {{-- Content is admin-authored and sanitised on save --}}
                        <div class="cms-content">{!! $page->content !!}</div>
                        @if($page->slug === 'about-us')
                            <div class="d-flex flex-wrap gap-2 mt-4">
                                <a href="{{ auth()->check() ? route('matches') : route('register') }}" class="btn btn-primary rounded-pill px-4">{{ auth()->check() ? 'See your matches' : 'Register free' }}</a>
                                <a href="{{ route('contact.create') }}" class="btn btn-outline-primary rounded-pill px-4">Contact us</a>
                            </div>
                        @elseif($page->slug === 'contact-us')
                            <a href="{{ route('contact.create') }}" class="btn btn-primary mt-3">Send us a message</a>
                        @endif
                    </div>
                </div>
            </div>
        </article>
    </div>
</div>
@endsection
