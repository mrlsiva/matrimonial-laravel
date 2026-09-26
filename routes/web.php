<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\Auth\AdminLoginController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\OtpLoginController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\InterestController;
use App\Http\Controllers\MembershipController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PhotoController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProfileViewController;
use App\Http\Controllers\SearchController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public pages
|--------------------------------------------------------------------------
*/
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/pages/{cmsPage}', [HomeController::class, 'page'])->name('pages.show');
Route::get('/contact-us', [ContactController::class, 'create'])->name('contact.create');
Route::post('/contact-us', [ContactController::class, 'store'])->middleware('throttle:5,10')->name('contact.store');
Route::get('/membership-plans', [MembershipController::class, 'index'])->name('membership.index');
Route::get('/banners/{banner}/click', [HomeController::class, 'bannerClick'])->name('banners.click');

// Razorpay server-to-server webhook (CSRF exempt, signature verified).
Route::post('/razorpay/webhook', [PaymentController::class, 'webhook'])->name('razorpay.webhook');

/*
|--------------------------------------------------------------------------
| Guest authentication
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/register', [RegisterController::class, 'create'])->name('register');
    Route::post('/register', [RegisterController::class, 'store'])->middleware('throttle:10,1');

    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:10,1');

    Route::get('/login/otp', [OtpLoginController::class, 'create'])->name('login.otp');
    Route::post('/login/otp/send', [OtpLoginController::class, 'send'])->middleware('throttle:otp')->name('login.otp.send');
    Route::post('/login/otp/verify', [OtpLoginController::class, 'verify'])->middleware('throttle:10,1')->name('login.otp.verify');

    Route::get('/forgot-password', [PasswordResetController::class, 'request'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'email'])->middleware('throttle:5,1')->name('password.email');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'reset'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'update'])->name('password.update');

    Route::get('/admin/login', [AdminLoginController::class, 'create'])->name('admin.login');
    Route::post('/admin/login', [AdminLoginController::class, 'store'])->middleware('throttle:5,1');
});

Route::post('/logout', [LoginController::class, 'destroy'])->middleware('auth')->name('logout');

/*
|--------------------------------------------------------------------------
| Customer area
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'active', 'role:customer'])->group(function () {
    Route::get('/verify-email', [EmailVerificationController::class, 'notice'])->name('verification.notice');
    Route::post('/verify-email', [EmailVerificationController::class, 'verify'])->middleware('throttle:10,1')->name('verification.verify');
    Route::post('/verify-email/resend', [EmailVerificationController::class, 'resend'])->middleware('throttle:otp')->name('verification.send');

    Route::middleware('verified')->group(function () {
        Route::get('/profile/create', [ProfileController::class, 'create'])->name('profile.create');
        Route::post('/profile', [ProfileController::class, 'store'])->name('profile.store');

        Route::get('/account', [AccountController::class, 'edit'])->name('account.edit');
        Route::put('/account/password', [AccountController::class, 'updatePassword'])->name('account.password');

        Route::middleware('profile.exists')->group(function () {
            Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

            Route::get('/profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
            Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
            Route::delete('/profile/horoscope', [ProfileController::class, 'deleteHoroscope'])->name('profile.horoscope.destroy');

            Route::get('/photos', [PhotoController::class, 'index'])->name('photos.index');
            Route::post('/photos', [PhotoController::class, 'store'])->name('photos.store');
            Route::patch('/photos/{photo}/primary', [PhotoController::class, 'makePrimary'])->name('photos.primary');
            Route::delete('/photos/{photo}', [PhotoController::class, 'destroy'])->name('photos.destroy');

            Route::get('/search', [SearchController::class, 'index'])->name('search');
            Route::get('/matches', [SearchController::class, 'matches'])->name('matches');

            Route::get('/profiles/{profile}', [ProfileViewController::class, 'show'])->name('profiles.show');
            Route::post('/profiles/{profile}/contact', [ProfileViewController::class, 'contact'])->name('profiles.contact');
            Route::get('/profiles/{profile}/horoscope', [ProfileViewController::class, 'horoscope'])->name('profiles.horoscope');

            Route::get('/interests', [InterestController::class, 'index'])->name('interests.index');
            Route::post('/interests/{user}', [InterestController::class, 'store'])->name('interests.store');
            Route::patch('/interests/{interest}/accept', [InterestController::class, 'accept'])->name('interests.accept');
            Route::patch('/interests/{interest}/reject', [InterestController::class, 'reject'])->name('interests.reject');
            Route::delete('/interests/{interest}', [InterestController::class, 'cancel'])->name('interests.cancel');

            Route::get('/shortlist', [FavoriteController::class, 'index'])->name('favorites.index');
            Route::post('/shortlist/{user}', [FavoriteController::class, 'toggle'])->name('favorites.toggle');

            Route::get('/messages', [ChatController::class, 'index'])->name('chat.index');
            Route::get('/messages/{user}', [ChatController::class, 'show'])->name('chat.show');
            Route::get('/messages/{user}/poll', [ChatController::class, 'poll'])->name('chat.poll');
            Route::post('/messages/{user}', [ChatController::class, 'send'])->middleware('throttle:chat')->name('chat.send');

            Route::post('/membership/{plan}/checkout', [PaymentController::class, 'checkout'])->name('payment.checkout');
            Route::post('/payment/verify', [PaymentController::class, 'verify'])->name('payment.verify');
            Route::post('/payment/failed', [PaymentController::class, 'failed'])->name('payment.failed');
            Route::get('/payment/{payment}/result', [PaymentController::class, 'result'])->name('payment.result');
            Route::get('/payments', [PaymentController::class, 'index'])->name('payments.index');
            Route::get('/payments/{payment}/invoice', [PaymentController::class, 'invoice'])->name('payments.invoice');

            Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
            Route::get('/notifications/latest', [NotificationController::class, 'latest'])->name('notifications.latest');
            Route::get('/notifications/{id}', [NotificationController::class, 'open'])->name('notifications.open');
            Route::post('/notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.readAll');
        });
    });
});

/*
|--------------------------------------------------------------------------
| Admin panel
|--------------------------------------------------------------------------
*/
Route::prefix('admin')->name('admin.')->middleware(['auth', 'active', 'role:admin'])->group(function () {
    Route::get('/', [Admin\DashboardController::class, 'index'])->name('dashboard');

    Route::get('/users', [Admin\UserController::class, 'index'])->name('users.index');
    Route::get('/users/create', [Admin\UserController::class, 'create'])->name('users.create');
    Route::post('/users', [Admin\UserController::class, 'store'])->name('users.store');
    Route::get('/users/import', [Admin\MemberImportController::class, 'create'])->name('users.import');
    Route::post('/users/import', [Admin\MemberImportController::class, 'store'])->name('users.import.store');
    Route::get('/users/import/template', [Admin\MemberImportController::class, 'template'])->name('users.import.template');
    Route::get('/users/{user}', [Admin\UserController::class, 'show'])->name('users.show');
    Route::get('/users/{user}/edit', [Admin\UserController::class, 'edit'])->name('users.edit');
    Route::put('/users/{user}', [Admin\UserController::class, 'update'])->name('users.update');
    Route::patch('/users/{user}/status', [Admin\UserController::class, 'updateStatus'])->name('users.status');
    Route::delete('/users/{user}', [Admin\UserController::class, 'destroy'])->name('users.destroy');

    Route::get('/profiles', [Admin\ProfileModerationController::class, 'index'])->name('profiles.index');
    Route::patch('/profiles/{profile}/approve', [Admin\ProfileModerationController::class, 'approve'])->name('profiles.approve');
    Route::patch('/profiles/{profile}/reject', [Admin\ProfileModerationController::class, 'reject'])->name('profiles.reject');
    Route::patch('/profiles/{profile}/verify', [Admin\ProfileModerationController::class, 'toggleVerified'])->name('profiles.verify');
    Route::get('/profiles/{profile}/horoscope', [Admin\ProfileModerationController::class, 'horoscope'])->name('profiles.horoscope');

    Route::get('/photos', [Admin\PhotoModerationController::class, 'index'])->name('photos.index');
    Route::patch('/photos/{photo}/{status}', [Admin\PhotoModerationController::class, 'update'])->whereIn('status', ['approved', 'rejected'])->name('photos.update');

    Route::resource('plans', Admin\MembershipPlanController::class)->except('show');

    Route::get('/masters/{type}', [Admin\MasterDataController::class, 'index'])->name('masters.index');
    Route::post('/masters/{type}', [Admin\MasterDataController::class, 'store'])->name('masters.store');
    Route::put('/masters/{type}/{id}', [Admin\MasterDataController::class, 'update'])->name('masters.update');
    Route::delete('/masters/{type}/{id}', [Admin\MasterDataController::class, 'destroy'])->name('masters.destroy');

    Route::get('/payments', [Admin\PaymentController::class, 'index'])->name('payments.index');
    Route::get('/payments/create', [Admin\PaymentController::class, 'create'])->name('payments.create');
    Route::post('/payments', [Admin\PaymentController::class, 'store'])->name('payments.store');
    Route::get('/payments/{payment}', [Admin\PaymentController::class, 'show'])->name('payments.show');
    Route::get('/payments/{payment}/edit', [Admin\PaymentController::class, 'edit'])->name('payments.edit');
    Route::put('/payments/{payment}', [Admin\PaymentController::class, 'update'])->name('payments.update');
    Route::get('/subscriptions', [Admin\PaymentController::class, 'subscriptions'])->name('subscriptions.index');

    Route::resource('pages', Admin\CmsPageController::class)->except('show')->parameters(['pages' => 'page']);
    Route::resource('banners', Admin\BannerController::class)->except('show');

    Route::get('/messages', [Admin\ContactMessageController::class, 'index'])->name('messages.index');
    Route::get('/messages/{message}', [Admin\ContactMessageController::class, 'show'])->name('messages.show');
    Route::post('/messages/{message}/reply', [Admin\ContactMessageController::class, 'reply'])->name('messages.reply');
    Route::delete('/messages/{message}', [Admin\ContactMessageController::class, 'destroy'])->name('messages.destroy');

    Route::get('/settings', [Admin\SettingsController::class, 'edit'])->name('settings.edit');
    Route::put('/settings/password', [Admin\SettingsController::class, 'updatePassword'])->name('settings.password');
});
