<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('contributions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contribution_plan_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 15, 2);
            $table->enum('payment_method', ['cash', 'transfer']);
            $table->string('payment_ref', 100)->nullable()->unique();
            $table->date('payment_date');
            $table->string('receipt_path')->nullable()->comment('Path to uploaded receipt');
            $table->enum('status', ['submitted', 'pending_review', 'approved', 'rejected', 'paid'])->default('submitted');
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete()->comment('Staff member who recorded this contribution');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->decimal('fine_amount', 15, 2)->default(0);
            $table->text('receipt_notes')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('member_id');
            $table->index(['status', 'created_at']);
            $table->index('recorded_by');
            $table->index('payment_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contributions');
    }
};
