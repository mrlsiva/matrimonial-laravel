<?php

namespace App\Models;

use App\Models\Concerns\IsMasterData;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Religion extends Model
{
    use IsMasterData;

    protected $fillable = ['name', 'slug', 'is_active', 'sort_order'];

    public function castes(): HasMany
    {
        return $this->hasMany(Caste::class)->orderBy('sort_order')->orderBy('name');
    }
}
