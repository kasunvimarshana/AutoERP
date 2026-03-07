<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')
                  ->nullable()
                  ->constrained('tenants')
                  ->nullOnDelete()
                  ->comment('NULL = system-wide role; non-null = tenant-specific role');
            $table->string('name', 100)->unique();
            $table->string('display_name', 150)->nullable();
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index('tenant_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
