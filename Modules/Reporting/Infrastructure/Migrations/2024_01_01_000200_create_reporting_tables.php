<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reporting_dashboards', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->index();
            $table->uuid('user_id')->index();
            $table->string('name');
            $table->text('description')->nullable();
            $table->json('layout')->nullable();
            $table->boolean('is_shared')->default(false);
            $table->unsignedInteger('refresh_seconds')->default(300);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('reporting_reports', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->index();
            $table->uuid('user_id')->index();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('type')->default('custom');
            $table->string('data_source')->nullable();
            $table->json('fields')->nullable();
            $table->json('filters')->nullable();
            $table->json('group_by')->nullable();
            $table->json('sort_by')->nullable();
            $table->boolean('is_shared')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reporting_reports');
        Schema::dropIfExists('reporting_dashboards');
    }
};
