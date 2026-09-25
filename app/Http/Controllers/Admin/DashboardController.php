<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use App\Models\Payment;
use App\Models\Profile;
use App\Models\ProfilePhoto;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $paid = Payment::where('status', 'paid');

        $stats = [
            'users' => User::customers()->count(),
            'users_today' => User::customers()->whereDate('created_at', today())->count(),
            'blocked' => User::customers()->where('status', 'blocked')->count(),
            'males' => Profile::where('gender', 'male')->count(),
            'females' => Profile::where('gender', 'female')->count(),
            'pending_profiles' => Profile::where('approval_status', 'pending')->count(),
            'approved_profiles' => Profile::where('approval_status', 'approved')->count(),
            'pending_photos' => ProfilePhoto::where('status', 'pending')->count(),
            'active_subscriptions' => Subscription::where('status', 'active')->where('expires_at', '>', now())->count(),
            'revenue_total' => (float) (clone $paid)->sum('amount'),
            'revenue_month' => (float) (clone $paid)->where('paid_at', '>=', now()->startOfMonth())->sum('amount'),
            'payments_failed' => Payment::where('status', 'failed')->count(),
            'new_messages' => ContactMessage::where('status', 'new')->count(),
        ];

        // Last 6 months of registrations and revenue for the chart.
        $months = collect(range(5, 0))->map(fn ($i) => now()->startOfMonth()->subMonths($i));
        $from = $months->first();

        $registrations = User::customers()->where('created_at', '>=', $from)->get(['created_at'])
            ->groupBy(fn ($u) => $u->created_at->format('Y-m'))->map->count();
        $revenue = Payment::where('status', 'paid')->where('paid_at', '>=', $from)->get(['paid_at', 'amount'])
            ->groupBy(fn ($p) => $p->paid_at->format('Y-m'))->map(fn ($g) => (float) $g->sum('amount'));

        $chart = [
            'labels' => $months->map(fn (Carbon $m) => $m->format('M Y'))->values(),
            'registrations' => $months->map(fn (Carbon $m) => $registrations->get($m->format('Y-m'), 0))->values(),
            'revenue' => $months->map(fn (Carbon $m) => $revenue->get($m->format('Y-m'), 0))->values(),
        ];

        $planBreakdown = Subscription::where('status', 'active')->where('expires_at', '>', now())
            ->with('plan:id,name')->get()->groupBy('plan.name')->map->count();

        return view('admin.dashboard', [
            'stats' => $stats,
            'chart' => $chart,
            'planBreakdown' => $planBreakdown,
            'latestUsers' => User::customers()->with('profile')->latest()->take(6)->get(),
            'latestPayments' => Payment::with(['user', 'plan'])->latest()->take(6)->get(),
        ]);
    }
}
