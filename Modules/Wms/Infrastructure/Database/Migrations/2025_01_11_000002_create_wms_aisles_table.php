<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wms_aisles', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('zone_id');
            $table->foreign('zone_id')->references('id')->on('wms_zones')->cascadeOnDelete();
            $table->string('name', 255);
            $table->string('code', 50);
            $table->text('description')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();

            $table->unique(['tenant_id', 'zone_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wms_aisles');
    }
};
