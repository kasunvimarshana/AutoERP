<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('party_code');
            $table->string('party_type')->default("organization");
            $table->string('legal_name');
            $table->string('display_name')->nullable();
            $table->string('tax_number')->nullable();
            $table->string('status')->default("active");
            $table->json('metadata')->nullable();
            $table->unique(['tenant_id', 'party_code']);
            $table->index(['tenant_id', 'legal_name']);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parties');
    }
};
