<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('vehicle_finance_status_histories')) {
            return;
        }

        if (DB::table('vehicle_finance_status_histories')->exists()) {
            throw new \RuntimeException('Vehicle Rental removal stopped because operational data still exists in [vehicle_finance_status_histories].');
        }

        Schema::dropIfExists('vehicle_finance_status_histories');
    }

    public function down(): void
    {
        throw new \LogicException('Vehicle Rental removal is irreversible. Restore a verified database backup and deploy the prior application version.');
    }
};
