<?php

namespace Database\Seeders;

use App\Models\Banner;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

/**
 * Registers the default home banner as an admin-manageable slide.
 * The image is copied into storage so deleting the slide never removes the
 * static fallback in public/images.
 */
class BannerSeeder extends Seeder
{
    public function run(): void
    {
        if (Banner::where('position', 'home_slider')->exists()) {
            return;
        }

        $path = 'banners/kalyan-matrimony-home.webp';
        Storage::disk('public')->put($path, file_get_contents(public_path('images/banner.webp')));

        Banner::create([
            'title' => config('app.name').' - Find Your Life Partner',
            'subtitle' => 'Verified profiles, compatible matches, safe and trusted.',
            'image' => $path,
            'link_url' => null, // empty = guests go to Register, members go to Matches
            'type' => 'banner',
            'position' => 'home_slider',
            'is_active' => true,
            'sort_order' => 0,
        ]);
    }
}
