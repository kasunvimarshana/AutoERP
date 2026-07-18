<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('rental_billing_periods')) {
            return;
        }

        if (DB::table('rental_billing_periods')->exists()) {
            throw new \RuntimeException('Vehicle Rental removal stopped because operational data still exists in [rental_billing_periods].');
        }

        Schema::dropIfExists('rental_billing_periods');
    }

    public function down(): void
    {
        throw new \LogicException('Vehicle Rental removal is irreversible. Restore a verified database backup and deploy the prior application version.');
    }
};
