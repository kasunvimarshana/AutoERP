<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tax_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained(null, 'id', 'tax_rates_tenant_id_fk')->cascadeOnDelete();
            $table->foreignId('tax_group_id')->constrained('tax_groups', 'id', 'tax_rates_tax_group_id_fk')->cascadeOnDelete();
            $table->string('name');
            $table->decimal('rate', 10, 6);
            $table->enum('type', ['percentage', 'fixed'])->default('percentage');
            $table->foreignId('account_id')->nullable();
            $table->foreign('account_id', 'tax_rates_account_id_fk')->references('id')->on('accounts')->nullOnDelete();
            $table->boolean('is_compound')->default(false);
            $table->boolean('is_active')->default(true);
            $table->date('valid_from')->nullable();
            $table->date('valid_to')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_rates');
    }
};
