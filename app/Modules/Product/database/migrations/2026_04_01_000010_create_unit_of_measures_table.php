<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('unit_of_measures', static function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->string('name', 255);
            $table->string('code', 50);
            $table->string('symbol', 50);
            $table->string('base_unit', 50)->nullable();
            $table->decimal('conversion_factor', 20, 6)->default(1);
            $table->boolean('is_base')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('unit_of_measures');
    }
};
