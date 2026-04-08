<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('currencies', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('code', 10)->comment('ISO 4217 code, e.g. USD');
            $table->string('name');
            $table->string('symbol', 10);
            $table->integer('decimal_places')->default(2);
            $table->string('decimal_separator', 5)->default('.');
            $table->string('thousands_separator', 5)->default(',');
            $table->string('symbol_position', 10)->default('before')
                  ->comment('before or after');
            $table->boolean('is_base')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'code']);
        });

        Schema::create('exchange_rates', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('from_currency', 10);
            $table->string('to_currency', 10);
            $table->decimal('rate', 18, 8);
            $table->date('effective_date');
            $table->enum('source', ['manual', 'api', 'import'])->default('manual');
            $table->string('provider')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'from_currency', 'to_currency', 'effective_date']);
        });

        Schema::create('settings', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignUlid('organization_id')->nullable()->constrained('organizations')->cascadeOnDelete();
            $table->foreignUlid('branch_id')->nullable()->constrained('branches')->cascadeOnDelete();
            $table->string('group')->comment('module or feature group');
            $table->string('key');
            $table->text('value')->nullable();
            $table->string('cast')->default('string')
                  ->comment('string,integer,boolean,json,float,array');
            $table->text('description')->nullable();
            $table->boolean('is_public')->default(false);
            $table->timestamps();

            $table->unique(['tenant_id', 'organization_id', 'branch_id', 'group', 'key']);
            $table->index(['tenant_id', 'group', 'key']);
        });

        Schema::create('number_sequences', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignUlid('organization_id')->nullable()->constrained('organizations')->cascadeOnDelete();
            $table->foreignUlid('branch_id')->nullable()->constrained('branches')->cascadeOnDelete();
            $table->string('document_type')
                  ->comment('purchase_order,sales_order,grn,delivery,transfer,adjustment,return,cycle_count');
            $table->string('prefix')->nullable();
            $table->string('suffix')->nullable();
            $table->integer('padding_length')->default(6);
            $table->bigInteger('next_number')->default(1);
            $table->boolean('reset_yearly')->default(false);
            $table->boolean('reset_monthly')->default(false);
            $table->integer('reset_year')->nullable();
            $table->integer('reset_month')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'organization_id', 'branch_id', 'document_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('number_sequences');
        Schema::dropIfExists('settings');
        Schema::dropIfExists('exchange_rates');
        Schema::dropIfExists('currencies');
    }
};
