<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('journal_entries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();

            $table->string('entry_number', 50)->unique();
            $table->timestamp('entry_date');
            $table->string('reference');
            $table->text('description')->nullable();
            $table->decimal('total_debit', 19, 4)->default(0);
            $table->decimal('total_credit', 19, 4)->default(0);
            $table->string('currency_code', 3)->default('USD');
            $table->boolean('is_posted')->default(false);
            $table->timestamp('posted_at')->nullable();
            $table->string('posted_by')->nullable();

            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'entry_date']);
            $table->index(['tenant_id', 'is_posted']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_entries');
    }
};
