<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class RegisterController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function store(RegisterRequest $request): RedirectResponse
    {
        $user = DB::transaction(function () use ($request) {
            $user = User::create([
                'name' => $request->name,
                'email' => strtolower($request->email),
                'mobile' => $request->mobile,
                'password' => $request->password,
                'role' => User::ROLE_CUSTOMER,
            ]);

            $profile = new Profile([
                'gender' => $request->gender,
                'date_of_birth' => $request->date_of_birth,
                'created_by' => $request->created_by,
            ]);
            $profile->user()->associate($user);
            $profile->save();

            return $user;
        });

        $user->sendEmailVerificationNotification();

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('verification.notice')
            ->with('success', 'Account created! We have sent a 6-digit code to '.$user->email.'.');
    }
}
