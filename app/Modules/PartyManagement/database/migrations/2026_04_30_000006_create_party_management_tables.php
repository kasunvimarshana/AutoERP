<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parties', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->string('party_type', 20);   // individual | company
            $table->string('name', 255);
            $table->string('tax_number', 100)->nullable();
            $table->string('email', 255)->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('address_line_1', 255)->nullable();
            $table->string('address_line_2', 255)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('state_province', 100)->nullable();
            $table->string('postal_code', 20)->nullable();
            $table->string('country_code', 3)->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'party_type']);
        });

        Schema::create('asset_ownerships', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->uuid('party_id');
            $table->uuid('asset_id');
            $table->string('ownership_type', 30);  // owner | lessee | guarantor
            $table->date('acquisition_date');
            $table->date('disposal_date')->nullable();
            $table->decimal('acquisition_cost', 20, 6);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'party_id']);
            $table->index(['tenant_id', 'asset_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_ownerships');
        Schema::dropIfExists('parties');
    }
};
