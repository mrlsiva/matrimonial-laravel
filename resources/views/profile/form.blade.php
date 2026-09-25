@extends('layouts.app')

@section('title', $profile->exists ? 'Edit Profile' : 'Create Profile')

@section('content')
@php
    $o = $options;
    $action = $profile->exists ? route('profile.update') : route('profile.store');
    $sections = ['basic' => 'Basic details', 'religion' => 'Religion & horoscope', 'career' => 'Education & career', 'location' => 'Location', 'family' => 'Family', 'partner' => 'Partner preferences'];
@endphp
<div class="row g-4">
    <div class="col-lg-3 d-none d-lg-block">
        <div class="list-group sticky-sidebar small">
            @foreach($sections as $id => $label)
                <a href="#{{ $id }}" class="list-group-item list-group-item-action">{{ $label }}</a>
            @endforeach
            @if($profile->exists)
                <a href="{{ route('photos.index') }}" class="list-group-item list-group-item-action"><i class="bi bi-images me-1"></i> Photos</a>
                <a href="{{ route('profiles.show', $profile) }}" class="list-group-item list-group-item-action"><i class="bi bi-eye me-1"></i> Preview profile</a>
            @endif
        </div>
    </div>
    <div class="col-lg-9">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1 class="h3 mb-0">{{ $profile->exists ? 'Edit your profile' : 'Create your profile' }}</h1>
            @if($profile->exists)
                <span class="badge bg-{{ ['approved' => 'success', 'rejected' => 'danger'][$profile->approval_status] ?? 'warning' }}">{{ ucfirst($profile->approval_status) }}</span>
            @endif
        </div>
        @if($profile->approval_status === 'rejected')
            <div class="alert alert-danger small"><strong>Reason:</strong> {{ $profile->rejection_reason }}. Saving changes will resubmit your profile for approval.</div>
        @endif

        <form method="POST" action="{{ $action }}" enctype="multipart/form-data" class="card card-soft">
            @csrf
            @if($profile->exists) @method('PUT') @endif
            <div class="card-body p-4">

                <h2 class="form-section-title" id="basic">Basic details</h2>
                <div class="row g-3">
                    <x-select name="created_by" label="Profile created by" :options="$o['created_by']" :selected="$profile->created_by" required col="col-md-4" />
                    <x-select name="gender" label="Gender" :options="['male' => 'Male (Groom)', 'female' => 'Female (Bride)']" :selected="$profile->gender" required col="col-md-4" />
                    <x-input name="date_of_birth" label="Date of birth" type="date" :value="$profile->date_of_birth?->toDateString()" required col="col-md-4" :max="now()->subYears(18)->toDateString()" />
                    <x-select name="marital_status" label="Marital status" :options="$o['marital_status']" :selected="$profile->marital_status" required col="col-md-4" />
                    <x-input name="children_count" label="No. of children" type="number" min="0" max="10" :value="$profile->children_count" col="col-md-4" help="Leave blank if never married." />
                    <x-select name="mother_tongue" label="Mother tongue" :options="$o['mother_tongue']" :selected="$profile->mother_tongue" required col="col-md-4" />
                    <x-select name="height_cm" label="Height" :options="$heights" :selected="$profile->height_cm" required col="col-md-4" />
                    <x-input name="weight_kg" label="Weight (kg)" type="number" min="30" max="200" :value="$profile->weight_kg" col="col-md-4" />
                    <x-select name="physical_status" label="Physical status" :options="$o['physical_status']" :selected="$profile->physical_status ?? 'normal'" required col="col-md-4" />
                    <x-select name="complexion" label="Complexion" :options="$o['complexion']" :selected="$profile->complexion" col="col-md-3" />
                    <x-select name="body_type" label="Body type" :options="$o['body_type']" :selected="$profile->body_type" col="col-md-3" />
                    <x-select name="diet" label="Diet" :options="$o['diet']" :selected="$profile->diet" col="col-md-2" />
                    <x-select name="smoking" label="Smoking" :options="$o['habits']" :selected="$profile->smoking" col="col-md-2" />
                    <x-select name="drinking" label="Drinking" :options="$o['habits']" :selected="$profile->drinking" col="col-md-2" />
                    <x-input name="about_me" label="About me" type="textarea" rows="4" :value="$profile->about_me" required col="col-12" minlength="30" maxlength="2000" help="Describe your personality, values and interests (min. 30 characters). Do not include phone numbers or email." />
                </div>

                <h2 class="form-section-title" id="religion">Religion &amp; horoscope</h2>
                <div class="row g-3">
                    <x-select name="religion_id" label="Religion" :options="$religions->pluck('name', 'id')" :selected="$profile->religion_id" required col="col-md-4"
                              data-dependent="#caste_id" data-source="{{ url('api/religions/{id}/castes') }}" />
                    <x-select name="caste_id" label="Caste" :options="$castes->pluck('name', 'id')" :selected="$profile->caste_id" col="col-md-4" data-placeholder="Select caste" placeholder="Select caste" />
                    <x-input name="sub_caste" label="Sub-caste" :value="$profile->sub_caste" col="col-md-4" />
                    <x-input name="gothram" label="Gothram" :value="$profile->gothram" col="col-md-4" />
                    <x-select name="star" label="Star (Nakshatra)" :options="$o['star']" :selected="$profile->star" col="col-md-4" />
                    <x-select name="rasi" label="Rasi (Moon sign)" :options="$o['rasi']" :selected="$profile->rasi" col="col-md-4" />
                    <x-select name="dosham" label="Dosham / Manglik" :options="$o['dosham']" :selected="$profile->dosham" col="col-md-4" />
                    <x-input name="birth_time" label="Time of birth" type="time" :value="$profile->birth_time ? substr($profile->birth_time, 0, 5) : null" col="col-md-4" />
                    <x-input name="birth_place" label="Place of birth" :value="$profile->birth_place" col="col-md-4" />
                    <div class="col-12">
                        <label class="form-label small fw-medium" for="horoscope">Horoscope (PDF/JPG/PNG, max 4 MB)</label>
                        <input type="file" name="horoscope" id="horoscope" class="form-control @error('horoscope') is-invalid @enderror" accept=".pdf,.jpg,.jpeg,.png">
                        @error('horoscope')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        @if($profile->horoscope_file)
                            <div class="form-text"><i class="bi bi-file-earmark-check text-success"></i> Horoscope uploaded ·
                                <a href="{{ route('profiles.horoscope', $profile) }}" target="_blank">View</a> ·
                                <button form="deleteHoroscope" class="btn btn-link btn-sm p-0 text-danger align-baseline">Remove</button>
                            </div>
                        @endif
                        <div class="form-text">Only premium members and accepted connections can view your horoscope.</div>
                    </div>
                </div>

                <h2 class="form-section-title" id="career">Education &amp; career</h2>
                <div class="row g-3">
                    <x-select name="education_level_id" label="Highest education" :options="$educationLevels->pluck('name', 'id')" :selected="$profile->education_level_id" required col="col-md-6" />
                    <x-input name="education_detail" label="Education details" :value="$profile->education_detail" col="col-md-6" placeholder="e.g. B.E. Computer Science, Anna University" />
                    <x-select name="occupation_id" label="Occupation" :options="$occupations->pluck('name', 'id')" :selected="$profile->occupation_id" required col="col-md-4" />
                    <x-select name="employed_in" label="Employed in" :options="$o['employed_in']" :selected="$profile->employed_in" col="col-md-4" />
                    <x-select name="annual_income" label="Annual income" :options="$o['annual_income']" :selected="$profile->annual_income" col="col-md-4" />
                    <x-input name="company_name" label="Company / organisation" :value="$profile->company_name" col="col-md-6" />
                </div>

                <h2 class="form-section-title" id="location">Location</h2>
                <div class="row g-3">
                    <x-select name="state_id" label="State" :options="$states->pluck('name', 'id')" :selected="$profile->state_id" required col="col-md-4"
                              data-dependent="#city_id" data-source="{{ url('api/states/{id}/cities') }}" />
                    <x-select name="city_id" label="City" :options="$cities->pluck('name', 'id')" :selected="$profile->city_id" required col="col-md-4" data-placeholder="Select city" placeholder="Select city" />
                    <x-input name="address" label="Area / locality" :value="$profile->address" col="col-md-4" help="Not shown publicly." />
                </div>

                <h2 class="form-section-title" id="family">Family</h2>
                <div class="row g-3">
                    <x-select name="family_type" label="Family type" :options="$o['family_type']" :selected="$profile->family_type" col="col-md-4" />
                    <x-select name="family_status" label="Family status" :options="$o['family_status']" :selected="$profile->family_status" col="col-md-4" />
                    <div class="col-md-2"><x-input name="brothers" label="Brothers" type="number" min="0" max="20" :value="$profile->brothers" col="" /></div>
                    <div class="col-md-2"><x-input name="sisters" label="Sisters" type="number" min="0" max="20" :value="$profile->sisters" col="" /></div>
                    <x-input name="father_occupation" label="Father's occupation" :value="$profile->father_occupation" />
                    <x-input name="mother_occupation" label="Mother's occupation" :value="$profile->mother_occupation" />
                    <x-input name="about_family" label="About family" type="textarea" :value="$profile->about_family" col="col-12" maxlength="1000" />
                </div>

                <h2 class="form-section-title" id="partner">Partner preferences</h2>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-medium">Age range</label>
                        <div class="input-group">
                            <input type="number" name="partner_age_min" class="form-control @error('partner_age_min') is-invalid @enderror" min="18" max="75" placeholder="From" value="{{ old('partner_age_min', $profile->partner_age_min) }}">
                            <span class="input-group-text">to</span>
                            <input type="number" name="partner_age_max" class="form-control @error('partner_age_max') is-invalid @enderror" min="18" max="75" placeholder="To" value="{{ old('partner_age_max', $profile->partner_age_max) }}">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-medium">Height range</label>
                        <div class="input-group">
                            <select name="partner_height_min" class="form-select" aria-label="Minimum height">
                                <option value="">Any</option>
                                @foreach($heights as $cm => $label)<option value="{{ $cm }}" @selected(old('partner_height_min', $profile->partner_height_min) == $cm)>{{ $label }}</option>@endforeach
                            </select>
                            <span class="input-group-text">to</span>
                            <select name="partner_height_max" class="form-select" aria-label="Maximum height">
                                <option value="">Any</option>
                                @foreach($heights as $cm => $label)<option value="{{ $cm }}" @selected(old('partner_height_max', $profile->partner_height_max) == $cm)>{{ $label }}</option>@endforeach
                            </select>
                        </div>
                    </div>
                    <x-select name="partner_marital_status[]" label="Marital status" :options="$o['marital_status']" :selected="$profile->partner_marital_status ?? []" multiple col="col-md-6" size="4" />
                    <x-select name="partner_mother_tongue" label="Mother tongue" :options="$o['mother_tongue']" :selected="$profile->partner_mother_tongue" placeholder="Any" col="col-md-6" />
                    <x-select name="partner_religion_id" label="Religion" :options="$religions->pluck('name', 'id')" :selected="$profile->partner_religion_id" placeholder="Any" col="col-md-6"
                              data-dependent="#partner_caste_ids" data-source="{{ url('api/religions/{id}/castes') }}" />
                    <x-select name="partner_caste_ids[]" label="Castes" :options="$partnerCastes->pluck('name', 'id')" :selected="$profile->partner_caste_ids ?? []" multiple col="col-md-6" size="4" />
                    <x-select name="partner_education_ids[]" label="Education" :options="$educationLevels->pluck('name', 'id')" :selected="$profile->partner_education_ids ?? []" multiple col="col-md-6" size="5" />
                    <x-select name="partner_state_ids[]" label="Preferred states" :options="$states->pluck('name', 'id')" :selected="$profile->partner_state_ids ?? []" multiple col="col-md-6" size="5" />
                    <div class="col-12"><div class="form-text mt-0">Hold Ctrl (Cmd on Mac) to select multiple. Leave empty for "Any".</div></div>
                    <x-input name="partner_expectations" label="Expectations in your own words" type="textarea" :value="$profile->partner_expectations" col="col-12" maxlength="2000" />
                </div>
            </div>
            <div class="card-footer bg-white p-3 text-end sticky-bottom border-top">
                <button class="btn btn-primary px-5"><i class="bi bi-check2-circle me-2"></i>{{ $profile->exists ? 'Save changes' : 'Create profile' }}</button>
            </div>
        </form>
        @if($profile->horoscope_file)
            <form id="deleteHoroscope" method="POST" action="{{ route('profile.horoscope.destroy') }}" onsubmit="return confirm('Remove horoscope?')">@csrf @method('DELETE')</form>
        @endif
    </div>
</div>
@endsection
