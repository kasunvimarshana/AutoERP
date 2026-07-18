<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('vehicle_finance_agreements')) {
            return;
        }

        if (DB::table('vehicle_finance_agreements')->exists()) {
            throw new \RuntimeException('Vehicle Rental removal stopped because operational data still exists in [vehicle_finance_agreements].');
        }

        Schema::dropIfExists('vehicle_finance_agreements');
    }

    public function down(): void
    {
        throw new \LogicException('Vehicle Rental removal is irreversible. Restore a verified database backup and deploy the prior application version.');
    }
};
