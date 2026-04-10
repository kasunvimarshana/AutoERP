<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('module', 100);
            $table->string('key', 150);
            $table->json('value');
            $table->enum('type', ['string', 'integer', 'boolean', 'json', 'date']);
            $table->timestamp('updated_at');
            $table->unique(['tenant_id', 'module', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
