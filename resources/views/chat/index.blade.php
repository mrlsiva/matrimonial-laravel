@extends('layouts.app')

@section('title', $partner ? 'Chat with '.$partner->profile?->profile_code : 'Messages')

@section('content')
<div class="card card-soft overflow-hidden">
    <div class="row g-0 chat-wrap">
        <div class="col-md-4 border-end d-flex flex-column {{ $partner ? 'd-none d-md-flex' : '' }}">
            <div class="p-3 border-bottom"><h1 class="h6 mb-0">Messages</h1></div>
            <div class="chat-list flex-grow-1">
                <div class="list-group list-group-flush">
                    @foreach($conversations as $c)
                        <a href="{{ route('chat.show', $c->user) }}" class="list-group-item list-group-item-action d-flex gap-2 align-items-center {{ $partner?->id === $c->user->id ? 'active' : '' }}">
                            <img src="{{ $c->user->profile?->photo_url ?? asset('images/avatar-male.svg') }}" class="avatar-sm" alt="">
                            <div class="flex-grow-1 overflow-hidden">
                                <div class="d-flex justify-content-between">
                                    <strong class="small">{{ $c->user->profile?->profile_code ?? $c->user->name }}</strong>
                                    <small class="text-muted">{{ $c->last?->created_at->diffForHumans(null, true) }}</small>
                                </div>
                                <div class="small text-muted text-truncate">{{ $c->last?->sender_id === auth()->id() ? 'You: ' : '' }}{{ $c->last?->message }}</div>
                            </div>
                            @if($c->unread)<span class="badge bg-danger rounded-pill">{{ $c->unread }}</span>@endif
                        </a>
                    @endforeach
                    @if($newConnections->isNotEmpty())
                        <div class="list-group-item small text-muted bg-light">New connections</div>
                        @foreach($newConnections as $u)
                            <a href="{{ route('chat.show', $u) }}" class="list-group-item list-group-item-action d-flex gap-2 align-items-center {{ $partner?->id === $u->id ? 'active' : '' }}">
                                <img src="{{ $u->profile?->photo_url }}" class="avatar-sm" alt="">
                                <div><strong class="small">{{ $u->profile?->profile_code }}</strong><div class="small text-muted">Say hello 👋</div></div>
                            </a>
                        @endforeach
                    @endif
                    @if($conversations->isEmpty() && $newConnections->isEmpty())
                        <div class="p-4 text-center text-muted small">No conversations yet. Accepted interests appear here, or <a href="{{ route('membership.index') }}">upgrade</a> to chat with anyone.</div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-md-8 d-flex flex-column">
            @if($partner)
                <div class="p-3 border-bottom d-flex align-items-center gap-2">
                    <a href="{{ route('chat.index') }}" class="btn btn-sm btn-light d-md-none" aria-label="Back"><i class="bi bi-arrow-left"></i></a>
                    <img src="{{ $partner->profile?->photo_url }}" class="avatar-sm" alt="">
                    <div>
                        <a href="{{ $partner->profile ? route('profiles.show', $partner->profile) : '#' }}" class="fw-semibold text-decoration-none">{{ $partner->profile?->profile_code }}</a>
                        <div class="small text-muted">{{ $partner->profile?->age }} yrs · {{ $partner->profile?->location }}</div>
                    </div>
                </div>
                <div class="chat-body flex-grow-1 p-3" id="chatBody">
                    @forelse($messages as $m)
                        <div class="bubble {{ $m->sender_id === auth()->id() ? 'me' : 'them' }}" data-id="{{ $m->id }}">{{ $m->message }}<small>{{ $m->created_at->format('d M, h:i A') }}</small></div>
                    @empty
                        <p class="text-center text-muted small my-5" id="chatEmpty">Start the conversation. Be respectful — never share passwords or send money.</p>
                    @endforelse
                </div>
                @if($canSend)
                    <form id="chatForm" class="p-3 border-top d-flex gap-2" autocomplete="off">
                        <textarea name="message" class="form-control" rows="1" maxlength="2000" placeholder="Type a message…" required aria-label="Message"></textarea>
                        <button class="btn btn-primary px-3" aria-label="Send"><i class="bi bi-send-fill"></i></button>
                    </form>
                @else
                    <div class="p-3 border-top small text-center text-muted">Your chat access has ended. <a href="{{ route('membership.index') }}">Upgrade</a> to keep chatting.</div>
                @endif
            @else
                <div class="d-flex flex-column align-items-center justify-content-center h-100 text-muted p-5 text-center">
                    <i class="bi bi-chat-heart display-3 text-brand"></i>
                    <p class="mt-3">Select a conversation to start chatting.</p>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@if($partner)
@push('scripts')
<script>
(() => {
    const body = document.getElementById('chatBody');
    const form = document.getElementById('chatForm');
    const pollUrl = @json(route('chat.poll', $partner));
    const sendUrl = @json(route('chat.send', $partner));
    let lastId = {{ (int) ($messages->last()?->id ?? 0) }};

    const scroll = () => body.scrollTop = body.scrollHeight;
    const append = (m) => {
        if (body.querySelector(`[data-id="${m.id}"]`)) return;
        document.getElementById('chatEmpty')?.remove();
        const div = document.createElement('div');
        div.className = 'bubble ' + (m.mine ? 'me' : 'them');
        div.dataset.id = m.id;
        div.textContent = m.message;
        const t = document.createElement('small'); t.textContent = m.time; div.appendChild(t);
        body.appendChild(div);
        lastId = Math.max(lastId, m.id);
    };
    scroll();

    form?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const input = form.elements.message;
        const text = input.value.trim();
        if (!text) return;
        input.disabled = true;
        try {
            const data = await api(sendUrl, { method: 'POST', body: { message: text } });
            append(data.message); input.value = ''; scroll();
        } catch (err) { toast(err.message, 'error'); }
        input.disabled = false; input.focus();
    });
    form?.elements.message.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); form.requestSubmit(); }
    });

    // Poll for new messages every 5s while the tab is visible.
    setInterval(async () => {
        if (document.hidden) return;
        try {
            const data = await api(pollUrl + '?after=' + lastId);
            if (data.messages.length) { data.messages.forEach(append); scroll(); }
        } catch (e) { /* ignore transient errors */ }
    }, 5000);
})();
</script>
@endpush
@endif
