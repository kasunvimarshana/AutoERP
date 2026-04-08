<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('uom_conversions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('from_uom_id');
            $table->unsignedBigInteger('to_uom_id');
            $table->decimal('factor', 15, 8);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('from_uom_id')->references('id')->on('units_of_measure')->onDelete('restrict');
            $table->foreign('to_uom_id')->references('id')->on('units_of_measure')->onDelete('restrict');

            $table->unique(['tenant_id', 'from_uom_id', 'to_uom_id']);
            $table->index('tenant_id');
            $table->index('from_uom_id');
            $table->index('to_uom_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('uom_conversions');
    }
};
