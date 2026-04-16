<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('module_configurations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('module_name');
            $table->string('config_key');
            $table->json('config_value');
            $table->timestamps();

            $table->unique(['tenant_id', 'module_name', 'config_key'], 'uq_module_configurations_tenant_module_key');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('module_configurations');
    }
};