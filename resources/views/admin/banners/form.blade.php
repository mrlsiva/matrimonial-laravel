@extends('layouts.admin')

@section('title', $banner->exists ? 'Edit banner' : 'New banner / advertisement')

@section('content')
<form method="POST" action="{{ $banner->exists ? route('admin.banners.update', $banner) : route('admin.banners.store') }}" enctype="multipart/form-data" class="card stat-card" style="max-width:900px">
    @csrf
    @if($banner->exists) @method('PUT') @endif
    <div class="card-body row g-3">
        <x-input name="title" label="Title" :value="$banner->title" required col="col-md-6" />
        <x-input name="subtitle" label="Subtitle" :value="$banner->subtitle" col="col-md-6" help="Home slider banners are shown full-width as designed (no text overlay), so put any text inside the image." />
        <x-select name="type" label="Type" :options="['banner' => 'Banner', 'advertisement' => 'Advertisement']" :selected="$banner->type" required col="col-md-4" />
        <x-select name="position" label="Position" :options="\App\Models\Banner::POSITIONS" :selected="$banner->position" required col="col-md-4" />
        <x-input name="sort_order" label="Sort order" type="number" min="0" :value="$banner->sort_order ?? 0" col="col-md-4" />
        <x-input name="link_url" label="Link URL" type="url" :value="$banner->link_url" col="col-md-6" placeholder="https://" />
        <x-input name="starts_on" label="Start date" type="date" :value="$banner->starts_on?->toDateString()" col="col-md-3" />
        <x-input name="ends_on" label="End date" type="date" :value="$banner->ends_on?->toDateString()" col="col-md-3" />
        <div class="col-md-8">
            <label class="form-label small fw-medium" for="image">Image @unless($banner->exists)<span class="text-danger">*</span>@endunless</label>
            <input type="file" name="image" id="image" class="form-control @error('image') is-invalid @enderror" accept="image/jpeg,image/png,image/webp" @required(! $banner->exists) data-preview="#bannerPreview">
            @error('image')<div class="invalid-feedback">{{ $message }}</div>@enderror
            <div class="form-text">Recommended 2048×768 for home slider, 600×400 for ads. Resized and converted to WebP automatically.</div>
            <div id="bannerPreview" class="mt-2">@if($banner->exists)<img src="{{ $banner->image_url }}" alt="" class="rounded" style="max-width:100%;max-height:160px">@endif</div>
        </div>
        <div class="col-md-4 d-flex align-items-center">
            <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active" @checked(old('is_active', $banner->is_active))><label class="form-check-label" for="is_active">Active</label></div>
        </div>
    </div>
    <div class="card-footer bg-white text-end">
        <a href="{{ route('admin.banners.index') }}" class="btn btn-light">Cancel</a>
        <button class="btn btn-primary px-4">Save</button>
    </div>
</form>
@endsection
