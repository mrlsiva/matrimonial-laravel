<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $o = config('matrimony.options');
        $in = fn (string $key) => Rule::in(array_is_list($o[$key]) ? $o[$key] : array_keys($o[$key]));
        $habits = Rule::in(array_keys($o['habits']));

        return [
            // Basic
            'created_by' => ['required', $in('created_by')],
            'gender' => ['required', Rule::in(['male', 'female'])],
            'date_of_birth' => ['required', 'date', 'before_or_equal:'.now()->subYears(18)->toDateString(), 'after:'.now()->subYears(75)->toDateString()],
            'marital_status' => ['required', $in('marital_status')],
            'children_count' => ['nullable', 'integer', 'between:0,10', 'exclude_if:marital_status,never_married'],
            'height_cm' => ['required', 'integer', 'between:120,230'],
            'weight_kg' => ['nullable', 'integer', 'between:30,200'],
            'complexion' => ['nullable', $in('complexion')],
            'body_type' => ['nullable', $in('body_type')],
            'physical_status' => ['required', $in('physical_status')],
            'mother_tongue' => ['required', $in('mother_tongue')],
            'diet' => ['nullable', $in('diet')],
            'smoking' => ['nullable', $habits],
            'drinking' => ['nullable', $habits],
            'about_me' => ['required', 'string', 'min:30', 'max:2000'],

            // Religion & horoscope
            'religion_id' => ['required', Rule::exists('religions', 'id')->where('is_active', true)],
            'caste_id' => ['nullable', Rule::exists('castes', 'id')->where('religion_id', $this->input('religion_id'))],
            'sub_caste' => ['nullable', 'string', 'max:100'],
            'gothram' => ['nullable', 'string', 'max:100'],
            'star' => ['nullable', $in('star')],
            'rasi' => ['nullable', $in('rasi')],
            'dosham' => ['nullable', $in('dosham')],
            'birth_time' => ['nullable', 'date_format:H:i'],
            'birth_place' => ['nullable', 'string', 'max:120'],
            'horoscope' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:4096'],

            // Education & career
            'education_level_id' => ['required', Rule::exists('education_levels', 'id')],
            'education_detail' => ['nullable', 'string', 'max:150'],
            'occupation_id' => ['required', Rule::exists('occupations', 'id')],
            'employed_in' => ['nullable', $in('employed_in')],
            'company_name' => ['nullable', 'string', 'max:150'],
            'annual_income' => ['nullable', $in('annual_income')],

            // Location
            'state_id' => ['required', Rule::exists('states', 'id')],
            'city_id' => ['required', Rule::exists('cities', 'id')->where('state_id', $this->input('state_id'))],
            'address' => ['nullable', 'string', 'max:255'],

            // Family
            'family_type' => ['nullable', $in('family_type')],
            'family_status' => ['nullable', $in('family_status')],
            'father_occupation' => ['nullable', 'string', 'max:100'],
            'mother_occupation' => ['nullable', 'string', 'max:100'],
            'brothers' => ['nullable', 'integer', 'between:0,20'],
            'sisters' => ['nullable', 'integer', 'between:0,20'],
            'about_family' => ['nullable', 'string', 'max:1000'],

            // Partner expectations
            'partner_age_min' => ['nullable', 'integer', 'between:18,75'],
            'partner_age_max' => ['nullable', 'integer', 'between:18,75', 'gte:partner_age_min'],
            'partner_height_min' => ['nullable', 'integer', 'between:120,230'],
            'partner_height_max' => ['nullable', 'integer', 'between:120,230', 'gte:partner_height_min'],
            'partner_marital_status' => ['nullable', 'array'],
            'partner_marital_status.*' => [$in('marital_status')],
            'partner_religion_id' => ['nullable', Rule::exists('religions', 'id')],
            'partner_caste_ids' => ['nullable', 'array'],
            'partner_caste_ids.*' => ['integer', Rule::exists('castes', 'id')],
            'partner_education_ids' => ['nullable', 'array'],
            'partner_education_ids.*' => ['integer', Rule::exists('education_levels', 'id')],
            'partner_state_ids' => ['nullable', 'array'],
            'partner_state_ids.*' => ['integer', Rule::exists('states', 'id')],
            'partner_mother_tongue' => ['nullable', $in('mother_tongue')],
            'partner_expectations' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'date_of_birth.before_or_equal' => 'Age must be at least 18 years.',
            'caste_id.exists' => 'Selected caste does not belong to the chosen religion.',
            'city_id.exists' => 'Selected city does not belong to the chosen state.',
            'about_me.min' => 'Please write at least 30 characters about yourself.',
        ];
    }

    /** Validated data without the upload field, with empty multi-selects normalised to null. */
    public function profileData(): array
    {
        $data = collect($this->validated())->except('horoscope')->all();

        foreach (['partner_marital_status', 'partner_caste_ids', 'partner_education_ids', 'partner_state_ids'] as $key) {
            $data[$key] = empty($data[$key]) ? null : array_values(array_map(
                fn ($v) => is_numeric($v) ? (int) $v : $v,
                $data[$key]
            ));
        }

        return $data;
    }
}
