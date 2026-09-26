<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\ProfileRequest;
use App\Models\Profile;
use App\Services\OtpService;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/** Admin "Add / edit member": account fields on top of the full profile rules. */
class MemberRequest extends ProfileRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->isAdmin();
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('mobile')) {
            $this->merge(['mobile' => OtpService::normaliseMobile($this->mobile)]);
        }
        $this->merge(['email' => strtolower(trim((string) $this->email))]);
    }

    public function rules(): array
    {
        $userId = $this->route('user')?->id;

        return [
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')->ignore($userId)],
            'mobile' => ['nullable', 'digits:10', 'regex:/^[6-9]\d{9}$/', Rule::unique('users', 'mobile')->ignore($userId)],
            'password' => [$userId ? 'nullable' : 'required', 'string', Password::min(8)],
            'status' => ['required', Rule::in(['active', 'blocked'])],
            'approval_status' => ['required', Rule::in([Profile::STATUS_PENDING, Profile::STATUS_APPROVED, Profile::STATUS_REJECTED])],
            'rejection_reason' => ['nullable', 'required_if:approval_status,rejected', 'string', 'max:255'],
            'is_verified' => ['boolean'],
            'mark_verified' => ['boolean'],
        ] + parent::rules();
    }

    public function messages(): array
    {
        return parent::messages() + [
            'mobile.regex' => 'Please enter a valid 10-digit Indian mobile number.',
            'rejection_reason.required_if' => 'Please give a reason when rejecting the profile.',
        ];
    }

    public function accountData(): array
    {
        return collect($this->validated())->only(['name', 'email', 'mobile', 'status'])
            ->when($this->filled('password'), fn ($c) => $c->put('password', $this->password))
            ->all();
    }

    public function profileData(): array
    {
        return collect(parent::profileData())
            ->except(['name', 'email', 'mobile', 'password', 'status', 'approval_status', 'rejection_reason', 'is_verified', 'mark_verified'])
            ->all();
    }
}
