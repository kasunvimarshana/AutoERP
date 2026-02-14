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
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('tenant_id')->nullable()->after('id')->constrained('tenants')->onDelete('cascade');
            $table->foreignId('organization_id')->nullable()->after('tenant_id')->constrained('organizations')->onDelete('set null');
            $table->foreignId('branch_id')->nullable()->after('organization_id')->constrained('branches')->onDelete('set null');
            $table->string('username')->nullable()->after('name')->unique();
            $table->string('phone')->nullable()->after('email');
            $table->boolean('is_active')->default(true)->after('password');
            $table->boolean('is_verified')->default(false)->after('is_active');
            $table->timestamp('last_login_at')->nullable()->after('is_verified');
            $table->json('settings')->nullable()->after('last_login_at');
            $table->json('metadata')->nullable()->after('settings');
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropColumn([
                'tenant_id',
                'organization_id',
                'branch_id',
                'username',
                'phone',
                'is_active',
                'is_verified',
                'last_login_at',
                'settings',
                'metadata'
            ]);
        });
    }
};
