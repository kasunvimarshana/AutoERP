<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rental_status_histories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units')->nullOnDelete();
            $table->foreignId('reservation_id')->nullable()->constrained('rental_reservations')->cascadeOnDelete();
            $table->foreignId('agreement_id')->nullable()->constrained('rental_agreements')->cascadeOnDelete();
            $table->string('entity_type', 20);
            $table->string('old_status', 30)->nullable();
            $table->string('new_status', 30);
            $table->text('reason')->nullable();
            $table->unsignedBigInteger('changed_by')->nullable();
            $table->timestamp('changed_at');
            $table->timestamps();

            $table->index(['tenant_id', 'organization_unit_id'], 'rental_status_histories_tenant_org_idx');
            $table->index(['reservation_id', 'changed_at'], 'rental_status_histories_reservation_idx');
            $table->index(['agreement_id', 'changed_at'], 'rental_status_histories_agreement_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rental_status_histories');
    }
};
