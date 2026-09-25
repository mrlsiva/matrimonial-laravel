<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CmsPage extends Model
{
    protected $fillable = ['title', 'slug', 'content', 'image', 'meta_title', 'meta_description', 'show_in_footer', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'show_in_footer' => 'boolean'];
    }

    protected static function booted(): void
    {
        static::saving(fn (CmsPage $page) => $page->slug = Str::slug($page->slug ?: $page->title));
        static::deleted(fn (CmsPage $page) => $page->image && Storage::disk('public')->delete($page->image));
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->image ? Storage::disk('public')->url($this->image) : null;
    }
}
