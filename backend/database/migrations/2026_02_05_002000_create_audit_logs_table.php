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
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid('audit_id')->unique(); // Unique identifier for audit entry
            
            // User and tenant information
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('tenant_id')->nullable();
            
            // Event information
            $table->string('event'); // e.g., 'created', 'updated', 'deleted', 'viewed', 'exported'
            $table->string('auditable_type'); // Model class name
            $table->unsignedBigInteger('auditable_id'); // Model ID
            
            // Change tracking
            $table->json('old_values')->nullable(); // Previous values (for updates/deletes)
            $table->json('new_values')->nullable(); // New values (for creates/updates)
            
            // Request context
            $table->string('url')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->json('tags')->nullable(); // Additional tags for filtering
            
            // Metadata
            $table->json('metadata')->nullable(); // Additional context
            
            $table->timestamp('created_at')->useCurrent();
            
            // Indexes for performance
            $table->index(['auditable_type', 'auditable_id']);
            $table->index(['user_id', 'created_at']);
            $table->index(['tenant_id', 'created_at']);
            $table->index('event');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
