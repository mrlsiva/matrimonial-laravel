<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->string('source', 20)->default('razorpay')->after('membership_plan_id')->index();
            $table->string('reference', 100)->nullable()->after('method')->comment('Receipt / UTR / cheque no. for manual payments');
            $table->text('notes')->nullable()->after('failure_reason');
            $table->foreignId('recorded_by')->nullable()->after('notes')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('recorded_by');
            $table->dropColumn(['source', 'reference', 'notes']);
        });
    }
};
