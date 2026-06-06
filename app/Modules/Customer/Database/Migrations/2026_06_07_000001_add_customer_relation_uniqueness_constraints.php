<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_bank_accounts', function (Blueprint $table): void {
            $table->unique(['customer_id', 'account_number'], 'customer_bank_accounts_customer_number_uk');
        });

    }

    public function down(): void
    {
        Schema::table('customer_bank_accounts', function (Blueprint $table): void {
            $table->dropUnique('customer_bank_accounts_customer_number_uk');
        });
    }
};
