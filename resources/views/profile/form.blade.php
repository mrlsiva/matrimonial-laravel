@extends('layouts.app')

@section('title', $profile->exists ? 'Edit Profile' : 'Create Profile')

@section('content')
@php
    $action = $profile->exists ? route('profile.update') : route('profile.store');
    $sections = ['basic' => 'Basic details', 'religion' => 'Religion & horoscope', 'career' => 'Education & career', 'location' => 'Location', 'family' => 'Family', 'partner' => 'Partner preferences'];
@endphp
<div class="row g-4">
    <div class="col-lg-3 d-none d-lg-block">
        <div class="list-group sticky-sidebar small">
            @foreach($sections as $id => $label)
                <a href="#{{ $id }}" class="list-group-item list-group-item-action">{{ $label }}</a>
            @endforeach
            @if($profile->exists)
                <a href="{{ route('photos.index') }}" class="list-group-item list-group-item-action"><i class="bi bi-images me-1"></i> Photos</a>
                <a href="{{ route('profiles.show', $profile) }}" class="list-group-item list-group-item-action"><i class="bi bi-eye me-1"></i> Preview profile</a>
            @endif
        </div>
    </div>
    <div class="col-lg-9">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1 class="h3 mb-0">{{ $profile->exists ? 'Edit your profile' : 'Create your profile' }}</h1>
            @if($profile->exists)
                <span class="badge bg-{{ ['approved' => 'success', 'rejected' => 'danger'][$profile->approval_status] ?? 'warning' }}">{{ ucfirst($profile->approval_status) }}</span>
            @endif
        </div>
        @if($profile->approval_status === 'rejected')
            <div class="alert alert-danger small"><strong>Reason:</strong> {{ $profile->rejection_reason }}. Saving changes will resubmit your profile for approval.</div>
        @endif

        <form method="POST" action="{{ $action }}" enctype="multipart/form-data" class="card card-soft">
            @csrf
            @if($profile->exists) @method('PUT') @endif
            <div class="card-body p-4">

                @include('profile._fields')
            </div>
            <div class="card-footer bg-white p-3 text-end sticky-bottom border-top">
                <button class="btn btn-primary px-5"><i class="bi bi-check2-circle me-2"></i>{{ $profile->exists ? 'Save changes' : 'Create profile' }}</button>
            </div>
        </form>
        @if($profile->horoscope_file)
            <form id="deleteHoroscope" method="POST" action="{{ route('profile.horoscope.destroy') }}" onsubmit="return confirm('Remove horoscope?')">@csrf @method('DELETE')</form>
        @endif
    </div>
</div>
@endsection
