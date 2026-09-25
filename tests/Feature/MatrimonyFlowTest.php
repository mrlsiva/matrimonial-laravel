<?php

namespace Tests\Feature;

use App\Mail\OtpMail;
use App\Models\Caste;
use App\Models\Chat;
use App\Models\City;
use App\Models\EducationLevel;
use App\Models\Interest;
use App\Models\MembershipPlan;
use App\Models\Occupation;
use App\Models\Payment;
use App\Models\Profile;
use App\Models\Religion;
use App\Models\User;
use App\Services\RazorpayService;
use Database\Seeders\AdminSeeder;
use Database\Seeders\CmsPageSeeder;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\MembershipPlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MatrimonyFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([AdminSeeder::class, MasterDataSeeder::class, MembershipPlanSeeder::class, CmsPageSeeder::class]);
    }

    private function member(string $gender = 'male', string $status = Profile::STATUS_APPROVED): User
    {
        static $n = 0;
        $n++;
        $user = User::factory()->create(['role' => 'customer', 'mobile' => '98765'.str_pad((string) $n, 5, '0', STR_PAD_LEFT)]);
        $city = City::first();
        $religion = Religion::where('name', 'Hindu')->first();

        $profile = new Profile([
            'gender' => $gender,
            'date_of_birth' => now()->subYears(27)->toDateString(),
            'marital_status' => 'never_married',
            'height_cm' => 170,
            'mother_tongue' => 'Tamil',
            'about_me' => str_repeat('Friendly and family oriented person. ', 2),
            'religion_id' => $religion->id,
            'caste_id' => $religion->castes()->first()->id,
            'education_level_id' => EducationLevel::first()->id,
            'occupation_id' => Occupation::first()->id,
            'state_id' => $city->state_id,
            'city_id' => $city->id,
        ]);
        $profile->user()->associate($user);
        $profile->save();
        $profile->forceFill(['approval_status' => $status])->save();

        return $user->fresh();
    }

    public function test_public_pages_render(): void
    {
        $this->get('/')->assertOk()->assertSee('Search your match');
        $this->get('/membership-plans')->assertOk()->assertSee('Platinum');
        $this->get('/pages/about-us')->assertOk()->assertSee('About Us');
        $this->get('/contact-us')->assertOk();
        $this->get('/login')->assertOk();
        $this->get('/register')->assertOk();
        $this->get('/login/otp')->assertOk();
        $this->get('/forgot-password')->assertOk();
        $this->get('/admin/login')->assertOk();
        $this->get('/api/states')->assertOk()->assertJsonStructure(['data' => [['id', 'name']]]);
        $religion = Religion::first();
        $this->get("/api/religions/{$religion->id}/castes")->assertOk()->assertJsonPath('data.0.name', fn ($v) => is_string($v));
    }

    public function test_registration_with_email_otp_verification(): void
    {
        Mail::fake();

        $this->post('/register', [
            'created_by' => 'self',
            'name' => 'Test Groom',
            'gender' => 'male',
            'date_of_birth' => now()->subYears(28)->toDateString(),
            'email' => 'groom@example.com',
            'mobile' => '9876543210',
            'password' => 'Secret123',
            'password_confirmation' => 'Secret123',
            'terms' => '1',
        ])->assertRedirect(route('verification.notice'));

        $user = User::where('email', 'groom@example.com')->first();
        $this->assertNotNull($user->profile);
        $this->assertSame('pending', $user->profile->approval_status);
        $this->assertMatchesRegularExpression('/^KM\d{6}$/', $user->profile->profile_code);

        // Unverified users are sent to the OTP screen.
        $this->get('/dashboard')->assertRedirect(route('verification.notice'));

        $code = null;
        Mail::assertSent(OtpMail::class, function (OtpMail $mail) use (&$code) {
            $code = $mail->code;
            return true;
        });

        $this->post('/verify-email', ['code' => '000000'])->assertSessionHasErrors('code');
        $this->post('/verify-email', ['code' => $code])->assertRedirect(route('profile.edit'));
        $this->assertNotNull($user->fresh()->email_verified_at);
        $this->get('/dashboard')->assertOk();
    }

    public function test_underage_registration_is_rejected(): void
    {
        $this->post('/register', [
            'created_by' => 'self', 'name' => 'Too Young', 'gender' => 'female',
            'date_of_birth' => now()->subYears(16)->toDateString(),
            'email' => 'young@example.com', 'mobile' => '9876500000',
            'password' => 'Secret123', 'password_confirmation' => 'Secret123', 'terms' => '1',
        ])->assertSessionHasErrors('date_of_birth');
    }

    public function test_login_with_password_and_with_otp(): void
    {
        $user = $this->member();

        $this->post('/login', ['identifier' => $user->email, 'password' => 'wrong'])->assertSessionHasErrors('identifier');
        $this->post('/login', ['identifier' => $user->mobile, 'password' => 'password'])->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
        $this->post('/logout');

        Mail::fake();
        $this->post('/login/otp/send', ['identifier' => $user->email])->assertRedirect(route('login.otp'));
        $code = null;
        Mail::assertSent(OtpMail::class, function (OtpMail $m) use (&$code) { $code = $m->code; return true; });
        $this->post('/login/otp/verify', ['code' => $code])->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_blocked_user_cannot_login_and_admin_cannot_use_customer_login(): void
    {
        $user = $this->member();
        $user->update(['status' => 'blocked']);
        $this->post('/login', ['identifier' => $user->email, 'password' => 'password'])->assertSessionHasErrors('identifier');
        $this->assertGuest();

        $this->post('/login', ['identifier' => 'admin@matrimonial.test', 'password' => 'Admin@12345'])->assertSessionHasErrors('identifier');
    }

    public function test_member_pages_render(): void
    {
        $me = $this->member('male');
        $bride = $this->member('female');

        $this->actingAs($me);
        foreach (['/dashboard', '/profile/edit', '/photos', '/search', '/matches', '/interests', '/interests?tab=sent',
                     '/shortlist', '/messages', '/payments', '/notifications', '/account', '/membership-plans'] as $url) {
            $this->get($url)->assertOk();
        }
        $this->get(route('profiles.show', $bride->profile))->assertOk()->assertSee($bride->profile->profile_code);
        $this->get('/search?gender=female&age_min=20&age_max=35&with_photo=0')->assertOk()->assertSee($bride->profile->profile_code);
        $this->get('/search?profile_code='.$bride->profile->profile_code)->assertOk()->assertSee($bride->profile->profile_code);
    }

    public function test_pending_profiles_are_hidden_from_others(): void
    {
        $me = $this->member('male');
        $pending = $this->member('female', Profile::STATUS_PENDING);

        $this->actingAs($me)->get(route('profiles.show', $pending->profile))->assertNotFound();
        $this->actingAs($me)->get('/search?gender=female')->assertDontSee($pending->profile->profile_code);
        $this->actingAs($pending)->get(route('profiles.show', $pending->profile))->assertOk()->assertSee('preview');
    }

    public function test_profile_update_validates_and_saves(): void
    {
        $me = $this->member('female');
        $religion = Religion::where('name', 'Muslim')->first();
        $wrongCaste = Caste::where('religion_id', '!=', $religion->id)->first();
        $city = City::first();

        $payload = [
            'created_by' => 'parent', 'gender' => 'female', 'date_of_birth' => now()->subYears(25)->toDateString(),
            'marital_status' => 'never_married', 'height_cm' => 160, 'physical_status' => 'normal', 'mother_tongue' => 'Tamil',
            'about_me' => 'I am a cheerful person who loves reading books and cooking for family.',
            'religion_id' => $religion->id, 'caste_id' => $wrongCaste->id,
            'education_level_id' => EducationLevel::first()->id, 'occupation_id' => Occupation::first()->id,
            'state_id' => $city->state_id, 'city_id' => $city->id,
            'partner_age_min' => 26, 'partner_age_max' => 32, 'partner_marital_status' => ['never_married'],
        ];

        $this->actingAs($me)->put('/profile', $payload)->assertSessionHasErrors('caste_id');

        $payload['caste_id'] = $religion->castes()->first()->id;
        $this->actingAs($me)->put('/profile', $payload)->assertRedirect(route('profile.edit'))->assertSessionHas('success');
        $profile = $me->profile->fresh();
        $this->assertSame('parent', $profile->created_by);
        $this->assertSame(['never_married'], $profile->partner_marital_status);
    }

    public function test_photo_upload_is_optimised_and_pending(): void
    {
        Storage::fake('public');
        $me = $this->member();

        $this->actingAs($me)->post('/photos', ['photos' => [UploadedFile::fake()->image('me.jpg', 800, 1000)]])->assertSessionHas('success');
        $photo = $me->profile->photos()->first();
        $this->assertSame('pending', $photo->status);
        $this->assertTrue($photo->is_primary);
        $this->assertStringEndsWith('.webp', $photo->path);
        Storage::disk('public')->assertExists([$photo->path, $photo->thumb_path]);

        // Too small image is rejected.
        $this->actingAs($me)->post('/photos', ['photos' => [UploadedFile::fake()->image('tiny.jpg', 100, 100)]])->assertSessionHasErrors();
    }

    public function test_interest_flow_unlocks_chat(): void
    {
        $groom = $this->member('male');
        $bride = $this->member('female');

        // Free member cannot chat before an accepted interest.
        $this->actingAs($groom)->postJson(route('chat.send', $bride), ['message' => 'Hi'])->assertForbidden();

        $this->actingAs($groom)->postJson(route('interests.store', $bride))->assertOk();
        $this->actingAs($groom)->postJson(route('interests.store', $bride))->assertStatus(422);
        $this->assertCount(1, $bride->fresh()->unreadNotifications);

        $interest = Interest::first();
        $this->actingAs($groom)->patchJson(route('interests.accept', $interest))->assertForbidden();
        $this->actingAs($bride)->patchJson(route('interests.accept', $interest))->assertOk();
        $this->assertSame('accepted', $interest->fresh()->status);

        $this->actingAs($groom)->postJson(route('chat.send', $bride), ['message' => 'Hello!'])->assertCreated()->assertJsonPath('message.mine', true);
        $this->actingAs($bride)->getJson(route('chat.poll', $groom).'?after=0')->assertOk()->assertJsonPath('messages.0.message', 'Hello!');
        $this->assertNotNull(Chat::first()->fresh()->read_at);
        $this->actingAs($bride)->get(route('chat.show', $groom))->assertOk()->assertSee('Hello!');
        $this->actingAs($groom)->get('/messages')->assertOk()->assertSee($bride->profile->profile_code);
    }

    public function test_same_gender_interest_and_daily_limit(): void
    {
        $a = $this->member('male');
        $b = $this->member('male');
        $this->actingAs($a)->postJson(route('interests.store', $b))->assertStatus(422);

        $brides = collect(range(1, 6))->map(fn () => $this->member('female'));
        foreach ($brides->take(5) as $bride) {
            $this->actingAs($a)->postJson(route('interests.store', $bride))->assertOk();
        }
        $this->actingAs($a)->postJson(route('interests.store', $brides->last()))
            ->assertStatus(422)->assertJsonPath('message', fn ($m) => str_contains($m, 'daily interest limit'));
    }

    public function test_shortlist_toggle(): void
    {
        $me = $this->member('male');
        $bride = $this->member('female');

        $this->actingAs($me)->postJson(route('favorites.toggle', $bride))->assertJsonPath('favorited', true);
        $this->actingAs($me)->get('/shortlist')->assertSee($bride->profile->profile_code);
        $this->actingAs($me)->postJson(route('favorites.toggle', $bride))->assertJsonPath('favorited', false);
    }

    public function test_razorpay_checkout_verify_and_premium_activation(): void
    {
        config(['services.razorpay.key' => 'rzp_test_key', 'services.razorpay.secret' => 'test_secret', 'services.razorpay.webhook_secret' => 'hook_secret']);
        $this->partialMock(RazorpayService::class, function ($mock) {
            $mock->shouldReceive('createOrder')->andReturn(['id' => 'order_TEST1', 'amount' => 299900, 'currency' => 'INR']);
            $mock->shouldReceive('fetchPaymentMethod')->andReturn('upi');
        });

        $me = $this->member('male');
        $bride = $this->member('female');
        $gold = MembershipPlan::where('slug', 'gold')->first();

        $this->actingAs($me)->postJson(route('payment.checkout', $gold))
            ->assertOk()->assertJsonPath('order_id', 'order_TEST1')->assertJsonPath('amount', 299900);

        // Tampered signature fails.
        $this->actingAs($me)->post('/payment/verify', [
            'razorpay_order_id' => 'order_TEST1', 'razorpay_payment_id' => 'pay_X', 'razorpay_signature' => 'bad',
        ]);
        $this->assertSame('failed', Payment::first()->status);
        $this->assertFalse($me->fresh()->isPremium());

        // Valid signature activates the plan.
        Payment::first()->update(['status' => 'created']);
        $signature = hash_hmac('sha256', 'order_TEST1|pay_OK', 'test_secret');
        $payment = Payment::first();
        $this->actingAs($me)->post('/payment/verify', [
            'razorpay_order_id' => 'order_TEST1', 'razorpay_payment_id' => 'pay_OK', 'razorpay_signature' => $signature,
        ])->assertRedirect(route('payment.result', $payment));

        $payment->refresh();
        $this->assertSame('paid', $payment->status);
        $this->assertSame('upi', $payment->method);
        $this->assertNotNull($payment->invoice_no);
        $me = $me->fresh();
        $this->assertTrue($me->isPremium());
        $this->assertSame('Gold', $me->currentPlan()->name);
        $this->assertTrue($me->activeSubscription->expires_at->between(now()->addDays(89), now()->addDays(91)));

        // Premium members can chat without an accepted interest and reveal contacts.
        $this->actingAs($me)->postJson(route('chat.send', $bride), ['message' => 'Namaste'])->assertCreated();
        $this->actingAs($me)->postJson(route('profiles.contact', $bride->profile))->assertOk()->assertJsonPath('email', $bride->email)->assertJsonPath('remaining', 49);

        $this->actingAs($me)->get(route('payment.result', $payment))->assertOk()->assertSee('Payment successful');
        $this->actingAs($me)->get(route('payments.invoice', $payment))->assertOk()->assertSee($payment->invoice_no);

        // Webhook replay is idempotent (no second subscription).
        $body = json_encode(['event' => 'payment.captured', 'payload' => ['payment' => ['entity' => ['id' => 'pay_OK', 'order_id' => 'order_TEST1', 'method' => 'upi']]]]);
        $this->call('POST', '/razorpay/webhook', [], [], [], ['CONTENT_TYPE' => 'application/json', 'HTTP_X_RAZORPAY_SIGNATURE' => hash_hmac('sha256', $body, 'hook_secret')], $body)->assertOk();
        $this->assertSame(1, $me->subscriptions()->count());
        $this->call('POST', '/razorpay/webhook', [], [], [], ['CONTENT_TYPE' => 'application/json', 'HTTP_X_RAZORPAY_SIGNATURE' => 'bad'], $body)->assertStatus(400);
    }

    public function test_free_member_cannot_reveal_contact(): void
    {
        $me = $this->member('male');
        $bride = $this->member('female');

        $this->actingAs($me)->postJson(route('profiles.contact', $bride->profile))->assertStatus(402);
    }

    public function test_subscription_expiry_command(): void
    {
        $me = $this->member();
        $me->subscriptions()->create([
            'membership_plan_id' => MembershipPlan::where('slug', 'gold')->value('id'),
            'amount' => 2999, 'starts_at' => now()->subDays(91), 'expires_at' => now()->subDay(), 'status' => 'active',
        ]);

        $this->artisan('subscriptions:process')->assertSuccessful();
        $this->assertSame('expired', $me->subscriptions()->first()->status);
        $this->assertCount(1, $me->fresh()->notifications);
    }

    public function test_admin_panel(): void
    {
        $this->post('/admin/login', ['email' => 'admin@matrimonial.test', 'password' => 'Admin@12345'])->assertRedirect(route('admin.dashboard'));
        $admin = User::where('role', 'admin')->first();
        $pending = $this->member('female', Profile::STATUS_PENDING);

        $this->actingAs($admin);
        foreach (['/admin', '/admin/users', '/admin/profiles', '/admin/photos', '/admin/plans', '/admin/plans/create',
                     '/admin/payments', '/admin/subscriptions', '/admin/pages', '/admin/pages/create', '/admin/banners',
                     '/admin/banners/create', '/admin/messages', '/admin/settings', '/admin/masters/religions',
                     '/admin/masters/castes', '/admin/masters/education-levels', '/admin/masters/occupations',
                     '/admin/masters/states', '/admin/masters/cities', '/admin/payments?export=csv'] as $url) {
            $this->get($url)->assertOk();
        }
        $this->get(route('admin.users.show', $pending))->assertOk()->assertSee($pending->profile->profile_code);
        $this->get('/admin/masters/unknown')->assertNotFound();

        // Approve profile -> public + notification.
        $this->patch(route('admin.profiles.approve', $pending->profile))->assertSessionHas('success');
        $this->assertSame('approved', $pending->profile->fresh()->approval_status);
        $this->assertCount(1, $pending->fresh()->notifications);

        // Verified badge toggle.
        $this->patch(route('admin.profiles.verify', $pending->profile));
        $this->assertTrue($pending->profile->fresh()->is_verified);

        // Block user.
        $this->patch(route('admin.users.status', $pending), ['action' => 'block']);
        $this->assertSame('blocked', $pending->fresh()->status);

        // Master data CRUD.
        $this->post('/admin/masters/religions', ['name' => 'Zoroastrian', 'is_active' => 1])->assertSessionHas('success');
        $this->post('/admin/masters/religions', ['name' => 'Zoroastrian'])->assertSessionHasErrors('name');
        $state = \App\Models\State::first();
        $this->post('/admin/masters/cities', ['state_id' => $state->id, 'name' => 'Test City', 'is_active' => 1])->assertSessionHas('success');
        $city = City::where('name', 'Test City')->first();
        $this->put("/admin/masters/cities/{$city->id}", ['state_id' => $state->id, 'name' => 'Renamed City'])->assertSessionHas('success');
        $this->assertFalse($city->fresh()->is_active);
        $this->delete("/admin/masters/cities/{$city->id}")->assertSessionHas('success');

        // Plans CRUD.
        $this->post('/admin/plans', ['name' => 'Diamond', 'price' => 9999, 'duration_days' => 365, 'contact_views_limit' => 500,
            'badge_color' => 'info', 'can_chat' => 1, 'is_active' => 1, 'features_text' => "VIP support\nFeatured"])->assertRedirect(route('admin.plans.index'));
        $this->assertSame(['VIP support', 'Featured'], MembershipPlan::where('slug', 'diamond')->first()->features);

        // CMS page with script is sanitised.
        $this->post('/admin/pages', ['title' => 'Success Stories', 'content' => '<p onclick="x()">Hi</p><script>alert(1)</script>', 'is_active' => 1])->assertRedirect();
        $page = \App\Models\CmsPage::where('slug', 'success-stories')->first();
        $this->assertStringNotContainsString('script', $page->content);
        $this->assertStringNotContainsString('onclick', $page->content);

        // Banner upload.
        Storage::fake('public');
        $this->post('/admin/banners', ['title' => 'Diwali Offer', 'type' => 'banner', 'position' => 'home_slider', 'is_active' => 1,
            'image' => UploadedFile::fake()->image('b.jpg', 1920, 600)])->assertRedirect(route('admin.banners.index'));
        $this->get('/')->assertSee('Diwali Offer');
    }

    public function test_cms_page_side_image_upload_and_removal(): void
    {
        Storage::fake('public');
        $admin = User::where('role', 'admin')->first();
        $page = \App\Models\CmsPage::where('slug', 'about-us')->first();
        $fields = ['title' => $page->title, 'slug' => $page->slug, 'content' => $page->content, 'is_active' => 1];

        $this->actingAs($admin)->put(route('admin.pages.update', $page), $fields + ['image' => UploadedFile::fake()->image('poster.png', 1024, 1536)])
            ->assertRedirect(route('admin.pages.index'));
        $page->refresh();
        $this->assertStringEndsWith('.webp', $page->image);
        Storage::disk('public')->assertExists($page->image);
        $this->get('/pages/about-us')->assertOk()->assertSee($page->image_url, false);

        $old = $page->image;
        $this->actingAs($admin)->put(route('admin.pages.update', $page), $fields + ['remove_image' => 1]);
        $this->assertNull($page->fresh()->image);
        Storage::disk('public')->assertMissing($old);
    }

    public function test_customers_cannot_access_admin(): void
    {
        $this->actingAs($this->member())->get('/admin')->assertForbidden();
        $this->post('/logout');
        $this->get('/admin')->assertRedirect(route('admin.login'));
    }

    public function test_contact_form(): void
    {
        $this->post('/contact-us', ['name' => 'Ravi', 'email' => 'ravi@example.com', 'subject' => 'Help', 'message' => 'I need help with my profile.'])
            ->assertSessionHas('success');
        $this->assertDatabaseHas('contact_messages', ['email' => 'ravi@example.com', 'status' => 'new']);

        // Honeypot blocks bots.
        $this->post('/contact-us', ['name' => 'Bot', 'email' => 'b@x.com', 'subject' => 'x', 'message' => 'spam spam spam', 'website' => 'http://spam'])
            ->assertSessionHasErrors('website');
    }
}
