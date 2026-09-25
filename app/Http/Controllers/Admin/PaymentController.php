<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MembershipPlan;
use App\Models\Payment;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PaymentController extends Controller
{
    public function index(Request $request): View|StreamedResponse
    {
        $query = Payment::with(['user', 'plan'])
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->when($request->plan, fn ($q, $p) => $q->where('membership_plan_id', $p))
            ->when($request->from, fn ($q, $d) => $q->whereDate('created_at', '>=', $d))
            ->when($request->to, fn ($q, $d) => $q->whereDate('created_at', '<=', $d))
            ->when($request->q, fn ($q, $term) => $q->where(fn ($q) => $q
                ->where('invoice_no', 'like', "%{$term}%")
                ->orWhere('razorpay_order_id', 'like', "%{$term}%")
                ->orWhere('razorpay_payment_id', 'like', "%{$term}%")
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
        return view('admin.payments.show', ['payment' => $payment->load(['user.profile', 'plan', 'subscription'])]);
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
            fputcsv($out, ['Invoice', 'Date', 'Member', 'Email', 'Plan', 'Amount', 'Status', 'Order ID', 'Payment ID', 'Method']);
            $query->chunk(500, function ($payments) use ($out) {
                foreach ($payments as $p) {
                    fputcsv($out, [
                        $p->invoice_no, $p->created_at->format('Y-m-d H:i'), $p->user?->name, $p->user?->email,
                        $p->plan?->name, $p->amount, $p->status, $p->razorpay_order_id, $p->razorpay_payment_id, $p->method,
                    ]);
                }
            });
            fclose($out);
        }, 'payments-'.now()->format('Ymd-His').'.csv', ['Content-Type' => 'text/csv']);
    }
}
