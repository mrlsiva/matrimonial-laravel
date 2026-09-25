@extends('layouts.admin')

@section('title', 'Payment #'.$payment->id)

@section('content')
<a href="{{ route('admin.payments.index') }}" class="btn btn-sm btn-light border mb-3"><i class="bi bi-arrow-left"></i> Payments</a>
<div class="row g-3">
    <div class="col-lg-7">
        <div class="card stat-card"><div class="card-body">
            <div class="d-flex justify-content-between"><h2 class="h6">Transaction</h2><span class="badge bg-{{ $payment->statusBadge() }}">{{ $payment->status }}</span></div>
            <table class="table table-sm small mb-0">
                <tr><th class="w-25">Invoice no.</th><td>{{ $payment->invoice_no ?? '—' }}</td></tr>
                <tr><th>Amount</th><td>₹{{ number_format($payment->amount, 2) }} {{ $payment->currency }}</td></tr>
                <tr><th>Plan</th><td>{{ $payment->plan?->name }}</td></tr>
                <tr><th>Razorpay order</th><td>{{ $payment->razorpay_order_id ?? '—' }}</td></tr>
                <tr><th>Razorpay payment</th><td>{{ $payment->razorpay_payment_id ?? '—' }}</td></tr>
                <tr><th>Method</th><td>{{ $payment->method ?? '—' }}</td></tr>
                <tr><th>Created</th><td>{{ $payment->created_at->format('d M Y, h:i:s A') }}</td></tr>
                <tr><th>Paid at</th><td>{{ $payment->paid_at?->format('d M Y, h:i:s A') ?? '—' }}</td></tr>
                @if($payment->failure_reason)<tr><th>Failure reason</th><td class="text-danger">{{ $payment->failure_reason }}</td></tr>@endif
            </table>
        </div></div>
    </div>
    <div class="col-lg-5">
        <div class="card stat-card mb-3"><div class="card-body">
            <h2 class="h6">Member</h2>
            <p class="small mb-0">
                @if($payment->user && ! $payment->user->trashed())<a href="{{ route('admin.users.show', $payment->user) }}">{{ $payment->user->name }}</a>@else{{ $payment->user?->name }} <span class="badge bg-secondary">deleted</span>@endif
                <br>{{ $payment->user?->email }}<br>{{ $payment->user?->mobile }}<br>{{ $payment->user?->profile?->profile_code }}
            </p>
        </div></div>
        @if($payment->subscription)
            <div class="card stat-card"><div class="card-body">
                <h2 class="h6">Subscription</h2>
                <p class="small mb-0">{{ $payment->subscription->starts_at->format('d M Y') }} → {{ $payment->subscription->expires_at->format('d M Y') }}<br>
                    Status: <span class="badge bg-{{ $payment->subscription->statusBadge() }}">{{ $payment->subscription->isActive() ? 'active' : $payment->subscription->status }}</span><br>
                    Contact views used: {{ $payment->subscription->contact_views_used }}</p>
            </div></div>
        @endif
    </div>
</div>
@endsection
