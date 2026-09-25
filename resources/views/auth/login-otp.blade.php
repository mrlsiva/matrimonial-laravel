@extends('layouts.app')

@section('title', 'Login with OTP')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-7 col-lg-5">
        <div class="card card-soft">
            <div class="card-body p-4 p-md-5">
                <h1 class="h3 text-center mb-2">Login with OTP</h1>
                <p class="text-center text-muted small mb-4">We'll send a one-time code to your registered email or mobile.</p>

                <form method="POST" action="{{ route('login.otp.send') }}" class="row g-2 mb-3">
                    @csrf
                    <div class="col-12">
                        <label class="form-label small fw-medium" for="identifier">Email or mobile number</label>
                        <div class="input-group">
                            <input type="text" id="identifier" name="identifier" class="form-control @error('identifier') is-invalid @enderror" value="{{ old('identifier', $identifier) }}" required autocomplete="username">
                            <button class="btn btn-outline-primary">{{ $identifier ? 'Resend' : 'Send OTP' }}</button>
                        </div>
                        @error('identifier')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>
                </form>

                @if($identifier)
                    <form method="POST" action="{{ route('login.otp.verify') }}" class="row g-3">
                        @csrf
                        <div class="col-12">
                            <label class="form-label small fw-medium" for="code">6-digit code sent to {{ $identifier }}</label>
                            <input type="text" id="code" name="code" inputmode="numeric" pattern="\d{6}" maxlength="6" class="form-control form-control-lg text-center @error('code') is-invalid @enderror" style="letter-spacing:.5rem" required autofocus autocomplete="one-time-code">
                            @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <div class="form-text">Code is valid for {{ config('matrimony.otp_expiry_minutes') }} minutes.</div>
                        </div>
                        <div class="col-12"><button class="btn btn-primary w-100 py-2">Verify &amp; login</button></div>
                    </form>
                @endif
                <p class="text-center small mt-4 mb-0"><a href="{{ route('login') }}">Login with password instead</a></p>
            </div>
        </div>
    </div>
</div>
@endsection
