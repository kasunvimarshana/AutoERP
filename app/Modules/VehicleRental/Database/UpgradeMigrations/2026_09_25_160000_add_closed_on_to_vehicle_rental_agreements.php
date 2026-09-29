<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Configuration\Contracts\ConfigurationResolverInterface;
use Modules\VehicleRental\Constants\RentalConfiguration;

return new class extends Migration
{
    private const TABLES = [
        'vehicle_rental_customer_agreements',
        'vehicle_rental_owner_agreements',
    ];

    public function up(): void
    {
        foreach (self::TABLES as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->date('closed_on')->nullable()->after('closed_at');
            });
        }

        /** @var ConfigurationResolverInterface $configuration */
        $configuration = app(ConfigurationResolverInterface::class);
        $timezones = [];

        foreach (self::TABLES as $tableName) {
            DB::table($tableName)
                ->select(['id', 'tenant_id', 'organization_unit_id', 'closed_at'])
                ->whereNotNull('closed_at')
                ->whereNull('closed_on')
                ->orderBy('id')
                ->chunkById(500, function ($agreements) use ($configuration, &$timezones, $tableName): void {
                    foreach ($agreements as $agreement) {
                        $tenantId = (int) $agreement->tenant_id;
                        $organizationUnitId = (int) $agreement->organization_unit_id;
                        $cacheKey = $tenantId.':'.$organizationUnitId;
                        $timezone = $timezones[$cacheKey] ??= (string) $configuration->value(
                            RentalConfiguration::WORKSPACE_TIMEZONE,
                            $tenantId,
                            $organizationUnitId,
                        );
                        $closedOn = CarbonImmutable::parse((string) $agreement->closed_at, 'UTC')
                            ->setTimezone($timezone)
                            ->toDateString();

                        DB::table($tableName)
                            ->where('id', (int) $agreement->id)
                            ->update(['closed_on' => $closedOn]);
                    }
                });
        }
    }

    public function down(): void
    {
        foreach (self::TABLES as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->dropColumn('closed_on');
            });
        }
    }
};
