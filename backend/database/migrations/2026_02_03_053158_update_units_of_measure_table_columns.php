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
        Schema::table('units_of_measure', function (Blueprint $table) {
            // Add UUID column
            $table->uuid('uuid')->unique()->after('id');
            
            // Add audit columns
            $table->foreignId('created_by')->nullable()->after('is_system')->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->after('created_by')->constrained('users')->nullOnDelete();
            
            // Add soft deletes
            $table->softDeletes()->after('updated_at');
        });

        // SQLite doesn't support dropping columns with constraints well, so we need to rename
        Schema::table('units_of_measure', function (Blueprint $table) {
            // Drop unique constraint on code first
            $table->dropUnique(['code']);
            
            // Rename symbol to abbreviation for consistency with model
            $table->renameColumn('symbol', 'abbreviation');
            
            // Rename code to code_old to avoid conflicts - make it nullable
            $table->renameColumn('code', 'code_old');
        });
        
        // Make code_old nullable
        Schema::table('units_of_measure', function (Blueprint $table) {
            $table->string('code_old', 20)->nullable()->change();
        });

        // Make abbreviation unique per tenant
        Schema::table('units_of_measure', function (Blueprint $table) {
            $table->unique(['tenant_id', 'abbreviation']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('units_of_measure', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropForeign(['updated_by']);
            $table->dropUnique(['tenant_id', 'abbreviation']);
            $table->renameColumn('abbreviation', 'symbol');
            $table->renameColumn('code_old', 'code');
            $table->unique(['code']);
            $table->dropColumn(['uuid', 'created_by', 'updated_by', 'deleted_at']);
        });
    }
};
