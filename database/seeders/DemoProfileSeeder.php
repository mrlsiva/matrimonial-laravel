<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\EducationLevel;
use App\Models\Occupation;
use App\Models\Profile;
use App\Models\Religion;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;

/**
 * Creates approved demo members (password: Password@123) so search, matches and
 * interests can be tried immediately. Runs only in the local environment.
 */
class DemoProfileSeeder extends Seeder
{
    public function run(): void
    {
        $male = ['Arun Kumar', 'Karthik Raja', 'Vignesh Babu', 'Prakash Raj', 'Suresh Kannan', 'Rahul Menon', 'Ashwin Iyer', 'Manoj Pillai', 'Dinesh Kumar', 'Sanjay Reddy', 'Harish Nair', 'Gokul Krishnan'];
        $female = ['Priya Lakshmi', 'Divya Bharathi', 'Keerthana Devi', 'Anitha Rani', 'Meena Kumari', 'Sowmya Iyer', 'Lavanya Nair', 'Nandhini Raj', 'Revathi Pillai', 'Harini Reddy', 'Swathi Menon', 'Kavya Shree'];

        $cities = City::with('state')->whereHas('state', fn ($q) => $q->whereIn('name', ['Tamil Nadu', 'Kerala', 'Karnataka']))->get();
        $hindu = Religion::where('name', 'Hindu')->with('castes')->first();
        $education = EducationLevel::pluck('id')->all();
        $occupations = Occupation::whereNotIn('name', ['Not Working', 'Student'])->pluck('id')->all();
        $o = config('matrimony.options');

        foreach (['male' => $male, 'female' => $female] as $gender => $names) {
            foreach ($names as $i => $name) {
                $email = strtolower(str_replace(' ', '.', $name)).'@example.com';
                if (User::where('email', $email)->exists()) {
                    continue;
                }

                $user = User::create([
                    'name' => $name,
                    'email' => $email,
                    'mobile' => ($gender === 'male' ? '98' : '97').str_pad((string) (40000000 + $i * 1234), 8, '0', STR_PAD_LEFT),
                    'password' => 'Password@123',
                    'role' => User::ROLE_CUSTOMER,
                    'email_verified_at' => now(),
                    'last_login_at' => now()->subHours(rand(1, 200)),
                ]);

                $city = $cities->random();
                $age = $gender === 'male' ? rand(26, 34) : rand(22, 30);

                $profile = new Profile([
                    'created_by' => Arr::random(['self', 'parent']),
                    'gender' => $gender,
                    'date_of_birth' => now()->subYears($age)->subDays(rand(0, 360))->toDateString(),
                    'marital_status' => 'never_married',
                    'height_cm' => $gender === 'male' ? rand(165, 185) : rand(150, 168),
                    'weight_kg' => $gender === 'male' ? rand(60, 85) : rand(45, 65),
                    'complexion' => Arr::random($o['complexion']),
                    'body_type' => Arr::random($o['body_type']),
                    'physical_status' => 'normal',
                    'mother_tongue' => $city->state->name === 'Kerala' ? 'Malayalam' : ($city->state->name === 'Karnataka' ? 'Kannada' : 'Tamil'),
                    'diet' => Arr::random($o['diet']),
                    'smoking' => 'no',
                    'drinking' => Arr::random(['no', 'occasionally']),
                    'about_me' => "I am a {$age}-year-old ".($gender === 'male' ? 'man' : 'woman')." from {$city->name} who values family, honesty and a good sense of humour. I enjoy travel, music and spending time with loved ones.",
                    'religion_id' => $hindu->id,
                    'caste_id' => $hindu->castes->random()->id,
                    'star' => Arr::random($o['star']),
                    'rasi' => Arr::random($o['rasi']),
                    'dosham' => 'no',
                    'education_level_id' => Arr::random($education),
                    'occupation_id' => Arr::random($occupations),
                    'employed_in' => Arr::random(['Private', 'Government', 'Business']),
                    'annual_income' => Arr::random(array_keys($o['annual_income'])),
                    'state_id' => $city->state_id,
                    'city_id' => $city->id,
                    'family_type' => Arr::random($o['family_type']),
                    'family_status' => Arr::random($o['family_status']),
                    'father_occupation' => 'Retired',
                    'mother_occupation' => 'Homemaker',
                    'brothers' => rand(0, 2),
                    'sisters' => rand(0, 2),
                    'partner_age_min' => $gender === 'male' ? $age - 7 : $age,
                    'partner_age_max' => $gender === 'male' ? $age : $age + 7,
                    'partner_religion_id' => $hindu->id,
                    'partner_expectations' => 'Looking for a caring, well-educated partner with good family values.',
                ]);
                $profile->user()->associate($user);
                $profile->save();

                $profile->forceFill([
                    'approval_status' => Profile::STATUS_APPROVED,
                    'approved_at' => now()->subDays(rand(0, 30)),
                    'is_verified' => $i % 3 === 0,
                ])->save();
            }
        }
    }
}
