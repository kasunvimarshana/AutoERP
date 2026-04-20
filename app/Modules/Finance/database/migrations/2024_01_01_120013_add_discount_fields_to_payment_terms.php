<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_terms', function (Blueprint $table): void {
            $table->text('description')->nullable()->after('name');
            $table->unsignedInteger('discount_days')->nullable()->after('days');
            $table->decimal('discount_rate', 8, 4)->nullable()->after('discount_days');
        });
    }

    public function down(): void
    {
        Schema::table('payment_terms', function (Blueprint $table): void {
            $table->dropColumn(['description', 'discount_days', 'discount_rate']);
        });
    }
};
