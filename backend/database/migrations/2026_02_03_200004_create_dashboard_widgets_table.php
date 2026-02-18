<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('dashboard_widgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('dashboard_id')->constrained()->onDelete('cascade');
            $table->string('widget_type'); // kpi, chart, table, recent_activity, quick_stats
            $table->string('title');
            $table->json('config');
            $table->integer('position_x')->default(0);
            $table->integer('position_y')->default(0);
            $table->integer('width')->default(4);
            $table->integer('height')->default(3);
            $table->timestamps();

            // Indexes
            $table->index(['tenant_id', 'dashboard_id']);
            $table->index('widget_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dashboard_widgets');
    }
};
