@extends('layouts.app')

@section('title', 'Invoice '.$payment->invoice_no)

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="d-flex justify-content-end gap-2 mb-3 no-print">
            <a href="{{ route('payments.index') }}" class="btn btn-sm btn-light border"><i class="bi bi-arrow-left"></i> Back</a>
            <button onclick="window.print()" class="btn btn-sm btn-primary"><i class="bi bi-printer me-1"></i>Print / Save PDF</button>
        </div>
        <div class="card card-soft">
            <div class="card-body p-4 p-md-5">
                <div class="d-flex justify-content-between align-items-start mb-4">
                    <div>
                        <img src="{{ asset('images/logo.svg') }}" alt="" height="40">
                        <h2 class="h5 mt-2 mb-0">{{ config('app.name') }}</h2>
                        <div class="small text-muted">{{ config('matrimony.support_email') }}<br>{{ config('matrimony.support_phone') }}</div>
                    </div>
                    <div class="text-end">
                        <h1 class="h3 text-brand mb-1">INVOICE</h1>
                        <div class="small"><strong>{{ $payment->invoice_no }}</strong><br>Date: {{ $payment->paid_at->format('d M Y') }}</div>
                        <span class="badge bg-success mt-2">PAID</span>
                    </div>
                </div>
                <div class="mb-4">
                    <div class="small text-muted">Billed to</div>
                    <strong>{{ $payment->user->name }}</strong><br>
                    <span class="small">{{ $payment->user->email }} · +91 {{ $payment->user->mobile }}<br>Profile ID: {{ $payment->user->profile?->profile_code }}</span>
                </div>
                <table class="table">
                    <thead class="table-light"><tr><th>Description</th><th>Validity</th><th class="text-end">Amount</th></tr></thead>
                    <tbody>
                        <tr>
                            <td>{{ $payment->plan?->name }} Membership ({{ $payment->plan?->duration_days }} days)</td>
                            <td class="small">@if($payment->subscription){{ $payment->subscription->starts_at->format('d M Y') }} – {{ $payment->subscription->expires_at->format('d M Y') }}@endif</td>
                            <td class="text-end">₹{{ number_format($payment->amount, 2) }}</td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr><th colspan="2" class="text-end">Total (incl. taxes)</th><th class="text-end">₹{{ number_format($payment->amount, 2) }}</th></tr>
                    </tfoot>
                </table>
                <div class="small text-muted">
                    @if($payment->isManual())
                        Payment method: {{ $payment->methodLabel() }}@if($payment->reference) · Reference: {{ $payment->reference }}@endif
                    @else
                        Payment method: {{ strtoupper($payment->method ?? 'Online') }} via Razorpay<br>
                        Transaction ID: {{ $payment->razorpay_payment_id }} · Order ID: {{ $payment->razorpay_order_id }}
                    @endif
                </div>
                <p class="small text-muted mt-4 mb-0">This is a computer-generated invoice and does not require a signature.</p>
            </div>
        </div>
    </div>
</div>
@endsection
