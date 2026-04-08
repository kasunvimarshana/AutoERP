<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organization_units', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->uuid('parent_id')->nullable();
            $table->string('code', 50);
            $table->string('name', 200);
            // company, division, department, team, branch, region, site, etc.
            $table->string('type', 50);
            $table->text('description')->nullable();
            $table->unsignedBigInteger('manager_user_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'code']);
            $table->index(['parent_id']);

            $table->foreign('parent_id')->references('id')->on('organization_units')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organization_units');
    }
};
