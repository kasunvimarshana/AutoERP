<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // user_tenants – a user can belong to multiple tenants (with a default)
        Schema::create('user_tenants', function (Blueprint $table) {
            // $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->json('metadata')->nullable();

            $table->foreignId('user_id')->constrained('users', 'id')->cascadeOnDelete();
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->primary(['tenant_id', 'user_id'], 'user_tenants_pk');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_tenants');
    }
};
