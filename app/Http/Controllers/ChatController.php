<?php

namespace App\Http\Controllers;

use App\Models\Chat;
use App\Models\Interest;
use App\Models\User;
use App\Notifications\NewChatMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class ChatController extends Controller
{
    public function index(Request $request): View
    {
        return view('chat.index', $this->sidebarData($request->user()) + ['partner' => null, 'messages' => collect(), 'canSend' => false]);
    }

    public function show(Request $request, User $user): View|RedirectResponse
    {
        $me = $request->user();
        $canSend = $me->canChatWith($user);
        $hasHistory = Chat::conversation($me->id, $user->id)->exists();

        if (! $canSend && ! $hasHistory) {
            return redirect()->route('membership.index')
                ->with('error', 'Chat is available with a premium membership or after your interest is accepted.');
        }

        Chat::where('sender_id', $user->id)->where('receiver_id', $me->id)->whereNull('read_at')->update(['read_at' => now()]);

        $messages = Chat::conversation($me->id, $user->id)->latest('id')->take(50)->get()->reverse()->values();

        return view('chat.index', $this->sidebarData($me) + [
            'partner' => $user->load('profile.primaryPhoto'),
            'messages' => $messages,
            'canSend' => $canSend,
        ]);
    }

    public function poll(Request $request, User $user): JsonResponse
    {
        $me = $request->user();
        $after = (int) $request->query('after', 0);

        $messages = Chat::conversation($me->id, $user->id)->where('id', '>', $after)->orderBy('id')->take(100)->get();
        Chat::where('sender_id', $user->id)->where('receiver_id', $me->id)->whereNull('read_at')->update(['read_at' => now()]);

        return response()->json(['messages' => $messages->map(fn (Chat $c) => $this->present($c, $me))]);
    }

    public function send(Request $request, User $user): JsonResponse
    {
        $me = $request->user();
        abort_unless($me->canChatWith($user), 403, 'You are not allowed to message this member.');

        $data = $request->validate(['message' => ['required', 'string', 'max:2000']]);

        // Notify only for the first unread message, to avoid flooding the bell.
        $alreadyUnread = Chat::where('sender_id', $me->id)->where('receiver_id', $user->id)->whereNull('read_at')->exists();

        $chat = Chat::create(['sender_id' => $me->id, 'receiver_id' => $user->id, 'message' => trim($data['message'])]);

        if (! $alreadyUnread) {
            $user->notify(new NewChatMessage($me));
        }

        return response()->json(['message' => $this->present($chat, $me)], 201);
    }

    private function present(Chat $chat, User $me): array
    {
        return [
            'id' => $chat->id,
            'mine' => $chat->sender_id === $me->id,
            'message' => $chat->message, // rendered client-side via textContent
            'time' => $chat->created_at->format('d M, h:i A'),
            'read' => (bool) $chat->read_at,
        ];
    }

    /** Conversation list plus accepted connections that have not been messaged yet. */
    private function sidebarData(User $me): array
    {
        $threads = Chat::query()
            ->selectRaw('CASE WHEN sender_id = ? THEN receiver_id ELSE sender_id END AS partner_id, MAX(id) AS last_id', [$me->id])
            ->where(fn ($q) => $q->where('sender_id', $me->id)->orWhere('receiver_id', $me->id))
            ->groupBy('partner_id')
            ->orderByDesc('last_id')
            ->limit(50)
            ->get();

        $lastMessages = Chat::whereIn('id', $threads->pluck('last_id'))->get()->keyBy('id');
        $unread = Chat::where('receiver_id', $me->id)->whereNull('read_at')
            ->selectRaw('sender_id, COUNT(*) AS total')->groupBy('sender_id')->pluck('total', 'sender_id');

        $connectionIds = Interest::where('status', 'accepted')
            ->where(fn ($q) => $q->where('sender_id', $me->id)->orWhere('receiver_id', $me->id))
            ->get(['sender_id', 'receiver_id'])
            ->map(fn ($i) => $i->sender_id === $me->id ? $i->receiver_id : $i->sender_id);

        $partnerIds = $threads->pluck('partner_id')->merge($connectionIds)->unique();
        $users = User::with('profile.primaryPhoto')->whereIn('id', $partnerIds)->get()->keyBy('id');

        $conversations = $threads->map(fn ($t) => (object) [
            'user' => $users->get($t->partner_id),
            'last' => $lastMessages->get($t->last_id),
            'unread' => $unread->get($t->partner_id, 0),
        ])->filter(fn ($c) => $c->user);

        /** @var Collection $newConnections */
        $newConnections = $connectionIds->diff($threads->pluck('partner_id'))->map(fn ($id) => $users->get($id))->filter();

        return ['conversations' => $conversations, 'newConnections' => $newConnections];
    }
}
