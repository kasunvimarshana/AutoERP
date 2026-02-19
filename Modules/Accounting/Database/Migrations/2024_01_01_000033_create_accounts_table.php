<?php

declare(strict_types=1);

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
        Schema::create('accounts', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('tenant_id');
            $table->ulid('organization_id');
            $table->ulid('parent_id')->nullable();
            $table->string('code', 50)->unique();
            $table->string('name', 200);
            $table->text('description')->nullable();
            $table->string('type', 50);
            $table->string('status', 20)->default('active');
            $table->string('normal_balance', 10);
            $table->boolean('is_system')->default(false);
            $table->boolean('is_bank_account')->default(false);
            $table->boolean('is_reconcilable')->default(false);
            $table->boolean('allow_manual_entries')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Foreign keys
            $table->foreign('parent_id')->references('id')->on('accounts')->onDelete('set null');

            // Indexes
            $table->index(['tenant_id', 'organization_id']);
            $table->index(['tenant_id', 'parent_id']);
            $table->index(['tenant_id', 'type']);
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'code']);
            $table->index(['tenant_id', 'is_system']);
            $table->index(['tenant_id', 'is_bank_account']);

            // Composite indexes
            $table->index(['tenant_id', 'organization_id', 'type']);
            $table->index(['tenant_id', 'organization_id', 'status']);
            $table->index(['tenant_id', 'type', 'status']);
            $table->unique(['tenant_id', 'organization_id', 'code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};
