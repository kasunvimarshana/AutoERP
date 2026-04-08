<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('identifiers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('identifiable_type');
            $table->unsignedBigInteger('identifiable_id');
            $table->string('technology');
            $table->string('code');
            $table->string('encoding')->nullable();
            $table->string('status')->default("active");
            $table->boolean('is_primary')->default(false);
            $table->json('metadata')->nullable();
            $table->timestamp('issued_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->unique(['tenant_id', 'technology', 'code']);
            $table->index(['identifiable_type', 'identifiable_id']);
            $table->index(['tenant_id', 'code']);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('identifiers');
    }
};
