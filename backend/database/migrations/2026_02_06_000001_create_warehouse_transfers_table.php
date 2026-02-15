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
        Schema::create('warehouse_transfers', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            
            // Transfer Identification
            $table->string('transfer_number')->unique();
            $table->string('reference_number')->nullable();
            
            // Source and Destination
            $table->foreignId('source_branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignId('source_location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->foreignId('destination_branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignId('destination_location_id')->nullable()->constrained('locations')->nullOnDelete();
            
            // Status Management
            $table->enum('status', ['draft', 'pending', 'approved', 'in_transit', 'received', 'cancelled'])->default('draft')->index();
            $table->enum('priority', ['low', 'normal', 'high', 'urgent'])->default('normal');
            
            // Transfer Details
            $table->date('transfer_date')->index();
            $table->date('expected_delivery_date')->nullable();
            $table->date('actual_delivery_date')->nullable();
            
            // Costs
            $table->decimal('shipping_cost', 15, 2)->default(0);
            $table->decimal('handling_cost', 15, 2)->default(0);
            $table->decimal('total_cost', 15, 2)->default(0);
            
            // Tracking
            $table->string('tracking_number')->nullable();
            $table->string('carrier')->nullable();
            
            // Notes
            $table->text('notes')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->json('metadata')->nullable();
            
            // Audit Fields
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cancelled_at')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['tenant_id', 'source_branch_id']);
            $table->index(['tenant_id', 'destination_branch_id']);
            $table->index(['tenant_id', 'status', 'transfer_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('warehouse_transfers');
    }
};
