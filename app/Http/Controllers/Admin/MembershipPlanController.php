<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MembershipPlanRequest;
use App\Models\MembershipPlan;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class MembershipPlanController extends Controller
{
    public function index(): View
    {
        return view('admin.plans.index', [
            'plans' => MembershipPlan::withCount(['subscriptions as active_count' => fn ($q) => $q->where('status', 'active')->where('expires_at', '>', now())])
                ->orderBy('sort_order')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.plans.form', ['plan' => new MembershipPlan(['is_active' => true, 'badge_color' => 'primary'])]);
    }

    public function store(MembershipPlanRequest $request): RedirectResponse
    {
        MembershipPlan::create($request->planData());

        return redirect()->route('admin.plans.index')->with('success', 'Plan created.');
    }

    public function edit(MembershipPlan $plan): View
    {
        return view('admin.plans.form', compact('plan'));
    }

    public function update(MembershipPlanRequest $request, MembershipPlan $plan): RedirectResponse
    {
        $plan->update($request->planData());

        return redirect()->route('admin.plans.index')->with('success', 'Plan updated.');
    }

    public function destroy(MembershipPlan $plan): RedirectResponse
    {
        if ($plan->subscriptions()->exists()) {
            $plan->update(['is_active' => false]);

            return back()->with('success', 'Plan has subscribers, so it was deactivated instead of deleted.');
        }

        $plan->delete();

        return back()->with('success', 'Plan deleted.');
    }
}
