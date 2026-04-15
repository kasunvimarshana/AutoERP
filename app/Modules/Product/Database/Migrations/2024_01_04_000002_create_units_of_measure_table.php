<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('units_of_measure', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('name', 100);
            $table->string('symbol', 20);
            $table->enum('type', ['quantity', 'weight', 'volume', 'length', 'area', 'time', 'other']);
            $table->boolean('is_base')->default(false);
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();

            $table->unique(['tenant_id', 'symbol']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('units_of_measure');
    }
};