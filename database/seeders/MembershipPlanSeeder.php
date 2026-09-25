<?php

namespace Database\Seeders;

use App\Models\MembershipPlan;
use Illuminate\Database\Seeder;

class MembershipPlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Free', 'slug' => 'free', 'price' => 0, 'duration_days' => 0,
                'contact_views_limit' => 0, 'daily_interest_limit' => 5, 'can_chat' => false,
                'can_view_horoscope' => false, 'profile_highlight' => false, 'badge_color' => 'secondary', 'sort_order' => 1,
                'features' => ['Create profile & upload photos', 'Search and view profiles', 'Chat after interest is accepted'],
            ],
            [
                'name' => 'Gold', 'slug' => 'gold', 'price' => 2999, 'duration_days' => 90,
                'contact_views_limit' => 50, 'daily_interest_limit' => 50, 'can_chat' => true,
                'can_view_horoscope' => true, 'profile_highlight' => false, 'badge_color' => 'warning', 'sort_order' => 2,
                'features' => ['Gold badge on your profile', 'Priority customer support'],
            ],
            [
                'name' => 'Platinum', 'slug' => 'platinum', 'price' => 5999, 'duration_days' => 180,
                'contact_views_limit' => 150, 'daily_interest_limit' => null, 'can_chat' => true,
                'can_view_horoscope' => true, 'profile_highlight' => true, 'badge_color' => 'dark', 'sort_order' => 3,
                'features' => ['Highlighted profile in search results', 'Dedicated relationship advisor'],
            ],
        ];

        foreach ($plans as $plan) {
            MembershipPlan::updateOrCreate(['slug' => $plan['slug']], $plan + ['is_active' => true]);
        }
    }
}
