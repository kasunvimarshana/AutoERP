<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicle_service_jobs', function (Blueprint $table): void {
            $table->foreignId('bill_to_customer_id')->nullable();
            $table->index('bill_to_customer_id', 'vehicle_service_jobs_bill_to_customer_ix');
            $table->foreign(['bill_to_customer_id', 'tenant_id'], 'vehicle_service_jobs_bill_to_customer_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('customers')
                ->restrictOnDelete();
        });

        DB::table('vehicle_service_jobs')
            ->whereNull('bill_to_customer_id')
            ->update([
                'bill_to_customer_id' => DB::raw('customer_id'),
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        Schema::table('vehicle_service_jobs', function (Blueprint $table): void {
            $table->dropForeign('vehicle_service_jobs_bill_to_customer_id_tenant_fk');
            $table->dropIndex('vehicle_service_jobs_bill_to_customer_ix');
            $table->dropColumn('bill_to_customer_id');
        });
    }
};
