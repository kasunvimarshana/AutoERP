<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hr_attendance_batch_imports', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1);
            $table->foreignId('tenant_id')->constrained('tenants', indexName: 'hr_att_batch_imp_tenant_fk')->restrictOnDelete();
            $table->string('batch_number', 64);
            $table->foreignId('imported_by_user_id')->nullable()->constrained('users', indexName: 'hr_att_batch_imp_user_fk')->restrictOnDelete();
            $table->string('file_name', 255);
            $table->unsignedInteger('total_rows')->default(0);
            $table->unsignedInteger('processed_rows')->default(0);
            $table->unsignedInteger('skipped_rows')->default(0);
            $table->unsignedInteger('failed_rows')->default(0);
            $table->string('status', 32)->default('pending');
            $table->json('errors_payload')->nullable();
            $table->timestamps();

            $table->unique(['id', 'tenant_id'], 'hr_att_batch_imp_id_tenant_uk');
            $table->unique(['tenant_id', 'batch_number'], 'hr_att_batch_imp_batch_uk');
            $table->index(['tenant_id', 'status'], 'hr_att_batch_imp_status_ix');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_attendance_batch_imports');
    }
};
