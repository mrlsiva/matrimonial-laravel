<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContactView extends Model
{
    protected $fillable = ['viewer_id', 'viewed_user_id'];
}
