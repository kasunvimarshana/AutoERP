<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('rental_driver_assignments')) {
            return;
        }

        if (DB::table('rental_driver_assignments')->exists()) {
            throw new \RuntimeException('Vehicle Rental removal stopped because operational data still exists in [rental_driver_assignments].');
        }

        Schema::dropIfExists('rental_driver_assignments');
    }

    public function down(): void
    {
        throw new \LogicException('Vehicle Rental removal is irreversible. Restore a verified database backup and deploy the prior application version.');
    }
};
