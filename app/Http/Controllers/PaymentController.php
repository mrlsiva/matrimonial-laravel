<?php

namespace App\Http\Controllers;

use App\Models\MembershipPlan;
use App\Models\Payment;
use App\Services\RazorpayService;
use App\Services\SubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Throwable;

class PaymentController extends Controller
{
    public function __construct(private RazorpayService $razorpay, private SubscriptionService $subscriptions) {}

    public function index(Request $request): View
    {
        return view('payments.index', [
            'payments' => $request->user()->payments()->with('plan')->latest()->paginate(15),
            'subscriptions' => $request->user()->subscriptions()->with('plan')->latest()->get(),
        ]);
    }

    /** Step 1: create a Razorpay order for the selected plan. */
    public function checkout(Request $request, MembershipPlan $plan): JsonResponse
    {
        abort_unless($plan->is_active && ! $plan->isFree(), 404);

        if (! $this->razorpay->isConfigured()) {
            return response()->json(['message' => 'Online payments are not configured yet. Please contact support.'], 503);
        }

        $user = $request->user();
        $payment = Payment::create([
            'user_id' => $user->id,
            'membership_plan_id' => $plan->id,
            'amount' => $plan->price,
            'currency' => config('services.razorpay.currency', 'INR'),
            'status' => 'created',
        ]);

        try {
            $order = $this->razorpay->createOrder((float) $plan->price, 'PAY-'.$payment->id, [
                'payment_id' => (string) $payment->id,
                'user_id' => (string) $user->id,
                'plan' => $plan->slug,
            ]);
        } catch (Throwable $e) {
            Log::error('Razorpay order creation failed', ['payment' => $payment->id, 'error' => $e->getMessage()]);
            $payment->update(['status' => 'failed', 'failure_reason' => 'Order creation failed']);

            return response()->json(['message' => 'Unable to start payment right now. Please try again.'], 502);
        }

        $payment->update(['razorpay_order_id' => $order['id']]);

        return response()->json([
            'key' => config('services.razorpay.key'),
            'order_id' => $order['id'],
            'amount' => $order['amount'],
            'currency' => $order['currency'],
            'name' => config('app.name'),
            'description' => $plan->name.' membership - '.$plan->duration_days.' days',
            'prefill' => ['name' => $user->name, 'email' => $user->email, 'contact' => $user->mobile],
            'callback' => route('payment.verify'),
            'failed' => route('payment.failed'),
        ]);
    }

    /** Step 2: Razorpay checkout success handler posts here; verify signature and activate. */
    public function verify(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'razorpay_order_id' => ['required', 'string'],
            'razorpay_payment_id' => ['required', 'string'],
            'razorpay_signature' => ['required', 'string'],
        ]);

        $payment = Payment::where('razorpay_order_id', $data['razorpay_order_id'])
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        if (! $this->razorpay->verifyPaymentSignature($data['razorpay_order_id'], $data['razorpay_payment_id'], $data['razorpay_signature'])) {
            $payment->update([
                'status' => 'failed',
                'razorpay_payment_id' => $data['razorpay_payment_id'],
                'failure_reason' => 'Signature verification failed',
            ]);
            Log::warning('Razorpay signature mismatch', ['payment' => $payment->id]);

            return redirect()->route('payment.result', $payment);
        }

        $method = $this->razorpay->fetchPaymentMethod($data['razorpay_payment_id']);
        $this->subscriptions->completePayment($payment, $data['razorpay_payment_id'], $data['razorpay_signature'], $method);

        return redirect()->route('payment.result', $payment);
    }

    /** Checkout "payment.failed" / dismissed handler. */
    public function failed(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'razorpay_order_id' => ['required', 'string'],
            'razorpay_payment_id' => ['nullable', 'string'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $payment = Payment::where('razorpay_order_id', $data['razorpay_order_id'])
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        if (! $payment->isPaid()) {
            $payment->update([
                'status' => 'failed',
                'razorpay_payment_id' => $data['razorpay_payment_id'] ?? $payment->razorpay_payment_id,
                'failure_reason' => $data['reason'] ?? 'Payment cancelled',
            ]);
        }

        return redirect()->route('payment.result', $payment);
    }

    public function result(Request $request, Payment $payment): View
    {
        abort_unless($payment->user_id === $request->user()->id, 403);

        return view('payments.result', ['payment' => $payment->load(['plan', 'subscription'])]);
    }

    public function invoice(Request $request, Payment $payment): View
    {
        abort_unless($payment->user_id === $request->user()->id && $payment->isPaid(), 404);

        return view('payments.invoice', ['payment' => $payment->load(['plan', 'subscription', 'user.profile'])]);
    }

    /**
     * Server-to-server confirmation from Razorpay. Activates the plan even if the
     * user closed the browser before the checkout callback completed.
     */
    public function webhook(Request $request): JsonResponse
    {
        $payload = $request->getContent();

        if (! $this->razorpay->verifyWebhookSignature($payload, (string) $request->header('X-Razorpay-Signature'))) {
            return response()->json(['status' => 'invalid signature'], 400);
        }

        $event = $request->input('event');
        $entity = $request->input('payload.payment.entity', []);
        $payment = isset($entity['order_id']) ? Payment::where('razorpay_order_id', $entity['order_id'])->first() : null;

        if ($payment && in_array($event, ['payment.captured', 'order.paid'], true)) {
            $this->subscriptions->completePayment($payment, $entity['id'], null, $entity['method'] ?? null);
        } elseif ($payment && $event === 'payment.failed' && ! $payment->isPaid()) {
            $payment->update([
                'status' => 'failed',
                'razorpay_payment_id' => $entity['id'] ?? null,
                'failure_reason' => $entity['error_description'] ?? 'Payment failed',
            ]);
        }

        return response()->json(['status' => 'ok']);
    }
}
