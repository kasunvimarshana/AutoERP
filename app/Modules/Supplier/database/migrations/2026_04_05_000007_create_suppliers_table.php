<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suppliers', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('name', 255);
            $table->string('code', 50)->nullable();
            $table->string('email', 255)->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('tax_number', 100)->nullable();
            $table->string('currency', 3)->default('USD');
            $table->unsignedSmallInteger('payment_terms')->nullable();
            $table->decimal('credit_limit', 20, 6)->nullable();
            $table->json('address')->nullable();
            $table->json('bank_details')->nullable();
            $table->enum('status', ['active', 'inactive', 'blocked'])->default('active');
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'code']);
            $table->index(['tenant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};
