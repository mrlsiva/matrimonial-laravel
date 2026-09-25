@extends('layouts.admin')

@section('title', 'Profile Approval')

@section('content')
<div class="d-flex flex-wrap justify-content-between gap-2 mb-3">
    <ul class="nav nav-pills">
        @foreach(['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected'] as $key => $label)
            <li class="nav-item"><a class="nav-link {{ $status === $key ? 'active' : '' }}" href="{{ route('admin.profiles.index', ['status' => $key]) }}">{{ $label }}</a></li>
        @endforeach
    </ul>
    <form method="GET" class="d-flex gap-2">
        <input type="hidden" name="status" value="{{ $status }}">
        <input type="search" name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="Profile ID, name or email" aria-label="Search">
        <button class="btn btn-sm btn-primary">Search</button>
    </form>
</div>

<div class="card stat-card">
    <div class="table-responsive">
        <table class="table mb-0 small">
            <thead class="table-light"><tr><th></th><th>Profile</th><th>Details</th><th>Photos</th><th>Complete</th><th>Updated</th><th class="text-end">Actions</th></tr></thead>
            <tbody>
            @forelse($profiles as $p)
                <tr>
                    <td><img src="{{ $p->photo_url }}" alt="" class="rounded" style="width:48px;height:60px;object-fit:cover"></td>
                    <td>
                        <a href="{{ route('admin.users.show', $p->user) }}" class="fw-semibold">{{ $p->user->name }}</a>
                        @if($p->is_verified)<i class="bi bi-patch-check-fill verified-badge"></i>@endif
                        <div class="text-muted">{{ $p->profile_code }} · {{ $p->user->email }}</div>
                    </td>
                    <td>{{ ucfirst($p->gender) }}, {{ $p->age }} yrs<br><span class="text-muted">{{ $p->religion?->name }} {{ $p->caste?->name ? '· '.$p->caste->name : '' }} · {{ $p->city?->name }}</span></td>
                    <td>{{ $p->photos_count }} @if($p->pending_photos_count)<span class="badge bg-warning text-dark">{{ $p->pending_photos_count }} pending</span>@endif</td>
                    <td>{{ $p->completionPercent() }}%</td>
                    <td>{{ $p->updated_at->diffForHumans() }}</td>
                    <td class="text-end text-nowrap">
                        @if($status !== 'approved')
                            <form method="POST" action="{{ route('admin.profiles.approve', $p) }}" class="d-inline">@csrf @method('PATCH')<button class="btn btn-sm btn-success"><i class="bi bi-check-lg"></i> Approve</button></form>
                        @endif
                        @if($status !== 'rejected')
                            <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#rejectModal{{ $p->id }}">Reject</button>
                        @endif
                        <form method="POST" action="{{ route('admin.profiles.verify', $p) }}" class="d-inline">@csrf @method('PATCH')
                            <button class="btn btn-sm {{ $p->is_verified ? 'btn-primary' : 'btn-outline-primary' }}" title="Toggle verified badge"><i class="bi bi-patch-check"></i></button>
                        </form>
                        @if($status !== 'rejected')
                            @include('admin.profiles._reject-modal', ['profile' => $p, 'modalSuffix' => $p->id])
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center text-muted py-5">No {{ $status }} profiles.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $profiles->links() }}</div>
@endsection
