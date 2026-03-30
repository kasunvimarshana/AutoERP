<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('name');
            $table->string('code', 50)->nullable();
            $table->string('type', 100);
            $table->text('description')->nullable();
            $table->string('address', 500)->nullable();
            $table->decimal('capacity', 12, 2)->nullable();
            $table->unsignedBigInteger('location_id')->nullable();
            $table->json('metadata')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('tenant_id');
            $table->index('type');
            $table->index('location_id');
            $table->index('code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warehouses');
    }
};
