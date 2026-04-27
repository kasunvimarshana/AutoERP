<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('price_lists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
$table->foreignId('org_unit_id')->nullable()->constrained('org_units', 'id')->nullOnDelete();
$table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->string('name');
            $table->enum('type', ['purchase', 'sales'])->default('sales');
            $table->foreignId('currency_id')->constrained('currencies', 'id', 'price_lists_currency_id_fk');
            $table->boolean('is_default')->default(false);
            $table->date('valid_from')->nullable();
            $table->date('valid_to')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['tenant_id', 'name'], 'price_lists_tenant_name_uk');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('price_lists');
    }
};
