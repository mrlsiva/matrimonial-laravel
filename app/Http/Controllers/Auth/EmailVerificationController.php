<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\OtpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class EmailVerificationController extends Controller
{
    public function __construct(private OtpService $otp) {}

    public function notice(Request $request): View|RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->route('dashboard');
        }

        return view('auth.verify-email');
    }

    public function verify(Request $request): RedirectResponse
    {
        $request->validate(['code' => ['required', 'digits:6']]);
        $user = $request->user();

        if (! $this->otp->verify($user->email, OtpService::PURPOSE_VERIFY_EMAIL, $request->code)) {
            throw ValidationException::withMessages(['code' => 'The code is invalid or has expired.']);
        }

        $user->markEmailAsVerified();

        return redirect()->route('profile.edit')->with('success', 'Email verified! Complete your profile so we can submit it for approval.');
    }

    public function resend(Request $request): RedirectResponse
    {
        $request->user()->sendEmailVerificationNotification();

        return back()->with('success', 'A new code has been sent to '.$request->user()->email.'.');
    }
}
