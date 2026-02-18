<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('tenant_id')->nullable()->after('id');
            $table->string('avatar')->nullable()->after('email');
            $table->string('phone', 20)->nullable()->after('avatar');
            $table->string('timezone', 50)->default('UTC')->after('phone');
            $table->string('locale', 10)->default('en')->after('timezone');
            $table->boolean('is_active')->default(true)->after('locale');
            $table->boolean('is_verified')->default(false)->after('is_active');
            $table->timestamp('last_login_at')->nullable()->after('email_verified_at');
            $table->string('last_login_ip', 45)->nullable()->after('last_login_at');
            $table->boolean('mfa_enabled')->default(false)->after('last_login_ip');
            $table->text('mfa_secret')->nullable()->after('mfa_enabled');
            $table->softDeletes();

            $table->index('tenant_id');
            $table->index('email');
            $table->index(['tenant_id', 'email']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['tenant_id']);
            $table->dropIndex(['email']);
            $table->dropIndex(['tenant_id', 'email']);
            $table->dropSoftDeletes();
            $table->dropColumn([
                'tenant_id',
                'avatar',
                'phone',
                'timezone',
                'locale',
                'is_active',
                'is_verified',
                'last_login_at',
                'last_login_ip',
                'mfa_enabled',
                'mfa_secret',
            ]);
        });
    }
};
