@extends('layouts.app')

@section('title', 'Membership Plans')
@section('meta_description', 'Compare Free, Gold and Platinum membership plans on '.config('app.name').'. Chat instantly and view verified contact details.')

@section('content')
<div class="text-center mb-5">
    <h1 class="section-title">Choose your membership</h1>
    <p class="text-muted mt-3">Secure payments powered by Razorpay · UPI, cards, netbanking and wallets accepted.</p>
    @if($subscription)
        <div class="alert alert-success d-inline-block">
            <i class="bi bi-gem me-1"></i> You are on <strong>{{ $subscription->plan->name }}</strong> until {{ $subscription->expires_at->format('d M Y') }}
            ({{ $subscription->daysLeft() }} days left).
        </div>
    @endif
</div>

@include('membership._plans')

<div class="text-center small text-muted mt-4">
    Prices are inclusive of applicable taxes. Renewing the same plan extends your current expiry date; switching plans starts the new plan immediately.
</div>

{{-- Hidden form used to post the Razorpay result back to the server --}}
<form id="rzpForm" method="POST" class="d-none">
    @csrf
    <input type="hidden" name="razorpay_order_id">
    <input type="hidden" name="razorpay_payment_id">
    <input type="hidden" name="razorpay_signature">
    <input type="hidden" name="reason">
</form>
@endsection

@auth
@push('scripts')
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script>
document.querySelectorAll('.js-buy-plan').forEach(btn => {
    btn.addEventListener('click', async () => {
        const original = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Please wait…';
        try {
            const order = await api(btn.dataset.checkout, { method: 'POST' });
            const form = document.getElementById('rzpForm');
            const submit = (action, fields) => {
                form.action = action;
                Object.entries(fields).forEach(([k, v]) => form.elements[k].value = v || '');
                form.submit();
            };
            let settled = false;
            const rzp = new Razorpay({
                key: order.key,
                amount: order.amount,
                currency: order.currency,
                name: order.name,
                description: order.description,
                order_id: order.order_id,
                prefill: order.prefill,
                theme: { color: '#b0174b' },
                handler: (res) => { settled = true; submit(order.callback, res); },
                modal: {
                    ondismiss: () => {
                        if (!settled) submit(order.failed, { razorpay_order_id: order.order_id, reason: 'Checkout closed by user' });
                    },
                },
            });
            rzp.on('payment.failed', (res) => {
                settled = true;
                submit(order.failed, {
                    razorpay_order_id: order.order_id,
                    razorpay_payment_id: res.error?.metadata?.payment_id,
                    reason: res.error?.description,
                });
            });
            rzp.open();
        } catch (e) {
            toast(e.message, 'error');
        }
        btn.disabled = false;
        btn.innerHTML = original;
    });
});
</script>
@endpush
@endauth
