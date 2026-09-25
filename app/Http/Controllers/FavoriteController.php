<?php

namespace App\Http\Controllers;

use App\Models\Profile;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FavoriteController extends Controller
{
    public function index(Request $request): View
    {
        $ids = $request->user()->favorites()->pluck('users.id');

        return view('favorites.index', [
            'results' => Profile::public()
                ->whereIn('user_id', $ids)
                ->with(['primaryPhoto', 'religion', 'caste', 'city', 'state', 'educationLevel', 'occupation'])
                ->paginate(12),
            'favoriteIds' => $ids->all(),
        ]);
    }

    public function toggle(Request $request, User $user): JsonResponse|RedirectResponse
    {
        abort_if($user->id === $request->user()->id || ! $user->hasApprovedProfile(), 422, 'This profile cannot be shortlisted.');

        $result = $request->user()->favorites()->toggle($user->id);
        $added = count($result['attached']) > 0;
        $message = $added ? 'Added to your shortlist.' : 'Removed from your shortlist.';

        if ($request->expectsJson()) {
            return response()->json(['message' => $message, 'favorited' => $added]);
        }

        return back()->with('success', $message);
    }
}
