@extends('layouts.admin')

@section('title', 'Subscriptions')

@section('content')
<form method="GET" class="d-flex flex-wrap gap-2 mb-3">
    <select name="status" class="form-select form-select-sm" style="max-width:180px" aria-label="Status">
        <option value="">All statuses</option>
        @foreach(['active', 'expired', 'cancelled'] as $s)<option value="{{ $s }}" @selected(request('status') === $s)>{{ ucfirst($s) }}</option>@endforeach
    </select>
    <select name="plan" class="form-select form-select-sm" style="max-width:180px" aria-label="Plan">
        <option value="">All plans</option>
        @foreach($plans as $id => $name)<option value="{{ $id }}" @selected(request('plan') == $id)>{{ $name }}</option>@endforeach
    </select>
    <button class="btn btn-sm btn-primary">Filter</button>
</form>
<div class="card stat-card">
    <div class="table-responsive">
        <table class="table mb-0 small">
            <thead class="table-light"><tr><th>Member</th><th>Plan</th><th>Amount</th><th>Start</th><th>Expiry</th><th>Contact views</th><th>Status</th></tr></thead>
            <tbody>
            @forelse($subscriptions as $s)
                <tr>
                    <td>@if($s->user && ! $s->user->trashed())<a href="{{ route('admin.users.show', $s->user) }}">{{ $s->user->name }}</a>@else{{ $s->user?->name }}@endif</td>
                    <td><span class="badge bg-{{ $s->plan->badge_color }}">{{ $s->plan->name }}</span></td>
                    <td>₹{{ number_format($s->amount, 2) }}</td>
                    <td>{{ $s->starts_at->format('d M Y') }}</td>
                    <td>{{ $s->expires_at->format('d M Y') }} @if($s->isActive())<span class="text-muted">({{ $s->daysLeft() }}d)</span>@endif</td>
                    <td>{{ $s->contact_views_used }} / {{ $s->plan->contact_views_limit }}</td>
                    <td><span class="badge bg-{{ $s->statusBadge() }}">{{ $s->isActive() ? 'active' : ($s->status === 'active' ? 'expired' : $s->status) }}</span></td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center text-muted py-4">No subscriptions found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $subscriptions->links() }}</div>
@endsection
