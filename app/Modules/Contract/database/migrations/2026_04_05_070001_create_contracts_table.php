<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('contract_number');
            $table->string('type', 30)->default('customer');
            $table->string('status', 30)->default('draft');
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->unsignedBigInteger('supplier_id')->nullable();
            $table->unsignedBigInteger('owner_id')->nullable();
            $table->decimal('value', 15, 4)->default(0);
            $table->string('currency', 3)->default('USD');
            $table->date('start_date');
            $table->date('end_date');
            $table->text('terms')->nullable();
            $table->boolean('auto_renew')->default(false);
            $table->dateTime('terminated_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['tenant_id', 'contract_number']);
            $table->index(['tenant_id', 'status', 'end_date']);
        });
    }
    public function down(): void { Schema::dropIfExists('contracts'); }
};
