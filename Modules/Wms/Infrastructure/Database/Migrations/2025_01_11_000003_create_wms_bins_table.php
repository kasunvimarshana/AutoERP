<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wms_bins', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('aisle_id');
            $table->foreign('aisle_id')->references('id')->on('wms_aisles')->cascadeOnDelete();
            $table->string('code', 100);
            $table->text('description')->nullable();
            $table->unsignedInteger('max_capacity')->nullable();
            $table->unsignedInteger('current_capacity')->default(0);
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();

            $table->unique(['tenant_id', 'aisle_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wms_bins');
    }
};
