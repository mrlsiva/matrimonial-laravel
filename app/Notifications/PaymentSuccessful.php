<?php

namespace App\Notifications;

use App\Models\Payment;

class PaymentSuccessful extends AppNotification
{
    protected bool $sendMail = true;

    public function __construct(private Payment $payment) {}

    protected function title(): string
    {
        return 'Payment successful';
    }

    protected function message(): string
    {
        return "We received ₹{$this->payment->amount} for the {$this->payment->plan?->name} plan. Invoice {$this->payment->invoice_no}.";
    }

    protected function url(): ?string
    {
        return route('payments.invoice', $this->payment);
    }

    protected function icon(): string
    {
        return 'bi-credit-card';
    }
}
