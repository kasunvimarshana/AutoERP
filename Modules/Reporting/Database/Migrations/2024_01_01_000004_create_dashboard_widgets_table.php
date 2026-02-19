<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dashboard_widgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('organization_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('dashboard_id')->constrained('dashboards')->cascadeOnDelete();
            $table->foreignId('report_id')->nullable()->constrained('reports')->cascadeOnDelete();
            $table->string('type', 50);
            $table->string('chart_type', 50)->nullable();
            $table->string('title');
            $table->text('description')->nullable();
            $table->json('configuration')->nullable();
            $table->json('data_source')->nullable();
            $table->integer('refresh_interval')->nullable();
            $table->integer('order')->default(0);
            $table->integer('width')->default(6);
            $table->integer('height')->default(4);
            $table->integer('position_x')->default(0);
            $table->integer('position_y')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['dashboard_id', 'order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dashboard_widgets');
    }
};
