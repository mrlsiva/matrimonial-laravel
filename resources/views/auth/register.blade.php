@extends('layouts.app')

@section('title', 'Register Free')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8 col-xl-7">
        <div class="card card-soft">
            <div class="card-body p-4 p-md-5">
                <h1 class="h3 text-center mb-1">Create your free account</h1>
                <p class="text-center text-muted mb-4">Already registered? <a href="{{ route('login') }}">Sign in</a></p>

                <form method="POST" action="{{ route('register') }}" class="row g-3 needs-validation" novalidate>
                    @csrf
                    <x-select name="created_by" label="Profile created for / by" :options="config('matrimony.options.created_by')" selected="self" required />
                    <x-input name="name" label="Full name" required autocomplete="name" />

                    <div class="col-md-6">
                        <label class="form-label small fw-medium d-block">Gender<span class="text-danger">*</span></label>
                        <div class="btn-group w-100" role="group">
                            <input type="radio" class="btn-check" name="gender" id="g-male" value="male" @checked(old('gender') === 'male') required>
                            <label class="btn btn-outline-primary" for="g-male"><i class="bi bi-gender-male me-1"></i>Groom</label>
                            <input type="radio" class="btn-check" name="gender" id="g-female" value="female" @checked(old('gender') === 'female') required>
                            <label class="btn btn-outline-primary" for="g-female"><i class="bi bi-gender-female me-1"></i>Bride</label>
                        </div>
                        @error('gender')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>
                    <x-input name="date_of_birth" label="Date of birth" type="date" required :max="now()->subYears(18)->toDateString()" />

                    <x-input name="email" label="Email address" type="email" required autocomplete="email" />
                    <div class="col-md-6">
                        <label for="mobile" class="form-label small fw-medium">Mobile number<span class="text-danger">*</span></label>
                        <div class="input-group has-validation">
                            <span class="input-group-text">+91</span>
                            <input type="tel" name="mobile" id="mobile" value="{{ old('mobile') }}" class="form-control @error('mobile') is-invalid @enderror" pattern="[6-9][0-9]{9}" maxlength="10" required autocomplete="tel-national">
                            @error('mobile')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <x-input name="password" label="Password" type="password" required minlength="8" autocomplete="new-password" help="At least 8 characters with letters and numbers." />
                    <x-input name="password_confirmation" label="Confirm password" type="password" required autocomplete="new-password" />

                    <div class="col-12">
                        <div class="form-check">
                            <input class="form-check-input @error('terms') is-invalid @enderror" type="checkbox" name="terms" id="terms" value="1" required>
                            <label class="form-check-label small" for="terms">
                                I agree to the <a href="{{ route('pages.show', 'terms-and-conditions') }}" target="_blank">Terms</a> and
                                <a href="{{ route('pages.show', 'privacy-policy') }}" target="_blank">Privacy Policy</a>.
                            </label>
                        </div>
                    </div>
                    <div class="col-12">
                        <button class="btn btn-primary w-100 py-2">Register &amp; verify email</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
