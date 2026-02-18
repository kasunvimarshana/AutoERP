<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pos_reference_counters', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->string('reference_type'); // transaction, expense, adjustment, etc
            $table->string('prefix')->nullable();
            $table->integer('current_number')->default(0);
            $table->integer('padding')->default(6);
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->unique(['tenant_id', 'reference_type', 'prefix']);
            $table->index(['tenant_id', 'reference_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_reference_counters');
    }
};
