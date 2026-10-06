<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Core\Services\DecimalMath;
use Modules\Core\Support\TenantExecutionContext;

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

        app(TenantExecutionContext::class)->runAsControlPlane(function (): void {
            DB::transaction(function (): void {
                $math = new DecimalMath();

                DB::table(self::TABLE)
                    ->orderBy('id')
                    ->chunkById(250, function ($rows) use ($math): void {
                        foreach ($rows as $row) {
                            $rate = DB::table('invoices')
                                ->where('tenant_id', (int) $row->tenant_id)
                                ->where('id', (int) $row->invoice_id)
                                ->value('exchange_rate');
                            if ($rate === null || $math->compare((string) $rate, '0.000000') <= 0) {
                                throw new RuntimeException(
                                    'Cannot backfill payment allocation invoice exchange rate snapshot from a positive invoice rate.',
                                );
                            }

                            DB::table(self::TABLE)
                                ->where('id', (int) $row->id)
                                ->where('tenant_id', (int) $row->tenant_id)
                                ->update([
                                    'invoice_exchange_rate_snapshot' => $math->normalize((string) $rate),
                                    'updated_at' => now(),
                                ]);
                        }
                    });

                if (DB::table(self::TABLE)->whereNull('invoice_exchange_rate_snapshot')->exists()) {
                    throw new RuntimeException('Payment allocation invoice exchange rate snapshot backfill is incomplete.');
                }
            }, 3);
        });

        Schema::table(self::TABLE, function (Blueprint $table): void {
            $table->decimal('invoice_exchange_rate_snapshot', 20, 6)->nullable(false)->change();
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
