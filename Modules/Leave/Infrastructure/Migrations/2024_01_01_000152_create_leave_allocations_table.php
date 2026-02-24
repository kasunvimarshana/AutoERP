<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('leave_allocations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->index();
            $table->uuid('employee_id')->index();
            $table->uuid('leave_type_id')->index();
            // BCMath-managed decimal columns (scale 2 to support half-day allocations)
            $table->decimal('total_days', 8, 2);
            $table->decimal('used_days', 8, 2)->default(0);
            $table->string('period_label')->nullable()->comment('e.g. "2026", "Q1 2026"');
            $table->date('valid_from')->nullable();
            $table->date('valid_to')->nullable();
            $table->text('notes')->nullable();
            $table->string('status')->default('draft');
            $table->uuid('approved_by')->nullable()->index();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            // One active allocation per employee per leave type is recommended;
            // multiple draft allocations are allowed to support annual renewals.
            $table->index(['tenant_id', 'employee_id', 'leave_type_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_allocations');
    }
};
