{{-- Expects: $p (Profile), optional $favoriteIds (array of user ids) --}}
@php($isFav = in_array($p->user_id, $favoriteIds ?? []))
@php($premium = $p->relationLoaded('user') ? $p->user?->activeSubscription?->plan : null)
<div class="card profile-card h-100 position-relative {{ $premium?->profile_highlight ? 'highlight' : '' }}">
    <a href="{{ route('profiles.show', $p) }}" class="position-relative d-block">
        <img src="{{ $p->photo_url }}" class="photo" alt="Profile photo of {{ $p->profile_code }}" loading="lazy">
        @if($premium)
            <span class="badge bg-{{ $premium->badge_color }} ribbon"><i class="bi bi-gem me-1"></i>{{ $premium->name }}</span>
        @endif
    </a>
    @auth
        @if(isset($favoriteIds))
            <button class="btn fav-btn shadow-sm" data-ajax="{{ route('favorites.toggle', $p->user_id) }}" data-toggle="favorite" title="{{ $isFav ? 'Remove from shortlist' : 'Add to shortlist' }}">
                <i class="bi {{ $isFav ? 'bi-star-fill text-warning' : 'bi-star' }}"></i>
            </button>
        @endif
    @endauth
    <div class="card-body p-3">
        <div class="d-flex justify-content-between align-items-start">
            <h6 class="mb-1 fw-semibold">
                <a href="{{ route('profiles.show', $p) }}" class="text-decoration-none text-dark">{{ $p->profile_code }}</a>
                @if($p->is_verified)<i class="bi bi-patch-check-fill verified-badge" title="Verified profile"></i>@endif
            </h6>
        </div>
        <div class="small text-muted">
            {{ $p->age }} yrs{{ $p->height_cm ? ', '.\App\Models\Profile::heightLabel($p->height_cm) : '' }}<br>
            {{ collect([$p->religion?->name, $p->caste?->name])->filter()->implode(' • ') }}<br>
            {{ $p->occupation?->name ?? $p->educationLevel?->name }}<br>
            <i class="bi bi-geo-alt"></i> {{ $p->location }}
        </div>
    </div>
    <div class="card-footer bg-white border-0 pt-0 pb-3 px-3">
        <a href="{{ route('profiles.show', $p) }}" class="btn btn-sm btn-outline-primary w-100 rounded-pill">View profile</a>
    </div>
</div>
