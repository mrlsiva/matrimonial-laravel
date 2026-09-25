<?php

namespace App\Http\Controllers;

use App\Models\MembershipPlan;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MembershipController extends Controller
{
    public function index(Request $request): View
    {
        return view('membership.index', [
            'plans' => MembershipPlan::active()->get(),
            'subscription' => $request->user()?->activeSubscription()->with('plan')->first(),
            'razorpayKey' => config('services.razorpay.key'),
        ]);
    }
}
