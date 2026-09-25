<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

/**
 * Shared behaviour for lookup tables (religion, caste, state, city ...).
 */
trait IsMasterData
{
    public static function bootIsMasterData(): void
    {
        static::saving(function ($model) {
            $model->slug = Str::slug($model->name);
        });
    }

    public function initializeIsMasterData(): void
    {
        $this->mergeCasts(['is_active' => 'boolean']);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('sort_order')->orderBy('name');
    }
}
