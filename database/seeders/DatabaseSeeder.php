<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            AdminSeeder::class,
            MasterDataSeeder::class,
            MembershipPlanSeeder::class,
            CmsPageSeeder::class,
            BannerSeeder::class,
        ]);

        // Sample members for local testing only.
        if (app()->environment('local')) {
            $this->call(DemoProfileSeeder::class);
        }
    }
}
