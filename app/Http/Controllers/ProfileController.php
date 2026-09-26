<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileRequest;
use App\Models\Profile;
use App\Services\ProfileFormOptions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function create(Request $request): View|RedirectResponse
    {
        if ($request->user()->profile) {
            return redirect()->route('profile.edit');
        }

        return view('profile.form', ProfileFormOptions::for(new Profile(['created_by' => 'self'])));
    }

    public function store(ProfileRequest $request): RedirectResponse
    {
        abort_if($request->user()->profile()->exists(), 409, 'Profile already exists.');

        $profile = new Profile($request->profileData());
        $profile->user()->associate($request->user());
        $profile->save();
        $this->storeHoroscope($request, $profile);

        return redirect()->route('photos.index')
            ->with('success', 'Profile saved and sent for approval. Add photos to get more responses!');
    }

    public function edit(Request $request): View
    {
        return view('profile.form', ProfileFormOptions::for($request->user()->profile));
    }

    public function update(ProfileRequest $request): RedirectResponse
    {
        $profile = $request->user()->profile;
        $profile->fill($request->profileData());

        // Material changes to a rejected profile send it back to the moderation queue.
        if ($profile->approval_status === Profile::STATUS_REJECTED) {
            $profile->forceFill(['approval_status' => Profile::STATUS_PENDING, 'rejection_reason' => null]);
        }

        $profile->save();
        $this->storeHoroscope($request, $profile);

        $message = $profile->approval_status === Profile::STATUS_PENDING
            ? 'Profile updated. It will be visible to others once approved by our team.'
            : 'Profile updated successfully.';

        return redirect()->route('profile.edit')->with('success', $message);
    }

    public function deleteHoroscope(Request $request): RedirectResponse
    {
        $profile = $request->user()->profile;
        if ($profile->horoscope_file) {
            Storage::disk('local')->delete($profile->horoscope_file);
            $profile->forceFill(['horoscope_file' => null])->save();
        }

        return back()->with('success', 'Horoscope removed.');
    }

    /** Horoscopes are stored on the private disk and streamed only to permitted viewers. */
    private function storeHoroscope(ProfileRequest $request, Profile $profile): void
    {
        if (! $request->hasFile('horoscope')) {
            return;
        }

        if ($profile->horoscope_file) {
            Storage::disk('local')->delete($profile->horoscope_file);
        }

        $path = $request->file('horoscope')->store('horoscopes/'.$profile->user_id, 'local');
        $profile->forceFill(['horoscope_file' => $path])->save();
    }
}
