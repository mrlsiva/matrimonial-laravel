<?php

namespace App\Services;

use App\Exceptions\ImportRowFailed;
use App\Models\Caste;
use App\Models\City;
use App\Models\EducationLevel;
use App\Models\MembershipPlan;
use App\Models\Occupation;
use App\Models\Payment;
use App\Models\Profile;
use App\Models\Religion;
use App\Models\State;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Throwable;

/**
 * Bulk member upload / download in CSV (opens in Excel). The same column list is used
 * for the blank template, the export and the import, so an export can be edited and
 * uploaded again. Master data (religion, caste, city...) is written as names, not IDs.
 */
class MemberImportService
{
    public const MAX_ROWS = 2000;

    /** CSV header => short help shown on the import page. */
    public const COLUMNS = [
        'name' => 'Required. Full name',
        'email' => 'Required. Used to match existing members when updating',
        'mobile' => '10-digit Indian mobile number',
        'password' => 'Blank = random password (member can log in with OTP or reset it)',
        'gender' => 'Required for new members: Male / Female',
        'date_of_birth' => 'Required for new members: YYYY-MM-DD or DD-MM-YYYY, 18+',
        'marital_status' => 'Never Married / Divorced / Widowed / Awaiting Divorce',
        'children_count' => 'Number',
        'created_by' => 'Self / Parent / Sibling / Relative / Friend',
        'height_cm' => 'Height in cm, e.g. 170',
        'weight_kg' => 'Weight in kg',
        'complexion' => '', 'body_type' => '', 'physical_status' => 'Normal / Physically Challenged',
        'mother_tongue' => 'e.g. Tamil', 'diet' => '', 'smoking' => 'No / Occasionally / Yes', 'drinking' => 'No / Occasionally / Yes',
        'religion' => 'Name as in Master Data, e.g. Hindu',
        'caste' => 'Must belong to the religion',
        'sub_caste' => '', 'gothram' => '', 'star' => '', 'rasi' => '', 'dosham' => "No / Yes / Don't Know",
        'birth_time' => 'HH:MM (24h) or 06:30 AM', 'birth_place' => '',
        'education' => 'Highest education, name as in Master Data',
        'education_detail' => '', 'occupation' => 'Name as in Master Data', 'employed_in' => '', 'company_name' => '',
        'annual_income' => 'e.g. 5-10 or "₹5 - 10 Lakh"',
        'state' => 'Name as in Master Data', 'city' => 'Must belong to the state', 'address' => '',
        'family_type' => 'Joint / Nuclear', 'family_status' => '', 'father_occupation' => '', 'mother_occupation' => '',
        'brothers' => '', 'sisters' => '', 'about_me' => '', 'about_family' => '',
        'partner_age_min' => '', 'partner_age_max' => '', 'partner_expectations' => '',
        'profile_status' => 'Approved / Pending (blank = the default chosen on upload)',
        'account_status' => 'Active / Blocked (blank = Active)',
        'plan' => 'Membership plan name, to record a manual payment',
        'payment_amount' => 'Blank = plan price',
        'payment_method' => 'Cash / UPI / Bank transfer / Cheque / Card / Other',
        'payment_reference' => 'Receipt / UTR / cheque no.',
        'payment_date' => 'Date received (blank = today)',
    ];

    /** Columns that map 1:1 onto a profile attribute of the same name. */
    private const PROFILE_TEXT = [
        'children_count', 'height_cm', 'weight_kg', 'sub_caste', 'gothram', 'birth_place', 'education_detail',
        'company_name', 'address', 'father_occupation', 'mother_occupation', 'brothers', 'sisters', 'about_me',
        'about_family', 'partner_age_min', 'partner_age_max', 'partner_expectations',
    ];

    /** Columns validated against config('matrimony.options'). */
    private const PROFILE_OPTIONS = [
        'marital_status' => 'marital_status', 'created_by' => 'created_by', 'complexion' => 'complexion',
        'body_type' => 'body_type', 'physical_status' => 'physical_status', 'mother_tongue' => 'mother_tongue',
        'diet' => 'diet', 'smoking' => 'habits', 'drinking' => 'habits', 'star' => 'star', 'rasi' => 'rasi',
        'dosham' => 'dosham', 'employed_in' => 'employed_in', 'annual_income' => 'annual_income',
        'family_type' => 'family_type', 'family_status' => 'family_status',
    ];

    private array $lookups = [];

    public function __construct(private SubscriptionService $subscriptions) {}

    // ------------------------------------------------------------------ download

    public function templateRows(): array
    {
        $religion = Religion::active()->first();
        $state = State::active()->first();

        $example = [
            'name' => 'Priya Raman', 'email' => 'priya.example@gmail.com', 'mobile' => '9876543210', 'password' => '',
            'gender' => 'Female', 'date_of_birth' => '1997-05-21', 'marital_status' => 'Never Married', 'children_count' => '',
            'created_by' => 'Parent', 'height_cm' => '160', 'weight_kg' => '55', 'complexion' => 'Fair', 'body_type' => 'Slim',
            'physical_status' => 'Normal', 'mother_tongue' => 'Tamil', 'diet' => 'Vegetarian', 'smoking' => 'No', 'drinking' => 'No',
            'religion' => $religion?->name, 'caste' => $religion?->castes()->value('name'), 'sub_caste' => '', 'gothram' => '',
            'star' => 'Rohini', 'rasi' => 'Vrishabha (Taurus)', 'dosham' => 'No', 'birth_time' => '06:30', 'birth_place' => 'Madurai',
            'education' => EducationLevel::active()->value('name'), 'education_detail' => 'B.E. Computer Science',
            'occupation' => Occupation::active()->value('name'), 'employed_in' => 'Private', 'company_name' => '', 'annual_income' => '5-10',
            'state' => $state?->name, 'city' => $state ? City::where('state_id', $state->id)->value('name') : '', 'address' => '',
            'family_type' => 'Nuclear', 'family_status' => 'Middle Class', 'father_occupation' => 'Retired', 'mother_occupation' => 'Homemaker',
            'brothers' => '1', 'sisters' => '0', 'about_me' => 'Cheerful, family oriented software engineer who loves music and travel.',
            'about_family' => '', 'partner_age_min' => '26', 'partner_age_max' => '31', 'partner_expectations' => '',
            'profile_status' => 'Approved', 'account_status' => 'Active',
            'plan' => MembershipPlan::where('price', '>', 0)->orderBy('sort_order')->value('name'), 'payment_amount' => '',
            'payment_method' => 'Cash', 'payment_reference' => 'RCPT-1001', 'payment_date' => now()->toDateString(),
        ];

        return [array_keys(self::COLUMNS), array_values($example)];
    }

    /** Header + one row per member, in the import format (plus a few read-only columns). */
    public function exportHeader(): array
    {
        $cols = collect(array_keys(self::COLUMNS))->reject(fn ($c) => $c === 'password' || str_starts_with($c, 'payment_'))->values()->all();

        return array_merge(['profile_code'], $cols, ['plan_expires_on', 'joined_on']);
    }

    public function exportRow(User $user): array
    {
        $p = $user->profile;
        $o = config('matrimony.options');
        $label = fn (string $key, $value) => $value === null ? '' : (array_is_list($o[$key]) ? $value : ($o[$key][$value] ?? $value));

        $row = [
            'profile_code' => $p?->profile_code,
            'name' => $user->name, 'email' => $user->email, 'mobile' => $user->mobile,
            'gender' => $p ? ucfirst($p->gender) : '', 'date_of_birth' => $p?->date_of_birth?->toDateString(),
            'religion' => $p?->religion?->name, 'caste' => $p?->caste?->name, 'education' => $p?->educationLevel?->name,
            'occupation' => $p?->occupation?->name, 'state' => $p?->state?->name, 'city' => $p?->city?->name,
            'birth_time' => $p?->birth_time ? substr($p->birth_time, 0, 5) : '',
            'profile_status' => $p ? ucfirst($p->approval_status) : '', 'account_status' => ucfirst($user->status),
            'plan' => $user->activeSubscription?->plan?->name,
            'plan_expires_on' => $user->activeSubscription?->expires_at?->toDateString(),
            'joined_on' => $user->created_at?->toDateString(),
        ];
        foreach (self::PROFILE_TEXT as $col) {
            $row[$col] = $p?->{$col};
        }
        foreach (self::PROFILE_OPTIONS as $col => $key) {
            $row[$col] = $label($key, $p?->{$col});
        }

        return array_map(fn ($c) => $row[$c] ?? '', $this->exportHeader());
    }

    // ------------------------------------------------------------------ upload

    /**
     * @return array{created:int, updated:int, payments:int, errors:array<int, array{row:int, email:?string, messages:array}>}
     */
    public function import(UploadedFile $file, User $admin, array $options = []): array
    {
        $update = (bool) ($options['update_existing'] ?? false);
        $defaultStatus = $options['default_profile_status'] ?? Profile::STATUS_APPROVED;
        $markVerified = (bool) ($options['mark_verified'] ?? true);

        $result = ['created' => 0, 'updated' => 0, 'payments' => 0, 'errors' => []];
        $rows = $this->readCsv($file);

        if ($rows === null) {
            $result['errors'][] = ['row' => 1, 'email' => null, 'messages' => ['The file has no "name" and "email" header row. Please start from the downloaded template.']];

            return $result;
        }
        if (count($rows) > self::MAX_ROWS) {
            $result['errors'][] = ['row' => 1, 'email' => null, 'messages' => ['Too many rows. Upload at most '.self::MAX_ROWS.' members per file.']];

            return $result;
        }

        foreach ($rows as $line => $row) {
            if (collect($row)->filter(fn ($v) => $v !== '')->isEmpty()) {
                continue; // blank line
            }

            try {
                $outcome = DB::transaction(fn () => $this->importRow($row, $admin, $update, $defaultStatus, $markVerified));
            } catch (ImportRowFailed $e) {
                $result['errors'][] = ['row' => $line, 'email' => $row['email'] ?? null, 'messages' => $e->messages];

                continue;
            } catch (Throwable $e) {
                report($e);
                $result['errors'][] = ['row' => $line, 'email' => $row['email'] ?? null, 'messages' => ['Could not save this row: '.$e->getMessage()]];

                continue;
            }

            $result[$outcome['action']]++;
            $result['payments'] += $outcome['payment'] ? 1 : 0;
        }

        return $result;
    }

    /** @return array<int, array<string,string>>|null  keyed by spreadsheet line number */
    private function readCsv(UploadedFile $file): ?array
    {
        $handle = fopen($file->getRealPath(), 'r');
        $first = fgets($handle);
        $first = preg_replace('/^\xEF\xBB\xBF/', '', (string) $first); // Excel UTF-8 BOM
        $delimiter = substr_count($first, ';') > substr_count($first, ',') ? ';' : ',';
        $header = array_map(fn ($h) => Str::snake(trim(strtolower((string) $h))), str_getcsv($first, $delimiter));

        if (! in_array('email', $header, true) || ! in_array('name', $header, true)) {
            fclose($handle);

            return null;
        }

        $rows = [];
        $line = 1;
        while (($cells = fgetcsv($handle, 0, $delimiter)) !== false) {
            $line++;
            $cells = array_map(fn ($v) => trim((string) $v), $cells);
            $cells = array_pad(array_slice($cells, 0, count($header)), count($header), '');
            $rows[$line] = array_combine($header, $cells);
        }
        fclose($handle);

        return $rows;
    }

    private function importRow(array $row, User $admin, bool $update, string $defaultStatus, bool $markVerified): array
    {
        $errors = [];
        $email = strtolower($row['email'] ?? '');
        $mobile = filled($row['mobile'] ?? null) ? OtpService::normaliseMobile($row['mobile']) : null;

        $user = $email ? User::withTrashed()->where('email', $email)->first() : null;
        if ($user && ($user->isAdmin() || $user->trashed())) {
            throw new ImportRowFailed(['This email belongs to an admin or a deleted account.']);
        }
        if ($user && ! $update) {
            throw new ImportRowFailed(['A member with this email already exists. Tick "Update existing members" to change them.']);
        }

        $isNew = ! $user;

        // ---- account
        $account = [
            'name' => $row['name'] ?? '',
            'email' => $email,
            'mobile' => $mobile,
            'password' => $row['password'] ?? '',
            'account_status' => strtolower($row['account_status'] ?? ''),
        ];
        $validator = Validator::make($account, [
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150'],
            'mobile' => ['nullable', 'digits:10', 'regex:/^[6-9]\d{9}$/', Rule::unique('users', 'mobile')->ignore($user?->id)],
            'password' => ['nullable', 'string', 'min:8'],
            'account_status' => ['nullable', Rule::in(['active', 'blocked'])],
        ], ['mobile.regex' => 'The mobile must be a valid 10-digit Indian number.']);
        $errors = array_merge($errors, $validator->errors()->all());

        // ---- profile
        [$profileData, $profileErrors] = $this->profileAttributes($row, $user?->profile);
        $errors = array_merge($errors, $profileErrors);

        $profileStatus = strtolower($row['profile_status'] ?? '') ?: ($isNew ? $defaultStatus : null);
        if ($profileStatus && ! in_array($profileStatus, [Profile::STATUS_PENDING, Profile::STATUS_APPROVED], true)) {
            $errors[] = 'Profile status must be Approved or Pending.';
        }

        // ---- payment
        [$paymentPlan, $paymentData, $paymentErrors] = $this->paymentAttributes($row);
        $errors = array_merge($errors, $paymentErrors);

        if ($errors) {
            throw new ImportRowFailed($errors);
        }

        // ---- save
        if ($isNew) {
            $user = User::create([
                'name' => $account['name'],
                'email' => $email,
                'mobile' => $mobile,
                'password' => $account['password'] ?: Str::password(12),
                'role' => User::ROLE_CUSTOMER,
                'status' => $account['account_status'] ?: 'active',
                'email_verified_at' => $markVerified ? now() : null,
                'mobile_verified_at' => $markVerified && $mobile ? now() : null,
            ]);
        } else {
            $user->fill(array_filter([
                'name' => $account['name'],
                'mobile' => $mobile,
                'status' => $account['account_status'] ?: null,
            ]));
            if ($account['password']) {
                $user->password = $account['password'];
            }
            $user->save();
        }

        $profile = $user->profile ?? new Profile(['created_by' => 'self']);
        $profile->fill($profileData);
        if ($profileStatus) {
            $profile->forceFill([
                'approval_status' => $profileStatus,
                'approved_at' => $profileStatus === Profile::STATUS_APPROVED ? ($profile->approved_at ?? now()) : null,
                'rejection_reason' => null,
            ]);
        }
        $profile->user()->associate($user);
        $profile->save();

        $paymentRecorded = false;
        if ($paymentPlan && ! $this->paymentAlreadyRecorded($user, $paymentPlan, $paymentData)) {
            $this->subscriptions->recordManualPayment($user, $paymentPlan, $paymentData, $admin, notify: false);
            $paymentRecorded = true;
        }

        return ['action' => $isNew ? 'created' : 'updated', 'payment' => $paymentRecorded];
    }

    /** @return array{0: array, 1: array<string>} */
    private function profileAttributes(array $row, ?Profile $existing): array
    {
        $isNew = ! $existing;
        $errors = [];
        $data = [];
        $value = fn (string $col) => $row[$col] ?? '';

        if (filled($value('gender'))) {
            $gender = strtolower($value('gender'));
            $gender = ['m' => 'male', 'groom' => 'male', 'f' => 'female', 'bride' => 'female'][$gender] ?? $gender;
            if (in_array($gender, ['male', 'female'], true)) {
                $data['gender'] = $gender;
            } else {
                $errors[] = 'Gender must be Male or Female.';
            }
        } elseif ($isNew) {
            $errors[] = 'Gender is required.';
        }

        if (filled($value('date_of_birth'))) {
            $dob = $this->parseDate($value('date_of_birth'));
            if (! $dob) {
                $errors[] = 'Date of birth "'.$value('date_of_birth').'" is not a valid date (use YYYY-MM-DD).';
            } elseif ($dob->age < 18 || $dob->age > 75) {
                $errors[] = 'Age must be between 18 and 75.';
            } else {
                $data['date_of_birth'] = $dob->toDateString();
            }
        } elseif ($isNew) {
            $errors[] = 'Date of birth is required.';
        }

        foreach (self::PROFILE_OPTIONS as $col => $key) {
            if (filled($value($col))) {
                $matched = $this->matchOption($key, $value($col));
                if ($matched === null) {
                    $errors[] = Str::headline($col).' "'.$value($col).'" is not a valid option.';
                } else {
                    $data[$col] = $matched;
                }
            }
        }

        foreach (self::PROFILE_TEXT as $col) {
            if (filled($value($col))) {
                $data[$col] = $value($col);
            }
        }

        if (filled($value('birth_time'))) {
            $time = $this->parseTime($value('birth_time'));
            if ($time) {
                $data['birth_time'] = $time;
            } else {
                $errors[] = 'Birth time must look like 06:30 or 06:30 AM.';
            }
        }

        // Master data by name.
        $religionId = $this->lookup('religions', $value('religion'), $errors, 'Religion');
        $stateId = $this->lookup('states', $value('state'), $errors, 'State');
        if ($religionId) {
            $data['religion_id'] = $religionId;
        }
        if ($stateId) {
            $data['state_id'] = $stateId;
        }
        // Caste / city must belong to the row's religion / state (or the member's current one when updating).
        $religionId ??= $existing?->religion_id;
        $stateId ??= $existing?->state_id;
        if (filled($value('caste'))) {
            if ($religionId) {
                $data['caste_id'] = $this->lookup('castes:'.$religionId, $value('caste'), $errors, 'Caste', ' for the chosen religion');
            } else {
                $errors[] = 'Caste needs a religion in the same row.';
            }
        } elseif (isset($data['religion_id']) && $existing && $existing->religion_id !== $data['religion_id']) {
            $data['caste_id'] = null; // old caste belongs to the old religion
        }
        if (filled($value('city'))) {
            if ($stateId) {
                $data['city_id'] = $this->lookup('cities:'.$stateId, $value('city'), $errors, 'City', ' in the chosen state');
            } else {
                $errors[] = 'City needs a state in the same row.';
            }
        } elseif (isset($data['state_id']) && $existing && $existing->state_id !== $data['state_id']) {
            $errors[] = 'Please give the city when changing the state.';
        }
        if ($id = $this->lookup('education_levels', $value('education'), $errors, 'Education')) {
            $data['education_level_id'] = $id;
        }
        if ($id = $this->lookup('occupations', $value('occupation'), $errors, 'Occupation')) {
            $data['occupation_id'] = $id;
        }

        $validator = Validator::make($data, [
            'children_count' => ['nullable', 'integer', 'between:0,10'],
            'height_cm' => ['nullable', 'integer', 'between:120,230'],
            'weight_kg' => ['nullable', 'integer', 'between:30,200'],
            'brothers' => ['nullable', 'integer', 'between:0,20'],
            'sisters' => ['nullable', 'integer', 'between:0,20'],
            'partner_age_min' => ['nullable', 'integer', 'between:18,75'],
            'partner_age_max' => ['nullable', 'integer', 'between:18,75'],
            'sub_caste' => ['nullable', 'string', 'max:100'],
            'gothram' => ['nullable', 'string', 'max:100'],
            'birth_place' => ['nullable', 'string', 'max:120'],
            'education_detail' => ['nullable', 'string', 'max:150'],
            'company_name' => ['nullable', 'string', 'max:150'],
            'address' => ['nullable', 'string', 'max:255'],
            'father_occupation' => ['nullable', 'string', 'max:100'],
            'mother_occupation' => ['nullable', 'string', 'max:100'],
            'about_me' => ['nullable', 'string', 'max:2000'],
            'about_family' => ['nullable', 'string', 'max:1000'],
            'partner_expectations' => ['nullable', 'string', 'max:2000'],
        ]);
        $errors = array_merge($errors, $validator->errors()->all());

        if (($data['marital_status'] ?? null) === 'never_married') {
            $data['children_count'] = null;
        }

        return [$data, $errors];
    }

    /** @return array{0: ?MembershipPlan, 1: array, 2: array<string>} */
    private function paymentAttributes(array $row): array
    {
        $planName = $row['plan'] ?? '';
        $hasPaymentCells = collect(['payment_amount', 'payment_method', 'payment_reference', 'payment_date'])
            ->contains(fn ($c) => filled($row[$c] ?? ''));

        if (blank($planName)) {
            return [null, [], $hasPaymentCells ? ['Payment details need a plan name in the "plan" column.'] : []];
        }

        $errors = [];
        $plan = MembershipPlan::whereRaw('LOWER(name) = ?', [strtolower($planName)])
            ->orWhere('slug', Str::slug($planName))->first();
        if (! $plan) {
            return [null, [], ['Plan "'.$planName.'" was not found.']];
        }
        if ($plan->isFree()) {
            return [null, [], []]; // nothing to pay for the free plan
        }

        $method = strtolower(str_replace([' ', '-'], '_', $row['payment_method'] ?? '')) ?: 'cash';
        $method = ['bank' => 'bank_transfer', 'neft' => 'bank_transfer', 'imps' => 'bank_transfer', 'gpay' => 'upi', 'phonepe' => 'upi', 'card_(pos)' => 'card'][$method] ?? $method;
        if (! array_key_exists($method, Payment::MANUAL_METHODS)) {
            $errors[] = 'Payment method "'.$row['payment_method'].'" is not valid.';
        }

        $amount = filled($row['payment_amount'] ?? '') ? str_replace([',', '₹', ' '], '', $row['payment_amount']) : $plan->price;
        if (! is_numeric($amount) || $amount < 0) {
            $errors[] = 'Payment amount must be a number.';
        }

        $paidAt = filled($row['payment_date'] ?? '') ? $this->parseDate($row['payment_date']) : today();
        if (! $paidAt) {
            $errors[] = 'Payment date "'.$row['payment_date'].'" is not a valid date.';
        } elseif ($paidAt->isFuture()) {
            $errors[] = 'Payment date cannot be in the future.';
        }

        return [$plan, [
            'amount' => $amount,
            'method' => $method,
            'reference' => ($row['payment_reference'] ?? '') ?: null,
            'paid_at' => $paidAt,
            'status' => 'paid',
            'notes' => 'Bulk import',
        ], $errors];
    }

    /** Re-uploading the same sheet must not charge the member twice. */
    private function paymentAlreadyRecorded(User $user, MembershipPlan $plan, array $data): bool
    {
        return Payment::where('user_id', $user->id)
            ->where('membership_plan_id', $plan->id)
            ->where('source', Payment::SOURCE_MANUAL)
            ->where('status', 'paid')
            ->where('amount', $data['amount'])
            ->whereDate('paid_at', $data['paid_at'])
            ->when($data['reference'], fn ($q, $ref) => $q->where('reference', $ref))
            ->exists();
    }

    // ------------------------------------------------------------------ helpers

    private function matchOption(string $key, string $value): ?string
    {
        $options = config('matrimony.options.'.$key);
        $needle = Str::of($value)->lower()->replace(['_', '-'], ' ')->squish()->value();
        $normal = fn ($v) => Str::of((string) $v)->lower()->replace(['_', '-'], ' ')->squish()->value();

        foreach ($options as $optKey => $label) {
            $stored = array_is_list($options) ? $label : $optKey;
            if ($normal($label) === $needle || $normal($optKey) === $needle || (string) $optKey === $value) {
                return $stored;
            }
        }

        return null;
    }

    /** Case-insensitive name => id lookup for master data, cached per import. */
    private function lookup(string $table, string $name, array &$errors, string $label, string $suffix = ''): ?int
    {
        if (blank($name)) {
            return null;
        }

        $this->lookups[$table] ??= $this->loadLookup($table);
        $id = $this->lookups[$table]->get(Str::lower(Str::squish($name)));
        if (! $id) {
            $errors[] = "{$label} \"{$name}\" was not found{$suffix}. Add it under Master Data or fix the spelling.";
        }

        return $id;
    }

    private function loadLookup(string $table): Collection
    {
        [$table, $parentId] = array_pad(explode(':', $table), 2, null);

        $query = match ($table) {
            'religions' => Religion::query(),
            'states' => State::query(),
            'education_levels' => EducationLevel::query(),
            'occupations' => Occupation::query(),
            'castes' => Caste::where('religion_id', $parentId),
            'cities' => City::where('state_id', $parentId),
        };

        return $query->get(['id', 'name'])->mapWithKeys(fn ($m) => [Str::lower(Str::squish($m->name)) => $m->id]);
    }

    /** Dates are read day-first (Indian style) unless they start with the year. */
    private function parseDate(string $value): ?Carbon
    {
        $date = $this->strictParse(trim($value), ['Y-m-d', 'Y/m/d', 'j-n-Y', 'j/n/Y', 'j.n.Y', 'j-M-Y', 'j M Y']);

        return $date ? Carbon::instance($date)->startOfDay() : null;
    }

    private function parseTime(string $value): ?string
    {
        $time = $this->strictParse(strtoupper(trim($value)), ['G:i', 'G:i:s', 'g:i A', 'g:iA']);

        return $time?->format('H:i');
    }

    /** First format that parses without overflow (e.g. 31-02-1995 is rejected, not rolled over). */
    private function strictParse(string $value, array $formats): ?\DateTime
    {
        foreach ($formats as $format) {
            $parsed = \DateTime::createFromFormat('!'.$format, $value);
            $problems = \DateTime::getLastErrors();
            if ($parsed && (! $problems || ($problems['warning_count'] === 0 && $problems['error_count'] === 0))) {
                return $parsed;
            }
        }

        return null;
    }
}
