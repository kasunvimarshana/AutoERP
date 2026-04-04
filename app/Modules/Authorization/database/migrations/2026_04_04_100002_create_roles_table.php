<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->string('name', 100);
            $table->string('guard_name', 50)->default('api');
            $table->string('description')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['tenant_id', 'name', 'guard_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
