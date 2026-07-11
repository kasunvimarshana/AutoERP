<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const ACTIVE_IDENTITY_SLOT = 1;

    public function up(): void
    {
        Schema::table('payment_allocations', function (Blueprint $table): void {
            $table->dropUnique('payment_allocations_payment_invoice_uk');
        });

        Schema::table('payment_allocations', function (Blueprint $table): void {
            $table->unsignedTinyInteger('active_identity_slot')
                ->nullable()
                ->default(self::ACTIVE_IDENTITY_SLOT)
                ->after('invoice_id');
        });

        Schema::table('payment_allocations', function (Blueprint $table): void {
            $table->unique(
                ['payment_id', 'invoice_id', 'active_identity_slot'],
                'payment_allocations_payment_invoice_active_uk',
            );
        });
    }

    public function down(): void
    {
        Schema::table('payment_allocations', function (Blueprint $table): void {
            $table->dropUnique('payment_allocations_payment_invoice_active_uk');
        });

        Schema::table('payment_allocations', function (Blueprint $table): void {
            $table->dropColumn('active_identity_slot');
        });

        Schema::table('payment_allocations', function (Blueprint $table): void {
            $table->unique(['payment_id', 'invoice_id'], 'payment_allocations_payment_invoice_uk');
        });
    }
};
