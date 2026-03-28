<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('name');
            $table->string('code', 100);
            $table->string('email', 255)->nullable();
            $table->string('phone', 50)->nullable();
            $table->json('address')->nullable();
            $table->json('contact_person')->nullable();
            $table->string('payment_terms', 100)->nullable();
            $table->string('currency', 3)->default('USD');
            $table->string('tax_number', 100)->nullable();
            $table->string('status', 50)->default('active');
            $table->string('type', 50)->default('other');
            $table->json('attributes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'code']);
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'type']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};
