<?php

namespace App\Http\Controllers;

use App\Models\Banner;
use App\Models\Interest;
use App\Services\ProfileSearchService;
use App\Services\SubscriptionService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request, ProfileSearchService $search, SubscriptionService $subscriptions): View
    {
        $user = $request->user()->load(['profile.primaryPhoto', 'activeSubscription.plan']);

        return view('dashboard', [
            'user' => $user,
            'profile' => $user->profile,
            'subscription' => $user->activeSubscription,
            'completion' => $user->profile->completionPercent(),
            'stats' => [
                'received' => Interest::where('receiver_id', $user->id)->where('status', 'pending')->count(),
                'sent' => Interest::where('sender_id', $user->id)->count(),
                'accepted' => Interest::where('status', 'accepted')->where(fn ($q) => $q->where('sender_id', $user->id)->orWhere('receiver_id', $user->id))->count(),
                'shortlisted' => $user->favorites()->count(),
                'views' => $user->profile->views_count,
            ],
            'interestsLeft' => $subscriptions->interestsRemainingToday($user),
            'matches' => $search->matchQuery($user)->take(6)->get(),
            'ads' => Banner::live('dashboard')->get(),
        ]);
    }
}
