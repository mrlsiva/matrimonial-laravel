<?php

namespace App\Models;

use App\Models\Concerns\IsMasterData;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Caste extends Model
{
    use IsMasterData;

    protected $fillable = ['religion_id', 'name', 'slug', 'is_active', 'sort_order'];

    public function religion(): BelongsTo
    {
        return $this->belongsTo(Religion::class);
    }
}
