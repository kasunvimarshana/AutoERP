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
        Schema::create('customer_notes', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            
            // Note Information
            $table->enum('type', ['general', 'call', 'meeting', 'email', 'task', 'other'])->default('general');
            $table->string('subject')->nullable();
            $table->text('content');
            
            // Interaction Details
            $table->timestamp('interaction_date')->nullable();
            $table->integer('duration_minutes')->nullable(); // For calls/meetings
            $table->enum('outcome', ['positive', 'neutral', 'negative', 'follow_up_required'])->nullable();
            
            // Visibility and Priority
            $table->boolean('is_private')->default(false);
            $table->boolean('is_important')->default(false);
            $table->boolean('is_pinned')->default(false);
            
            // Attachments
            $table->json('attachments')->nullable();
            
            // Audit fields
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['customer_id', 'type']);
            $table->index(['customer_id', 'interaction_date']);
            $table->index(['created_by']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_notes');
    }
};
