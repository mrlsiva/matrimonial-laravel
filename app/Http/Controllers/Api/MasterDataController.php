<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MembershipPlan;
use App\Models\Religion;
use App\Models\State;
use Illuminate\Http\JsonResponse;

class MasterDataController extends Controller
{
    public function religions(): JsonResponse
    {
        return response()->json(['data' => Religion::active()->get(['id', 'name'])]);
    }

    public function castes(Religion $religion): JsonResponse
    {
        return response()->json(['data' => $religion->castes()->where('is_active', true)->get(['id', 'name'])]);
    }

    public function states(): JsonResponse
    {
        return response()->json(['data' => State::active()->get(['id', 'name'])]);
    }

    public function cities(State $state): JsonResponse
    {
        return response()->json(['data' => $state->cities()->where('is_active', true)->get(['id', 'name'])]);
    }

    public function plans(): JsonResponse
    {
        return response()->json([
            'data' => MembershipPlan::active()->get(['id', 'name', 'slug', 'price', 'duration_days', 'contact_views_limit', 'can_chat', 'features']),
        ]);
    }
}
