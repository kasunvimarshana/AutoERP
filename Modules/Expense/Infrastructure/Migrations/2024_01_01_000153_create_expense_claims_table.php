<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('expense_claims', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->index();
            $table->uuid('employee_id')->index();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('currency', 3)->default('USD');
            $table->decimal('total_amount', 18, 8)->default(0);
            $table->string('status')->default('draft');
            $table->uuid('approver_id')->nullable()->index();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('reimbursed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expense_claims');
    }
};
