<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bin_locations', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->foreignId('warehouse_zone_id')->constrained('warehouse_zones')->cascadeOnDelete();
            $table->string('aisle')->nullable();
            $table->string('row')->nullable();
            $table->string('level')->nullable();
            $table->string('bin_code');
            $table->decimal('capacity', 20, 4)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['tenant_id', 'warehouse_zone_id', 'bin_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bin_locations');
    }
};
