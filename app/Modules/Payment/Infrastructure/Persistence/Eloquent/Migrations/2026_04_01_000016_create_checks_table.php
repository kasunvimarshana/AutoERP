<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('checks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete();
            $table->json('metadata')->nullable();

            $table->string('check_number');
            $table->string('type')->comment('inbound (received), outbound (issued)');
            $table->string('party_type')->nullable();
            $table->unsignedBigInteger('party_id')->nullable();
            $table->foreignId('bank_account_id')->constrained('bank_accounts');
            $table->date('check_date');
            $table->date('due_date')->nullable()->comment('when it can be deposited/cashed');
            $table->decimal('amount', 20, 4);
            $table->string('status')->default('pending')->comment('pending, deposited, cleared, bounced, cancelled');
            $table->date('clearance_date')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'check_number', 'bank_account_id'], 'checks_check_number_bank_account_uk');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('checks');
    }
};
