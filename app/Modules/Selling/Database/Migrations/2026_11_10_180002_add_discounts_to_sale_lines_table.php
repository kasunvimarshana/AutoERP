<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sale_lines', function (Blueprint $table): void {
            $table->string('discount_type', 20)->nullable();
            $table->decimal('discount_value', 20, 6)->default('0');
            $table->decimal('discount_amount', 20, 6)->default('0');
            $table->decimal('bill_discount_allocated', 20, 6)->default('0');
        });
    }

    public function down(): void
    {
        Schema::table('sale_lines', function (Blueprint $table): void {
            $table->dropColumn('discount_type');
            $table->dropColumn('discount_value');
            $table->dropColumn('discount_amount');
            $table->dropColumn('bill_discount_allocated');
        });
    }
};
