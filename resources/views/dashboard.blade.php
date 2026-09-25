@extends('layouts.app')

@section('title', 'My Dashboard')

@section('content')
<div class="row g-4">
    <div class="col-lg-3">
        <div class="card card-soft sticky-sidebar">
            <div class="card-body text-center">
                <img src="{{ $profile->photo_url }}" alt="My photo" class="rounded-circle mb-3" style="width:120px;height:120px;object-fit:cover">
                <h2 class="h5 mb-0">{{ $user->name }} @if($profile->is_verified)<i class="bi bi-patch-check-fill verified-badge" title="Verified"></i>@endif</h2>
                <div class="text-muted small">{{ $profile->profile_code }}</div>
                <div class="mt-2">
                    @switch($profile->approval_status)
                        @case('approved')<span class="badge bg-success">Profile live</span>@break
                        @case('rejected')<span class="badge bg-danger">Changes required</span>@break
                        @default<span class="badge bg-warning text-dark">Pending approval</span>
                    @endswitch
                </div>
                <div class="mt-3 text-start">
                    <div class="d-flex justify-content-between small"><span>Profile completeness</span><strong>{{ $completion }}%</strong></div>
                    <div class="progress" style="height:6px" role="progressbar" aria-valuenow="{{ $completion }}" aria-valuemin="0" aria-valuemax="100">
                        <div class="progress-bar bg-brand" style="width: {{ $completion }}%"></div>
                    </div>
                </div>
                <div class="d-grid gap-2 mt-3">
                    <a href="{{ route('profile.edit') }}" class="btn btn-sm btn-outline-primary">Edit profile</a>
                    <a href="{{ route('photos.index') }}" class="btn btn-sm btn-outline-secondary">Manage photos</a>
                </div>
                <hr>
                @if($subscription)
                    <div class="small">
                        <span class="badge bg-{{ $subscription->plan->badge_color }} mb-1"><i class="bi bi-gem"></i> {{ $subscription->plan->name }}</span><br>
                        Expires {{ $subscription->expires_at->format('d M Y') }}<br>
                        <span class="text-muted">{{ $subscription->contactViewsRemaining() }} contact views left</span>
                    </div>
                @else
                    <div class="small text-muted mb-2">You are on the Free plan.</div>
                    <a href="{{ route('membership.index') }}" class="btn btn-sm btn-gold w-100">Upgrade now</a>
                @endif
            </div>
        </div>
    </div>

    <div class="col-lg-9">
        @if($profile->approval_status === 'rejected')
            <div class="alert alert-danger"><strong>Your profile was not approved.</strong> {{ $profile->rejection_reason }} <a href="{{ route('profile.edit') }}">Update your profile</a> to resubmit.</div>
        @elseif($profile->approval_status === 'pending')
            <div class="alert alert-warning">Your profile is awaiting admin approval. Complete all sections and add photos to speed things up.</div>
        @endif

        <div class="row g-3 mb-4">
            @foreach([
                ['Interests received', $stats['received'], 'bi-heart', 'danger', route('interests.index', ['tab' => 'received'])],
                ['Interests sent', $stats['sent'], 'bi-send', 'primary', route('interests.index', ['tab' => 'sent'])],
                ['Connections', $stats['accepted'], 'bi-people', 'success', route('interests.index', ['tab' => 'accepted'])],
                ['Shortlisted', $stats['shortlisted'], 'bi-star', 'warning', route('favorites.index')],
                ['Profile views', $stats['views'], 'bi-eye', 'info', null],
                ['Interests left today', $interestsLeft ?? '∞', 'bi-lightning', 'secondary', route('membership.index')],
            ] as [$label, $value, $icon, $color, $link])
                <div class="col-6 col-md-4">
                    <a @if($link) href="{{ $link }}" @endif class="text-decoration-none text-dark">
                        <div class="card stat-card h-100">
                            <div class="card-body d-flex align-items-center gap-3">
                                <div class="icon bg-{{ $color }}-subtle text-{{ $color }}"><i class="bi {{ $icon }}"></i></div>
                                <div><div class="h4 mb-0">{{ $value }}</div><div class="small text-muted">{{ $label }}</div></div>
                            </div>
                        </div>
                    </a>
                </div>
            @endforeach
        </div>

        @foreach($ads as $ad)
            <div class="ad-slot mb-4">
                <a href="{{ $ad->link_url ? route('banners.click', $ad) : '#' }}" rel="sponsored noopener" @if($ad->link_url) target="_blank" @endif><img src="{{ $ad->image_url }}" alt="{{ $ad->title }}"></a>
            </div>
        @endforeach

        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="h5 mb-0">Recommended matches</h2>
            <a href="{{ route('matches') }}" class="small">See all <i class="bi bi-arrow-right"></i></a>
        </div>
        @if($matches->isEmpty())
            <div class="card card-soft"><div class="card-body text-center text-muted py-5">
                No matches yet. <a href="{{ route('profile.edit') }}#partner">Set your partner preferences</a> or try the <a href="{{ route('search') }}">advanced search</a>.
            </div></div>
        @else
            <div class="row g-3">
                @foreach($matches as $p)
                    <div class="col-6 col-md-4">@include('partials.profile-card', ['p' => $p])</div>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection
