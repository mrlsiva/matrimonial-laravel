@extends('layouts.app')

@section('title', $payment->isPaid() ? 'Payment successful' : 'Payment failed')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8 col-lg-6">
        <div class="card card-soft text-center">
            <div class="card-body p-5">
                @if($payment->isPaid())
                    <i class="bi bi-check-circle-fill text-success display-3"></i>
                    <h1 class="h3 mt-3">Payment successful!</h1>
                    <p class="text-muted">Your <strong>{{ $payment->plan?->name }}</strong> membership is active
                        @if($payment->subscription) until <strong>{{ $payment->subscription->expires_at->format('d M Y') }}</strong>@endif.</p>
                    <table class="table table-sm small text-start mt-4">
                        <tr><th>Invoice no.</th><td>{{ $payment->invoice_no }}</td></tr>
                        <tr><th>Amount paid</th><td>₹{{ number_format($payment->amount, 2) }}</td></tr>
                        <tr><th>Payment ID</th><td>{{ $payment->razorpay_payment_id }}</td></tr>
                        <tr><th>Date</th><td>{{ $payment->paid_at?->format('d M Y, h:i A') }}</td></tr>
                    </table>
                    <div class="d-flex gap-2 justify-content-center">
                        <a href="{{ route('payments.invoice', $payment) }}" class="btn btn-outline-primary">View invoice</a>
                        <a href="{{ route('matches') }}" class="btn btn-primary">Start connecting</a>
                    </div>
                @else
                    <i class="bi bi-x-circle-fill text-danger display-3"></i>
                    <h1 class="h3 mt-3">Payment {{ $payment->status === 'created' ? 'incomplete' : 'failed' }}</h1>
                    <p class="text-muted">{{ $payment->failure_reason ?? 'The payment could not be completed.' }}<br>
                        If money was debited from your account, it will be auto-refunded by your bank within 5–7 working days, or your plan will be activated automatically once Razorpay confirms the payment.</p>
                    <div class="d-flex gap-2 justify-content-center">
                        <a href="{{ route('membership.index') }}" class="btn btn-primary">Try again</a>
                        <a href="{{ route('contact.create') }}" class="btn btn-outline-secondary">Contact support</a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
