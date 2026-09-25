<?php

namespace App\Services;

use Razorpay\Api\Api;
use Razorpay\Api\Errors\SignatureVerificationError;
use RuntimeException;

class RazorpayService
{
    private ?Api $api = null;

    public function isConfigured(): bool
    {
        return filled(config('services.razorpay.key')) && filled(config('services.razorpay.secret'));
    }

    private function api(): Api
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('Razorpay keys are not configured. Set RAZORPAY_KEY and RAZORPAY_SECRET in .env.');
        }

        return $this->api ??= new Api(config('services.razorpay.key'), config('services.razorpay.secret'));
    }

    /**
     * @return array{id: string, amount: int, currency: string}
     */
    public function createOrder(float $amount, string $receipt, array $notes = []): array
    {
        $order = $this->api()->order->create([
            'amount' => (int) round($amount * 100), // paise
            'currency' => config('services.razorpay.currency', 'INR'),
            'receipt' => $receipt,
            'notes' => $notes,
        ]);

        return ['id' => $order['id'], 'amount' => $order['amount'], 'currency' => $order['currency']];
    }

    public function verifyPaymentSignature(string $orderId, string $paymentId, string $signature): bool
    {
        try {
            $this->api()->utility->verifyPaymentSignature([
                'razorpay_order_id' => $orderId,
                'razorpay_payment_id' => $paymentId,
                'razorpay_signature' => $signature,
            ]);

            return true;
        } catch (SignatureVerificationError) {
            return false;
        }
    }

    public function verifyWebhookSignature(string $payload, string $signature): bool
    {
        $secret = config('services.razorpay.webhook_secret');

        return filled($secret) && hash_equals(hash_hmac('sha256', $payload, $secret), $signature);
    }

    public function fetchPaymentMethod(string $paymentId): ?string
    {
        try {
            return $this->api()->payment->fetch($paymentId)['method'] ?? null;
        } catch (\Throwable) {
            return null;
        }
    }
}
