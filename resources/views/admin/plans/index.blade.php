@extends('layouts.admin')

@section('title', 'Membership Plans')

@section('content')
<div class="d-flex justify-content-end mb-3">
    <a href="{{ route('admin.plans.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>New plan</a>
</div>
<div class="card stat-card">
    <div class="table-responsive">
        <table class="table mb-0 small">
            <thead class="table-light"><tr><th>Plan</th><th>Price</th><th>Duration</th><th>Contact views</th><th>Interests/day</th><th>Chat</th><th>Active subs</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
            <tbody>
            @forelse($plans as $plan)
                <tr>
                    <td><span class="badge bg-{{ $plan->badge_color }}">{{ $plan->name }}</span> @if($plan->profile_highlight)<i class="bi bi-star-fill text-warning" title="Highlighted"></i>@endif</td>
                    <td>{{ $plan->isFree() ? 'Free' : '₹'.number_format($plan->price, 2) }}</td>
                    <td>{{ $plan->duration_days ? $plan->duration_days.' days' : '—' }}</td>
                    <td>{{ $plan->contact_views_limit }}</td>
                    <td>{{ $plan->daily_interest_limit ?? 'Unlimited' }}</td>
                    <td>{!! $plan->can_chat ? '<i class="bi bi-check-lg text-success"></i>' : '<i class="bi bi-x-lg text-muted"></i>' !!}</td>
                    <td>{{ $plan->active_count }}</td>
                    <td><span class="badge bg-{{ $plan->is_active ? 'success' : 'secondary' }}">{{ $plan->is_active ? 'Active' : 'Inactive' }}</span></td>
                    <td class="text-end text-nowrap">
                        <a href="{{ route('admin.plans.edit', $plan) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                        <form method="POST" action="{{ route('admin.plans.destroy', $plan) }}" class="d-inline" onsubmit="return confirm('Delete this plan?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button></form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="9" class="text-center text-muted py-4">No plans yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
<p class="small text-muted mt-2">The plan priced at ₹0 defines the limits for members without a paid subscription.</p>
@endsection
