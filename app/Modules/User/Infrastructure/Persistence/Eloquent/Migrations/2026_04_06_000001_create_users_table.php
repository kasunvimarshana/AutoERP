<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')->nullable()->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete();
            $table->json('metadata')->nullable();

            $table->string('first_name');
            $table->string('last_name')->nullable();
            $table->string('email');
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->string('status')->default('active')->comment('active, inactive, suspended');
            $table->string('avatar_path')->nullable();
            $table->string('phone')->nullable();
            $table->json('preferences')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('gender')->nullable();
            $table->string('marital_status')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'email'], 'users_email_uk');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
