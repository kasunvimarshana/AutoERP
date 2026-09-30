<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicle_service_job_lines', function (Blueprint $table): void {
            $table->foreignId('batch_id')->nullable();
            $table->foreignId('batch_price_revision_id')->nullable();
            $table->index('batch_id', 'vehicle_service_job_lines_batch_ix');
            $table->index('batch_price_revision_id', 'vehicle_service_job_lines_batch_price_ix');
            $table->foreign(['batch_id', 'tenant_id'], 'vehicle_service_job_lines_batch_id_tenant_fk')
                ->references(['id', 'tenant_id'])->on('inventory_batches')->restrictOnDelete();
            $table->foreign(['batch_price_revision_id', 'tenant_id'], 'vehicle_service_job_lines_batch_price_id_tenant_fk')
                ->references(['id', 'tenant_id'])->on('inventory_batch_price_revisions')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('vehicle_service_job_lines', function (Blueprint $table): void {
            $table->dropForeign(['batch_id', 'tenant_id'])->index('vehicle_service_job_lines_batch_id_tenant_fk');
            $table->dropForeign(['batch_price_revision_id', 'tenant_id'])->index('vehicle_service_job_lines_batch_price_id_tenant_fk');
            $table->dropIndex('vehicle_service_job_lines_batch_ix');
            $table->dropIndex('vehicle_service_job_lines_batch_price_ix');
            $table->dropColumn('batch_id');
            $table->dropColumn('batch_price_revision_id');
        });
    }
};
