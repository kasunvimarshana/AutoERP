<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('warehouse_pickings', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            
            // Picking Identification
            $table->string('picking_number')->unique();
            $table->string('reference_type')->nullable(); // SalesOrder, WorkOrder, Transfer
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('reference_number')->nullable();
            
            // Status Management
            $table->enum('status', ['pending', 'assigned', 'in_progress', 'completed', 'cancelled'])->default('pending')->index();
            $table->enum('priority', ['low', 'normal', 'high', 'urgent'])->default('normal');
            $table->enum('picking_type', ['sales', 'transfer', 'manufacturing', 'other'])->default('sales');
            
            // Picking Details
            $table->date('scheduled_date')->index();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            
            // Assignment
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('assigned_at')->nullable();
            
            // Location Information
            $table->foreignId('picking_location_id')->nullable()->constrained('locations')->nullOnDelete();
            
            // Notes
            $table->text('notes')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->json('metadata')->nullable();
            
            // Audit Fields
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cancelled_at')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['tenant_id', 'branch_id', 'status']);
            $table->index(['reference_type', 'reference_id']);
            $table->index(['assigned_to', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('warehouse_pickings');
    }
};
