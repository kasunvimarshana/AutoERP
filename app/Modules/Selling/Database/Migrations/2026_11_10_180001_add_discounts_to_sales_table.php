<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table): void {
            $table->string('bill_discount_type', 20)->nullable();
            $table->decimal('bill_discount_value', 20, 6)->default('0');
            $table->decimal('bill_discount_amount', 20, 6)->default('0');
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table): void {
            $table->dropColumn('bill_discount_type');
            $table->dropColumn('bill_discount_value');
            $table->dropColumn('bill_discount_amount');
        });
    }
};
