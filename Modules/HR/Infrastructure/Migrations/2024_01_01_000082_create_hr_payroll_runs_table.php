<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('hr_payroll_runs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->index();
            $table->date('period_start');
            $table->date('period_end');
            $table->string('status')->default('draft');
            $table->decimal('total_gross', 18, 8)->default(0);
            $table->decimal('total_net', 18, 8)->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_payroll_runs');
    }
};
