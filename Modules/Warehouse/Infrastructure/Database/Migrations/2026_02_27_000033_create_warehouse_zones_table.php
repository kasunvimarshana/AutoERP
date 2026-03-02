<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warehouse_zones', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('warehouse_id');
            $table->string('name');
            $table->string('code');
            $table->string('zone_type'); // storage/receiving/shipping/quarantine
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('warehouse_id')->references('id')->on('warehouses')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warehouse_zones');
    }
};
