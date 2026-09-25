<?php

namespace App\Models;

use App\Models\Concerns\IsMasterData;
use Illuminate\Database\Eloquent\Model;

class Occupation extends Model
{
    use IsMasterData;

    protected $fillable = ['name', 'slug', 'is_active', 'sort_order'];
}
