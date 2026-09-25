<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('membership_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name', 60);
            $table->string('slug', 80)->unique();
            $table->decimal('price', 10, 2)->default(0);
            $table->unsignedSmallInteger('duration_days')->default(0);
            $table->unsignedInteger('contact_views_limit')->default(0)->comment('0 = none');
            $table->unsignedInteger('daily_interest_limit')->nullable()->comment('null = unlimited');
            $table->boolean('can_chat')->default(false);
            $table->boolean('can_view_horoscope')->default(false);
            $table->boolean('profile_highlight')->default(false);
            $table->json('features')->nullable();
            $table->string('badge_color', 20)->default('secondary');
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('membership_plan_id')->nullable()->constrained()->nullOnDelete();
            $table->string('invoice_no', 40)->nullable()->unique();
            $table->string('razorpay_order_id', 60)->nullable()->unique();
            $table->string('razorpay_payment_id', 60)->nullable()->index();
            $table->string('razorpay_signature')->nullable();
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('INR');
            $table->enum('status', ['created', 'paid', 'failed'])->default('created')->index();
            $table->string('method', 30)->nullable();
            $table->string('failure_reason')->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });

        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('membership_plan_id')->constrained()->restrictOnDelete();
            $table->foreignId('payment_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->decimal('amount', 10, 2)->default(0);
            $table->dateTime('starts_at');
            $table->dateTime('expires_at')->index();
            $table->enum('status', ['active', 'expired', 'cancelled'])->default('active')->index();
            $table->unsignedInteger('contact_views_used')->default(0);
            $table->boolean('expiry_reminder_sent')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('membership_plans');
    }
};
