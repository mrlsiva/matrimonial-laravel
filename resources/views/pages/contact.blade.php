@extends('layouts.app')

@section('title', 'Contact Us')
@section('meta_description', 'Get in touch with the '.config('app.name').' support team for help with your profile, membership or payments.')

@section('content')
<div class="row g-4">
    <div class="col-lg-5">
        <h1 class="h2">Contact support</h1>
        @if($page?->content)
            <div class="text-muted">{!! $page->content !!}</div>
        @else
            <p class="text-muted">Have a question about your profile, membership or payment? Our team typically replies within one business day.</p>
        @endif
        <ul class="list-unstyled mt-4">
            <li class="mb-3"><i class="bi bi-envelope-fill text-brand me-2"></i>{{ config('matrimony.support_email') }}</li>
            <li class="mb-3"><i class="bi bi-telephone-fill text-brand me-2"></i>{{ config('matrimony.support_phone') }}</li>
            <li><i class="bi bi-clock-fill text-brand me-2"></i>Mon – Sat, 9:30 AM – 6:30 PM IST</li>
        </ul>
    </div>
    <div class="col-lg-7">
        <div class="card card-soft">
            <div class="card-body p-4">
                <form method="POST" action="{{ route('contact.store') }}" class="row g-3">
                    @csrf
                    <x-input name="name" label="Your name" :value="auth()->user()?->name" required />
                    <x-input name="email" label="Email" type="email" :value="auth()->user()?->email" required />
                    <x-input name="phone" label="Phone" :value="auth()->user()?->mobile" />
                    <x-input name="subject" label="Subject" required />
                    <x-input name="message" label="Message" type="textarea" col="col-12" rows="5" required />
                    {{-- Honeypot field, hidden from humans --}}
                    <div class="d-none" aria-hidden="true"><input type="text" name="website" tabindex="-1" autocomplete="off"></div>
                    <div class="col-12"><button class="btn btn-primary px-4"><i class="bi bi-send me-2"></i>Send message</button></div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
