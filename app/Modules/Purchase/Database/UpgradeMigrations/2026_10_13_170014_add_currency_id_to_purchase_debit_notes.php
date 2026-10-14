<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('purchase_debit_notes', 'currency_id')) {
            return;
        }

        // Historical rows remain unresolved until their currency is provable.
        // Never infer currency from the tenant's current default.
        Schema::table('purchase_debit_notes', function (Blueprint $table): void {
            $table->unsignedBigInteger('currency_id')->nullable();
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('purchase_debit_notes', 'currency_id')) {
            return;
        }

        Schema::table('purchase_debit_notes', function (Blueprint $table): void {
            $table->dropColumn('currency_id');
        });
    }
};
