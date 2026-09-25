<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Admin Login | {{ config('app.name') }}</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="{{ asset('css/app.css') }}?v=5" rel="stylesheet">
</head>
<body class="d-flex align-items-center min-vh-100" style="background:linear-gradient(135deg,#1f0a13,#5a0824)">
<div class="container">
    <div class="row justify-content-center">
        <div class="col-sm-9 col-md-6 col-lg-4">
            <div class="card border-0 shadow-lg">
                <div class="card-body p-4 p-md-5">
                    <div class="text-center mb-4">
                        <img src="{{ asset('images/logo.svg') }}" alt="" height="48">
                        <h1 class="h5 mt-2 mb-0" style="font-family:Poppins,sans-serif">Admin Panel</h1>
                        <small class="text-muted">{{ config('app.name') }}</small>
                    </div>
                    @include('partials.alerts', ['showErrors' => false])
                    <form method="POST" action="{{ route('admin.login') }}" class="row g-3">
                        @csrf
                        <x-input name="email" label="Email" type="email" col="col-12" required autofocus autocomplete="username" />
                        <x-input name="password" label="Password" type="password" col="col-12" required autocomplete="current-password" />
                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="remember" id="remember" value="1">
                                <label class="form-check-label small" for="remember">Keep me signed in</label>
                            </div>
                        </div>
                        <div class="col-12"><button class="btn btn-primary w-100 py-2"><i class="bi bi-shield-lock me-2"></i>Sign in</button></div>
                    </form>
                    <p class="text-center small mt-3 mb-0"><a href="{{ route('password.request') }}">Forgot password?</a></p>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
