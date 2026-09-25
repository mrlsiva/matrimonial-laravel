@extends('layouts.admin')

@section('title', $page->exists ? 'Edit page: '.$page->title : 'New page')

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-bs5.min.css" rel="stylesheet">
@endpush

@section('content')
<form method="POST" action="{{ $page->exists ? route('admin.pages.update', $page) : route('admin.pages.store') }}" enctype="multipart/form-data" class="card stat-card">
    @csrf
    @if($page->exists) @method('PUT') @endif
    <div class="card-body row g-3">
        <x-input name="title" label="Title" :value="$page->title" required col="col-md-6" />
        <x-input name="slug" label="URL slug" :value="$page->slug" col="col-md-6" help="Page URL: /pages/your-slug. Auto-generated from the title if empty." />
        <div class="col-12">
            <label class="form-label small fw-medium" for="content">Content</label>
            <textarea name="content" id="content" class="form-control" rows="14">{{ old('content', $page->content) }}</textarea>
        </div>
        <div class="col-12">
            <label class="form-label small fw-medium" for="image">Side image / poster <span class="text-muted fw-normal">(optional)</span></label>
            <input type="file" name="image" id="image" class="form-control @error('image') is-invalid @enderror" accept="image/jpeg,image/png,image/webp" data-preview="#pagePreview">
            @error('image')<div class="invalid-feedback">{{ $message }}</div>@enderror
            <div class="form-text">Shown beside the page content on desktop and above it on mobile. Portrait images (e.g. 1024×1536) work best. Converted to WebP automatically.</div>
            <div id="pagePreview" class="mt-2">
                @if($page->image)
                    <img src="{{ $page->image_url }}" alt="" class="rounded border" style="max-height:200px">
                    <div class="form-check mt-2"><input class="form-check-input" type="checkbox" name="remove_image" value="1" id="remove_image"><label class="form-check-label small" for="remove_image">Remove image</label></div>
                @endif
            </div>
        </div>
        <x-input name="meta_title" label="SEO meta title" :value="$page->meta_title" col="col-md-6" maxlength="160" />
        <x-input name="meta_description" label="SEO meta description" :value="$page->meta_description" col="col-md-6" maxlength="255" />
        <div class="col-12 d-flex gap-4">
            <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active" @checked(old('is_active', $page->is_active))><label class="form-check-label" for="is_active">Published</label></div>
            <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="show_in_footer" value="1" id="show_in_footer" @checked(old('show_in_footer', $page->show_in_footer))><label class="form-check-label" for="show_in_footer">Show link in footer</label></div>
        </div>
    </div>
    <div class="card-footer bg-white text-end">
        <a href="{{ route('admin.pages.index') }}" class="btn btn-light">Cancel</a>
        <button class="btn btn-primary px-4">Save page</button>
    </div>
</form>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-bs5.min.js"></script>
<script>
$('#content').summernote({
    height: 360,
    toolbar: [
        ['style', ['style']],
        ['font', ['bold', 'italic', 'underline', 'clear']],
        ['para', ['ul', 'ol', 'paragraph']],
        ['insert', ['link', 'table', 'hr']],
        ['view', ['codeview']],
    ],
});
</script>
@endpush
