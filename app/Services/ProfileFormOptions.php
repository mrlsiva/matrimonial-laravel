<?php

namespace App\Services;

use App\Models\Caste;
use App\Models\City;
use App\Models\EducationLevel;
use App\Models\Occupation;
use App\Models\Profile;
use App\Models\Religion;
use App\Models\State;

/** View data for the profile fields partial (profile._fields), shared by members and admins. */
class ProfileFormOptions
{
    public static function for(Profile $profile): array
    {
        return [
            'profile' => $profile,
            'religions' => Religion::active()->get(['id', 'name']),
            'castes' => $profile->religion_id ? Caste::where('religion_id', $profile->religion_id)->where('is_active', true)->orderBy('name')->get(['id', 'name']) : collect(),
            'partnerCastes' => $profile->partner_religion_id ? Caste::where('religion_id', $profile->partner_religion_id)->where('is_active', true)->orderBy('name')->get(['id', 'name']) : collect(),
            'educationLevels' => EducationLevel::active()->get(['id', 'name']),
            'occupations' => Occupation::active()->get(['id', 'name']),
            'states' => State::active()->get(['id', 'name']),
            'cities' => $profile->state_id ? City::where('state_id', $profile->state_id)->where('is_active', true)->orderBy('name')->get(['id', 'name']) : collect(),
            'options' => config('matrimony.options'),
            'heights' => Profile::heightOptions(),
        ];
    }
}
