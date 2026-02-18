<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('permissions', function (Blueprint $table) {
            if (! Schema::hasColumn('permissions', 'description')) {
                $table->string('description', 500)->nullable()->after('guard_name');
            }
            if (! Schema::hasColumn('permissions', 'resource')) {
                $table->string('resource', 100)->nullable()->after('name');
                $table->index('resource');
            }
            if (! Schema::hasColumn('permissions', 'action')) {
                $table->string('action', 100)->nullable()->after('resource');
                $table->index('action');
            }
            if (! Schema::hasColumn('permissions', 'tenant_id')) {
                $table->unsignedBigInteger('tenant_id')->nullable()->after('id');
                $table->index('tenant_id');
            }
            if (! Schema::hasColumn('permissions', 'is_system')) {
                $table->boolean('is_system')->default(false)->after('guard_name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('permissions', function (Blueprint $table) {
            if (Schema::hasColumn('permissions', 'tenant_id')) {
                $table->dropIndex(['tenant_id']);
                $table->dropColumn('tenant_id');
            }
            if (Schema::hasColumn('permissions', 'resource')) {
                $table->dropIndex(['resource']);
                $table->dropColumn('resource');
            }
            if (Schema::hasColumn('permissions', 'action')) {
                $table->dropIndex(['action']);
                $table->dropColumn('action');
            }
            if (Schema::hasColumn('permissions', 'description')) {
                $table->dropColumn('description');
            }
            if (Schema::hasColumn('permissions', 'is_system')) {
                $table->dropColumn('is_system');
            }
        });
    }
};
