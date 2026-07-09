<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('vehicle_service_jobs', 'bill_to_customer_id')) {
            return;
        }

        Schema::table('vehicle_service_jobs', function (Blueprint $table): void {
            $table->foreignId('bill_to_customer_id')->nullable()->after('customer_id');
            $table->index('bill_to_customer_id', 'vehicle_service_jobs_bill_to_customer_ix');
            $table->foreign(['bill_to_customer_id', 'tenant_id'], 'vehicle_service_jobs_bill_to_customer_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('customers')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        // This repair migration aligns drifted databases with the baseline schema.
    }
};
