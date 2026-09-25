@extends('layouts.admin')

@section('title', $config['label'])

@section('content')
@php($parentKey = $config['parent_key'] ?? null)
<div class="row g-3">
    <div class="col-lg-4">
        <div class="card stat-card">
            <div class="card-body">
                <h2 class="h6">Add {{ \Illuminate\Support\Str::singular(strtolower($config['label'])) }}</h2>
                <form method="POST" action="{{ route('admin.masters.store', $type) }}" class="row g-2">
                    @csrf
                    @if($parentKey)
                        <x-select :name="$parentKey" :label="$config['parent_label']" :options="$parents" :selected="request('parent')" required col="col-12" />
                    @endif
                    <x-input name="name" label="Name" required col="col-12" />
                    <x-input name="sort_order" label="Sort order" type="number" min="0" value="0" col="col-6" />
                    <div class="col-6 d-flex align-items-end">
                        <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="is_active" value="1" id="new-active" checked><label class="form-check-label small" for="new-active">Active</label></div>
                    </div>
                    <div class="col-12"><button class="btn btn-primary btn-sm w-100"><i class="bi bi-plus-lg"></i> Add</button></div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <form method="GET" class="d-flex gap-2 mb-2">
            @if($parentKey)
                <select name="parent" class="form-select form-select-sm" style="max-width:220px" aria-label="Filter by {{ $config['parent_label'] }}">
                    <option value="">All {{ strtolower($config['parent_label']) }}s</option>
                    @foreach($parents as $id => $name)<option value="{{ $id }}" @selected(request('parent') == $id)>{{ $name }}</option>@endforeach
                </select>
            @endif
            <input type="search" name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="Search…" aria-label="Search">
            <button class="btn btn-sm btn-outline-primary">Filter</button>
        </form>
        <div class="card stat-card">
            <div class="table-responsive">
                <table class="table mb-0 small">
                    <thead class="table-light"><tr><th>Name</th>@if($parentKey)<th>{{ $config['parent_label'] }}</th>@endif<th>Order</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
                    <tbody>
                    @forelse($items as $item)
                        @php($rel = $parentKey ? \Illuminate\Support\Str::beforeLast($parentKey, '_id') : null)
                        <tr>
                            <td>{{ $item->name }}</td>
                            @if($parentKey)<td>{{ $item->{$rel}?->name }}</td>@endif
                            <td>{{ $item->sort_order }}</td>
                            <td><span class="badge bg-{{ $item->is_active ? 'success' : 'secondary' }}">{{ $item->is_active ? 'Active' : 'Inactive' }}</span></td>
                            <td class="text-end text-nowrap">
                                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="collapse" data-bs-target="#edit-{{ $item->id }}" aria-label="Edit"><i class="bi bi-pencil"></i></button>
                                <form method="POST" action="{{ route('admin.masters.destroy', [$type, $item->id]) }}" class="d-inline" onsubmit="return confirm('Delete {{ addslashes($item->name) }}?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger" aria-label="Delete"><i class="bi bi-trash"></i></button></form>
                            </td>
                        </tr>
                        <tr class="collapse" id="edit-{{ $item->id }}">
                            <td colspan="{{ $parentKey ? 5 : 4 }}" class="bg-light">
                                <form method="POST" action="{{ route('admin.masters.update', [$type, $item->id]) }}" class="row g-2 align-items-end">
                                    @csrf @method('PUT')
                                    @if($parentKey)
                                        <div class="col-md-3"><select name="{{ $parentKey }}" class="form-select form-select-sm" aria-label="{{ $config['parent_label'] }}">@foreach($parents as $id => $name)<option value="{{ $id }}" @selected($item->{$parentKey} == $id)>{{ $name }}</option>@endforeach</select></div>
                                    @endif
                                    <div class="col-md-4"><input name="name" value="{{ $item->name }}" class="form-control form-control-sm" required aria-label="Name"></div>
                                    <div class="col-md-2"><input name="sort_order" type="number" min="0" value="{{ $item->sort_order }}" class="form-control form-control-sm" aria-label="Sort order"></div>
                                    <div class="col-md-2"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="is_active" value="1" id="a-{{ $item->id }}" @checked($item->is_active)><label class="form-check-label" for="a-{{ $item->id }}">Active</label></div></div>
                                    <div class="col-md-1"><button class="btn btn-sm btn-primary w-100">Save</button></div>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-4">No records.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="mt-3">{{ $items->links() }}</div>
    </div>
</div>
@endsection
