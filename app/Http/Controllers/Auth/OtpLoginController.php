<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\OtpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class OtpLoginController extends Controller
{
    public function __construct(private OtpService $otp) {}

    public function create(Request $request): View
    {
        return view('auth.login-otp', ['identifier' => $request->session()->get('otp_login_identifier')]);
    }

    public function send(Request $request): RedirectResponse
    {
        $request->validate(['identifier' => ['required', 'string', 'max:150']]);

        $user = LoginController::findCustomer($request->identifier);

        // Same response whether or not the account exists, to avoid account enumeration.
        if ($user && ! $user->isBlocked()) {
            $target = OtpService::isEmail($request->identifier) ? $user->email : $user->mobile;
            $this->otp->send($target, OtpService::PURPOSE_LOGIN);
        }

        $request->session()->put('otp_login_identifier', trim($request->identifier));

        return redirect()->route('login.otp')->with('success', 'If an account exists, a 6-digit code has been sent.');
    }

    public function verify(Request $request): RedirectResponse
    {
        $request->validate(['code' => ['required', 'digits:6']]);

        $identifier = $request->session()->get('otp_login_identifier');
        $user = $identifier ? LoginController::findCustomer($identifier) : null;
        $isEmail = $identifier && OtpService::isEmail($identifier);
        $target = $user ? ($isEmail ? $user->email : $user->mobile) : null;

        if (! $user || ! $this->otp->verify($target, OtpService::PURPOSE_LOGIN, $request->code)) {
            throw ValidationException::withMessages(['code' => 'The code is invalid or has expired.']);
        }

        // A correct OTP proves ownership of the channel it was sent to.
        $verifiedColumn = $isEmail ? 'email_verified_at' : 'mobile_verified_at';
        if (! $user->{$verifiedColumn}) {
            $user->forceFill([$verifiedColumn => now()])->save();
        }
        $request->session()->forget('otp_login_identifier');

        return LoginController::completeLogin($request, $user);
    }
}
