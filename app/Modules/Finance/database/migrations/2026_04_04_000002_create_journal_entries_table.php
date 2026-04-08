<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Finance\Domain\ValueObjects\EntryStatus;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('journal_entries', static function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')
                  ->constrained('tenants')
                  ->cascadeOnDelete();
            $table->string('reference_number', 100)->unique();
            $table->date('entry_date');
            $table->text('description')->nullable();
            $table->enum('status', EntryStatus::ALL)->default(EntryStatus::DRAFT);
            $table->timestamp('posted_at')->nullable();
            $table->foreignId('posted_by')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();
            $table->timestamp('voided_at')->nullable();
            $table->foreignId('voided_by')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();
            $table->text('void_reason')->nullable();
            $table->decimal('total_debit', 20, 6)->default(0.000000);
            $table->decimal('total_credit', 20, 6)->default(0.000000);
            $table->string('currency', 3)->default('USD');
            $table->string('source_type', 100)->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'entry_date']);
            $table->index(['source_type', 'source_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_entries');
    }
};
