<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('fs_service_orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->index();
            $table->uuid('service_team_id')->nullable()->index();
            $table->string('reference_no');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('customer_id')->nullable()->index();
            $table->string('contact_name')->nullable();
            $table->string('contact_phone')->nullable();
            $table->string('location')->nullable();
            $table->string('technician_id')->nullable()->index();
            $table->string('status')->default('new');
            $table->decimal('duration_hours', 18, 8)->default(0);
            $table->decimal('labor_cost', 18, 8)->default(0);
            $table->decimal('parts_cost', 18, 8)->default(0);
            $table->text('resolution_notes')->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['tenant_id', 'reference_no']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fs_service_orders');
    }
};
