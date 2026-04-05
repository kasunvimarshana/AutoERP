<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('crm_leads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('company')->nullable();
            $table->string('source', 50)->default('other');
            $table->string('status', 30)->default('new');
            $table->decimal('estimated_value', 15, 4)->nullable();
            $table->unsignedBigInteger('owner_id')->nullable();
            $table->text('notes')->nullable();
            $table->dateTime('converted_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'owner_id']);
        });
    }
    public function down(): void { Schema::dropIfExists('crm_leads'); }
};
