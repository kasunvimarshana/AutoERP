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
        Schema::table('currencies', function (Blueprint $table) {
            // Add UUID column
            $table->uuid('uuid')->unique()->after('id');
            
            // Add tenant relationship
            $table->foreignId('tenant_id')->nullable()->after('uuid')->constrained('tenants')->cascadeOnDelete();
            
            // Rename is_default to is_base_currency for consistency with model
            $table->renameColumn('is_default', 'is_base_currency');
            
            // Add audit columns
            $table->foreignId('created_by')->nullable()->after('is_active')->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->after('created_by')->constrained('users')->nullOnDelete();
            
            // Add soft deletes
            $table->softDeletes()->after('updated_at');
            
            // Add index for tenant filtering
            $table->index(['tenant_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('currencies', function (Blueprint $table) {
            $table->dropForeign(['tenant_id']);
            $table->dropForeign(['created_by']);
            $table->dropForeign(['updated_by']);
            $table->dropIndex(['tenant_id', 'is_active']);
            $table->dropColumn(['uuid', 'tenant_id', 'created_by', 'updated_by', 'deleted_at']);
            $table->renameColumn('is_base_currency', 'is_default');
        });
    }
};
