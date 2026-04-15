<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('currencies', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->char('code', 3)->unique();
            $table->string('name', 100);
            $table->string('symbol', 10);
            $table->boolean('is_base')->default(false);
            $table->unsignedTinyInteger('decimal_places')->default(2);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();

            $table->index(['tenant_id', 'is_active']);
            $table->index(['tenant_id', 'is_base']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('currencies');
    }
};