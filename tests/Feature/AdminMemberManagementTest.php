<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\EducationLevel;
use App\Models\MembershipPlan;
use App\Models\Occupation;
use App\Models\Payment;
use App\Models\Profile;
use App\Models\Religion;
use App\Models\User;
use App\Services\MemberImportService;
use Database\Seeders\AdminSeeder;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\MembershipPlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class AdminMemberManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([AdminSeeder::class, MasterDataSeeder::class, MembershipPlanSeeder::class]);
        $this->admin = User::where('role', 'admin')->first();
        $this->actingAs($this->admin);
    }

    private function memberForm(array $overrides = []): array
    {
        $religion = Religion::where('name', 'Hindu')->first();
        $city = City::first();

        return array_merge([
            'name' => 'Karthik Raja', 'email' => 'karthik@example.com', 'mobile' => '9876500001', 'password' => 'Secret@123',
            'status' => 'active', 'approval_status' => 'approved', 'is_verified' => '1', 'mark_verified' => '1',
            'created_by' => 'parent', 'gender' => 'male', 'date_of_birth' => now()->subYears(29)->toDateString(),
            'marital_status' => 'never_married', 'mother_tongue' => 'Tamil', 'height_cm' => 172, 'physical_status' => 'normal',
            'about_me' => 'Calm and caring engineer who values family and simple living.',
            'religion_id' => $religion->id, 'caste_id' => $religion->castes()->first()->id,
            'education_level_id' => EducationLevel::first()->id, 'occupation_id' => Occupation::first()->id,
            'state_id' => $city->state_id, 'city_id' => $city->id,
        ], $overrides);
    }

    private function csv(array $rows): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'imp');
        $out = fopen($path, 'w');
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, array_keys($rows[0]));
        foreach ($rows as $row) {
            fputcsv($out, $row);
        }
        fclose($out);

        return new UploadedFile($path, 'members.csv', 'text/csv', null, true);
    }

    private function importRow(array $overrides = []): array
    {
        $religion = Religion::where('name', 'Hindu')->first();
        $city = City::with('state')->first();

        return array_merge(array_fill_keys(array_keys(MemberImportService::COLUMNS), ''), [
            'name' => 'Priya Raman', 'email' => 'priya@example.com', 'mobile' => '98765 00002',
            'gender' => 'Female', 'date_of_birth' => now()->subYears(26)->format('d-m-Y'), 'marital_status' => 'Never Married',
            'mother_tongue' => 'tamil', 'height_cm' => '160', 'religion' => 'hindu', 'caste' => $religion->castes()->first()->name,
            'education' => EducationLevel::first()->name, 'occupation' => Occupation::first()->name,
            'state' => $city->state->name, 'city' => $city->name, 'birth_time' => '6:30 am', 'annual_income' => '₹5 - 10 Lakh',
        ], $overrides);
    }

    public function test_admin_pages_render(): void
    {
        $member = User::factory()->create(['role' => 'customer']);

        foreach ([route('admin.users.create'), route('admin.users.import'), route('admin.users.import.template'),
            route('admin.users.edit', $member), route('admin.payments.create', ['user_id' => $member->id]),
            route('admin.users.index', ['export' => 'csv'])] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_admin_creates_and_updates_a_member(): void
    {
        $this->post(route('admin.users.store'), $this->memberForm())->assertRedirect()->assertSessionHasNoErrors();

        $user = User::where('email', 'karthik@example.com')->firstOrFail();
        $this->assertSame('customer', $user->role);
        $this->assertNotNull($user->email_verified_at);
        $this->assertSame(Profile::STATUS_APPROVED, $user->profile->approval_status);
        $this->assertTrue($user->profile->is_verified);
        $this->assertNotNull($user->profile->profile_code);

        $this->put(route('admin.users.update', $user), $this->memberForm([
            'name' => 'Karthik R', 'password' => '', 'mobile' => '9876500009', 'mark_verified' => '0', 'height_cm' => 175,
        ]))->assertRedirect(route('admin.users.show', $user))->assertSessionHasNoErrors();

        $user->refresh();
        $this->assertSame('Karthik R', $user->name);
        $this->assertSame(175, $user->profile->height_cm);
        $this->assertNull($user->mobile_verified_at, 'a changed mobile is unverified unless the admin vouches for it');
        $this->assertTrue(auth()->validate(['email' => $user->email, 'password' => 'Secret@123']), 'blank password keeps the old one');

        $this->post(route('admin.users.store'), $this->memberForm(['mobile' => '9876500003']))->assertSessionHasErrors('email');
    }

    public function test_bulk_import_creates_members_and_records_payments(): void
    {
        $file = $this->csv([
            $this->importRow(['plan' => 'Gold', 'payment_method' => 'UPI', 'payment_reference' => 'UTR123']),
            $this->importRow(['name' => 'Bad Row', 'email' => 'bad@example.com', 'mobile' => '', 'gender' => 'X', 'date_of_birth' => '31-02-1990', 'religion' => 'Unknown']),
            $this->importRow(['name' => 'Too Young', 'email' => 'young@example.com', 'mobile' => '', 'date_of_birth' => now()->subYears(15)->toDateString()]),
        ]);

        $this->post(route('admin.users.import.store'), ['file' => $file, 'default_profile_status' => 'approved', 'mark_verified' => '1'])
            ->assertRedirect(route('admin.users.import'))
            ->assertSessionHas('import_result', fn ($r) => $r['created'] === 1 && $r['payments'] === 1 && count($r['errors']) === 2);

        $priya = User::where('email', 'priya@example.com')->firstOrFail();
        $this->assertSame('9876500002', $priya->mobile);
        $this->assertSame('female', $priya->profile->gender);
        $this->assertSame('06:30', substr($priya->profile->birth_time, 0, 5));
        $this->assertSame('5-10', $priya->profile->annual_income);
        $this->assertSame(Profile::STATUS_APPROVED, $priya->profile->approval_status);
        $this->assertTrue($priya->isPremium());
        $this->assertSame('Gold', $priya->currentPlan()->name);
        $this->assertDatabaseHas('payments', ['user_id' => $priya->id, 'source' => 'manual', 'method' => 'upi', 'reference' => 'UTR123', 'status' => 'paid']);
        $this->assertDatabaseMissing('users', ['email' => 'bad@example.com']);

        // Re-uploading without "update" is refused; with "update" it changes the member but never charges twice.
        $again = fn () => $this->csv([$this->importRow(['name' => 'Priya R', 'height_cm' => '162', 'plan' => 'Gold', 'payment_method' => 'UPI', 'payment_reference' => 'UTR123'])]);
        $this->post(route('admin.users.import.store'), ['file' => $again(), 'default_profile_status' => 'approved'])
            ->assertSessionHas('import_result', fn ($r) => $r['updated'] === 0 && count($r['errors']) === 1);
        $this->post(route('admin.users.import.store'), ['file' => $again(), 'default_profile_status' => 'approved', 'update_existing' => '1'])
            ->assertSessionHas('import_result', fn ($r) => $r['updated'] === 1 && $r['payments'] === 0 && ! $r['errors']);

        $this->assertSame('Priya R', $priya->fresh()->name);
        $this->assertSame(162, $priya->fresh()->profile->height_cm);
        $this->assertSame(1, Payment::where('user_id', $priya->id)->count());
    }

    public function test_manual_payment_activates_plan_and_can_be_updated(): void
    {
        $this->post(route('admin.users.store'), $this->memberForm());
        $member = User::where('email', 'karthik@example.com')->first();
        $gold = MembershipPlan::where('slug', 'gold')->first();

        // Pending first: nothing is activated.
        $this->post(route('admin.payments.store'), [
            'member' => $member->profile->profile_code, 'membership_plan_id' => $gold->id, 'amount' => 2500,
            'method' => 'cash', 'reference' => 'RCPT-9', 'status' => 'created', 'paid_at' => '',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $payment = Payment::where('user_id', $member->id)->firstOrFail();
        $this->assertTrue($payment->isManual());
        $this->assertFalse($member->fresh()->isPremium());

        // Marking it paid activates the plan and issues an invoice.
        $this->put(route('admin.payments.update', $payment), [
            'membership_plan_id' => $gold->id, 'amount' => 2500, 'method' => 'cash', 'reference' => 'RCPT-9',
            'status' => 'paid', 'paid_at' => today()->toDateString(),
        ])->assertRedirect(route('admin.payments.show', $payment))->assertSessionHasNoErrors();

        $payment->refresh();
        $this->assertNotNull($payment->invoice_no);
        $this->assertSame($payment->recorded_by, $this->admin->id);
        $this->assertTrue($member->fresh()->isPremium());

        // Admin extends the expiry.
        $newExpiry = now()->addDays(200)->toDateString();
        $this->put(route('admin.payments.update', $payment), [
            'amount' => 2500, 'method' => 'upi', 'status' => 'paid', 'paid_at' => today()->toDateString(),
            'starts_at' => today()->toDateString(), 'expires_at' => $newExpiry,
        ])->assertSessionHasNoErrors();
        $this->assertSame($newExpiry, $payment->fresh()->subscription->expires_at->toDateString());
        $this->assertSame('upi', $payment->fresh()->method);

        // Marking it failed withdraws the plan.
        $this->put(route('admin.payments.update', $payment), [
            'amount' => 2500, 'method' => 'upi', 'status' => 'failed',
        ])->assertSessionHasNoErrors();
        $this->assertFalse($member->fresh()->isPremium());

        $this->get(route('admin.payments.show', $payment))->assertOk()->assertSee('RCPT-9');
        $this->post(route('admin.payments.store'), ['member' => 'nobody@example.com', 'membership_plan_id' => $gold->id, 'amount' => 1, 'method' => 'cash', 'status' => 'paid', 'paid_at' => today()->toDateString()])
            ->assertSessionHasErrors('member');
    }

    public function test_razorpay_payments_cannot_be_edited(): void
    {
        $member = User::factory()->create(['role' => 'customer']);
        $payment = Payment::create(['user_id' => $member->id, 'membership_plan_id' => MembershipPlan::where('slug', 'gold')->value('id'), 'amount' => 2999, 'status' => 'paid']);

        $this->get(route('admin.payments.edit', $payment))->assertForbidden();
    }
}
