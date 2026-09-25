<?php

namespace Database\Seeders;

use App\Models\CmsPage;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class CmsPageSeeder extends Seeder
{
    public function run(): void
    {
        $app = config('app.name');

        $pages = [
            'about-us' => ['About Us', "<p>{$app} is a trusted matrimony platform dedicated to helping individuals and families find compatible life partners.</p><h4>Why choose us?</h4><ul><li>Every profile is manually screened before going live.</li><li>Photos are verified by our moderation team.</li><li>Verified badges for members who complete ID verification.</li><li>Your contact details are shared only with members you choose or premium members.</li></ul>"],
            'privacy-policy' => ['Privacy Policy', "<p>We respect your privacy. This policy explains what data we collect and how we use it.</p><h4>Information we collect</h4><p>Account details (name, email, mobile), profile information you provide, photos, and payment references from Razorpay. We never store card or UPI credentials.</p><h4>How we use it</h4><p>To display your profile to other members, recommend matches, process payments and send service notifications.</p><h4>Your choices</h4><p>You can edit your profile at any time or contact support to delete your account.</p>"],
            'terms-and-conditions' => ['Terms and Conditions', "<p>By registering on {$app} you agree to these terms.</p><ol><li>You must be at least 18 years old (21 for grooms as per Indian law for marriage).</li><li>You are registering for the purpose of marriage and the information provided is true.</li><li>Harassment, fraud or commercial use of the platform is prohibited and will lead to account termination.</li><li>Membership fees are non-refundable once the plan is activated.</li><li>{$app} is a platform to connect members and is not responsible for the conduct of members. Please verify details independently before proceeding.</li></ol>"],
            'contact-us' => ['Contact Us', '<p>Our support team is available Monday to Saturday, 9:30 AM to 6:30 PM IST. Use the form to reach us and we will respond within one business day.</p>'],
            'safety-tips' => ['Safety Tips', '<ul><li>Never share passwords, OTPs or bank details.</li><li>Never send money to anyone you meet online.</li><li>Meet in public places and inform your family.</li><li>Report suspicious profiles to our support team.</li></ul>'],
        ];

        foreach ($pages as $slug => [$title, $content]) {
            CmsPage::updateOrCreate(['slug' => $slug], [
                'title' => $title,
                'content' => $content,
                'meta_title' => $title,
                'meta_description' => "{$title} - {$app}",
                'is_active' => true,
                'show_in_footer' => true,
            ]);
        }

        // Default About Us poster (copied into storage so admins can replace or remove it).
        $about = CmsPage::where('slug', 'about-us')->first();
        if ($about && ! $about->image && file_exists(public_path('images/about-us.webp'))) {
            Storage::disk('public')->put('pages/about-us.webp', file_get_contents(public_path('images/about-us.webp')));
            $about->update(['image' => 'pages/about-us.webp']);
        }
    }
}
