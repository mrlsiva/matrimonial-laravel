<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MemberRequest;
use App\Models\Profile;
use App\Models\User;
use App\Notifications\ProfileStatusChanged;
use App\Services\MemberImportService;
use App\Services\ProfileFormOptions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UserController extends Controller
{
    public function index(Request $request, MemberImportService $members): View|StreamedResponse
    {
        $query = User::customers()
            ->with(['profile', 'activeSubscription.plan'])
            ->when($request->q, fn ($q, $term) => $q->where(fn ($q) => $q
                ->where('name', 'like', "%{$term}%")
                ->orWhere('email', 'like', "%{$term}%")
                ->orWhere('mobile', 'like', "%{$term}%")
                ->orWhereHas('profile', fn ($p) => $p->where('profile_code', $term))))
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->when($request->approval, fn ($q, $s) => $q->whereHas('profile', fn ($p) => $p->where('approval_status', $s)))
            ->when($request->gender, fn ($q, $g) => $q->whereHas('profile', fn ($p) => $p->where('gender', $g)))
            ->when($request->premium === '1', fn ($q) => $q->whereHas('activeSubscription'))
            ->latest();

        if ($request->export === 'csv') {
            return $this->exportCsv($query, $members);
        }

        return view('admin.users.index', ['users' => $query->paginate(20)->withQueryString()]);
    }

    public function create(): View
    {
        return view('admin.users.form', ProfileFormOptions::for(new Profile(['created_by' => 'self', 'physical_status' => 'normal'])) + [
            'user' => new User(['status' => 'active']),
        ]);
    }

    public function store(MemberRequest $request): RedirectResponse
    {
        $user = DB::transaction(function () use ($request) {
            $user = User::create($request->accountData() + [
                'role' => User::ROLE_CUSTOMER,
                'email_verified_at' => $request->boolean('mark_verified') ? now() : null,
                'mobile_verified_at' => $request->boolean('mark_verified') && $request->mobile ? now() : null,
            ]);

            $profile = new Profile($request->profileData());
            $profile->user()->associate($user);
            $this->applyModeration($request, $profile);
            $profile->save();

            return $user;
        });
        $this->storeHoroscope($request, $user->profile);

        return redirect()->route('admin.users.show', $user)->with('success', 'Member created. You can now add photos or record a payment.');
    }

    public function edit(User $user): View
    {
        abort_if($user->isAdmin(), 404);

        $profile = $user->profile ?? new Profile(['created_by' => 'self', 'physical_status' => 'normal']);

        return view('admin.users.form', ProfileFormOptions::for($profile) + ['user' => $user]);
    }

    public function update(MemberRequest $request, User $user): RedirectResponse
    {
        abort_if($user->isAdmin(), 403);

        DB::transaction(function () use ($request, $user) {
            $user->fill($request->accountData());
            // A changed email / mobile is unverified again unless the admin vouches for it.
            $verify = $request->boolean('mark_verified');
            foreach (['email' => 'email_verified_at', 'mobile' => 'mobile_verified_at'] as $field => $column) {
                if (! $user->{$field}) {
                    $user->{$column} = null;
                } elseif ($user->isDirty($field)) {
                    $user->{$column} = $verify ? now() : null;
                } elseif ($verify) {
                    $user->{$column} ??= now();
                }
            }
            $user->save();

            if ($user->status === 'blocked') {
                DB::table('sessions')->where('user_id', $user->id)->delete();
            }

            $profile = $user->profile ?? new Profile;
            $profile->fill($request->profileData());
            $profile->user()->associate($user);
            $this->applyModeration($request, $profile);
            $profile->save();
        });
        $this->storeHoroscope($request, $user->fresh('profile')->profile);

        return redirect()->route('admin.users.show', $user)->with('success', 'Member details updated.');
    }

    public function show(User $user): View
    {
        abort_if($user->isAdmin(), 404);

        $user->load([
            'profile.photos', 'profile.religion', 'profile.caste', 'profile.educationLevel',
            'profile.occupation', 'profile.state', 'profile.city', 'activeSubscription.plan',
        ]);

        return view('admin.users.show', [
            'user' => $user,
            'payments' => $user->payments()->with('plan')->latest()->get(),
            'subscriptions' => $user->subscriptions()->with('plan')->latest()->get(),
            'interestStats' => [
                'sent' => $user->sentInterests()->count(),
                'received' => $user->receivedInterests()->count(),
            ],
        ]);
    }

    /** Approve / block / unblock an account. "approve" also approves the profile. */
    public function updateStatus(Request $request, User $user): RedirectResponse
    {
        abort_if($user->isAdmin(), 403);
        $request->validate(['action' => ['required', Rule::in(['approve', 'block', 'unblock'])]]);

        switch ($request->action) {
            case 'block':
                $user->update(['status' => 'blocked']);
                DB::table('sessions')->where('user_id', $user->id)->delete();
                break;
            case 'unblock':
                $user->update(['status' => 'active']);
                break;
            case 'approve':
                $user->update(['status' => 'active']);
                if ($user->profile && $user->profile->approval_status !== Profile::STATUS_APPROVED) {
                    $user->profile->forceFill(['approval_status' => Profile::STATUS_APPROVED, 'approved_at' => now(), 'rejection_reason' => null])->save();
                    $user->notify(new ProfileStatusChanged($user->profile));
                }
                break;
        }

        $done = ['approve' => 'approved', 'block' => 'blocked', 'unblock' => 'unblocked'][$request->action];

        return back()->with('success', "User {$done} successfully.");
    }

    public function destroy(User $user): RedirectResponse
    {
        abort_if($user->isAdmin(), 403);

        DB::table('sessions')->where('user_id', $user->id)->delete();
        // Soft delete keeps payment history intact for accounting.
        $user->forceFill(['status' => 'blocked'])->save();
        $user->profile?->forceFill(['approval_status' => Profile::STATUS_REJECTED, 'rejection_reason' => 'Account deleted'])->save();
        $user->delete();

        return redirect()->route('admin.users.index')->with('success', 'User deleted.');
    }

    private function applyModeration(MemberRequest $request, Profile $profile): void
    {
        $status = $request->approval_status;
        $profile->forceFill([
            'approval_status' => $status,
            'approved_at' => $status === Profile::STATUS_APPROVED ? ($profile->approved_at ?? now()) : null,
            'rejection_reason' => $status === Profile::STATUS_REJECTED ? $request->rejection_reason : null,
            'is_verified' => $request->boolean('is_verified'),
        ]);
    }

    private function storeHoroscope(MemberRequest $request, Profile $profile): void
    {
        if (! $request->hasFile('horoscope')) {
            return;
        }

        if ($profile->horoscope_file) {
            Storage::disk('local')->delete($profile->horoscope_file);
        }

        $path = $request->file('horoscope')->store('horoscopes/'.$profile->user_id, 'local');
        $profile->forceFill(['horoscope_file' => $path])->save();
    }

    /** Same columns as the bulk-upload template, so the file can be edited and uploaded again. */
    private function exportCsv($query, MemberImportService $members): StreamedResponse
    {
        return response()->streamDownload(function () use ($query, $members) {
            $out = fopen('php://output', 'w');
            fwrite($out, '﻿'); // UTF-8 BOM so Excel shows ₹ and Tamil names correctly
            fputcsv($out, $members->exportHeader());
            $query->with(['profile.religion', 'profile.caste', 'profile.educationLevel', 'profile.occupation', 'profile.state', 'profile.city'])
                ->chunk(500, function ($users) use ($out, $members) {
                    foreach ($users as $user) {
                        fputcsv($out, $members->exportRow($user));
                    }
                });
            fclose($out);
        }, 'members-'.now()->format('Ymd-His').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
