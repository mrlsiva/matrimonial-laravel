<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProfilePhoto;
use App\Notifications\PhotoModerated;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PhotoModerationController extends Controller
{
    public function index(Request $request): View
    {
        $status = in_array($request->status, ['pending', 'approved', 'rejected']) ? $request->status : 'pending';

        return view('admin.photos.index', [
            'status' => $status,
            'photos' => ProfilePhoto::with('profile.user')
                ->where('status', $status)
                ->whereHas('profile.user')
                ->oldest()
                ->paginate(24)
                ->withQueryString(),
        ]);
    }

    public function update(ProfilePhoto $photo, string $status): RedirectResponse
    {
        $photo->update(['status' => $status]);
        $photo->profile->user->notify(new PhotoModerated($photo));

        return back()->with('success', 'Photo '.$status.'.');
    }
}
