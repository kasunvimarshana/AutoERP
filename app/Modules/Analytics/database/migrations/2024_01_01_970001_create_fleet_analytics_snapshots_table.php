<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fleet_analytics_snapshots', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('org_unit_id')->nullable()->constrained('org_units')->nullOnDelete();
            $table->unsignedBigInteger('row_version')->default(1);
            $table->date('summary_date');
            $table->unsignedInteger('total_rentals')->default(0);
            $table->unsignedInteger('completed_rentals')->default(0);
            $table->unsignedInteger('active_rentals')->default(0);
            $table->decimal('total_revenue', 20, 6)->default(0);
            $table->decimal('total_service_cost', 20, 6)->default(0);
            $table->decimal('net_revenue', 20, 6)->default(0);
            $table->json('metadata')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'org_unit_id', 'summary_date'], 'fleet_analytics_snapshots_tenant_ou_date_uk');
            $table->index(['tenant_id', 'summary_date'], 'fleet_analytics_snapshots_tenant_date_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fleet_analytics_snapshots');
    }
};
