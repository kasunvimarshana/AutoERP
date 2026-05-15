<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rental_running_charts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete();
            $table->json('metadata')->nullable();

            $table->foreignId('agreement_id')->constrained('rental_agreements')->cascadeOnDelete();
            $table->date('log_date');
            $table->decimal('start_km', 20, 4)->nullable();
            $table->decimal('end_km', 20, 4)->nullable();
            $table->decimal('km_travelled', 20, 4)->nullable();
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->decimal('hours_used', 8, 2)->nullable();
            $table->decimal('driver_hours_normal', 8, 2)->nullable();
            $table->decimal('driver_hours_ot', 8, 2)->nullable();
            $table->integer('night_outs')->default(0);
            $table->decimal('other_charges', 20, 4)->default(0);
            $table->text('particulars')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();

            $table->timestamps();

            $table->unique(['tenant_id', 'organization_unit_id', 'agreement_id', 'log_date'], 'rental_running_charts_agreement_log_date_uk');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rental_running_charts');
    }
};
