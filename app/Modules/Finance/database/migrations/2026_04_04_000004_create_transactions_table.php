<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Finance\Domain\ValueObjects\TransactionType;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', static function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')
                  ->constrained('tenants')
                  ->cascadeOnDelete();
            $table->foreignId('journal_entry_id')
                  ->nullable()
                  ->constrained('journal_entries')
                  ->nullOnDelete();
            $table->string('reference_number', 100);
            $table->enum('type', TransactionType::ALL);
            $table->enum('status', ['pending', 'completed', 'failed', 'voided'])->default('pending');
            $table->date('transaction_date');
            $table->decimal('amount', 20, 6);
            $table->string('currency', 3)->default('USD');
            $table->decimal('exchange_rate', 15, 8)->default(1.00000000);
            $table->foreignId('from_account_id')
                  ->nullable()
                  ->constrained('accounts')
                  ->nullOnDelete();
            $table->foreignId('to_account_id')
                  ->nullable()
                  ->constrained('accounts')
                  ->nullOnDelete();
            $table->text('description')->nullable();
            $table->string('category', 100)->nullable();
            $table->json('tags')->nullable();
            $table->string('contact_type', 100)->nullable();
            $table->unsignedBigInteger('contact_id')->nullable();
            $table->json('attachments')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'reference_number']);
            $table->index(['tenant_id', 'type']);
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'transaction_date']);
            $table->index(['contact_type', 'contact_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
