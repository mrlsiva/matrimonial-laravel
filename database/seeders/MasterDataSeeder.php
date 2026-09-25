<?php

namespace Database\Seeders;

use App\Models\EducationLevel;
use App\Models\Occupation;
use App\Models\Religion;
use App\Models\State;
use Illuminate\Database\Seeder;

class MasterDataSeeder extends Seeder
{
    public function run(): void
    {
        $religions = [
            'Hindu' => ['Adi Dravida', 'Agamudayar', 'Brahmin - Iyer', 'Brahmin - Iyengar', 'Brahmin - Others', 'Chettiar', 'Devendra Kula Vellalar', 'Ezhava', 'Gounder', 'Kamma', 'Kapu', 'Maratha', 'Mudaliar', 'Nadar', 'Naidu', 'Nair', 'Pillai', 'Rajput', 'Reddy', 'Thevar', 'Vanniyar', 'Vishwakarma', 'Yadav', 'Agarwal', 'Jat', 'Kayastha', 'Lingayat', 'Vokkaliga', 'Other'],
            'Muslim' => ['Sunni', 'Shia', 'Rowther', 'Labbai', 'Marakayar', 'Other'],
            'Christian' => ['Roman Catholic', 'CSI', 'Protestant', 'Pentecost', 'Syrian Catholic', 'Born Again', 'Other'],
            'Sikh' => ['Jat', 'Khatri', 'Ramgarhia', 'Arora', 'Other'],
            'Jain' => ['Digambar', 'Shwetambar', 'Other'],
            'Buddhist' => ['Other'],
            'Parsi' => ['Other'],
            'Jewish' => ['Other'],
            'Inter-Religion' => ['Other'],
            'No Religion' => ['Other'],
        ];

        $order = 0;
        foreach ($religions as $name => $castes) {
            $religion = Religion::updateOrCreate(['name' => $name], ['is_active' => true, 'sort_order' => $order++]);
            foreach ($castes as $i => $caste) {
                $religion->castes()->updateOrCreate(['name' => $caste], ['is_active' => true, 'sort_order' => $caste === 'Other' ? 999 : $i]);
            }
        }

        $education = ['Doctorate (PhD)', 'Masters - Engineering', 'Masters - Arts / Science / Commerce', 'MBA / PGDM', 'Medicine - MBBS / MD / MS', 'Dental - BDS / MDS', 'Law - LLB / LLM', 'Bachelors - Engineering', 'Bachelors - Arts / Science / Commerce', 'CA / CS / ICWA', 'Diploma', 'Higher Secondary (12th)', 'Secondary (10th)', 'Other'];
        foreach ($education as $i => $name) {
            EducationLevel::updateOrCreate(['name' => $name], ['is_active' => true, 'sort_order' => $i]);
        }

        $occupations = ['Software Professional', 'Engineer - Non IT', 'Doctor', 'Nurse / Healthcare', 'Teacher / Lecturer', 'Chartered Accountant', 'Banking / Finance', 'Government Employee', 'Defence', 'Lawyer', 'Business Owner', 'Sales / Marketing', 'HR / Admin', 'Architect / Designer', 'Civil Services (IAS/IPS)', 'Scientist / Researcher', 'Farmer / Agriculture', 'Self Employed', 'Student', 'Not Working', 'Other'];
        foreach ($occupations as $i => $name) {
            Occupation::updateOrCreate(['name' => $name], ['is_active' => true, 'sort_order' => $i]);
        }

        $states = [
            'Tamil Nadu' => ['Chennai', 'Coimbatore', 'Madurai', 'Tiruchirappalli', 'Salem', 'Tirunelveli', 'Erode', 'Vellore', 'Thoothukudi', 'Thanjavur', 'Dindigul', 'Kanyakumari', 'Karur', 'Namakkal', 'Sivakasi', 'Virudhunagar', 'Hosur', 'Kanchipuram'],
            'Kerala' => ['Thiruvananthapuram', 'Kochi', 'Kozhikode', 'Thrissur', 'Kollam', 'Kannur', 'Palakkad', 'Kottayam'],
            'Karnataka' => ['Bengaluru', 'Mysuru', 'Mangaluru', 'Hubballi', 'Belagavi', 'Davangere'],
            'Andhra Pradesh' => ['Visakhapatnam', 'Vijayawada', 'Guntur', 'Tirupati', 'Nellore', 'Kurnool'],
            'Telangana' => ['Hyderabad', 'Warangal', 'Karimnagar', 'Nizamabad'],
            'Maharashtra' => ['Mumbai', 'Pune', 'Nagpur', 'Nashik', 'Aurangabad', 'Thane'],
            'Delhi' => ['New Delhi', 'North Delhi', 'South Delhi', 'East Delhi', 'West Delhi'],
            'Gujarat' => ['Ahmedabad', 'Surat', 'Vadodara', 'Rajkot'],
            'Rajasthan' => ['Jaipur', 'Jodhpur', 'Udaipur', 'Kota'],
            'Uttar Pradesh' => ['Lucknow', 'Kanpur', 'Noida', 'Varanasi', 'Agra', 'Prayagraj'],
            'West Bengal' => ['Kolkata', 'Howrah', 'Durgapur', 'Siliguri'],
            'Punjab' => ['Ludhiana', 'Amritsar', 'Jalandhar', 'Mohali'],
            'Madhya Pradesh' => ['Bhopal', 'Indore', 'Gwalior', 'Jabalpur'],
            'Odisha' => ['Bhubaneswar', 'Cuttack', 'Rourkela'],
            'Puducherry' => ['Puducherry', 'Karaikal'],
            'Haryana' => ['Gurugram', 'Faridabad', 'Panipat'],
            'Bihar' => ['Patna', 'Gaya', 'Muzaffarpur'],
            'Goa' => ['Panaji', 'Margao'],
        ];

        $order = 0;
        foreach ($states as $name => $cities) {
            $state = State::updateOrCreate(['name' => $name], ['is_active' => true, 'sort_order' => $order++]);
            foreach ($cities as $city) {
                $state->cities()->updateOrCreate(['name' => $city], ['is_active' => true]);
            }
        }
    }
}
