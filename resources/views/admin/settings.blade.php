@extends('layouts.admin')

@section('title', 'Admin Settings')

@section('content')
<form method="POST" action="{{ route('admin.settings.password') }}" class="card stat-card" style="max-width:640px">
    @csrf @method('PUT')
    <div class="card-body row g-3">
        <x-input name="name" label="Name" :value="$admin->name" required />
        <x-input name="email" label="Login email" type="email" :value="$admin->email" required />
        <x-input name="password" label="New password" type="password" autocomplete="new-password" help="Leave blank to keep current. Min 10 chars with upper/lower case, number and symbol." />
        <x-input name="password_confirmation" label="Confirm new password" type="password" autocomplete="new-password" />
        <x-input name="current_password" label="Current password (required to save)" type="password" col="col-12" required autocomplete="current-password" />
    </div>
    <div class="card-footer bg-white text-end"><button class="btn btn-primary px-4">Save</button></div>
</form>
@endsection
