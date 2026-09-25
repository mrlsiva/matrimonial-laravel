<?php

namespace App\Providers;

use App\Models\Chat;
use App\Models\CmsPage;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Paginator::useBootstrapFive();

        RateLimiter::for('otp', fn (Request $request) => [
            Limit::perMinute(3)->by($request->input('identifier', $request->user()?->email).'|'.$request->ip()),
            Limit::perHour(10)->by($request->ip()),
        ]);

        RateLimiter::for('chat', fn (Request $request) => Limit::perMinute(30)->by($request->user()?->id ?: $request->ip()));

        View::composer('layouts.app', function ($view) {
            $user = auth()->user();

            $view->with([
                'footerPages' => Cache::remember('footer_pages', 3600, fn () => Schema::hasTable('cms_pages')
                    ? CmsPage::where('is_active', true)->where('show_in_footer', true)->orderBy('title')->get(['title', 'slug'])
                    : collect()),
                'unreadNotifications' => $user ? $user->unreadNotifications()->count() : 0,
                'unreadChats' => $user ? Chat::where('receiver_id', $user->id)->whereNull('read_at')->count() : 0,
            ]);
        });
    }
}
