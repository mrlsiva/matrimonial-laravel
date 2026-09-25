@extends('layouts.admin')

@section('title', 'CMS Pages')

@section('content')
<div class="d-flex justify-content-end mb-3"><a href="{{ route('admin.pages.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>New page</a></div>
<div class="card stat-card">
    <div class="table-responsive">
        <table class="table mb-0 small">
            <thead class="table-light"><tr><th>Title</th><th>URL</th><th>Footer</th><th>Status</th><th>Updated</th><th class="text-end">Actions</th></tr></thead>
            <tbody>
            @forelse($pages as $page)
                <tr>
                    <td class="fw-semibold">{{ $page->title }}</td>
                    <td><a href="{{ route('pages.show', $page) }}" target="_blank">/pages/{{ $page->slug }}</a></td>
                    <td>{!! $page->show_in_footer ? '<i class="bi bi-check-lg text-success"></i>' : '—' !!}</td>
                    <td><span class="badge bg-{{ $page->is_active ? 'success' : 'secondary' }}">{{ $page->is_active ? 'Published' : 'Draft' }}</span></td>
                    <td>{{ $page->updated_at->diffForHumans() }}</td>
                    <td class="text-end text-nowrap">
                        <a href="{{ route('admin.pages.edit', $page) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                        <form method="POST" action="{{ route('admin.pages.destroy', $page) }}" class="d-inline" onsubmit="return confirm('Delete this page?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button></form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center text-muted py-4">No pages yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
