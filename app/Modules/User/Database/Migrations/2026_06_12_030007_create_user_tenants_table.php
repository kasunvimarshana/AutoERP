<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // user_tenants - a user can belong to multiple tenants (with a default)
        Schema::create('user_tenants', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete()->comment('Multi-tenant owner reference');
            $table->foreignId('organization_unit_id')->constrained('organization_units', 'id')->nullable()->cascadeOnDelete()->comment('Branch or department ownership');
            $table->json('metadata')->nullable()->comment('Extensible custom dynamic data');

            $table->foreignId('user_id')->constrained('users', 'id')->cascadeOnDelete();
            $table->foreignId('role_id')->nullable()->constrained('roles', 'id')->nullOnDelete();
            $table->boolean('is_default')->default(false);

            $table->timestamps();

            $table->unique(['tenant_id', 'organization_unit_id', 'user_id'], 'user_tenants_user_uk');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_tenants');
    }
};
