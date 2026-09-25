<?php

namespace App\Http\Requests\Auth;

use App\Services\OtpService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('mobile')) {
            $this->merge(['mobile' => OtpService::normaliseMobile($this->mobile)]);
        }
    }

    public function rules(): array
    {
        return [
            'created_by' => ['required', Rule::in(array_keys(config('matrimony.options.created_by')))],
            'name' => ['required', 'string', 'min:3', 'max:100', 'regex:/^[\pL\s.\'-]+$/u'],
            'gender' => ['required', Rule::in(['male', 'female'])],
            'date_of_birth' => ['required', 'date', 'before_or_equal:'.now()->subYears(18)->toDateString(), 'after:'.now()->subYears(75)->toDateString()],
            'email' => ['required', 'email:rfc', 'max:150', Rule::unique('users', 'email')],
            'mobile' => ['required', 'digits:10', 'regex:/^[6-9]\d{9}$/', Rule::unique('users', 'mobile')],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
            'terms' => ['accepted'],
        ];
    }

    public function messages(): array
    {
        return [
            'date_of_birth.before_or_equal' => 'You must be at least 18 years old to register.',
            'mobile.regex' => 'Please enter a valid 10-digit Indian mobile number.',
            'name.regex' => 'Name may only contain letters, spaces, dots and hyphens.',
            'terms.accepted' => 'You must accept the terms and privacy policy.',
        ];
    }
}
