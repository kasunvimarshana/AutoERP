<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Foundation schema for the canonical ERP redesign.
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_code', 50)->unique();
            $table->string('name', 150);
            $table->string('legal_name', 200)->nullable();
            $table->string('base_currency_code', 3)->default('USD');
            $table->string('timezone', 64)->default('UTC');
            $table->string('locale', 16)->default('en');
            $table->string('status_code', 30)->default('active');
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('suspended_at')->nullable();
            $table->timestamps();

            $table->index(['status_code']);
        });

        Schema::create('tenant_domains', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('domain', 255);
            $table->boolean('is_primary')->default(false);
            $table->boolean('is_verified')->default(false);
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'domain']);
            $table->unique(['domain']);
            $table->index(['tenant_id', 'is_primary']);
        });

        Schema::create('tenant_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('setting_group', 100);
            $table->string('setting_key', 150);
            $table->json('setting_value')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'setting_group', 'setting_key'], 'tenant_settings_scope_uk');
        });

        Schema::create('id_sequences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('sequence_key', 100);
            $table->string('scope_key', 150)->nullable();
            $table->string('prefix', 30)->nullable();
            $table->unsignedBigInteger('last_number')->default(0);
            $table->unsignedSmallInteger('padding_length')->default(6);
            $table->timestamps();

            $table->unique(['tenant_id', 'sequence_key', 'scope_key'], 'id_sequences_scope_uk');
        });

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->unsignedBigInteger('party_id')->nullable();
            $table->string('email', 190);
            $table->string('password');
            $table->string('display_name', 150);
            $table->string('locale', 16)->default('en');
            $table->string('timezone', 64)->default('UTC');
            $table->string('status_code', 30)->default('active');
            $table->timestamp('last_login_at')->nullable();
            $table->rememberToken();
            $table->timestamps();

            $table->unique(['tenant_id', 'email']);
            $table->index(['tenant_id', 'status_code']);
            $table->index(['tenant_id', 'party_id']);
        });

        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('role_code', 100);
            $table->string('role_name', 150);
            $table->text('description')->nullable();
            $table->boolean('is_system')->default(false);
            $table->timestamps();

            $table->unique(['tenant_id', 'role_code']);
        });

        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('permission_code', 150);
            $table->string('permission_name', 150);
            $table->string('module_code', 100)->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'permission_code']);
            $table->index(['tenant_id', 'module_code']);
        });

        Schema::create('user_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
            $table->timestamp('assigned_at')->useCurrent();

            $table->unique(['tenant_id', 'user_id', 'role_id']);
        });

        Schema::create('role_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained('permissions')->cascadeOnDelete();
            $table->timestamp('granted_at')->useCurrent();

            $table->unique(['tenant_id', 'role_id', 'permission_id']);
        });

        Schema::create('org_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('org_units')->nullOnDelete();
            $table->foreignId('manager_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('unit_code', 50);
            $table->string('unit_name', 150);
            $table->string('unit_type', 50);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['tenant_id', 'unit_code']);
            $table->index(['tenant_id', 'parent_id']);
            $table->index(['tenant_id', 'unit_type']);
        });

        Schema::create('org_unit_closure', function (Blueprint $table) {
            $table->foreignId('ancestor_id')->constrained('org_units')->cascadeOnDelete();
            $table->foreignId('descendant_id')->constrained('org_units')->cascadeOnDelete();
            $table->unsignedInteger('depth');
            $table->timestamps();

            $table->primary(['ancestor_id', 'descendant_id']);
            $table->index(['descendant_id', 'depth']);
        });

        Schema::create('user_org_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('org_unit_id')->constrained('org_units')->cascadeOnDelete();
            $table->string('membership_role', 50)->default('member');
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            $table->unique(['tenant_id', 'user_id', 'org_unit_id']);
            $table->index(['tenant_id', 'org_unit_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_org_units');
        Schema::dropIfExists('org_unit_closure');
        Schema::dropIfExists('org_units');
        Schema::dropIfExists('role_permissions');
        Schema::dropIfExists('user_roles');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('users');
        Schema::dropIfExists('id_sequences');
        Schema::dropIfExists('tenant_settings');
        Schema::dropIfExists('tenant_domains');
        Schema::dropIfExists('tenants');
    }
};
