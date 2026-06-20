<?php

declare(strict_types=1);

namespace Modules\Reporting\Services;

use Illuminate\Auth\Access\AuthorizationException;
use Modules\User\Services\UserAccessResolver;

final class ReportingAuthorizationService
{
    public const REPORTS_VIEW = 'reporting.reports.view';
    public const REPORTS_EXPORT = 'reporting.reports.export';

    public function __construct(private readonly UserAccessResolver $access) {}

    /**
     * @return array<string, string>
     */
    public static function descriptions(): array
    {
        return [
            self::REPORTS_VIEW => 'View and run reporting module reports.',
            self::REPORTS_EXPORT => 'Export reporting module reports to HTML, print, PDF, Excel, or CSV.',
        ];
    }

    public function assert(?int $userId, int $tenantId, string $permission): void
    {
        if ($userId === null || ! $this->access->can($userId, $tenantId, $permission)) {
            throw new AuthorizationException('This Reporting action requires permission: '.$permission);
        }
    }
}
