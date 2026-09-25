<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class MembershipPlan extends Model
{
    protected $fillable = [
        'name', 'slug', 'price', 'duration_days', 'contact_views_limit', 'daily_interest_limit',
        'can_chat', 'can_view_horoscope', 'profile_highlight', 'features', 'badge_color', 'is_active', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'can_chat' => 'boolean',
            'can_view_horoscope' => 'boolean',
            'profile_highlight' => 'boolean',
            'is_active' => 'boolean',
            'features' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::saving(fn (MembershipPlan $plan) => $plan->slug = Str::slug($plan->slug ?: $plan->name));
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('sort_order')->orderBy('price');
    }

    public function isFree(): bool
    {
        return (float) $this->price <= 0;
    }

    /** Limits that apply to members without a paid subscription. */
    public static function free(): ?self
    {
        return static::where('price', 0)->orderBy('sort_order')->first();
    }
}
