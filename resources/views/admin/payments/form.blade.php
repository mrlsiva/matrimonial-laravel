@extends('layouts.admin')

@section('title', $payment->exists ? 'Edit payment '.($payment->invoice_no ?? '#'.$payment->id) : 'Record manual payment')

@section('content')
@php
    $planLocked = $payment->exists && $payment->isPaid();
    $subscription = $payment->exists ? $payment->subscription : null;
@endphp
<a href="{{ $payment->exists ? route('admin.payments.show', $payment) : ($member ? route('admin.users.show', $member) : route('admin.payments.index')) }}" class="btn btn-sm btn-light border mb-3"><i class="bi bi-arrow-left"></i> Back</a>

<div class="row g-3">
    <div class="col-lg-8">
        <form method="POST" action="{{ $payment->exists ? route('admin.payments.update', $payment) : route('admin.payments.store') }}" class="card stat-card">
            @csrf
            @if($payment->exists) @method('PUT') @endif
            <div class="card-body row g-3">
                @if($payment->exists)
                    <div class="col-12 small">Member: <strong>{{ $member?->name }}</strong> · {{ $member?->email }} · {{ $member?->profile?->profile_code }}</div>
                @else
                    <x-input name="member" label="Member" :value="$member?->email" required col="col-12" placeholder="Email, mobile or profile ID (e.g. {{ config('matrimony.profile_code_prefix') }}100001)"
                             help="{{ $member ? 'Current plan: '.($member->activeSubscription?->plan->name ?? 'Free') : 'The member must already have an account.' }}" />
                @endif

                <div class="col-md-6">
                    <label for="membership_plan_id" class="form-label small fw-medium">Plan<span class="text-danger">*</span></label>
                    <select name="membership_plan_id" id="membership_plan_id" class="form-select @error('membership_plan_id') is-invalid @enderror" @disabled($planLocked) required>
                        <option value="">Select plan</option>
                        @foreach($plans as $plan)
                            <option value="{{ $plan->id }}" data-price="{{ $plan->price }}" @selected(old('membership_plan_id', $payment->membership_plan_id) == $plan->id)>
                                {{ $plan->name }} — ₹{{ number_format($plan->price) }} / {{ $plan->duration_days }} days
                            </option>
                        @endforeach
                    </select>
                    @error('membership_plan_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    @if($planLocked)<div class="form-text">The plan of a paid payment can't change. Mark it failed and record a new payment instead.</div>@endif
                </div>
                <x-input name="amount" label="Amount received (₹)" type="number" step="0.01" min="0" :value="$payment->amount" required col="col-md-6" />

                <x-select name="method" label="Paid by" :options="\App\Models\Payment::MANUAL_METHODS" :selected="$payment->method" required col="col-md-4" />
                <x-input name="reference" label="Receipt / UTR / cheque no." :value="$payment->reference" col="col-md-4" maxlength="100" />
                <x-input name="paid_at" label="Date received" type="date" :value="$payment->paid_at?->toDateString()" :max="now()->toDateString()" col="col-md-4" />

                <x-select name="status" label="Status" :options="['paid' => 'Paid — activate plan', 'created' => 'Pending — not received yet', 'failed' => 'Failed / cancelled']" :selected="$payment->status" required col="col-md-6" />
                <div class="col-md-6 d-flex align-items-end">
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" name="notify" value="1" id="notify" @checked(old('notify', ! $payment->exists))>
                        <label class="form-check-label small" for="notify">Email the member a receipt when it's marked paid</label>
                    </div>
                </div>

                @if($subscription)
                    <div class="col-12"><h3 class="h6 mb-0 mt-2">Plan validity</h3></div>
                    <x-input name="starts_at" label="Starts" type="date" :value="$subscription->starts_at->toDateString()" col="col-md-6" />
                    <x-input name="expires_at" label="Expires" type="date" :value="$subscription->expires_at->toDateString()" col="col-md-6" help="Change to extend or shorten this member's plan." />
                @endif

                <x-input name="notes" label="Notes" type="textarea" rows="2" :value="$payment->notes" col="col-12" maxlength="1000" placeholder="e.g. Paid at branch office, collected by Ravi" />
            </div>
            <div class="card-footer bg-white d-flex justify-content-end gap-2">
                <button class="btn btn-primary px-4"><i class="bi bi-check2-circle me-1"></i>{{ $payment->exists ? 'Save changes' : 'Record payment' }}</button>
            </div>
        </form>
    </div>
    <div class="col-lg-4">
        <div class="card stat-card"><div class="card-body small">
            <h2 class="h6">How it works</h2>
            <ul class="ps-3 mb-0">
                <li><strong>Paid</strong> activates the plan from the date received. The same plan renewed early is added on to the current expiry.</li>
                <li><strong>Pending</strong> saves the entry without activating anything; edit it later and mark it paid.</li>
                <li>Changing a paid payment to <strong>Failed</strong> cancels the plan it activated.</li>
                <li>An invoice number is created automatically and the member can download the invoice from their account.</li>
            </ul>
        </div></div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Fill the amount with the plan price when a plan is picked and the amount is still empty/default.
    (function () {
        const plan = document.getElementById('membership_plan_id');
        const amount = document.getElementById('amount');
        if (!plan || !amount) return;
        let auto = !amount.value;
        amount.addEventListener('input', () => { auto = false; });
        plan.addEventListener('change', () => {
            const price = plan.selectedOptions[0]?.dataset.price;
            if (auto && price) amount.value = price;
        });
    })();
</script>
@endpush
