@extends('layouts.app')

@section('title', 'Payments & Membership')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">Payments &amp; membership</h1>
    <a href="{{ route('membership.index') }}" class="btn btn-gold btn-sm"><i class="bi bi-gem me-1"></i>Upgrade / renew</a>
</div>

<div class="card card-soft mb-4">
    <div class="card-header bg-white"><strong>Subscriptions</strong></div>
    <div class="table-responsive">
        <table class="table mb-0 small">
            <thead class="table-light"><tr><th>Plan</th><th>Started</th><th>Expires</th><th>Contact views used</th><th>Status</th></tr></thead>
            <tbody>
            @forelse($subscriptions as $s)
                <tr>
                    <td><span class="badge bg-{{ $s->plan->badge_color }}">{{ $s->plan->name }}</span></td>
                    <td>{{ $s->starts_at->format('d M Y') }}</td>
                    <td>{{ $s->expires_at->format('d M Y') }} @if($s->isActive())<span class="text-muted">({{ $s->daysLeft() }} days left)</span>@endif</td>
                    <td>{{ $s->contact_views_used }} / {{ $s->plan->contact_views_limit }}</td>
                    <td><span class="badge bg-{{ $s->statusBadge() }}">{{ $s->isActive() ? 'Active' : ucfirst($s->status === 'active' ? 'expired' : $s->status) }}</span></td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-center text-muted py-4">You are on the Free plan.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="card card-soft">
    <div class="card-header bg-white"><strong>Payment history</strong></div>
    <div class="table-responsive">
        <table class="table mb-0 small">
            <thead class="table-light"><tr><th>Date</th><th>Plan</th><th>Amount</th><th>Invoice</th><th>Status</th><th></th></tr></thead>
            <tbody>
            @forelse($payments as $p)
                <tr>
                    <td>{{ $p->created_at->format('d M Y, h:i A') }}</td>
                    <td>{{ $p->plan?->name }}</td>
                    <td>₹{{ number_format($p->amount, 2) }}</td>
                    <td>{{ $p->invoice_no ?? '—' }}</td>
                    <td><span class="badge bg-{{ $p->statusBadge() }}">{{ ucfirst($p->status === 'created' ? 'incomplete' : $p->status) }}</span>
                        @if($p->failure_reason)<div class="text-muted">{{ $p->failure_reason }}</div>@endif</td>
                    <td>@if($p->isPaid())<a href="{{ route('payments.invoice', $p) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-file-earmark-text"></i> Invoice</a>@endif</td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center text-muted py-4">No payments yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $payments->links() }}</div>
@endsection
