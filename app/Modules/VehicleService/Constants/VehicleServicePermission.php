<?php

declare(strict_types=1);

namespace Modules\VehicleService\Constants;

final class VehicleServicePermission
{
    public const JOBS_VIEW = 'vehicle_service.jobs.view';
    public const JOBS_CREATE = 'vehicle_service.jobs.create';
    public const JOBS_UPDATE = 'vehicle_service.jobs.update';
    public const JOBS_TRANSITION = 'vehicle_service.jobs.transition';
    public const LINES_VIEW = 'vehicle_service.lines.view';
    public const LINES_MANAGE = 'vehicle_service.lines.manage';
    public const WORKFORCE_VIEW = 'vehicle_service.workforce.view';
    public const WORKFORCE_MANAGE = 'vehicle_service.workforce.manage';
    public const COMMISSIONS_VIEW = 'vehicle_service.commissions.view';
    public const COMMISSIONS_MANAGE = 'vehicle_service.commissions.manage';
    public const INVENTORY_VIEW = 'vehicle_service.inventory.view';
    public const INVENTORY_ISSUE = 'vehicle_service.inventory.issue';
    public const INVOICES_VIEW = 'vehicle_service.invoices.view';
    public const INVOICES_CREATE = 'vehicle_service.invoices.create';
    public const PAYMENTS_VIEW = 'vehicle_service.payments.view';
    public const PAYMENTS_CREATE = 'vehicle_service.payments.create';
    public const DOCUMENTS_VIEW = 'vehicle_service.documents.view';
    public const DOCUMENTS_MANAGE = 'vehicle_service.documents.manage';

    public static function descriptions(): array
    {
        return [
            self::JOBS_VIEW => 'View vehicle service jobs and status history.',
            self::JOBS_CREATE => 'Create vehicle service jobs.',
            self::JOBS_UPDATE => 'Update draft or active vehicle service jobs.',
            self::JOBS_TRANSITION => 'Inspect, start, complete, or cancel vehicle service jobs.',
            self::LINES_VIEW => 'View service job lines.',
            self::LINES_MANAGE => 'Create, update, and remove service job lines.',
            self::WORKFORCE_VIEW => 'View service workforce assignments.',
            self::WORKFORCE_MANAGE => 'Manage service workforce assignments.',
            self::COMMISSIONS_VIEW => 'View Vehicle Service commission defaults.',
            self::COMMISSIONS_MANAGE => 'Manage Vehicle Service commission defaults.',
            self::INVENTORY_VIEW => 'View inventory usage for service jobs.',
            self::INVENTORY_ISSUE => 'Issue inventory to service jobs.',
            self::INVOICES_VIEW => 'Preview and view service invoices.',
            self::INVOICES_CREATE => 'Create service invoices.',
            self::PAYMENTS_VIEW => 'View service payment options and previews.',
            self::PAYMENTS_CREATE => 'Create and post service receipts.',
            self::DOCUMENTS_VIEW => 'View and download service documents.',
            self::DOCUMENTS_MANAGE => 'Upload and remove service documents.',
        ];
    }
}
