<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('name');
            $table->string('type', 100);
            $table->string('code', 50)->nullable();
            $table->text('description')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('timezone', 100)->nullable();
            $table->json('metadata')->nullable();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->integer('_lft')->default(0);
            $table->integer('_rgt')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'parent_id']);
            $table->index(['tenant_id', 'type']);
            $table->index(['tenant_id', '_lft', '_rgt']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('locations');
    }
};
