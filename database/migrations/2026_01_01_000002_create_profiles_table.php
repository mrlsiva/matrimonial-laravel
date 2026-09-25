<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('profile_code', 20)->nullable()->unique();
            $table->string('slug')->nullable()->unique();
            $table->string('created_by', 20)->default('self');

            // Basic / personal
            $table->enum('gender', ['male', 'female'])->index();
            $table->date('date_of_birth')->index();
            $table->string('marital_status', 30)->default('never_married')->index();
            $table->unsignedTinyInteger('children_count')->nullable();
            $table->unsignedSmallInteger('height_cm')->nullable()->index();
            $table->unsignedSmallInteger('weight_kg')->nullable();
            $table->string('complexion', 30)->nullable();
            $table->string('body_type', 30)->nullable();
            $table->string('physical_status', 30)->default('normal');
            $table->string('mother_tongue', 40)->nullable()->index();
            $table->string('diet', 30)->nullable();
            $table->string('smoking', 20)->nullable();
            $table->string('drinking', 20)->nullable();
            $table->text('about_me')->nullable();

            // Religion & horoscope
            $table->foreignId('religion_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('caste_id')->nullable()->constrained()->nullOnDelete();
            $table->string('sub_caste', 100)->nullable();
            $table->string('gothram', 100)->nullable();
            $table->string('star', 40)->nullable();
            $table->string('rasi', 40)->nullable();
            $table->string('dosham', 20)->nullable();
            $table->time('birth_time')->nullable();
            $table->string('birth_place', 120)->nullable();
            $table->string('horoscope_file')->nullable();

            // Education & career
            $table->foreignId('education_level_id')->nullable()->constrained()->nullOnDelete();
            $table->string('education_detail', 150)->nullable();
            $table->foreignId('occupation_id')->nullable()->constrained()->nullOnDelete();
            $table->string('employed_in', 30)->nullable();
            $table->string('company_name', 150)->nullable();
            $table->string('annual_income', 20)->nullable()->index();

            // Location
            $table->string('country', 60)->default('India');
            $table->foreignId('state_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('city_id')->nullable()->constrained()->nullOnDelete();
            $table->string('address')->nullable();

            // Family
            $table->string('family_type', 30)->nullable();
            $table->string('family_status', 30)->nullable();
            $table->string('father_occupation', 100)->nullable();
            $table->string('mother_occupation', 100)->nullable();
            $table->unsignedTinyInteger('brothers')->nullable();
            $table->unsignedTinyInteger('sisters')->nullable();
            $table->text('about_family')->nullable();

            // Partner expectations
            $table->unsignedTinyInteger('partner_age_min')->nullable();
            $table->unsignedTinyInteger('partner_age_max')->nullable();
            $table->unsignedSmallInteger('partner_height_min')->nullable();
            $table->unsignedSmallInteger('partner_height_max')->nullable();
            $table->json('partner_marital_status')->nullable();
            $table->foreignId('partner_religion_id')->nullable()->constrained('religions')->nullOnDelete();
            $table->json('partner_caste_ids')->nullable();
            $table->json('partner_education_ids')->nullable();
            $table->json('partner_state_ids')->nullable();
            $table->string('partner_mother_tongue', 40)->nullable();
            $table->text('partner_expectations')->nullable();

            // Moderation
            $table->enum('approval_status', ['pending', 'approved', 'rejected'])->default('pending')->index();
            $table->string('rejection_reason')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->boolean('is_verified')->default(false)->index();
            $table->unsignedInteger('views_count')->default(0);
            $table->timestamps();
        });

        Schema::create('profile_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profile_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->string('thumb_path');
            $table->boolean('is_primary')->default(false);
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending')->index();
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profile_photos');
        Schema::dropIfExists('profiles');
    }
};
