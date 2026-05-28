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
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete()->comment('Multi-tenant owner reference');
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete()->comment('Branch or department ownership');
            $table->json('metadata')->nullable()->comment('Extensible custom dynamic data');

            $table->string('code')->nullable();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('type')->default('generic')->comment('generic, sales, purchase, rental, service, finance');
            $table->string('scope_type')->default('generic')->comment('generic, customer, supplier, module, source');
            $table->string('source_type')->nullable()->comment('Upstream module or pricing source type');
            $table->unsignedBigInteger('source_id')->nullable()->comment('Upstream module or source reference');
            $table->foreignId('currency_id')->nullable()->constrained('currencies', 'id', 'price_lists_currency_id_fk')->nullOnDelete();
            $table->unsignedInteger('priority')->default(0);
            $table->boolean('is_default')->default(false);
            $table->boolean('is_stackable')->default(true);
            $table->boolean('is_exclusive')->default(false);
            $table->date('valid_from')->nullable();
            $table->date('valid_to')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['tenant_id', 'name'], 'price_lists_name_uk');
            $table->unique(['tenant_id', 'code'], 'price_lists_code_uk');
            $table->index(['tenant_id', 'type'], 'price_lists_type_idx');
            $table->index(['tenant_id', 'scope_type'], 'price_lists_scope_idx');
            $table->index(['tenant_id', 'is_active'], 'price_lists_active_idx');
            $table->index(['tenant_id', 'priority'], 'price_lists_priority_idx');
            $table->index(['tenant_id', 'source_type', 'source_id'], 'price_lists_source_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('price_lists');
    }
};
