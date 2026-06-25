<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_number_sequences', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->restrictOnDelete();
            $table->string('sequence_key', 80);
            $table->unsignedBigInteger('last_number')->default(0);
            $table->timestamps();

            $table->unique(
                ['tenant_id', 'sequence_key'],
                'inventory_number_sequences_tenant_key_uk',
            );

            $table->unique(['id', 'tenant_id'], 'inventory_number_sequences_id_tenant_uk');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_number_sequences');
    }
};
