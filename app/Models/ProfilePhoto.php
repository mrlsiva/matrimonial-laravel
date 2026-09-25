<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ProfilePhoto extends Model
{
    protected $fillable = ['profile_id', 'path', 'thumb_path', 'is_primary', 'status', 'sort_order'];

    protected function casts(): array
    {
        return ['is_primary' => 'boolean'];
    }

    protected static function booted(): void
    {
        static::deleted(function (ProfilePhoto $photo) {
            Storage::disk('public')->delete([$photo->path, $photo->thumb_path]);
        });
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class);
    }

    public function getUrlAttribute(): string
    {
        return Storage::disk('public')->url($this->path);
    }

    public function getThumbUrlAttribute(): string
    {
        return Storage::disk('public')->url($this->thumb_path);
    }

    public function statusBadge(): string
    {
        return ['approved' => 'success', 'rejected' => 'danger'][$this->status] ?? 'warning';
    }
}
