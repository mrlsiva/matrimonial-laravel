@extends('layouts.admin')

@section('title', 'User: '.$user->name)

@section('content')
@php($p = $user->profile)
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <a href="{{ route('admin.users.index') }}" class="btn btn-sm btn-light border"><i class="bi bi-arrow-left"></i> Users</a>
    <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil me-1"></i>Edit details</a>
        <a href="{{ route('admin.payments.create', ['user_id' => $user->id]) }}" class="btn btn-sm btn-outline-success"><i class="bi bi-cash-coin me-1"></i>Record payment</a>
        @if($p)
            <form method="POST" action="{{ route('admin.profiles.verify', $p) }}">@csrf @method('PATCH')
                <button class="btn btn-sm {{ $p->is_verified ? 'btn-outline-primary' : 'btn-primary' }}"><i class="bi bi-patch-check me-1"></i>{{ $p->is_verified ? 'Remove badge' : 'Grant verified badge' }}</button>
            </form>
            @if($p->approval_status !== 'rejected')
                <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#rejectModal">Reject profile</button>
            @endif
        @endif
        @include('admin.users._actions')
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-4">
        <div class="card stat-card mb-3"><div class="card-body">
            <h2 class="h6">Account</h2>
            <table class="table table-sm small mb-0">
                <tr><th>Name</th><td>{{ $user->name }}</td></tr>
                <tr><th>Email</th><td>{{ $user->email }} {!! $user->email_verified_at ? '<i class="bi bi-check-circle-fill text-success"></i>' : '' !!}</td></tr>
                <tr><th>Mobile</th><td>{{ $user->mobile }} {!! $user->mobile_verified_at ? '<i class="bi bi-check-circle-fill text-success"></i>' : '' !!}</td></tr>
                <tr><th>Status</th><td><span class="badge bg-{{ $user->status === 'active' ? 'success' : 'dark' }}">{{ $user->status }}</span></td></tr>
                <tr><th>Joined</th><td>{{ $user->created_at->format('d M Y, h:i A') }}</td></tr>
                <tr><th>Last login</th><td>{{ $user->last_login_at?->format('d M Y, h:i A') ?? '—' }}</td></tr>
                <tr><th>Plan</th><td>{{ $user->activeSubscription?->plan->name ?? 'Free' }} @if($user->activeSubscription)(till {{ $user->activeSubscription->expires_at->format('d M Y') }})@endif</td></tr>
                <tr><th>Interests</th><td>{{ $interestStats['sent'] }} sent · {{ $interestStats['received'] }} received</td></tr>
            </table>
        </div></div>
        @if($p)
            <div class="card stat-card"><div class="card-body">
                <h2 class="h6">Photos</h2>
                <div class="row g-2">
                    @forelse($p->photos as $photo)
                        <div class="col-6">
                            <img src="{{ $photo->thumb_url }}" class="img-fluid rounded" alt="">
                            <div class="d-flex justify-content-between align-items-center mt-1">
                                <span class="badge bg-{{ $photo->statusBadge() }}">{{ $photo->status }}</span>
                                <div class="btn-group btn-group-sm">
                                    @if($photo->status !== 'approved')<form method="POST" action="{{ route('admin.photos.update', [$photo, 'approved']) }}">@csrf @method('PATCH')<button class="btn btn-sm btn-success" title="Approve"><i class="bi bi-check"></i></button></form>@endif
                                    @if($photo->status !== 'rejected')<form method="POST" action="{{ route('admin.photos.update', [$photo, 'rejected']) }}">@csrf @method('PATCH')<button class="btn btn-sm btn-outline-danger" title="Reject"><i class="bi bi-x"></i></button></form>@endif
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-muted small">No photos uploaded.</div>
                    @endforelse
                </div>
            </div></div>
        @endif
    </div>

    <div class="col-lg-8">
        @if($p)
            <div class="card stat-card mb-3"><div class="card-body">
                <div class="d-flex justify-content-between">
                    <h2 class="h6">Profile {{ $p->profile_code }}</h2>
                    <span class="badge bg-{{ ['approved' => 'success', 'rejected' => 'danger'][$p->approval_status] ?? 'warning' }}">{{ $p->approval_status }}</span>
                </div>
                @if($p->rejection_reason)<div class="alert alert-danger small py-2">Rejection reason: {{ $p->rejection_reason }}</div>@endif
                <div class="row small">
                    <div class="col-md-6">
                        <table class="table table-sm">
                            <tr><th>Gender / Age</th><td>{{ ucfirst($p->gender) }}, {{ $p->age }} yrs ({{ $p->date_of_birth->format('d M Y') }})</td></tr>
                            <tr><th>Marital status</th><td>{{ $p->marital_status_label }}</td></tr>
                            <tr><th>Height</th><td>{{ $p->height_label ?? '—' }}</td></tr>
                            <tr><th>Religion / Caste</th><td>{{ $p->religion?->name ?? '—' }} / {{ $p->caste?->name ?? '—' }}</td></tr>
                            <tr><th>Mother tongue</th><td>{{ $p->mother_tongue ?? '—' }}</td></tr>
                            <tr><th>Star / Rasi</th><td>{{ $p->star ?? '—' }} / {{ $p->rasi ?? '—' }}</td></tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-sm">
                            <tr><th>Education</th><td>{{ $p->educationLevel?->name ?? '—' }}</td></tr>
                            <tr><th>Occupation</th><td>{{ $p->occupation?->name ?? '—' }}</td></tr>
                            <tr><th>Income</th><td>{{ $p->income_label ?? '—' }}</td></tr>
                            <tr><th>Location</th><td>{{ $p->location }}</td></tr>
                            <tr><th>Created by</th><td>{{ ucfirst($p->created_by) }}</td></tr>
                            <tr><th>Completeness</th><td>{{ $p->completionPercent() }}%</td></tr>
                        </table>
                    </div>
                </div>
                <h3 class="h6 mt-2">About</h3>
                <p class="small" style="white-space:pre-line">{{ $p->about_me ?? '—' }}</p>
                @if($p->partner_expectations)
                    <h3 class="h6">Partner expectations</h3>
                    <p class="small mb-0" style="white-space:pre-line">{{ $p->partner_expectations }}</p>
                @endif
                @if($p->horoscope_file)
                    <a href="{{ route('admin.profiles.horoscope', $p) }}" target="_blank" class="btn btn-sm btn-outline-secondary mt-2"><i class="bi bi-file-earmark-text me-1"></i>View horoscope</a>
                @endif
            </div></div>
        @else
            <div class="alert alert-info">This user has not created a profile yet.</div>
        @endif

        <div class="card stat-card mb-3"><div class="card-body">
            <h2 class="h6">Subscriptions</h2>
            <table class="table table-sm small mb-0">
                <thead><tr><th>Plan</th><th>Start</th><th>Expiry</th><th>Status</th></tr></thead>
                <tbody>
                @forelse($subscriptions as $s)
                    <tr><td>{{ $s->plan->name }}</td><td>{{ $s->starts_at->format('d M Y') }}</td><td>{{ $s->expires_at->format('d M Y') }}</td><td><span class="badge bg-{{ $s->statusBadge() }}">{{ $s->isActive() ? 'active' : ($s->status === 'active' ? 'expired' : $s->status) }}</span></td></tr>
                @empty
                    <tr><td colspan="4" class="text-muted">None</td></tr>
                @endforelse
                </tbody>
            </table>
        </div></div>

        <div class="card stat-card"><div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
                <h2 class="h6">Payments</h2>
                <a href="{{ route('admin.payments.create', ['user_id' => $user->id]) }}" class="btn btn-sm btn-link p-0"><i class="bi bi-plus-lg"></i> Record payment</a>
            </div>
            <table class="table table-sm small mb-0">
                <thead><tr><th>Date</th><th>Plan</th><th>Amount</th><th>Paid by</th><th>Status</th><th>Invoice</th><th></th></tr></thead>
                <tbody>
                @forelse($payments as $pay)
                    <tr>
                        <td>{{ ($pay->paid_at ?? $pay->created_at)->format('d M Y') }}</td>
                        <td>{{ $pay->plan?->name }}</td>
                        <td>₹{{ number_format($pay->amount, 2) }}</td>
                        <td>{{ $pay->methodLabel() }}@if($pay->isManual()) <span class="badge bg-light text-dark border">manual</span>@endif</td>
                        <td><span class="badge bg-{{ $pay->statusBadge() }}">{{ $pay->status === 'created' ? 'pending' : $pay->status }}</span></td>
                        <td><a href="{{ route('admin.payments.show', $pay) }}">{{ $pay->invoice_no ?? 'View' }}</a></td>
                        <td class="text-end">@if($pay->isManual())<a href="{{ route('admin.payments.edit', $pay) }}" title="Edit payment"><i class="bi bi-pencil"></i></a>@endif</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-muted">None</td></tr>
                @endforelse
                </tbody>
            </table>
        </div></div>
    </div>
</div>

@if($p)
    @include('admin.profiles._reject-modal', ['profile' => $p])
@endif
@endsection
