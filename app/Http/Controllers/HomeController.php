<?php

namespace App\Http\Controllers;

use App\Models\Banner;
use App\Models\Caste;
use App\Models\City;
use App\Models\CmsPage;
use App\Models\MembershipPlan;
use App\Models\Profile;
use App\Models\Religion;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        return view('home', [
            'sliders' => Banner::live('home_slider')->get(),
            'middleAds' => Banner::live('home_middle')->get(),
            'featured' => Profile::public()
                ->with(['primaryPhoto', 'city', 'state', 'occupation', 'educationLevel'])
                ->whereHas('photos', fn ($q) => $q->where('status', 'approved'))
                ->orderByDesc('is_verified')
                ->latest('approved_at')
                ->take(8)
                ->get(),
            'plans' => MembershipPlan::active()->get(),
            'religions' => Religion::active()->get(['id', 'name']),
            'stats' => [
                'members' => Profile::public()->count(),
                'verified' => Profile::public()->where('is_verified', true)->count(),
            ],
        ]);
    }

    public function page(CmsPage $cmsPage): View
    {
        abort_unless($cmsPage->is_active, 404);

        if ($cmsPage->slug === 'about-us') {
            return view('pages.about', [
                'page' => $cmsPage,
                'stats' => [
                    'members' => Profile::public()->count(),
                    'verified' => Profile::public()->where('is_verified', true)->count(),
                    'communities' => Caste::where('is_active', true)->count(),
                    'cities' => City::where('is_active', true)->count(),
                ],
            ]);
        }

        return view('pages.show', ['page' => $cmsPage]);
    }

    public function bannerClick(Banner $banner): RedirectResponse
    {
        abort_unless($banner->is_active && $banner->link_url, 404);
        $banner->increment('clicks');

        return redirect()->away($banner->link_url);
    }
}
