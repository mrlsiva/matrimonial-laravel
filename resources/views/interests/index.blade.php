@extends('layouts.app')

@section('title', 'Interests')

@section('content')
<h1 class="h3 mb-3">Interests</h1>
<ul class="nav nav-pills mb-4">
    <li class="nav-item"><a class="nav-link {{ $tab === 'received' ? 'active' : '' }}" href="{{ route('interests.index', ['tab' => 'received']) }}">Received @if($counts['received'])<span class="badge bg-danger ms-1">{{ $counts['received'] }}</span>@endif</a></li>
    <li class="nav-item"><a class="nav-link {{ $tab === 'sent' ? 'active' : '' }}" href="{{ route('interests.index', ['tab' => 'sent']) }}">Sent</a></li>
    <li class="nav-item"><a class="nav-link {{ $tab === 'accepted' ? 'active' : '' }}" href="{{ route('interests.index', ['tab' => 'accepted']) }}">Connections</a></li>
</ul>

@forelse($interests as $interest)
    @php($other = $interest->sender_id === auth()->id() ? $interest->receiver : $interest->sender)
    @php($p = $other?->profile)
    @continue(! $p)
    <div class="card card-soft mb-3">
        <div class="card-body d-flex flex-wrap align-items-center gap-3">
            <a href="{{ route('profiles.show', $p) }}"><img src="{{ $p->photo_url }}" alt="" class="rounded" style="width:72px;height:90px;object-fit:cover"></a>
            <div class="flex-grow-1">
                <a href="{{ route('profiles.show', $p) }}" class="fw-semibold text-decoration-none">{{ $p->profile_code }}</a>
                @if($p->is_verified)<i class="bi bi-patch-check-fill verified-badge"></i>@endif
                <div class="small text-muted">{{ $p->age }} yrs · {{ $p->city?->name }}</div>
                @if($interest->message)<div class="small fst-italic mt-1">"{{ $interest->message }}"</div>@endif
                <div class="small text-muted mt-1">
                    {{ $interest->sender_id === auth()->id() ? 'Sent' : 'Received' }} {{ $interest->created_at->diffForHumans() }}
                    · <span class="badge bg-{{ $interest->statusBadge() }}">{{ ucfirst($interest->status) }}</span>
                </div>
            </div>
            <div class="d-flex gap-2">
                @if($interest->receiver_id === auth()->id() && $interest->status === 'pending')
                    <form method="POST" action="{{ route('interests.accept', $interest) }}">@csrf @method('PATCH')<button class="btn btn-success btn-sm"><i class="bi bi-check-lg"></i> Accept</button></form>
                    <form method="POST" action="{{ route('interests.reject', $interest) }}">@csrf @method('PATCH')<button class="btn btn-outline-danger btn-sm"><i class="bi bi-x-lg"></i> Decline</button></form>
                @elseif($interest->receiver_id === auth()->id() && $interest->status === 'rejected')
                    <form method="POST" action="{{ route('interests.accept', $interest) }}">@csrf @method('PATCH')<button class="btn btn-outline-success btn-sm">Accept instead</button></form>
                @elseif($interest->sender_id === auth()->id() && $interest->status === 'pending')
                    <form method="POST" action="{{ route('interests.cancel', $interest) }}" onsubmit="return confirm('Withdraw this interest?')">@csrf @method('DELETE')<button class="btn btn-outline-secondary btn-sm">Withdraw</button></form>
                @elseif($interest->status === 'accepted')
                    <a href="{{ route('chat.show', $other) }}" class="btn btn-primary btn-sm"><i class="bi bi-chat-dots"></i> Chat</a>
                @endif
            </div>
        </div>
    </div>
@empty
    <div class="card card-soft"><div class="card-body text-center text-muted py-5">
        <i class="bi bi-heart display-5"></i>
        <p class="mt-3">Nothing here yet.</p>
        <a href="{{ route('matches') }}" class="btn btn-primary">Find matches</a>
    </div></div>
@endforelse

<div class="mt-3">{{ $interests->links() }}</div>
@endsection
