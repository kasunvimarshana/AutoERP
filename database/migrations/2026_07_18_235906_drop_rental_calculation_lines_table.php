<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('rental_calculation_lines')) {
            return;
        }

        if (DB::table('rental_calculation_lines')->exists()) {
            throw new \RuntimeException('Vehicle Rental removal stopped because operational data still exists in [rental_calculation_lines].');
        }

        Schema::dropIfExists('rental_calculation_lines');
    }

    public function down(): void
    {
        throw new \LogicException('Vehicle Rental removal is irreversible. Restore a verified database backup and deploy the prior application version.');
    }
};
