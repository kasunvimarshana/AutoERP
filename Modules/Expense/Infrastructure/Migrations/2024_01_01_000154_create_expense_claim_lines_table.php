<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('expense_claim_lines', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->index();
            $table->uuid('claim_id')->index();
            $table->uuid('expense_category_id')->nullable()->index();
            $table->string('description');
            $table->date('expense_date');
            $table->decimal('amount', 18, 8);
            $table->string('receipt_path')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expense_claim_lines');
    }
};
