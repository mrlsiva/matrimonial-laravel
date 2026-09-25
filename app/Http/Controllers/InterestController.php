<?php

namespace App\Http\Controllers;

use App\Models\Interest;
use App\Models\User;
use App\Notifications\InterestReceived;
use App\Notifications\InterestResponded;
use App\Services\SubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InterestController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $tab = in_array($request->query('tab'), ['received', 'sent', 'accepted']) ? $request->query('tab') : 'received';
        $with = ['sender.profile.primaryPhoto', 'sender.profile.city', 'receiver.profile.primaryPhoto', 'receiver.profile.city'];

        $query = match ($tab) {
            'sent' => Interest::where('sender_id', $user->id)->where('status', '!=', 'cancelled'),
            'accepted' => Interest::where('status', 'accepted')->where(fn ($q) => $q->where('sender_id', $user->id)->orWhere('receiver_id', $user->id)),
            default => Interest::where('receiver_id', $user->id)->whereIn('status', ['pending', 'rejected']),
        };

        return view('interests.index', [
            'tab' => $tab,
            'interests' => $query->with($with)->latest()->paginate(12)->withQueryString(),
            'counts' => [
                'received' => Interest::where('receiver_id', $user->id)->where('status', 'pending')->count(),
            ],
        ]);
    }

    public function store(Request $request, User $user, SubscriptionService $subscriptions): JsonResponse|RedirectResponse
    {
        $sender = $request->user();
        $request->validate(['message' => ['nullable', 'string', 'max:500']]);

        $error = match (true) {
            $user->id === $sender->id => 'You cannot send interest to yourself.',
            ! $sender->hasApprovedProfile() => 'Your profile must be approved before you can send interests.',
            ! $user->hasApprovedProfile() || $user->isBlocked() => 'This profile is not available.',
            $user->profile->gender === $sender->profile->gender => 'You can only send interest to profiles of the opposite gender.',
            Interest::between($sender->id, $user->id)?->status === 'accepted' => 'You are already connected with this member.',
            Interest::where(['sender_id' => $sender->id, 'receiver_id' => $user->id])->whereIn('status', ['pending', 'rejected'])->exists() => 'You have already sent interest to this member.',
            Interest::where(['sender_id' => $user->id, 'receiver_id' => $sender->id, 'status' => 'pending'])->exists() => 'This member has already sent you an interest. Please respond to it from your Interests page.',
            $subscriptions->interestsRemainingToday($sender) === 0 => 'You have reached your daily interest limit. Upgrade your plan to send more.',
            default => null,
        };

        if ($error) {
            return $this->respond($request, $error, 422);
        }

        $interest = Interest::updateOrCreate(
            ['sender_id' => $sender->id, 'receiver_id' => $user->id],
            ['status' => 'pending', 'message' => $request->message, 'responded_at' => null]
        );

        $user->notify(new InterestReceived($interest->load('sender.profile')));

        return $this->respond($request, 'Interest sent successfully!');
    }

    public function accept(Request $request, Interest $interest): JsonResponse|RedirectResponse
    {
        return $this->respondTo($request, $interest, 'accepted');
    }

    public function reject(Request $request, Interest $interest): JsonResponse|RedirectResponse
    {
        return $this->respondTo($request, $interest, 'rejected');
    }

    public function cancel(Request $request, Interest $interest): JsonResponse|RedirectResponse
    {
        abort_unless($interest->sender_id === $request->user()->id, 403);
        abort_unless($interest->status === 'pending', 422, 'Only pending interests can be withdrawn.');

        $interest->update(['status' => 'cancelled']);

        return $this->respond($request, 'Interest withdrawn.');
    }

    private function respondTo(Request $request, Interest $interest, string $status): JsonResponse|RedirectResponse
    {
        abort_unless($interest->receiver_id === $request->user()->id, 403);

        if (! in_array($interest->status, ['pending', $status === 'accepted' ? 'rejected' : 'pending'], true)) {
            return $this->respond($request, 'This interest can no longer be updated.', 422);
        }

        $interest->update(['status' => $status, 'responded_at' => now()]);
        $interest->sender->notify(new InterestResponded($interest->load('receiver.profile')));

        return $this->respond($request, $status === 'accepted' ? 'Interest accepted! You can now chat with this member.' : 'Interest declined.');
    }

    private function respond(Request $request, string $message, int $status = 200): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $message], $status);
        }

        return back()->with($status >= 400 ? 'error' : 'success', $message);
    }
}
