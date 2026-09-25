<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Banner extends Model
{
    public const POSITIONS = [
        'home_slider' => 'Home - Main Slider',
        'home_middle' => 'Home - Middle Strip',
        'sidebar' => 'Search / Profile Sidebar',
        'dashboard' => 'Member Dashboard',
    ];

    protected $fillable = ['title', 'subtitle', 'image', 'link_url', 'type', 'position', 'starts_on', 'ends_on', 'is_active', 'sort_order'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'starts_on' => 'date', 'ends_on' => 'date'];
    }

    protected static function booted(): void
    {
        static::deleted(fn (Banner $banner) => Storage::disk('public')->delete($banner->image));
    }

    /** Active and within its scheduled window. */
    public function scopeLive(Builder $query, ?string $position = null): Builder
    {
        $today = now()->toDateString();

        return $query->where('is_active', true)
            ->when($position, fn ($q) => $q->where('position', $position))
            ->where(fn ($q) => $q->whereNull('starts_on')->orWhere('starts_on', '<=', $today))
            ->where(fn ($q) => $q->whereNull('ends_on')->orWhere('ends_on', '>=', $today))
            ->orderBy('sort_order');
    }

    public function getImageUrlAttribute(): string
    {
        return Storage::disk('public')->url($this->image);
    }
}
