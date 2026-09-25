<?php

namespace App\Http\Controllers;

use App\Models\Banner;
use App\Models\Interest;
use App\Models\Profile;
use App\Services\SubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProfileViewController extends Controller
{
    public function __construct(private SubscriptionService $subscriptions) {}

    public function show(Request $request, Profile $profile): View
    {
        $viewer = $request->user();
        $isOwner = $profile->user_id === $viewer->id;

        // Unapproved or blocked profiles are only visible to their owner.
        abort_unless($isOwner || $profile->isPublic(), 404);

        if (! $isOwner) {
            // Count one view per viewer per session.
            $key = 'viewed_profile_'.$profile->id;
            if (! $request->session()->has($key)) {
                $profile->increment('views_count');
                $request->session()->put($key, true);
            }
        }

        $profile->load(['user', 'religion', 'caste', 'educationLevel', 'occupation', 'state', 'city', 'partnerReligion']);
        $owner = $profile->user;
        $plan = $this->subscriptions->effectivePlan($viewer);

        return view('profile.show', [
            'profile' => $profile,
            'isOwner' => $isOwner,
            'photos' => $isOwner ? $profile->photos()->get() : $profile->approvedPhotos()->get(),
            'interest' => $isOwner ? null : Interest::between($viewer->id, $owner->id),
            'isFavorite' => $viewer->hasFavorited($owner),
            'canChat' => ! $isOwner && $viewer->canChatWith($owner),
            'contactVisible' => $this->subscriptions->canViewContact($viewer, $owner),
            'canViewHoroscope' => $isOwner || (bool) $plan?->can_view_horoscope || $viewer->hasAcceptedInterestWith($owner),
            'subscription' => $viewer->activeSubscription()->with('plan')->first(),
            'ads' => Banner::live('sidebar')->get(),
        ]);
    }

    /** Reveal contact details, consuming one contact view from the viewer's plan. */
    public function contact(Request $request, Profile $profile): JsonResponse
    {
        abort_unless($profile->isPublic(), 404);
        $viewer = $request->user();
        $owner = $profile->user;

        if (! $this->subscriptions->useContactView($viewer, $owner)) {
            return response()->json([
                'message' => 'Upgrade to a premium plan (or get your interest accepted) to view contact details.',
                'upgrade_url' => route('membership.index'),
            ], 402);
        }

        return response()->json([
            'name' => $owner->name,
            'mobile' => $owner->mobile ? '+91 '.$owner->mobile : '—',
            'email' => $owner->email,
            'remaining' => $viewer->activeSubscription()->with('plan')->first()?->contactViewsRemaining(),
        ]);
    }

    public function horoscope(Request $request, Profile $profile): StreamedResponse
    {
        $viewer = $request->user();
        $isOwner = $profile->user_id === $viewer->id;
        $plan = $this->subscriptions->effectivePlan($viewer);

        abort_unless($profile->horoscope_file && ($isOwner || ($profile->isPublic()
            && ($plan?->can_view_horoscope || $viewer->hasAcceptedInterestWith($profile->user)))), 403);

        return Storage::disk('local')->response($profile->horoscope_file, 'horoscope-'.$profile->profile_code.'.'.pathinfo($profile->horoscope_file, PATHINFO_EXTENSION));
    }
}
