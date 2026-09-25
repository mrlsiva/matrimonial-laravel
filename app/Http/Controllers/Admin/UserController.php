<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Profile;
use App\Models\User;
use App\Notifications\ProfileStatusChanged;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $users = User::customers()
            ->with(['profile', 'activeSubscription.plan'])
            ->when($request->q, fn ($q, $term) => $q->where(fn ($q) => $q
                ->where('name', 'like', "%{$term}%")
                ->orWhere('email', 'like', "%{$term}%")
                ->orWhere('mobile', 'like', "%{$term}%")
                ->orWhereHas('profile', fn ($p) => $p->where('profile_code', $term))))
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->when($request->approval, fn ($q, $s) => $q->whereHas('profile', fn ($p) => $p->where('approval_status', $s)))
            ->when($request->gender, fn ($q, $g) => $q->whereHas('profile', fn ($p) => $p->where('gender', $g)))
            ->when($request->premium === '1', fn ($q) => $q->whereHas('activeSubscription'))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.users.index', compact('users'));
    }

    public function show(User $user): View
    {
        abort_if($user->isAdmin(), 404);

        $user->load([
            'profile.photos', 'profile.religion', 'profile.caste', 'profile.educationLevel',
            'profile.occupation', 'profile.state', 'profile.city', 'activeSubscription.plan',
        ]);

        return view('admin.users.show', [
            'user' => $user,
            'payments' => $user->payments()->with('plan')->latest()->get(),
            'subscriptions' => $user->subscriptions()->with('plan')->latest()->get(),
            'interestStats' => [
                'sent' => $user->sentInterests()->count(),
                'received' => $user->receivedInterests()->count(),
            ],
        ]);
    }

    /** Approve / block / unblock an account. "approve" also approves the profile. */
    public function updateStatus(Request $request, User $user): RedirectResponse
    {
        abort_if($user->isAdmin(), 403);
        $request->validate(['action' => ['required', Rule::in(['approve', 'block', 'unblock'])]]);

        switch ($request->action) {
            case 'block':
                $user->update(['status' => 'blocked']);
                DB::table('sessions')->where('user_id', $user->id)->delete();
                break;
            case 'unblock':
                $user->update(['status' => 'active']);
                break;
            case 'approve':
                $user->update(['status' => 'active']);
                if ($user->profile && $user->profile->approval_status !== Profile::STATUS_APPROVED) {
                    $user->profile->forceFill(['approval_status' => Profile::STATUS_APPROVED, 'approved_at' => now(), 'rejection_reason' => null])->save();
                    $user->notify(new ProfileStatusChanged($user->profile));
                }
                break;
        }

        $done = ['approve' => 'approved', 'block' => 'blocked', 'unblock' => 'unblocked'][$request->action];

        return back()->with('success', "User {$done} successfully.");
    }

    public function destroy(User $user): RedirectResponse
    {
        abort_if($user->isAdmin(), 403);

        DB::table('sessions')->where('user_id', $user->id)->delete();
        // Soft delete keeps payment history intact for accounting.
        $user->forceFill(['status' => 'blocked'])->save();
        $user->profile?->forceFill(['approval_status' => Profile::STATUS_REJECTED, 'rejection_reason' => 'Account deleted'])->save();
        $user->delete();

        return redirect()->route('admin.users.index')->with('success', 'User deleted.');
    }
}
