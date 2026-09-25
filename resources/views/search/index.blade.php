@extends('layouts.app')

@section('title', 'Search Profiles')

@section('content')
@php($f = $filters)
<div class="row g-4">
    <aside class="col-lg-3">
        <button class="btn btn-outline-primary w-100 d-lg-none mb-2" data-bs-toggle="collapse" data-bs-target="#filters"><i class="bi bi-funnel me-1"></i> Filters</button>
        <form method="GET" action="{{ route('search') }}" id="filters" class="card card-soft collapse d-lg-block">
            <div class="card-body small">
                <div class="mb-3">
                    <label class="form-label fw-medium">Search by profile ID</label>
                    <div class="input-group input-group-sm">
                        <input type="text" name="profile_code" class="form-control" value="{{ $f['profile_code'] ?? '' }}" placeholder="e.g. {{ config('matrimony.profile_code_prefix') }}100001">
                        <button class="btn btn-primary" aria-label="Search"><i class="bi bi-search"></i></button>
                    </div>
                </div>
                <hr>
                <div class="mb-2">
                    <label class="form-label fw-medium">Looking for</label>
                    <select name="gender" class="form-select form-select-sm">
                        <option value="female" @selected(($f['gender'] ?? '') === 'female')>Bride</option>
                        <option value="male" @selected(($f['gender'] ?? '') === 'male')>Groom</option>
                    </select>
                </div>
                <div class="mb-2">
                    <label class="form-label fw-medium">Age</label>
                    <div class="input-group input-group-sm">
                        <input type="number" name="age_min" class="form-control" min="18" max="75" value="{{ $f['age_min'] ?? '' }}" placeholder="18" aria-label="Minimum age">
                        <span class="input-group-text">to</span>
                        <input type="number" name="age_max" class="form-control" min="18" max="75" value="{{ $f['age_max'] ?? '' }}" placeholder="75" aria-label="Maximum age">
                    </div>
                </div>
                <div class="mb-2">
                    <label class="form-label fw-medium">Height</label>
                    <div class="input-group input-group-sm">
                        <select name="height_min" class="form-select" aria-label="Minimum height"><option value="">Any</option>@foreach($heights as $cm => $l)<option value="{{ $cm }}" @selected(($f['height_min'] ?? null) == $cm)>{{ strtok($l, '(') }}</option>@endforeach</select>
                        <select name="height_max" class="form-select" aria-label="Maximum height"><option value="">Any</option>@foreach($heights as $cm => $l)<option value="{{ $cm }}" @selected(($f['height_max'] ?? null) == $cm)>{{ strtok($l, '(') }}</option>@endforeach</select>
                    </div>
                </div>
                <div class="mb-2">
                    <label class="form-label fw-medium">Marital status</label>
                    @foreach($options['marital_status'] as $k => $l)
                        <div class="form-check"><input class="form-check-input" type="checkbox" name="marital_status[]" value="{{ $k }}" id="ms-{{ $k }}" @checked(in_array($k, $f['marital_status'] ?? []))><label class="form-check-label" for="ms-{{ $k }}">{{ $l }}</label></div>
                    @endforeach
                </div>
                <div class="mb-2">
                    <label class="form-label fw-medium" for="s-religion">Religion</label>
                    <select name="religion_id" id="s-religion" class="form-select form-select-sm" data-dependent="#s-caste" data-source="{{ url('api/religions/{id}/castes') }}">
                        <option value="">Any</option>
                        @foreach($religions as $r)<option value="{{ $r->id }}" @selected(($f['religion_id'] ?? null) == $r->id)>{{ $r->name }}</option>@endforeach
                    </select>
                </div>
                <div class="mb-2">
                    <label class="form-label fw-medium" for="s-caste">Caste</label>
                    <select name="caste_id[]" id="s-caste" class="form-select form-select-sm" multiple size="3">
                        @foreach($castes as $c)<option value="{{ $c->id }}" @selected(in_array($c->id, $f['caste_id'] ?? []))>{{ $c->name }}</option>@endforeach
                    </select>
                </div>
                <div class="mb-2">
                    <label class="form-label fw-medium" for="s-mt">Mother tongue</label>
                    <select name="mother_tongue" id="s-mt" class="form-select form-select-sm">
                        <option value="">Any</option>
                        @foreach($options['mother_tongue'] as $mt)<option @selected(($f['mother_tongue'] ?? '') === $mt)>{{ $mt }}</option>@endforeach
                    </select>
                </div>
                <div class="mb-2">
                    <label class="form-label fw-medium" for="s-edu">Education</label>
                    <select name="education_level_id[]" id="s-edu" class="form-select form-select-sm" multiple size="3">
                        @foreach($educationLevels as $e)<option value="{{ $e->id }}" @selected(in_array($e->id, $f['education_level_id'] ?? []))>{{ $e->name }}</option>@endforeach
                    </select>
                </div>
                <div class="mb-2">
                    <label class="form-label fw-medium" for="s-occ">Occupation</label>
                    <select name="occupation_id[]" id="s-occ" class="form-select form-select-sm" multiple size="3">
                        @foreach($occupations as $oc)<option value="{{ $oc->id }}" @selected(in_array($oc->id, $f['occupation_id'] ?? []))>{{ $oc->name }}</option>@endforeach
                    </select>
                </div>
                <div class="mb-2">
                    <label class="form-label fw-medium">Annual income</label>
                    @foreach($options['annual_income'] as $k => $l)
                        <div class="form-check"><input class="form-check-input" type="checkbox" name="annual_income[]" value="{{ $k }}" id="inc-{{ $loop->index }}" @checked(in_array($k, $f['annual_income'] ?? []))><label class="form-check-label" for="inc-{{ $loop->index }}">{{ $l }}</label></div>
                    @endforeach
                </div>
                <div class="mb-2">
                    <label class="form-label fw-medium" for="s-state">State</label>
                    <select name="state_id" id="s-state" class="form-select form-select-sm" data-dependent="#s-city" data-source="{{ url('api/states/{id}/cities') }}">
                        <option value="">Any</option>
                        @foreach($states as $s)<option value="{{ $s->id }}" @selected(($f['state_id'] ?? null) == $s->id)>{{ $s->name }}</option>@endforeach
                    </select>
                </div>
                <div class="mb-2">
                    <label class="form-label fw-medium" for="s-city">City</label>
                    <select name="city_id" id="s-city" class="form-select form-select-sm" data-placeholder="Any">
                        <option value="">Any</option>
                        @foreach($cities as $c)<option value="{{ $c->id }}" @selected(($f['city_id'] ?? null) == $c->id)>{{ $c->name }}</option>@endforeach
                    </select>
                </div>
                <div class="mb-2">
                    <label class="form-label fw-medium" for="s-star">Star</label>
                    <select name="star" id="s-star" class="form-select form-select-sm">
                        <option value="">Any</option>
                        @foreach($options['star'] as $st)<option @selected(($f['star'] ?? '') === $st)>{{ $st }}</option>@endforeach
                    </select>
                </div>
                <div class="mb-2">
                    <label class="form-label fw-medium" for="s-diet">Diet</label>
                    <select name="diet" id="s-diet" class="form-select form-select-sm">
                        <option value="">Any</option>
                        @foreach($options['diet'] as $d)<option @selected(($f['diet'] ?? '') === $d)>{{ $d }}</option>@endforeach
                    </select>
                </div>
                <div class="form-check form-switch mt-3"><input class="form-check-input" type="checkbox" role="switch" name="with_photo" value="1" id="wp" @checked(! empty($f['with_photo']))><label class="form-check-label" for="wp">With photo only</label></div>
                <div class="form-check form-switch"><input class="form-check-input" type="checkbox" role="switch" name="verified_only" value="1" id="vo" @checked(! empty($f['verified_only']))><label class="form-check-label" for="vo">Verified profiles only</label></div>
                <input type="hidden" name="sort" value="{{ $f['sort'] ?? 'newest' }}">
                <div class="d-grid gap-2 mt-3">
                    <button class="btn btn-primary"><i class="bi bi-search me-1"></i>Search</button>
                    <a href="{{ route('search') }}" class="btn btn-light btn-sm">Reset</a>
                </div>
            </div>
        </form>
        @foreach($ads as $ad)
            <div class="ad-slot mt-3 d-none d-lg-block">
                <a href="{{ $ad->link_url ? route('banners.click', $ad) : '#' }}" rel="sponsored noopener" @if($ad->link_url) target="_blank" @endif><img src="{{ $ad->image_url }}" alt="{{ $ad->title }}"></a>
            </div>
        @endforeach
    </aside>

    <section class="col-lg-9">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <h1 class="h5 mb-0">{{ number_format($results->total()) }} {{ ($f['gender'] ?? 'female') === 'female' ? 'brides' : 'grooms' }} found</h1>
            <form method="GET" class="d-flex align-items-center gap-2" id="sortForm">
                @foreach(request()->except(['sort', 'page']) as $k => $v)
                    @foreach((array) $v as $vv)<input type="hidden" name="{{ is_array($v) ? $k.'[]' : $k }}" value="{{ $vv }}">@endforeach
                @endforeach
                <label for="sort" class="small text-muted text-nowrap">Sort by</label>
                <select name="sort" id="sort" class="form-select form-select-sm" onchange="this.form.submit()">
                    @foreach(\App\Services\ProfileSearchService::SORTS as $k => $l)<option value="{{ $k }}" @selected(($f['sort'] ?? 'newest') === $k)>{{ $l }}</option>@endforeach
                </select>
            </form>
        </div>

        @if($results->isEmpty())
            <div class="card card-soft"><div class="card-body text-center text-muted py-5"><i class="bi bi-search display-5"></i><p class="mt-3">No profiles match your filters. Try widening the age range or removing some filters.</p></div></div>
        @else
            <div class="row g-3">
                @foreach($results as $p)
                    <div class="col-6 col-md-4">@include('partials.profile-card', ['p' => $p])</div>
                @endforeach
            </div>
            <div class="mt-4">{{ $results->links() }}</div>
        @endif
    </section>
</div>
@endsection
