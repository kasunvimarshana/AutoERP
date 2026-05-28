<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('voucher_metadata_definitions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1);
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units')->nullOnDelete();
            $table->json('metadata')->nullable();
            $table->string('scope')->default('voucher');
            $table->string('key');
            $table->string('label');
            $table->string('value_type')->default('string');
            $table->boolean('is_required')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['tenant_id', 'scope', 'key'], 'voucher_metadata_definitions_scope_key_uk');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('voucher_metadata_definitions');
    }
};