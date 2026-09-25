@extends('layouts.app')

@section('title', 'Login')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-7 col-lg-5">
        <div class="card card-soft">
            <div class="card-body p-4 p-md-5">
                <h1 class="h3 text-center mb-4">Welcome back</h1>
                <form method="POST" action="{{ route('login') }}" class="row g-3">
                    @csrf
                    <x-input name="identifier" label="Email or mobile number" col="col-12" required autofocus autocomplete="username" />
                    <x-input name="password" label="Password" type="password" col="col-12" required autocomplete="current-password" />
                    <div class="col-12 d-flex justify-content-between align-items-center">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="remember" id="remember" value="1">
                            <label class="form-check-label small" for="remember">Remember me</label>
                        </div>
                        <a href="{{ route('password.request') }}" class="small">Forgot password?</a>
                    </div>
                    <div class="col-12"><button class="btn btn-primary w-100 py-2">Login</button></div>
                </form>
                <div class="text-center my-3 text-muted small">— or —</div>
                <a href="{{ route('login.otp') }}" class="btn btn-outline-primary w-100"><i class="bi bi-phone me-2"></i>Login with OTP</a>
                <p class="text-center small mt-4 mb-0">New here? <a href="{{ route('register') }}">Create a free account</a></p>
            </div>
        </div>
    </div>
</div>
@endsection
