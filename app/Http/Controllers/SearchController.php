<?php

namespace App\Http\Controllers;

use App\Models\Banner;
use App\Models\Caste;
use App\Models\City;
use App\Models\EducationLevel;
use App\Models\Occupation;
use App\Models\Profile;
use App\Models\Religion;
use App\Models\State;
use App\Services\ProfileSearchService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SearchController extends Controller
{
    public function __construct(private ProfileSearchService $search) {}

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'profile_code' => ['nullable', 'string', 'max:20'],
            'gender' => ['nullable', Rule::in(['male', 'female'])],
            'age_min' => ['nullable', 'integer', 'between:18,75'],
            'age_max' => ['nullable', 'integer', 'between:18,75'],
            'height_min' => ['nullable', 'integer'],
            'height_max' => ['nullable', 'integer'],
            'marital_status' => ['nullable', 'array'],
            'religion_id' => ['nullable', 'integer'],
            'caste_id' => ['nullable', 'array'],
            'caste_id.*' => ['integer'],
            'mother_tongue' => ['nullable', 'string', 'max:40'],
            'education_level_id' => ['nullable', 'array'],
            'education_level_id.*' => ['integer'],
            'occupation_id' => ['nullable', 'array'],
            'occupation_id.*' => ['integer'],
            'annual_income' => ['nullable', 'array'],
            'state_id' => ['nullable', 'integer'],
            'city_id' => ['nullable', 'integer'],
            'diet' => ['nullable', 'string', 'max:30'],
            'star' => ['nullable', 'string', 'max:40'],
            'with_photo' => ['nullable', 'boolean'],
            'verified_only' => ['nullable', 'boolean'],
            'sort' => ['nullable', Rule::in(array_keys(ProfileSearchService::SORTS))],
        ]);

        // Default to the opposite gender on a fresh search.
        if (! $request->has('gender')) {
            $filters['gender'] = $request->user()->profile->gender === 'male' ? 'female' : 'male';
        }

        return view('search.index', [
            'results' => $this->search->search($filters, $request->user()),
            'filters' => $filters,
            'religions' => Religion::active()->get(['id', 'name']),
            'castes' => ! empty($filters['religion_id']) ? Caste::where('religion_id', $filters['religion_id'])->orderBy('name')->get(['id', 'name']) : collect(),
            'educationLevels' => EducationLevel::active()->get(['id', 'name']),
            'occupations' => Occupation::active()->get(['id', 'name']),
            'states' => State::active()->get(['id', 'name']),
            'cities' => ! empty($filters['state_id']) ? City::where('state_id', $filters['state_id'])->orderBy('name')->get(['id', 'name']) : collect(),
            'options' => config('matrimony.options'),
            'heights' => Profile::heightOptions(),
            'ads' => Banner::live('sidebar')->get(),
            'favoriteIds' => $request->user()->favorites()->pluck('users.id')->all(),
        ]);
    }

    public function matches(Request $request): View
    {
        return view('search.matches', [
            'results' => $this->search->matchesFor($request->user()),
            'hasPreferences' => filled($request->user()->profile->partner_age_min) || filled($request->user()->profile->partner_religion_id),
            'favoriteIds' => $request->user()->favorites()->pluck('users.id')->all(),
        ]);
    }
}
