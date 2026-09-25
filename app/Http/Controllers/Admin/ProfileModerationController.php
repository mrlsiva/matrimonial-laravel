<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Profile;
use App\Notifications\ProfileStatusChanged;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProfileModerationController extends Controller
{
    public function index(Request $request): View
    {
        $status = in_array($request->status, ['pending', 'approved', 'rejected']) ? $request->status : 'pending';

        $profiles = Profile::with(['user', 'primaryPhoto', 'religion', 'caste', 'city', 'occupation'])
            ->withCount(['photos', 'photos as pending_photos_count' => fn ($q) => $q->where('status', 'pending')])
            ->where('approval_status', $status)
            ->whereHas('user')
            ->when($request->q, fn ($q, $term) => $q->where(fn ($q) => $q->where('profile_code', 'like', "%{$term}%")
                ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$term}%")->orWhere('email', 'like', "%{$term}%"))))
            ->oldest('updated_at')
            ->paginate(15)
            ->withQueryString();

        return view('admin.profiles.index', compact('profiles', 'status'));
    }

    public function approve(Profile $profile): RedirectResponse
    {
        $profile->forceFill(['approval_status' => Profile::STATUS_APPROVED, 'approved_at' => now(), 'rejection_reason' => null])->save();
        $profile->user->notify(new ProfileStatusChanged($profile));

        return back()->with('success', "Profile {$profile->profile_code} approved.");
    }

    public function reject(Request $request, Profile $profile): RedirectResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:255']]);

        $profile->forceFill(['approval_status' => Profile::STATUS_REJECTED, 'rejection_reason' => $data['reason']])->save();
        $profile->user->notify(new ProfileStatusChanged($profile));

        return back()->with('success', "Profile {$profile->profile_code} rejected.");
    }

    public function horoscope(Profile $profile): StreamedResponse
    {
        abort_unless($profile->horoscope_file && Storage::disk('local')->exists($profile->horoscope_file), 404);

        return Storage::disk('local')->response($profile->horoscope_file);
    }

    /** Toggle the "verified" trust badge (e.g. after ID document check). */
    public function toggleVerified(Profile $profile): RedirectResponse
    {
        $profile->forceFill(['is_verified' => ! $profile->is_verified])->save();

        return back()->with('success', $profile->is_verified ? 'Verification badge granted.' : 'Verification badge removed.');
    }
}
