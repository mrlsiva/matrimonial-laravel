<?php

namespace App\Http\Controllers;

use App\Models\ProfilePhoto;
use App\Services\ImageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PhotoController extends Controller
{
    public function index(Request $request): View
    {
        $profile = $request->user()->profile;

        return view('profile.photos', [
            'profile' => $profile,
            'photos' => $profile->photos()->get(),
            'maxPhotos' => config('matrimony.max_photos'),
        ]);
    }

    public function store(Request $request, ImageService $images): RedirectResponse
    {
        $profile = $request->user()->profile;
        $max = config('matrimony.max_photos');
        $remaining = $max - $profile->photos()->count();

        $request->validate([
            'photos' => ['required', 'array', 'min:1', 'max:'.max($remaining, 0)],
            'photos.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:'.config('matrimony.photo_max_kb'), 'dimensions:min_width=300,min_height=300,max_width=8000,max_height=8000'],
        ], [
            'photos.max' => "You can have at most {$max} photos. Delete some to upload more.",
            'photos.*.dimensions' => 'Each photo must be at least 300×300 pixels.',
        ]);

        $hasPrimary = $profile->photos()->where('is_primary', true)->exists();
        $sort = (int) $profile->photos()->max('sort_order');

        foreach ($request->file('photos') as $file) {
            $paths = $images->storeProfilePhoto($file, 'profiles/'.$profile->user_id);
            $profile->photos()->create($paths + [
                'is_primary' => ! $hasPrimary,
                'status' => 'pending',
                'sort_order' => ++$sort,
            ]);
            $hasPrimary = true;
        }

        return back()->with('success', 'Photos uploaded. They will appear on your profile after verification.');
    }

    public function makePrimary(Request $request, ProfilePhoto $photo): RedirectResponse
    {
        $this->authorizePhoto($request, $photo);

        DB::transaction(function () use ($photo) {
            ProfilePhoto::where('profile_id', $photo->profile_id)->update(['is_primary' => false]);
            $photo->update(['is_primary' => true]);
        });

        return back()->with('success', 'Profile picture updated.');
    }

    public function destroy(Request $request, ProfilePhoto $photo): RedirectResponse
    {
        $this->authorizePhoto($request, $photo);
        $wasPrimary = $photo->is_primary;
        $photo->delete();

        if ($wasPrimary) {
            ProfilePhoto::where('profile_id', $photo->profile_id)->orderBy('sort_order')->first()?->update(['is_primary' => true]);
        }

        return back()->with('success', 'Photo deleted.');
    }

    private function authorizePhoto(Request $request, ProfilePhoto $photo): void
    {
        abort_unless($photo->profile_id === $request->user()->profile->id, 403);
    }
}
