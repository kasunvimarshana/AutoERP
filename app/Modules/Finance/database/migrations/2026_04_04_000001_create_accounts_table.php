<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Finance\Domain\ValueObjects\AccountNature;
use Modules\Finance\Domain\ValueObjects\AccountType;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounts', static function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')
                  ->constrained('tenants')
                  ->cascadeOnDelete();
            $table->foreignId('parent_id')
                  ->nullable()
                  ->constrained('accounts')
                  ->nullOnDelete();
            $table->string('code', 20);
            $table->string('name', 255);
            $table->enum('type', AccountType::ALL);
            $table->enum('nature', AccountNature::ALL);
            $table->string('classification', 100)->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_bank_account')->default(false);
            $table->boolean('is_system')->default(false);
            $table->string('bank_name', 255)->nullable();
            $table->string('bank_account_number', 100)->nullable();
            $table->string('bank_routing_number', 50)->nullable();
            $table->string('currency', 3)->default('USD');
            $table->decimal('opening_balance', 20, 6)->default(0.000000);
            $table->decimal('current_balance', 20, 6)->default(0.000000);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'code']);
            $table->index(['tenant_id', 'type']);
            $table->index(['tenant_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};
