@extends('layouts.app')

@section('title', 'Choose a new password')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-7 col-lg-5">
        <div class="card card-soft">
            <div class="card-body p-4 p-md-5">
                <h1 class="h4 text-center mb-4">Choose a new password</h1>
                <form method="POST" action="{{ route('password.update') }}" class="row g-3">
                    @csrf
                    <input type="hidden" name="token" value="{{ $token }}">
                    <x-input name="email" label="Email address" type="email" col="col-12" :value="$email" required />
                    <x-input name="password" label="New password" type="password" col="col-12" required autocomplete="new-password" help="At least 8 characters with letters and numbers." />
                    <x-input name="password_confirmation" label="Confirm new password" type="password" col="col-12" required autocomplete="new-password" />
                    <div class="col-12"><button class="btn btn-primary w-100 py-2">Reset password</button></div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
