@extends('layouts.admin')

@section('title', 'Users')

@section('content')
<form method="GET" class="card stat-card mb-3">
    <div class="card-body row g-2 align-items-end">
        <div class="col-md-4"><input type="search" name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="Name, email, mobile or profile ID" aria-label="Search"></div>
        <div class="col-6 col-md-2">
            <select name="status" class="form-select form-select-sm" aria-label="Account status">
                <option value="">Any account</option>
                <option value="active" @selected(request('status') === 'active')>Active</option>
                <option value="blocked" @selected(request('status') === 'blocked')>Blocked</option>
            </select>
        </div>
        <div class="col-6 col-md-2">
            <select name="approval" class="form-select form-select-sm" aria-label="Profile status">
                <option value="">Any profile</option>
                @foreach(['pending', 'approved', 'rejected'] as $s)<option value="{{ $s }}" @selected(request('approval') === $s)>{{ ucfirst($s) }}</option>@endforeach
            </select>
        </div>
        <div class="col-6 col-md-2">
            <select name="gender" class="form-select form-select-sm" aria-label="Gender">
                <option value="">Any gender</option>
                <option value="male" @selected(request('gender') === 'male')>Male</option>
                <option value="female" @selected(request('gender') === 'female')>Female</option>
            </select>
        </div>
        <div class="col-6 col-md-1"><div class="form-check"><input class="form-check-input" type="checkbox" name="premium" value="1" id="prem" @checked(request('premium') === '1')><label class="form-check-label small" for="prem">Premium</label></div></div>
        <div class="col-md-1"><button class="btn btn-primary btn-sm w-100">Filter</button></div>
    </div>
</form>

<div class="card stat-card">
    <div class="table-responsive">
        <table class="table table-hover mb-0 small">
            <thead class="table-light"><tr><th>Member</th><th>Contact</th><th>Profile</th><th>Plan</th><th>Account</th><th>Joined</th><th class="text-end">Actions</th></tr></thead>
            <tbody>
            @forelse($users as $u)
                <tr>
                    <td>
                        <a href="{{ route('admin.users.show', $u) }}" class="fw-semibold">{{ $u->name }}</a>
                        @if($u->profile?->is_verified)<i class="bi bi-patch-check-fill verified-badge"></i>@endif
                        <div class="text-muted">{{ $u->profile?->profile_code }} · {{ ucfirst($u->profile?->gender ?? '') }}</div>
                    </td>
                    <td>{{ $u->email }} @if($u->email_verified_at)<i class="bi bi-check-circle-fill text-success" title="Email verified"></i>@endif<br>{{ $u->mobile }}</td>
                    <td><span class="badge bg-{{ ['approved' => 'success', 'rejected' => 'danger'][$u->profile?->approval_status] ?? 'warning' }}">{{ $u->profile?->approval_status ?? 'none' }}</span></td>
                    <td>{!! $u->activeSubscription ? '<span class="badge bg-'.e($u->activeSubscription->plan->badge_color).'">'.e($u->activeSubscription->plan->name).'</span>' : '<span class="text-muted">Free</span>' !!}</td>
                    <td><span class="badge bg-{{ $u->status === 'active' ? 'success' : 'dark' }}">{{ $u->status }}</span></td>
                    <td>{{ $u->created_at->format('d M Y') }}</td>
                    <td class="text-end text-nowrap">
                        @include('admin.users._actions', ['user' => $u])
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center text-muted py-4">No users found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $users->links() }}</div>
@endsection
