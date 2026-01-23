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
            $table->foreignId('vendor_id')->nullable()->after('tenant_id')->constrained('tenants')->nullOnDelete();
            $table->foreignId('branch_id')->nullable()->after('vendor_id')->constrained('tenants')->nullOnDelete();
            $table->boolean('mfa_enabled')->default(false)->after('status');
            $table->timestamp('email_verified_at')->nullable()->after('email');
            $table->timestamp('password_changed_at')->nullable()->after('password');
            $table->integer('failed_login_attempts')->default(0)->after('last_login_at');
            $table->timestamp('locked_until')->nullable()->after('failed_login_attempts');
            $table->json('security_settings')->nullable()->after('settings');
            
            $table->index(['tenant_id', 'vendor_id', 'branch_id']);
            $table->index('mfa_enabled');
            $table->index('locked_until');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['vendor_id']);
            $table->dropForeign(['branch_id']);
            $table->dropColumn([
                'vendor_id',
                'branch_id',
                'mfa_enabled',
                'email_verified_at',
                'password_changed_at',
                'failed_login_attempts',
                'locked_until',
                'security_settings',
            ]);
        });
    }
};
