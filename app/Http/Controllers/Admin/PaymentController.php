<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MembershipPlan;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\User;
use App\Services\OtpService;
use App\Services\SubscriptionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PaymentController extends Controller
{
    public function __construct(private SubscriptionService $subscriptions) {}

    public function index(Request $request): View|StreamedResponse
    {
        $query = Payment::with(['user', 'plan'])
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->when($request->plan, fn ($q, $p) => $q->where('membership_plan_id', $p))
            ->when($request->source, fn ($q, $s) => $q->where('source', $s))
            ->when($request->from, fn ($q, $d) => $q->whereDate('created_at', '>=', $d))
            ->when($request->to, fn ($q, $d) => $q->whereDate('created_at', '<=', $d))
            ->when($request->q, fn ($q, $term) => $q->where(fn ($q) => $q
                ->where('invoice_no', 'like', "%{$term}%")
                ->orWhere('razorpay_order_id', 'like', "%{$term}%")
                ->orWhere('razorpay_payment_id', 'like', "%{$term}%")
                ->orWhere('reference', 'like', "%{$term}%")
                ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$term}%")->orWhere('email', 'like', "%{$term}%"))))
            ->latest();

        if ($request->export === 'csv') {
            return $this->exportCsv($query);
        }

        return view('admin.payments.index', [
            'payments' => $query->paginate(20)->withQueryString(),
            'plans' => MembershipPlan::orderBy('sort_order')->pluck('name', 'id'),
            'totalPaid' => (clone $query)->reorder()->where('status', 'paid')->sum('amount'),
        ]);
    }

    public function show(Payment $payment): View
    {
        return view('admin.payments.show', ['payment' => $payment->load(['user.profile', 'plan', 'subscription', 'recorder'])]);
    }

    /** Record a cash / UPI / bank payment received outside Razorpay. */
    public function create(Request $request): View
    {
        $member = $request->user_id ? User::customers()->with('profile', 'activeSubscription.plan')->find($request->user_id) : null;

        return view('admin.payments.form', [
            'payment' => new Payment(['status' => 'paid', 'method' => 'cash', 'paid_at' => now()]),
            'member' => $member,
            'plans' => $this->paidPlans(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateManual($request);
        $member = $this->findMember($data['member']);
        $plan = MembershipPlan::findOrFail($data['membership_plan_id']);

        $payment = $this->subscriptions->recordManualPayment($member, $plan, $data, $request->user(), $request->boolean('notify'));

        return redirect()->route('admin.payments.show', $payment)->with('success', $payment->isPaid()
            ? "Payment recorded and the {$plan->name} plan is active for {$member->name}."
            : 'Payment saved as pending. Edit it and mark it paid once the money is received to activate the plan.');
    }

    public function edit(Payment $payment): View
    {
        abort_unless($payment->isManual(), 403, 'Online (Razorpay) payments cannot be edited.');

        return view('admin.payments.form', [
            'payment' => $payment->load(['user.profile', 'subscription']),
            'member' => $payment->user,
            'plans' => $this->paidPlans(),
        ]);
    }

    public function update(Request $request, Payment $payment): RedirectResponse
    {
        abort_unless($payment->isManual(), 403, 'Online (Razorpay) payments cannot be edited.');

        $this->subscriptions->updateManualPayment($payment, $this->validateManual($request, $payment), $request->boolean('notify'));

        return redirect()->route('admin.payments.show', $payment)->with('success', 'Payment updated.');
    }

    private function validateManual(Request $request, ?Payment $payment = null): array
    {
        $data = $request->validate([
            'member' => [$payment ? 'exclude' : 'required', 'string', 'max:150'],
            // The plan of a paid payment is fixed: it already granted a subscription.
            'membership_plan_id' => [$payment?->isPaid() ? 'exclude' : 'required', Rule::exists('membership_plans', 'id')->where(fn ($q) => $q->where('price', '>', 0))],
            'amount' => ['required', 'numeric', 'min:0', 'max:9999999'],
            'method' => ['required', Rule::in(array_keys(Payment::MANUAL_METHODS))],
            'reference' => ['nullable', 'string', 'max:100'],
            'status' => ['required', Rule::in(['paid', 'created', 'failed'])],
            'paid_at' => ['nullable', 'required_if:status,paid', 'date', 'before_or_equal:today'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'starts_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after:starts_at'],
        ], [
            'paid_at.required_if' => 'Enter the date the money was received.',
            'paid_at.before_or_equal' => 'The payment date cannot be in the future.',
        ]);

        if (! $payment) {
            $this->findMember($data['member']); // report an unknown member as a form error
        }

        return $data;
    }

    /** Find a member by email, mobile or profile ID. */
    private function findMember(string $needle): User
    {
        $needle = trim($needle);
        $mobile = OtpService::normaliseMobile($needle);

        $member = User::customers()
            ->where(fn ($q) => $q->where('email', strtolower($needle))
                ->when(strlen($mobile) === 10, fn ($q) => $q->orWhere('mobile', $mobile))
                ->orWhereHas('profile', fn ($p) => $p->where('profile_code', strtoupper($needle))))
            ->first();

        if (! $member) {
            throw ValidationException::withMessages(['member' => 'No member found with that email, mobile or profile ID.']);
        }

        return $member;
    }

    private function paidPlans()
    {
        return MembershipPlan::where('price', '>', 0)->orderBy('sort_order')->get();
    }

    public function subscriptions(Request $request): View
    {
        $subscriptions = Subscription::with(['user', 'plan', 'payment'])
            ->when($request->status === 'active', fn ($q) => $q->where('status', 'active')->where('expires_at', '>', now()))
            ->when($request->status === 'expired', fn ($q) => $q->where(fn ($q) => $q->where('status', 'expired')->orWhere('expires_at', '<=', now())))
            ->when($request->status === 'cancelled', fn ($q) => $q->where('status', 'cancelled'))
            ->when($request->plan, fn ($q, $p) => $q->where('membership_plan_id', $p))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.payments.subscriptions', [
            'subscriptions' => $subscriptions,
            'plans' => MembershipPlan::orderBy('sort_order')->pluck('name', 'id'),
        ]);
    }

    private function exportCsv($query): StreamedResponse
    {
        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Invoice', 'Date', 'Member', 'Email', 'Plan', 'Amount', 'Status', 'Source', 'Method', 'Reference', 'Order ID', 'Payment ID', 'Paid at']);
            $query->chunk(500, function ($payments) use ($out) {
                foreach ($payments as $p) {
                    fputcsv($out, [
                        $p->invoice_no, $p->created_at->format('Y-m-d H:i'), $p->user?->name, $p->user?->email,
                        $p->plan?->name, $p->amount, $p->status, $p->source, $p->methodLabel(), $p->reference,
                        $p->razorpay_order_id, $p->razorpay_payment_id, $p->paid_at?->format('Y-m-d H:i'),
                    ]);
                }
            });
            fclose($out);
        }, 'payments-'.now()->format('Ymd-His').'.csv', ['Content-Type' => 'text/csv']);
    }
}
