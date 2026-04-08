<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('code', 50);
            $table->string('name');
            $table->string('type', 50);
            $table->string('subtype', 100)->nullable();
            $table->text('description')->nullable();
            $table->string('currency', 3)->default('USD');
            $table->decimal('balance', 20, 4)->default(0);
            $table->boolean('is_system')->default(false);
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->string('status', 50)->default('active');
            $table->json('attributes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'code']);
            $table->index(['tenant_id', 'type']);
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'parent_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};
