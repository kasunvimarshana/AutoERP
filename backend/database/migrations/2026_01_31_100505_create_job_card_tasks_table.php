<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_card_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignId('job_card_id')->constrained('job_cards')->onDelete('cascade');
            $table->foreignId('sku_id')->nullable()->constrained('skus')->onDelete('set null');
            $table->enum('task_type', ['service', 'part']);
            $table->text('description');
            $table->decimal('quantity', 10, 2)->default(1);
            $table->decimal('unit_price', 15, 2)->default(0);
            $table->decimal('discount', 15, 2)->default(0);
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->decimal('line_total', 15, 2)->default(0);
            $table->enum('status', ['pending', 'in_progress', 'completed'])->default('pending');
            $table->foreignId('completed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->dateTime('completed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('tenant_id');
            $table->index('job_card_id');
            $table->index('sku_id');
            $table->index('task_type');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_card_tasks');
    }
};
