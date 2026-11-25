<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Hr\Commands;

use Illuminate\Console\Command;
use Modules\Core\Models\Tenant;
use Modules\Hr\Services\AttendanceAnomalyService;

final class DetectMissingPunchesCommand extends Command
{
    protected $signature = 'hr:detect-missing-punches {--tenant= : Specific Tenant ID} {--date= : Attendance date to scan (defaults to yesterday)}';

    protected $description = 'Scan attendance records to detect missing in/out punches, unpaired breaks, and duration anomalies.';

    public function handle(AttendanceAnomalyService $anomalyService): int
    {
        $tenantIdOpt = $this->option('tenant');
        $dateOpt = $this->option('date');

        $tenantIds = $tenantIdOpt !== null
            ? [(int) $tenantIdOpt]
            : Tenant::query()->pluck('id')->all();

        $totalDetected = 0;

        foreach ($tenantIds as $tenantId) {
            $result = $anomalyService->scanAnomalies((int) $tenantId, $dateOpt !== null ? (string) $dateOpt : null);
            $totalDetected += $result['anomalies_detected'];

            $this->info("Tenant #{$tenantId}: scanned {$result['total_scanned']} records, detected {$result['anomalies_detected']} anomalies.");
        }

        $this->info("Complete: {$totalDetected} total anomalies detected and flagged for regularization.");

        return self::SUCCESS;
    }
}
