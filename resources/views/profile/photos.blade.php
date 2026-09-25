@extends('layouts.app')

@section('title', 'My Photos')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">My photos</h1>
    <a href="{{ route('profile.edit') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i> Back to profile</a>
</div>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card card-soft">
            <div class="card-body">
                <h2 class="h6">Upload photos</h2>
                <p class="small text-muted">JPG, PNG or WebP · max {{ config('matrimony.photo_max_kb') / 1024 }} MB each · at least 300×300 px. You can have up to {{ $maxPhotos }} photos. Photos are optimised automatically and reviewed by our team before they appear.</p>
                @if($photos->count() < $maxPhotos)
                    <form method="POST" action="{{ route('photos.store') }}" enctype="multipart/form-data">
                        @csrf
                        <input type="file" name="photos[]" class="form-control mb-2 @error('photos') is-invalid @enderror @error('photos.*') is-invalid @enderror" accept="image/jpeg,image/png,image/webp" multiple required data-preview="#preview">
                        @error('photos')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        @foreach($errors->get('photos.*') as $messages)<div class="text-danger small">{{ $messages[0] }}</div>@endforeach
                        <div id="preview" class="my-2"></div>
                        <button class="btn btn-primary w-100"><i class="bi bi-cloud-upload me-2"></i>Upload</button>
                    </form>
                @else
                    <div class="alert alert-info small mb-0">You've reached the {{ $maxPhotos }}-photo limit. Delete a photo to upload another.</div>
                @endif
                <hr>
                <ul class="small text-muted ps-3 mb-0">
                    <li>Use a clear, recent photo of yourself only.</li>
                    <li>No group photos, celebrities or watermarks.</li>
                    <li>Photos with contact details will be rejected.</li>
                </ul>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        @if($photos->isEmpty())
            <div class="card card-soft"><div class="card-body text-center text-muted py-5"><i class="bi bi-images display-5"></i><p class="mt-2">No photos yet. Profiles with photos get up to 10× more responses.</p></div></div>
        @else
            <div class="row g-3">
                @foreach($photos as $photo)
                    <div class="col-6 col-md-4">
                        <div class="card h-100 overflow-hidden {{ $photo->is_primary ? 'border-primary border-2' : '' }}">
                            <img src="{{ $photo->thumb_url }}" class="card-img-top" alt="My photo" style="aspect-ratio:4/5;object-fit:cover">
                            <div class="card-body p-2">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="badge bg-{{ $photo->statusBadge() }}">{{ ucfirst($photo->status) }}</span>
                                    @if($photo->is_primary)<span class="badge bg-primary">Main</span>@endif
                                </div>
                                <div class="d-flex gap-1">
                                    @unless($photo->is_primary)
                                        <form method="POST" action="{{ route('photos.primary', $photo) }}" class="flex-grow-1">@csrf @method('PATCH')
                                            <button class="btn btn-sm btn-outline-primary w-100">Make main</button>
                                        </form>
                                    @endunless
                                    <form method="POST" action="{{ route('photos.destroy', $photo) }}" onsubmit="return confirm('Delete this photo?')">@csrf @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger" aria-label="Delete photo"><i class="bi bi-trash"></i></button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection
