@extends('layouts.app')

@section('title', 'My Shortlist')

@section('content')
<h1 class="h3 mb-3">My shortlist</h1>
@if($results->isEmpty())
    <div class="card card-soft"><div class="card-body text-center text-muted py-5">
        <i class="bi bi-star display-5"></i>
        <p class="mt-3">You haven't shortlisted anyone yet. Tap the <i class="bi bi-star"></i> on any profile to save it here.</p>
        <a href="{{ route('matches') }}" class="btn btn-primary">Browse matches</a>
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
