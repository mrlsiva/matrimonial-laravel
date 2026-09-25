@extends('layouts.admin')

@section('title', 'Banners & Advertisements')

@section('content')
<div class="d-flex justify-content-between mb-3">
    <ul class="nav nav-pills">
        <li class="nav-item"><a class="nav-link {{ ! request('type') ? 'active' : '' }}" href="{{ route('admin.banners.index') }}">All</a></li>
        <li class="nav-item"><a class="nav-link {{ request('type') === 'banner' ? 'active' : '' }}" href="{{ route('admin.banners.index', ['type' => 'banner']) }}">Banners</a></li>
        <li class="nav-item"><a class="nav-link {{ request('type') === 'advertisement' ? 'active' : '' }}" href="{{ route('admin.banners.index', ['type' => 'advertisement']) }}">Advertisements</a></li>
    </ul>
    <a href="{{ route('admin.banners.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>New</a>
</div>
<div class="card stat-card">
    <div class="table-responsive">
        <table class="table mb-0 small">
            <thead class="table-light"><tr><th>Image</th><th>Title</th><th>Type / Position</th><th>Schedule</th><th>Clicks</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
            <tbody>
            @forelse($banners as $b)
                <tr>
                    <td><img src="{{ $b->image_url }}" alt="" class="rounded" style="width:120px;height:50px;object-fit:cover"></td>
                    <td class="fw-semibold">{{ $b->title }}<div class="text-muted fw-normal">{{ \Illuminate\Support\Str::limit($b->link_url, 40) }}</div></td>
                    <td><span class="badge bg-{{ $b->type === 'banner' ? 'info' : 'warning' }}">{{ $b->type }}</span><br>{{ \App\Models\Banner::POSITIONS[$b->position] ?? $b->position }}</td>
                    <td>{{ $b->starts_on?->format('d M Y') ?? 'Now' }} → {{ $b->ends_on?->format('d M Y') ?? 'No end' }}</td>
                    <td>{{ $b->clicks }}</td>
                    <td><span class="badge bg-{{ $b->is_active ? 'success' : 'secondary' }}">{{ $b->is_active ? 'Active' : 'Inactive' }}</span></td>
                    <td class="text-end text-nowrap">
                        <a href="{{ route('admin.banners.edit', $b) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                        <form method="POST" action="{{ route('admin.banners.destroy', $b) }}" class="d-inline" onsubmit="return confirm('Delete this banner?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button></form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center text-muted py-4">No banners yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $banners->links() }}</div>
@endsection
