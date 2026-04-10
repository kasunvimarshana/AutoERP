<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('numbering_sequences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('document_type', 100);
            $table->string('prefix', 50)->nullable();
            $table->string('suffix', 50)->nullable();
            $table->unsignedBigInteger('current_number')->default(0);
            $table->unsignedTinyInteger('padding_length')->default(6);
            $table->enum('reset_frequency', ['never', 'yearly', 'monthly', 'daily'])->default('never');
            $table->timestamp('last_reset_at')->nullable();
            $table->unique(['tenant_id', 'document_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('numbering_sequences');
    }
};
