<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hr_employee_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', 'id', 'hr_employee_documents_tenant_id_fk')->cascadeOnDelete();
            $table->unsignedBigInteger('employee_id');
            $table->string('document_type', 50);
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('file_path');
            $table->string('mime_type', 100)->nullable();
            $table->unsignedBigInteger('file_size')->default(0);
            $table->date('issued_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->json('metadata')->nullable();
            $table->foreign('employee_id', 'hr_employee_documents_employee_id_fk')
                ->references('id')->on('employees')->cascadeOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id'], 'hr_employee_documents_tenant_id_idx');
            $table->index(['employee_id'], 'hr_employee_documents_employee_id_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_employee_documents');
    }
};
