@extends('layouts.app')

@section('title', 'Verify your email')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-7 col-lg-5">
        <div class="card card-soft">
            <div class="card-body p-4 p-md-5 text-center">
                <i class="bi bi-envelope-check display-4 text-brand"></i>
                <h1 class="h4 mt-3">Verify your email</h1>
                <p class="text-muted small">Enter the 6-digit code we sent to <strong>{{ auth()->user()->email }}</strong>.</p>

                <form method="POST" action="{{ route('verification.verify') }}" class="mt-3">
                    @csrf
                    <input type="text" name="code" inputmode="numeric" pattern="\d{6}" maxlength="6" class="form-control form-control-lg text-center mb-3 @error('code') is-invalid @enderror" style="letter-spacing:.5rem" required autofocus autocomplete="one-time-code" aria-label="Verification code">
                    <button class="btn btn-primary w-100 py-2">Verify</button>
                </form>

                <form method="POST" action="{{ route('verification.send') }}" class="mt-3">
                    @csrf
                    <button class="btn btn-link btn-sm">Didn't get it? Resend code</button>
                </form>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="btn btn-link btn-sm text-muted">Logout</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
