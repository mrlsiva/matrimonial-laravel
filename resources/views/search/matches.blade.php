@extends('layouts.app')

@section('title', 'My Matches')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h1 class="h3 mb-0">Your matches</h1>
        <p class="text-muted small mb-0">Profiles that fit your partner preferences · {{ number_format($results->total()) }} found</p>
    </div>
    <a href="{{ route('profile.edit') }}#partner" class="btn btn-sm btn-outline-primary"><i class="bi bi-sliders me-1"></i>Edit preferences</a>
</div>

@unless($hasPreferences)
    <div class="alert alert-info small">Tip: <a href="{{ route('profile.edit') }}#partner">set your partner preferences</a> to get more relevant matches.</div>
@endunless

@if($results->isEmpty())
    <div class="card card-soft"><div class="card-body text-center text-muted py-5">
        <i class="bi bi-heart display-5"></i>
        <p class="mt-3">No profiles match your preferences yet. Try relaxing them or use <a href="{{ route('search') }}">advanced search</a>.</p>
    </div></div>
@else
    <div class="row g-3">
        @foreach($results as $p)
            <div class="col-6 col-md-4 col-lg-3">@include('partials.profile-card', ['p' => $p])</div>
        @endforeach
    </div>
    <div class="mt-4">{{ $results->links() }}</div>
@endif
@endsection
