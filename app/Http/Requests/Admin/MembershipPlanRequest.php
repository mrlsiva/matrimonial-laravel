<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class MembershipPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->isAdmin();
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['slug' => Str::slug($this->slug ?: $this->name)]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:60'],
            'slug' => ['required', 'string', 'max:80', Rule::unique('membership_plans', 'slug')->ignore($this->route('plan'))],
            'price' => ['required', 'numeric', 'min:0', 'max:999999'],
            'duration_days' => ['required', 'integer', 'min:0', 'max:3650'],
            'contact_views_limit' => ['required', 'integer', 'min:0', 'max:100000'],
            'daily_interest_limit' => ['nullable', 'integer', 'min:0', 'max:10000'],
            'badge_color' => ['required', Rule::in(['primary', 'secondary', 'success', 'danger', 'warning', 'info', 'dark'])],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'features_text' => ['nullable', 'string', 'max:3000'],
        ];
    }

    public function planData(): array
    {
        return collect($this->validated())->except('features_text')->merge([
            'sort_order' => (int) $this->sort_order,
            'can_chat' => $this->boolean('can_chat'),
            'can_view_horoscope' => $this->boolean('can_view_horoscope'),
            'profile_highlight' => $this->boolean('profile_highlight'),
            'is_active' => $this->boolean('is_active'),
            'features' => collect(preg_split('/\r\n|\n/', (string) $this->features_text))->map(fn ($l) => trim($l))->filter()->values()->all(),
        ])->all();
    }
}
