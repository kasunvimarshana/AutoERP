<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TABLE = 'payment_allocations';

    public function up(): void
    {
        if (! Schema::hasColumn(self::TABLE, 'invoice_exchange_rate_snapshot')) {
            Schema::table(self::TABLE, function (Blueprint $table): void {
                $table->decimal('invoice_exchange_rate_snapshot', 20, 6)->nullable();
            });
        }

        DB::table(self::TABLE)
            ->orderBy('id')
            ->chunkById(250, function ($rows): void {
                foreach ($rows as $row) {
                    $rate = DB::table('invoices')
                        ->where('id', (int) $row->invoice_id)
                        ->value('exchange_rate');
                    if ($rate === null) {
                        throw new RuntimeException('Cannot backfill payment allocation invoice exchange rate snapshot.');
                    }

                    DB::table(self::TABLE)
                        ->where('id', (int) $row->id)
                        ->update(['invoice_exchange_rate_snapshot' => (string) $rate]);
                }
            });
    }

    public function down(): void
    {
        if (! Schema::hasColumn(self::TABLE, 'invoice_exchange_rate_snapshot')) {
            return;
        }

        Schema::table(self::TABLE, function (Blueprint $table): void {
            $table->dropColumn('invoice_exchange_rate_snapshot');
        });
    }
};
