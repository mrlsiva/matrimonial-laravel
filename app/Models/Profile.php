<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Profile extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    protected $guarded = ['id', 'user_id', 'profile_code', 'slug', 'approval_status', 'approved_at', 'is_verified', 'views_count', 'rejection_reason'];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'approved_at' => 'datetime',
            'is_verified' => 'boolean',
            'partner_marital_status' => 'array',
            'partner_caste_ids' => 'array',
            'partner_education_ids' => 'array',
            'partner_state_ids' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::created(function (Profile $profile) {
            $prefix = config('matrimony.profile_code_prefix');
            $profile->profile_code = $prefix.str_pad((string) (100000 + $profile->id), 6, '0', STR_PAD_LEFT);
            $profile->slug = $profile->buildSlug();
            $profile->saveQuietly();
        });
    }

    public function buildSlug(): string
    {
        $firstName = Str::of($this->user?->name ?? 'member')->explode(' ')->first();

        return Str::slug($this->profile_code.' '.$firstName);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    // ---- Relationships ----

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function photos(): HasMany
    {
        return $this->hasMany(ProfilePhoto::class)->orderByDesc('is_primary')->orderBy('sort_order');
    }

    public function approvedPhotos(): HasMany
    {
        return $this->photos()->where('status', 'approved');
    }

    public function primaryPhoto(): HasOne
    {
        return $this->hasOne(ProfilePhoto::class)->where('status', 'approved')->orderByDesc('is_primary')->orderBy('sort_order');
    }

    public function religion(): BelongsTo { return $this->belongsTo(Religion::class); }
    public function caste(): BelongsTo { return $this->belongsTo(Caste::class); }
    public function educationLevel(): BelongsTo { return $this->belongsTo(EducationLevel::class); }
    public function occupation(): BelongsTo { return $this->belongsTo(Occupation::class); }
    public function state(): BelongsTo { return $this->belongsTo(State::class); }
    public function city(): BelongsTo { return $this->belongsTo(City::class); }
    public function partnerReligion(): BelongsTo { return $this->belongsTo(Religion::class, 'partner_religion_id'); }

    // ---- Scopes ----

    /** Profiles that may be shown to other members: approved + owner account active. */
    public function scopePublic(Builder $query): Builder
    {
        return $query->where('approval_status', self::STATUS_APPROVED)
            ->whereHas('user', fn ($q) => $q->where('status', 'active')->where('role', User::ROLE_CUSTOMER));
    }

    public function scopeAgeBetween(Builder $query, ?int $min, ?int $max): Builder
    {
        if ($min) {
            $query->where('date_of_birth', '<=', now()->subYears($min)->toDateString());
        }
        if ($max) {
            $query->where('date_of_birth', '>', now()->subYears($max + 1)->toDateString());
        }

        return $query;
    }

    // ---- Accessors / helpers ----

    public function getAgeAttribute(): ?int
    {
        return $this->date_of_birth?->age;
    }

    public function getHeightLabelAttribute(): ?string
    {
        return $this->height_cm ? self::heightLabel($this->height_cm) : null;
    }

    public static function heightLabel(int $cm): string
    {
        $inches = (int) round($cm / 2.54);

        return intdiv($inches, 12)."' ".($inches % 12)."\" ({$cm} cm)";
    }

    public static function heightOptions(): array
    {
        $options = [];
        for ($cm = 137; $cm <= 213; $cm += 2) {
            $options[$cm] = self::heightLabel($cm);
        }

        return $options;
    }

    public function getPhotoUrlAttribute(): string
    {
        $photo = $this->relationLoaded('primaryPhoto') ? $this->primaryPhoto : $this->primaryPhoto()->first();

        return $photo ? Storage::disk('public')->url($photo->thumb_path) : $this->placeholderUrl();
    }

    public function placeholderUrl(): string
    {
        return asset('images/avatar-'.($this->gender === 'female' ? 'female' : 'male').'.svg');
    }

    public function getLocationAttribute(): string
    {
        return collect([$this->city?->name, $this->state?->name])->filter()->implode(', ') ?: $this->country;
    }

    public function getMaritalStatusLabelAttribute(): string
    {
        return config('matrimony.options.marital_status')[$this->marital_status] ?? ucfirst((string) $this->marital_status);
    }

    public function getIncomeLabelAttribute(): ?string
    {
        return config('matrimony.options.annual_income')[$this->annual_income] ?? null;
    }

    public function isPublic(): bool
    {
        return $this->approval_status === self::STATUS_APPROVED && $this->user?->status === 'active';
    }

    /** Rough completeness score shown on the dashboard. */
    public function completionPercent(): int
    {
        $fields = [
            'date_of_birth', 'height_cm', 'mother_tongue', 'about_me', 'religion_id', 'caste_id', 'star', 'rasi',
            'education_level_id', 'occupation_id', 'annual_income', 'state_id', 'city_id', 'family_type',
            'father_occupation', 'partner_age_min', 'partner_expectations',
        ];
        $filled = collect($fields)->filter(fn ($f) => filled($this->{$f}))->count();
        $filled += $this->photos()->exists() ? 3 : 0;

        return (int) min(100, round($filled / (count($fields) + 3) * 100));
    }
}
