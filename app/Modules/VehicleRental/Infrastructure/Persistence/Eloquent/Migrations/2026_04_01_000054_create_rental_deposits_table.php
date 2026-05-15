<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rental_deposits', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete();
            $table->json('metadata')->nullable();

            $table->foreignId('agreement_id')->constrained('rental_agreements')->cascadeOnDelete();
            $table->string('deposit_number');
            $table->decimal('amount', 20, 4);
            $table->string('type')->default('security');
            $table->string('status')->default('collected');
            $table->decimal('refunded_amount', 20, 4)->default(0);
            $table->decimal('retained_amount', 20, 4)->default(0);
            $table->text('retention_reason')->nullable();
            $table->date('refund_date')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'organization_unit_id', 'deposit_number'], 'rental_deposits_deposit_number_uk');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rental_deposits');
    }
};
