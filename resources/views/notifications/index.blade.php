@extends('layouts.app')

@section('title', 'Notifications')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">Notifications</h1>
    @if(auth()->user()->unreadNotifications()->exists())
        <form method="POST" action="{{ route('notifications.readAll') }}">@csrf<button class="btn btn-sm btn-outline-secondary">Mark all as read</button></form>
    @endif
</div>
<div class="card card-soft">
    <div class="list-group list-group-flush">
        @forelse($notifications as $n)
            <a href="{{ route('notifications.open', $n->id) }}" class="list-group-item list-group-item-action d-flex gap-3 py-3 {{ $n->read_at ? '' : 'bg-brand-soft' }}">
                <i class="bi {{ $n->data['icon'] ?? 'bi-bell' }} fs-4 text-brand"></i>
                <div class="flex-grow-1">
                    <div class="fw-semibold">{{ $n->data['title'] ?? 'Notification' }}</div>
                    <div class="small text-muted">{{ $n->data['message'] ?? '' }}</div>
                </div>
                <small class="text-muted text-nowrap">{{ $n->created_at->diffForHumans() }}</small>
            </a>
        @empty
            <div class="list-group-item text-center text-muted py-5">You're all caught up.</div>
        @endforelse
    </div>
</div>
<div class="mt-3">{{ $notifications->links() }}</div>
@endsection
