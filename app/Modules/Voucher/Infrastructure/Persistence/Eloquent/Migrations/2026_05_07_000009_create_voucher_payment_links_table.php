<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('voucher_payment_links', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1);
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units')->nullOnDelete();
            $table->json('metadata')->nullable();

            $table->foreignId('voucher_id')->constrained('vouchers')->cascadeOnDelete();
            $table->unsignedBigInteger('payment_id');
            $table->unsignedBigInteger('payment_allocation_id')->nullable();
            $table->string('payment_role')->default('primary');
            $table->string('direction')->default('outbound');
            $table->decimal('amount', 20, 4)->default(0);
            $table->string('status')->default('linked');
            $table->unsignedBigInteger('linked_by')->nullable();
            $table->timestamp('linked_at')->nullable();

            $table->timestamps();

            $table->unique(['voucher_id', 'payment_id', 'payment_role'], 'voucher_payment_links_uk');
            $table->index(['tenant_id', 'payment_id'], 'voucher_payment_links_payment_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('voucher_payment_links');
    }
};
