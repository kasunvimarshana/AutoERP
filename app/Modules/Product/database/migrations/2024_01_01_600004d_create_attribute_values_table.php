<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attribute_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
$table->foreignId('org_unit_id')->nullable()->constrained('org_units', 'id')->nullOnDelete();
$table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('attribute_id')->constrained(null, 'id', 'attribute_values_attribute_id_fk')->cascadeOnDelete();
            $table->string('value');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'attribute_id', 'value'], 'attribute_values_tenant_attribute_value_uk');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attribute_values');
    }
};
