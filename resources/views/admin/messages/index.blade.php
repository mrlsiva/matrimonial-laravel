@extends('layouts.admin')

@section('title', 'Support Messages')

@section('content')
<ul class="nav nav-pills mb-3">
    @foreach(['' => 'All', 'new' => 'New', 'read' => 'Read', 'replied' => 'Replied'] as $key => $label)
        <li class="nav-item"><a class="nav-link {{ request('status', '') === $key ? 'active' : '' }}" href="{{ route('admin.messages.index', array_filter(['status' => $key])) }}">{{ $label }}</a></li>
    @endforeach
</ul>
<div class="card stat-card">
    <div class="list-group list-group-flush">
        @forelse($messages as $m)
            <a href="{{ route('admin.messages.show', $m) }}" class="list-group-item list-group-item-action d-flex justify-content-between gap-3 {{ $m->status === 'new' ? 'fw-semibold' : '' }}">
                <div class="overflow-hidden">
                    <div>{{ $m->subject }}</div>
                    <div class="small text-muted text-truncate">{{ $m->name }} &lt;{{ $m->email }}&gt; — {{ \Illuminate\Support\Str::limit($m->message, 90) }}</div>
                </div>
                <div class="text-end text-nowrap small">
                    <span class="badge bg-{{ $m->statusBadge() }}">{{ $m->status }}</span><br>
                    <span class="text-muted">{{ $m->created_at->diffForHumans() }}</span>
                </div>
            </a>
        @empty
            <div class="list-group-item text-center text-muted py-5">No messages.</div>
        @endforelse
    </div>
</div>
<div class="mt-3">{{ $messages->links() }}</div>
@endsection
