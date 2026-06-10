<?php

declare(strict_types=1);

namespace Modules\Reporting\Services;

use Modules\Reporting\DTOs\ReportDefinition;

final class ReportTemplateResolver
{
    public function resolve(ReportDefinition $definition): string
    {
        return match ($definition->group) {
            'Inventory' => 'reports.inventory.report',
            'Purchase' => 'reports.purchase.report',
            'Finance', 'Invoice & Payment' => 'reports.finance.report',
            'Vehicle Service' => 'reports.vehicle-service.report',
            'Masters' => 'reports.master-data.report',
            default => 'reports.shared.report',
        };
    }
}
