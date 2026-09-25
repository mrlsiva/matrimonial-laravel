@extends('layouts.app')

@section('title', 'Forgot password')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-7 col-lg-5">
        <div class="card card-soft">
            <div class="card-body p-4 p-md-5">
                <h1 class="h4 text-center">Reset your password</h1>
                <p class="text-center text-muted small mb-4">Enter your registered email and we'll send you a reset link.</p>
                <form method="POST" action="{{ route('password.email') }}" class="row g-3">
                    @csrf
                    <x-input name="email" label="Email address" type="email" col="col-12" required autofocus />
                    <div class="col-12"><button class="btn btn-primary w-100 py-2">Send reset link</button></div>
                </form>
                <p class="text-center small mt-4 mb-0">Remembered it? <a href="{{ route('login') }}">Back to login</a> · <a href="{{ route('login.otp') }}">Login with OTP</a></p>
            </div>
        </div>
    </div>
</div>
@endsection
