@extends('layouts.admin')

@section('title', 'Message: '.$message->subject)

@section('content')
<div class="d-flex justify-content-between mb-3">
    <a href="{{ route('admin.messages.index') }}" class="btn btn-sm btn-light border"><i class="bi bi-arrow-left"></i> Messages</a>
    <form method="POST" action="{{ route('admin.messages.destroy', $message) }}" onsubmit="return confirm('Delete this message?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i> Delete</button></form>
</div>
<div class="row g-3">
    <div class="col-lg-7">
        <div class="card stat-card"><div class="card-body">
            <h2 class="h5">{{ $message->subject }}</h2>
            <p class="small text-muted">
                From <strong>{{ $message->name }}</strong> &lt;{{ $message->email }}&gt; @if($message->phone)· {{ $message->phone }}@endif
                · {{ $message->created_at->format('d M Y, h:i A') }}
                @if($message->user)· <a href="{{ route('admin.users.show', $message->user) }}">member profile</a>@endif
            </p>
            <div style="white-space:pre-line">{{ $message->message }}</div>
        </div></div>
    </div>
    <div class="col-lg-5">
        <div class="card stat-card"><div class="card-body">
            @if($message->admin_reply)
                <h2 class="h6">Reply sent {{ $message->replied_at?->diffForHumans() }}</h2>
                <div class="small bg-light p-2 rounded mb-3" style="white-space:pre-line">{{ $message->admin_reply }}</div>
            @endif
            <form method="POST" action="{{ route('admin.messages.reply', $message) }}">
                @csrf
                <label class="form-label small fw-medium" for="admin_reply">{{ $message->admin_reply ? 'Send another reply' : 'Reply by email' }}</label>
                <textarea name="admin_reply" id="admin_reply" rows="6" class="form-control @error('admin_reply') is-invalid @enderror" required>{{ old('admin_reply') }}</textarea>
                @error('admin_reply')<div class="invalid-feedback">{{ $message }}</div>@enderror
                <button class="btn btn-primary mt-2"><i class="bi bi-send me-1"></i>Send reply</button>
            </form>
        </div></div>
    </div>
</div>
@endsection
