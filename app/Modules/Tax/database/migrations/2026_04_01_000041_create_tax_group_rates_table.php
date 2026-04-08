<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tax_group_rates', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->uuid('tax_group_id');
            $table->string('name');
            $table->decimal('rate', 10, 6);
            $table->string('type')->default('percentage');
            $table->integer('sequence')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('tax_group_id')->references('id')->on('tax_groups')->onDelete('cascade');
            $table->index(['tenant_id', 'tax_group_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_group_rates');
    }
};
