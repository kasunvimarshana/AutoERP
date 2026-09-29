<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicle_rental_owner_usage_charges', function (Blueprint $table): void {
            $table->index(['agreement_id', 'component', 'period_from', 'id'], 'vruo_cycle_ix');
        });
    }

    public function down(): void
    {
        Schema::table('vehicle_rental_owner_usage_charges', function (Blueprint $table): void {
            $table->dropIndex('vruo_cycle_ix');
        });
    }
};
