@extends('layouts.app')

@section('title', $profile->profile_code.' - '.$profile->age.' yrs '.($profile->gender === 'female' ? 'Bride' : 'Groom').' '.($profile->city?->name ? 'from '.$profile->city->name : ''))
@section('meta_description', \Illuminate\Support\Str::limit($profile->about_me, 155))

@section('content')
@php
    $user = $profile->user;
    $o = config('matrimony.options');
    $row = fn ($label, $value) => filled($value) ? '<tr><th>'.e($label).'</th><td>'.e($value).'</td></tr>' : '';
@endphp

@if($isOwner && $profile->approval_status !== 'approved')
    <div class="alert alert-warning">This is a preview. Your profile is <strong>{{ $profile->approval_status }}</strong> and not yet visible to other members.</div>
@endif

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card card-soft sticky-sidebar">
            <div class="card-body">
                <img id="mainPhoto" src="{{ $photos->first()?->url ?? $profile->placeholderUrl() }}" class="profile-hero-photo mb-2" alt="Photo of {{ $profile->profile_code }}">
                @if($photos->count() > 1)
                    <div class="thumb-strip d-flex gap-2 flex-wrap mb-2">
                        @foreach($photos as $photo)
                            <img src="{{ $photo->thumb_url }}" data-full="{{ $photo->url }}" class="{{ $loop->first ? 'active' : '' }}" alt="Photo {{ $loop->iteration }}">
                        @endforeach
                    </div>
                @endif

                @unless($isOwner)
                    <div class="d-grid gap-2 mt-3">
                        @if(! $interest)
                            <button class="btn btn-primary" data-ajax="{{ route('interests.store', $user) }}" data-done="<i class='bi bi-check2'></i> Interest sent"><i class="bi bi-heart me-2"></i>Send interest</button>
                        @elseif($interest->status === 'pending' && $interest->sender_id === auth()->id())
                            <button class="btn btn-outline-secondary" disabled><i class="bi bi-hourglass-split me-2"></i>Interest pending</button>
                        @elseif($interest->status === 'pending')
                            <div class="btn-group">
                                <button class="btn btn-success" data-ajax="{{ route('interests.accept', $interest) }}" data-method="PATCH" data-reload="1"><i class="bi bi-check-lg"></i> Accept</button>
                                <button class="btn btn-outline-danger" data-ajax="{{ route('interests.reject', $interest) }}" data-method="PATCH" data-reload="1"><i class="bi bi-x-lg"></i> Decline</button>
                            </div>
                        @elseif($interest->status === 'accepted')
                            <span class="btn btn-success disabled"><i class="bi bi-people-fill me-2"></i>Connected</span>
                        @elseif($interest->status === 'cancelled' && $interest->sender_id === auth()->id())
                            <button class="btn btn-primary" data-ajax="{{ route('interests.store', $user) }}" data-done="<i class='bi bi-check2'></i> Interest sent"><i class="bi bi-heart me-2"></i>Send interest</button>
                        @else
                            <span class="btn btn-outline-secondary disabled">Interest {{ $interest->status }}</span>
                        @endif

                        @if($canChat)
                            <a href="{{ route('chat.show', $user) }}" class="btn btn-outline-primary"><i class="bi bi-chat-dots me-2"></i>Chat now</a>
                        @else
                            <a href="{{ route('membership.index') }}" class="btn btn-outline-secondary" data-bs-toggle="tooltip" title="Upgrade or get your interest accepted to chat"><i class="bi bi-lock me-2"></i>Chat (Premium)</a>
                        @endif

                        <button class="btn btn-light border" data-ajax="{{ route('favorites.toggle', $user) }}" data-toggle="favorite">
                            <i class="bi {{ $isFavorite ? 'bi-star-fill text-warning' : 'bi-star' }}"></i> Shortlist
                        </button>
                    </div>
                @else
                    <a href="{{ route('profile.edit') }}" class="btn btn-outline-primary w-100 mt-3"><i class="bi bi-pencil me-2"></i>Edit profile</a>
                @endunless
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card card-soft mb-4">
            <div class="card-body p-4">
                <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
                    <div>
                        <h1 class="h3 mb-1">{{ $profile->profile_code }}
                            @if($profile->is_verified)<i class="bi bi-patch-check-fill verified-badge fs-5" data-bs-toggle="tooltip" title="Verified by our team"></i>@endif
                        </h1>
                        <p class="text-muted mb-0">
                            {{ $profile->age }} yrs · {{ $profile->height_label }} · {{ $profile->marital_status_label }}<br>
                            {{ collect([$profile->religion?->name, $profile->caste?->name])->filter()->implode(', ') }} · {{ $profile->mother_tongue }}<br>
                            <i class="bi bi-geo-alt"></i> {{ $profile->location }}
                        </p>
                    </div>
                    <div class="text-end small text-muted">
                        Profile created by {{ $o['created_by'][$profile->created_by] ?? 'Self' }}<br>
                        Last active {{ $user->last_login_at?->diffForHumans() ?? 'recently' }}
                    </div>
                </div>
                @if($profile->about_me)
                    <hr>
                    <h2 class="h6 text-brand">About {{ $profile->gender === 'female' ? 'her' : 'him' }}</h2>
                    <p class="mb-0" style="white-space:pre-line">{{ $profile->about_me }}</p>
                @endif
            </div>
        </div>

        {{-- Contact details --}}
        @unless($isOwner)
            <div class="card card-soft mb-4">
                <div class="card-body p-4">
                    <h2 class="h6 text-brand"><i class="bi bi-telephone me-2"></i>Contact details</h2>
                    <div id="contactBox">
                        @if($contactVisible)
                            <p class="mb-1"><strong>Mobile:</strong> +91 {{ $user->mobile }}</p>
                            <p class="mb-0"><strong>Email:</strong> {{ $user->email }}</p>
                        @else
                            <p class="mb-1 blur-contact">Mobile: +91 98XXXXXX10</p>
                            <p class="blur-contact">Email: xxxxxxx@xxxxx.com</p>
                            @if($subscription && $subscription->contactViewsRemaining() > 0)
                                <button class="btn btn-sm btn-primary" id="revealContact" data-url="{{ route('profiles.contact', $profile) }}">
                                    View contact ({{ $subscription->contactViewsRemaining() }} left)
                                </button>
                            @else
                                <a href="{{ route('membership.index') }}" class="btn btn-sm btn-gold"><i class="bi bi-gem me-1"></i>Upgrade to view contact</a>
                                <span class="small text-muted ms-2">or get your interest accepted.</span>
                            @endif
                        @endif
                    </div>
                </div>
            </div>
        @endunless

        <div class="row g-4">
            <div class="col-md-6">
                <div class="card card-soft h-100"><div class="card-body">
                    <h2 class="h6 text-brand"><i class="bi bi-person me-2"></i>Personal</h2>
                    <table class="table table-sm detail-table small mb-0"><tbody>
                        {!! $row('Age', $profile->age.' years') !!}
                        {!! $row('Height', $profile->height_label) !!}
                        {!! $row('Weight', $profile->weight_kg ? $profile->weight_kg.' kg' : null) !!}
                        {!! $row('Marital status', $profile->marital_status_label) !!}
                        {!! $row('Children', $profile->children_count) !!}
                        {!! $row('Complexion', $profile->complexion) !!}
                        {!! $row('Body type', $profile->body_type) !!}
                        {!! $row('Physical status', $o['physical_status'][$profile->physical_status] ?? null) !!}
                        {!! $row('Diet', $profile->diet) !!}
                        {!! $row('Smoking', $o['habits'][$profile->smoking] ?? null) !!}
                        {!! $row('Drinking', $o['habits'][$profile->drinking] ?? null) !!}
                    </tbody></table>
                </div></div>
            </div>
            <div class="col-md-6">
                <div class="card card-soft h-100"><div class="card-body">
                    <h2 class="h6 text-brand"><i class="bi bi-moon-stars me-2"></i>Religion &amp; horoscope</h2>
                    <table class="table table-sm detail-table small mb-0"><tbody>
                        {!! $row('Religion', $profile->religion?->name) !!}
                        {!! $row('Caste', $profile->caste?->name) !!}
                        {!! $row('Sub-caste', $profile->sub_caste) !!}
                        {!! $row('Gothram', $profile->gothram) !!}
                        {!! $row('Star', $profile->star) !!}
                        {!! $row('Rasi', $profile->rasi) !!}
                        {!! $row('Dosham', $o['dosham'][$profile->dosham] ?? null) !!}
                        @if($canViewHoroscope)
                            {!! $row('Birth time', $profile->birth_time ? \Illuminate\Support\Carbon::parse($profile->birth_time)->format('h:i A') : null) !!}
                            {!! $row('Birth place', $profile->birth_place) !!}
                        @endif
                    </tbody></table>
                    @if($profile->horoscope_file)
                        @if($canViewHoroscope)
                            <a href="{{ route('profiles.horoscope', $profile) }}" target="_blank" class="btn btn-sm btn-outline-primary mt-2"><i class="bi bi-file-earmark-text me-1"></i>View horoscope</a>
                        @else
                            <a href="{{ route('membership.index') }}" class="btn btn-sm btn-outline-secondary mt-2"><i class="bi bi-lock me-1"></i>Horoscope available for premium members</a>
                        @endif
                    @endif
                </div></div>
            </div>
            <div class="col-md-6">
                <div class="card card-soft h-100"><div class="card-body">
                    <h2 class="h6 text-brand"><i class="bi bi-mortarboard me-2"></i>Education &amp; career</h2>
                    <table class="table table-sm detail-table small mb-0"><tbody>
                        {!! $row('Education', $profile->educationLevel?->name) !!}
                        {!! $row('Details', $profile->education_detail) !!}
                        {!! $row('Occupation', $profile->occupation?->name) !!}
                        {!! $row('Employed in', $profile->employed_in) !!}
                        {!! $row('Company', $profile->company_name) !!}
                        {!! $row('Annual income', $profile->income_label) !!}
                    </tbody></table>
                </div></div>
            </div>
            <div class="col-md-6">
                <div class="card card-soft h-100"><div class="card-body">
                    <h2 class="h6 text-brand"><i class="bi bi-house-heart me-2"></i>Family</h2>
                    <table class="table table-sm detail-table small mb-0"><tbody>
                        {!! $row('Family type', $profile->family_type) !!}
                        {!! $row('Family status', $profile->family_status) !!}
                        {!! $row("Father's occupation", $profile->father_occupation) !!}
                        {!! $row("Mother's occupation", $profile->mother_occupation) !!}
                        {!! $row('Brothers', $profile->brothers) !!}
                        {!! $row('Sisters', $profile->sisters) !!}
                    </tbody></table>
                    @if($profile->about_family)<p class="small mt-2 mb-0">{{ $profile->about_family }}</p>@endif
                </div></div>
            </div>
            <div class="col-12">
                <div class="card card-soft"><div class="card-body">
                    <h2 class="h6 text-brand"><i class="bi bi-search-heart me-2"></i>Partner preferences</h2>
                    <div class="row small">
                        <div class="col-md-6">
                            <table class="table table-sm detail-table mb-0"><tbody>
                                {!! $row('Age', $profile->partner_age_min ? $profile->partner_age_min.' – '.$profile->partner_age_max.' yrs' : 'Any') !!}
                                {!! $row('Height', $profile->partner_height_min ? \App\Models\Profile::heightLabel($profile->partner_height_min).' – '.($profile->partner_height_max ? \App\Models\Profile::heightLabel($profile->partner_height_max) : 'Any') : 'Any') !!}
                                {!! $row('Marital status', collect($profile->partner_marital_status)->map(fn ($s) => $o['marital_status'][$s] ?? $s)->implode(', ') ?: 'Any') !!}
                            </tbody></table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-sm detail-table mb-0"><tbody>
                                {!! $row('Religion', $profile->partnerReligion?->name ?? 'Any') !!}
                                {!! $row('Castes', $profile->partner_caste_ids ? \App\Models\Caste::whereIn('id', $profile->partner_caste_ids)->pluck('name')->implode(', ') : 'Any') !!}
                                {!! $row('Mother tongue', $profile->partner_mother_tongue ?? 'Any') !!}
                            </tbody></table>
                        </div>
                    </div>
                    @if($profile->partner_expectations)<p class="small mt-3 mb-0" style="white-space:pre-line">{{ $profile->partner_expectations }}</p>@endif
                </div></div>
            </div>
        </div>

        @foreach($ads as $ad)
            <div class="ad-slot mt-4">
                <a href="{{ $ad->link_url ? route('banners.click', $ad) : '#' }}" rel="sponsored noopener" @if($ad->link_url) target="_blank" @endif><img src="{{ $ad->image_url }}" alt="{{ $ad->title }}"></a>
            </div>
        @endforeach
    </div>
</div>
@endsection

@push('scripts')
<script>
document.querySelectorAll('.thumb-strip img').forEach(img => img.addEventListener('click', () => {
    document.getElementById('mainPhoto').src = img.dataset.full;
    document.querySelectorAll('.thumb-strip img').forEach(i => i.classList.remove('active'));
    img.classList.add('active');
}));

document.getElementById('revealContact')?.addEventListener('click', async (e) => {
    if (!confirm('This will use 1 contact view from your plan. Continue?')) return;
    e.target.disabled = true;
    try {
        const data = await api(e.target.dataset.url, { method: 'POST' });
        const box = document.getElementById('contactBox');
        box.innerHTML = '<p class="mb-1"><strong>Mobile:</strong> <span class="m"></span></p><p class="mb-0"><strong>Email:</strong> <span class="e"></span></p>';
        box.querySelector('.m').textContent = data.mobile;
        box.querySelector('.e').textContent = data.email;
        toast(`Contact revealed. ${data.remaining ?? 0} views left.`);
    } catch (err) {
        toast(err.message, 'error');
        e.target.disabled = false;
    }
});
</script>
@endpush
