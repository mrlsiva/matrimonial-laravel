<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'identifier' => ['required', 'string', 'max:150'],
            'password' => ['required', 'string'],
        ]);

        $user = self::findCustomer($data['identifier']);

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages(['identifier' => 'These credentials do not match our records.']);
        }

        return self::completeLogin($request, $user, $request->boolean('remember'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        $wasAdmin = $request->user()?->isAdmin();

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route($wasAdmin ? 'admin.login' : 'home');
    }

    /** Look up a customer by email or 10-digit mobile number. */
    public static function findCustomer(string $identifier): ?User
    {
        $identifier = trim($identifier);
        $column = OtpService::isEmail($identifier) ? 'email' : 'mobile';
        $value = $column === 'email' ? strtolower($identifier) : OtpService::normaliseMobile($identifier);

        return User::customers()->where($column, $value)->first();
    }

    public static function completeLogin(Request $request, User $user, bool $remember = false): RedirectResponse
    {
        if ($user->isBlocked()) {
            throw ValidationException::withMessages(['identifier' => 'Your account has been blocked. Please contact support.']);
        }

        Auth::login($user, $remember);
        $request->session()->regenerate();
        $user->forceFill(['last_login_at' => now()])->save();

        return redirect()->intended(route('dashboard'));
    }
}
