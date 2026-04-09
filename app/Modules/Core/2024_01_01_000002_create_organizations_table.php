<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organizations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->string('name');
            $table->string('code', 50);
            $table->string('type', 50); // company, division, department, branch, etc.
            $table->unsignedTinyInteger('level')->default(1);
            $table->string('path', 1000); // materialized path e.g. /1/3/7/
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('parent_id')->references('id')->on('organizations')->nullOnDelete();

            $table->index(['tenant_id', 'code']);
            $table->index(['tenant_id', 'parent_id']);
            $table->index('path');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organizations');
    }
};
