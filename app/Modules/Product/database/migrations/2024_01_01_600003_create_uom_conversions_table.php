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
            $table->foreignId('from_uom_id')->constrained('units_of_measure', 'id', 'uom_conversions_from_uom_id_fk')->cascadeOnDelete();
            $table->foreignId('to_uom_id')->constrained('units_of_measure', 'id', 'uom_conversions_to_uom_id_fk')->cascadeOnDelete();
            $table->decimal('factor', 20, 10);
            $table->timestamps();

            $table->unique(['from_uom_id', 'to_uom_id'], 'uom_conversions_from_to_uk');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('uom_conversions');
    }
};
