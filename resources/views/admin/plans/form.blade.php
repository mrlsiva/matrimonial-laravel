@extends('layouts.admin')

@section('title', $plan->exists ? 'Edit plan: '.$plan->name : 'New plan')

@section('content')
<form method="POST" action="{{ $plan->exists ? route('admin.plans.update', $plan) : route('admin.plans.store') }}" class="card stat-card" style="max-width:900px">
    @csrf
    @if($plan->exists) @method('PUT') @endif
    <div class="card-body row g-3">
        <x-input name="name" label="Plan name" :value="$plan->name" required col="col-md-4" />
        <x-input name="slug" label="URL slug" :value="$plan->slug" col="col-md-4" help="Auto-generated if empty." />
        <x-select name="badge_color" label="Badge colour" :options="['primary' => 'Primary', 'secondary' => 'Grey', 'success' => 'Green', 'danger' => 'Red', 'warning' => 'Gold', 'info' => 'Blue', 'dark' => 'Dark']" :selected="$plan->badge_color" required col="col-md-4" />
        <x-input name="price" label="Price (₹)" type="number" step="0.01" min="0" :value="$plan->price ?? 0" required col="col-md-3" />
        <x-input name="duration_days" label="Duration (days)" type="number" min="0" :value="$plan->duration_days ?? 0" required col="col-md-3" />
        <x-input name="contact_views_limit" label="Contact views" type="number" min="0" :value="$plan->contact_views_limit ?? 0" required col="col-md-3" />
        <x-input name="daily_interest_limit" label="Interests per day" type="number" min="0" :value="$plan->daily_interest_limit" col="col-md-3" help="Empty = unlimited." />
        <x-input name="sort_order" label="Sort order" type="number" min="0" :value="$plan->sort_order ?? 0" col="col-md-3" />
        <div class="col-md-9 d-flex flex-wrap gap-4 align-items-end">
            @foreach(['can_chat' => 'Can chat with anyone', 'can_view_horoscope' => 'Can view horoscopes', 'profile_highlight' => 'Highlight / most popular', 'is_active' => 'Active'] as $field => $label)
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" role="switch" name="{{ $field }}" id="{{ $field }}" value="1" @checked(old($field, $plan->{$field}))>
                    <label class="form-check-label small" for="{{ $field }}">{{ $label }}</label>
                </div>
            @endforeach
        </div>
        <x-input name="features_text" label="Extra features (one per line)" type="textarea" rows="4" :value="implode(PHP_EOL, $plan->features ?? [])" col="col-12" />
    </div>
    <div class="card-footer bg-white text-end">
        <a href="{{ route('admin.plans.index') }}" class="btn btn-light">Cancel</a>
        <button class="btn btn-primary px-4">Save plan</button>
    </div>
</form>
@endsection
