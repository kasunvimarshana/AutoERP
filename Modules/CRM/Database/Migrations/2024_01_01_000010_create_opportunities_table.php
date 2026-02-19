<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('opportunities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('organization_id')->constrained()->onDelete('cascade');
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->onDelete('set null');
            $table->string('opportunity_code', 50)->unique();
            $table->string('name');
            $table->string('stage', 20);
            $table->decimal('amount', 15, 2)->default(0);
            $table->integer('probability')->default(0);
            $table->date('expected_close_date')->nullable();
            $table->date('actual_close_date')->nullable();
            $table->string('lead_source')->nullable();
            $table->string('next_step')->nullable();
            $table->text('description')->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'organization_id']);
            $table->index(['tenant_id', 'customer_id']);
            $table->index(['tenant_id', 'stage']);
            $table->index(['tenant_id', 'assigned_to']);
            $table->index(['tenant_id', 'opportunity_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('opportunities');
    }
};
