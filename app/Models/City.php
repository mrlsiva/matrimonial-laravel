<?php

namespace App\Models;

use App\Models\Concerns\IsMasterData;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class City extends Model
{
    use IsMasterData;

    protected $fillable = ['state_id', 'name', 'slug', 'is_active', 'sort_order'];

    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }
}
