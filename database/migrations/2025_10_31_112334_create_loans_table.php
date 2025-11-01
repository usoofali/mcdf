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
        Schema::create('loans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 15, 2);
            $table->decimal('approved_amount', 15, 2)->nullable();
            $table->enum('status', ['pending', 'approved', 'disbursed', 'repaid', 'defaulted'])->default('pending');
            $table->text('purpose')->nullable();
            $table->text('remarks')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('disbursed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('disbursed_at')->nullable();
            $table->date('disbursed_date')->nullable();
            $table->decimal('interest_rate', 5, 2)->default(0)->comment('Interest rate percentage');
            $table->integer('repayment_period_months')->nullable()->comment('Repayment period in months');
            $table->date('due_date')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('member_id');
            $table->index(['status', 'created_at']);
            $table->index('approved_by');
            $table->index('disbursed_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loans');
    }
};
