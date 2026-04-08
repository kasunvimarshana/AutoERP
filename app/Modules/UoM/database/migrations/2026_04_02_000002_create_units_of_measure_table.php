<?php

declare(strict_types=1);

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
            $table->unsignedBigInteger('uom_category_id');
            $table->string('name');
            $table->string('code', 50);
            $table->string('symbol', 20);
            $table->boolean('is_base_unit')->default(false);
            $table->decimal('factor', 15, 8)->default(1.0);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('uom_category_id')->references('id')->on('uom_categories')->onDelete('restrict');

            $table->index('tenant_id');
            $table->index('uom_category_id');
            $table->index('code');
            $table->index('is_base_unit');
            $table->unique(['tenant_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('units_of_measure');
    }
};
