@extends('layouts.admin')

@section('title', 'Photo Verification')

@section('content')
<ul class="nav nav-pills mb-3">
    @foreach(['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected'] as $key => $label)
        <li class="nav-item"><a class="nav-link {{ $status === $key ? 'active' : '' }}" href="{{ route('admin.photos.index', ['status' => $key]) }}">{{ $label }}</a></li>
    @endforeach
</ul>

<div class="row g-3">
    @forelse($photos as $photo)
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card stat-card h-100 overflow-hidden">
                <a href="{{ $photo->url }}" target="_blank"><img src="{{ $photo->thumb_url }}" class="card-img-top" alt="" style="aspect-ratio:4/5;object-fit:cover"></a>
                <div class="card-body p-2 small">
                    <a href="{{ route('admin.users.show', $photo->profile->user) }}">{{ $photo->profile->profile_code }}</a>
                    <div class="text-muted">{{ $photo->profile->user->name }} · {{ $photo->created_at->diffForHumans() }}</div>
                    <div class="d-flex gap-1 mt-2">
                        @if($photo->status !== 'approved')
                            <form method="POST" action="{{ route('admin.photos.update', [$photo, 'approved']) }}" class="flex-grow-1">@csrf @method('PATCH')<button class="btn btn-sm btn-success w-100"><i class="bi bi-check-lg"></i></button></form>
                        @endif
                        @if($photo->status !== 'rejected')
                            <form method="POST" action="{{ route('admin.photos.update', [$photo, 'rejected']) }}" class="flex-grow-1">@csrf @method('PATCH')<button class="btn btn-sm btn-outline-danger w-100"><i class="bi bi-x-lg"></i></button></form>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="col-12"><div class="card stat-card"><div class="card-body text-center text-muted py-5">No {{ $status }} photos.</div></div></div>
    @endforelse
</div>
<div class="mt-3">{{ $photos->links() }}</div>
@endsection
