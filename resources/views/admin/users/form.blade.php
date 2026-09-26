@extends('layouts.admin')

@section('title', $user->exists ? 'Edit member: '.$user->name : 'Add member')

@section('content')
@php($approval = old('approval_status', $profile->exists ? $profile->approval_status : 'approved'))
<div class="d-flex justify-content-between align-items-center mb-3">
    <a href="{{ $user->exists ? route('admin.users.show', $user) : route('admin.users.index') }}" class="btn btn-sm btn-light border"><i class="bi bi-arrow-left"></i> Back</a>
    @unless($user->exists)
        <a href="{{ route('admin.users.import') }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-upload me-1"></i>Add many members from a file</a>
    @endunless
</div>

<form method="POST" action="{{ $user->exists ? route('admin.users.update', $user) : route('admin.users.store') }}" enctype="multipart/form-data" class="card stat-card">
    @csrf
    @if($user->exists) @method('PUT') @endif
    <div class="card-body p-4">
        <h2 class="form-section-title mt-0" id="account">Login account</h2>
        <div class="row g-3">
            <x-input name="name" label="Full name" :value="$user->name" required col="col-md-4" />
            <x-input name="email" label="Email" type="email" :value="$user->email" required col="col-md-4" />
            <x-input name="mobile" label="Mobile" :value="$user->mobile" col="col-md-4" maxlength="14" placeholder="10-digit number" />
            <x-input name="password" label="{{ $user->exists ? 'New password' : 'Password' }}" type="password" :required="! $user->exists" col="col-md-4" autocomplete="new-password"
                     help="{{ $user->exists ? 'Leave blank to keep the current password.' : 'At least 8 characters. The member can also log in with an OTP.' }}" />
            <x-select name="status" label="Account" :options="['active' => 'Active', 'blocked' => 'Blocked']" :selected="$user->status" required col="col-md-4" />
            <div class="col-md-4 d-flex align-items-end">
                <div class="form-check mb-2">
                    <input type="hidden" name="mark_verified" value="0">
                    <input class="form-check-input" type="checkbox" name="mark_verified" value="1" id="mark_verified"
                           @checked(old('mark_verified', $user->exists ? (bool) $user->email_verified_at : true))>
                    <label class="form-check-label small" for="mark_verified">Email &amp; mobile already verified (no OTP needed)</label>
                </div>
            </div>
        </div>

        <h2 class="form-section-title" id="moderation">Profile status</h2>
        <div class="row g-3">
            <x-select name="approval_status" label="Approval" :options="['approved' => 'Approved (visible to members)', 'pending' => 'Pending review', 'rejected' => 'Rejected']" :selected="$approval" required col="col-md-4" />
            <x-input name="rejection_reason" label="Rejection reason" :value="$profile->rejection_reason" col="col-md-5" help="Only needed when rejecting." />
            <div class="col-md-3 d-flex align-items-end">
                <div class="form-check mb-2">
                    <input type="hidden" name="is_verified" value="0">
                    <input class="form-check-input" type="checkbox" name="is_verified" value="1" id="is_verified" @checked(old('is_verified', $profile->is_verified))>
                    <label class="form-check-label small" for="is_verified">Verified badge</label>
                </div>
            </div>
        </div>

        @include('profile._fields', ['adminForm' => true])
    </div>
    <div class="card-footer bg-white p-3 d-flex justify-content-end gap-2 sticky-bottom border-top">
        <a href="{{ $user->exists ? route('admin.users.show', $user) : route('admin.users.index') }}" class="btn btn-light border">Cancel</a>
        <button class="btn btn-primary px-4"><i class="bi bi-check2-circle me-1"></i>{{ $user->exists ? 'Save changes' : 'Create member' }}</button>
    </div>
</form>
@endsection
