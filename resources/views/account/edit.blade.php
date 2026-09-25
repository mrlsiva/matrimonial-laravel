@extends('layouts.app')

@section('title', 'Account Settings')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-7">
        <h1 class="h3 mb-3">Account settings</h1>
        <div class="card card-soft mb-4">
            <div class="card-body">
                <h2 class="h6">Account details</h2>
                <table class="table table-sm small mb-0">
                    <tr><th class="w-25">Name</th><td>{{ $user->name }}</td></tr>
                    <tr><th>Email</th><td>{{ $user->email }} @if($user->email_verified_at)<span class="badge bg-success">Verified</span>@endif</td></tr>
                    <tr><th>Mobile</th><td>+91 {{ $user->mobile }} @if($user->mobile_verified_at)<span class="badge bg-success">Verified</span>@else<span class="badge bg-secondary">Unverified</span>@endif</td></tr>
                    <tr><th>Member since</th><td>{{ $user->created_at->format('d M Y') }}</td></tr>
                </table>
                <p class="small text-muted mt-2 mb-0">To change your email or mobile number, please <a href="{{ route('contact.create') }}">contact support</a>. Mobile numbers are verified automatically the first time you log in with a mobile OTP.</p>
            </div>
        </div>
        <div class="card card-soft">
            <div class="card-body">
                <h2 class="h6">Change password</h2>
                <form method="POST" action="{{ route('account.password') }}" class="row g-3">
                    @csrf @method('PUT')
                    <x-input name="current_password" label="Current password" type="password" col="col-12" required autocomplete="current-password" />
                    <x-input name="password" label="New password" type="password" required autocomplete="new-password" />
                    <x-input name="password_confirmation" label="Confirm new password" type="password" required autocomplete="new-password" />
                    <div class="col-12"><button class="btn btn-primary">Update password</button></div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
