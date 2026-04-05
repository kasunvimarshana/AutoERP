<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('crm_opportunities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('name');
            $table->unsignedBigInteger('contact_id')->nullable();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->unsignedBigInteger('owner_id')->nullable();
            $table->string('stage', 30)->default('prospecting');
            $table->decimal('probability', 5, 2)->default(0);
            $table->decimal('amount', 15, 4)->default(0);
            $table->string('currency', 3)->default('USD');
            $table->dateTime('expected_close_date')->nullable();
            $table->text('description')->nullable();
            $table->dateTime('closed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['tenant_id', 'stage']);
            $table->index(['tenant_id', 'owner_id']);
        });
    }
    public function down(): void { Schema::dropIfExists('crm_opportunities'); }
};
