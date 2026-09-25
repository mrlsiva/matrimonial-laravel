<?php

namespace App\Services;

use App\Models\Profile;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

/**
 * Builds public profile queries for advanced search and partner-preference matching.
 */
class ProfileSearchService
{
    public const SORTS = ['newest' => 'Newest first', 'age_asc' => 'Age: youngest', 'age_desc' => 'Age: oldest', 'verified' => 'Verified first'];

    public function baseQuery(?User $viewer): Builder
    {
        return Profile::query()
            ->public()
            ->with(['primaryPhoto', 'religion', 'caste', 'city', 'state', 'educationLevel', 'occupation', 'user.activeSubscription.plan'])
            ->when($viewer, fn ($q) => $q->where('user_id', '!=', $viewer->id));
    }

    public function search(array $filters, ?User $viewer, int $perPage = 12): LengthAwarePaginator
    {
        $query = $this->baseQuery($viewer);

        if (! empty($filters['profile_code'])) {
            return $query->where('profile_code', strtoupper(trim($filters['profile_code'])))->paginate($perPage)->withQueryString();
        }

        $query
            ->when($filters['gender'] ?? null, fn ($q, $v) => $q->where('gender', $v))
            ->ageBetween(isset($filters['age_min']) ? (int) $filters['age_min'] : null, isset($filters['age_max']) ? (int) $filters['age_max'] : null)
            ->when($filters['height_min'] ?? null, fn ($q, $v) => $q->where('height_cm', '>=', (int) $v))
            ->when($filters['height_max'] ?? null, fn ($q, $v) => $q->where('height_cm', '<=', (int) $v))
            ->when($filters['marital_status'] ?? null, fn ($q, $v) => $q->whereIn('marital_status', (array) $v))
            ->when($filters['religion_id'] ?? null, fn ($q, $v) => $q->where('religion_id', $v))
            ->when($filters['caste_id'] ?? null, fn ($q, $v) => $q->whereIn('caste_id', (array) $v))
            ->when($filters['mother_tongue'] ?? null, fn ($q, $v) => $q->where('mother_tongue', $v))
            ->when($filters['education_level_id'] ?? null, fn ($q, $v) => $q->whereIn('education_level_id', (array) $v))
            ->when($filters['occupation_id'] ?? null, fn ($q, $v) => $q->whereIn('occupation_id', (array) $v))
            ->when($filters['annual_income'] ?? null, fn ($q, $v) => $q->whereIn('annual_income', (array) $v))
            ->when($filters['state_id'] ?? null, fn ($q, $v) => $q->where('state_id', $v))
            ->when($filters['city_id'] ?? null, fn ($q, $v) => $q->where('city_id', $v))
            ->when($filters['diet'] ?? null, fn ($q, $v) => $q->where('diet', $v))
            ->when($filters['star'] ?? null, fn ($q, $v) => $q->where('star', $v))
            ->when(! empty($filters['with_photo']), fn ($q) => $q->whereHas('photos', fn ($p) => $p->where('status', 'approved')))
            ->when(! empty($filters['verified_only']), fn ($q) => $q->where('is_verified', true));

        match ($filters['sort'] ?? 'newest') {
            'age_asc' => $query->orderByDesc('date_of_birth'),
            'age_desc' => $query->orderBy('date_of_birth'),
            'verified' => $query->orderByDesc('is_verified')->latest('approved_at'),
            default => $query->latest('approved_at'),
        };

        return $query->paginate($perPage)->withQueryString();
    }

    /** Profiles matching the viewer's saved partner preferences. */
    public function matchesFor(User $viewer, int $perPage = 12): LengthAwarePaginator
    {
        return $this->matchQuery($viewer)->paginate($perPage);
    }

    public function matchQuery(User $viewer): Builder
    {
        $me = $viewer->profile;
        $query = $this->baseQuery($viewer);

        if (! $me) {
            return $query->latest('approved_at');
        }

        return $query
            ->where('gender', $me->gender === 'male' ? 'female' : 'male')
            ->ageBetween($me->partner_age_min, $me->partner_age_max)
            ->when($me->partner_height_min, fn ($q, $v) => $q->where(fn ($q) => $q->whereNull('height_cm')->orWhere('height_cm', '>=', $v)))
            ->when($me->partner_height_max, fn ($q, $v) => $q->where(fn ($q) => $q->whereNull('height_cm')->orWhere('height_cm', '<=', $v)))
            ->when($me->partner_marital_status, fn ($q, $v) => $q->whereIn('marital_status', $v))
            ->when($me->partner_religion_id, fn ($q, $v) => $q->where('religion_id', $v))
            ->when($me->partner_caste_ids, fn ($q, $v) => $q->whereIn('caste_id', $v))
            ->when($me->partner_education_ids, fn ($q, $v) => $q->whereIn('education_level_id', $v))
            ->when($me->partner_state_ids, fn ($q, $v) => $q->whereIn('state_id', $v))
            ->when($me->partner_mother_tongue, fn ($q, $v) => $q->where('mother_tongue', $v))
            ->orderByDesc('is_verified')
            ->latest('approved_at');
    }
}
