<?php

declare(strict_types=1);

namespace Modules\Tenant\Services\Plans;

use Modules\Audit\Constants\AuditEventCategory;
use Modules\Audit\Contracts\AuditRecorderInterface;
use Modules\Audit\Data\AuditEventData;
use Modules\Core\Contracts\CurrentUserContextAccessorInterface;
use Modules\Core\Contracts\ErrorNormalizerInterface;
use Modules\Core\Contracts\TransactionManagerInterface;
use Modules\Core\DTOs\DataRecord;
use Modules\Core\Results\Error;
use Modules\Core\Results\Result;
use Modules\Tenant\Constants\TenantErrorCode;
use Modules\Tenant\Repositories\TenantPlanRepositoryInterface;
use Throwable;

final class DeactivateTenantPlanService
{
    public function __construct(
        private readonly TenantPlanRepositoryInterface $plans,
        private readonly CurrentUserContextAccessorInterface $currentUser,
        private readonly AuditRecorderInterface $audit,
        private readonly TransactionManagerInterface $transactions,
        private readonly ErrorNormalizerInterface $errors,
    ) {}

    public function execute(int|string $id, int $expectedVersion): Result
    {
        try {
            /** @var array{status:string,record?:DataRecord} $outcome */
            $outcome = $this->transactions->runInTransaction(function () use ($id, $expectedVersion): array {
                $existing = $this->plans->findById($id, true);
                if ($existing === null) {
                    return ['status' => 'not_found'];
                }
                if ((int) $existing->require('row_version') !== $expectedVersion) {
                    return ['status' => 'version_conflict'];
                }
                if (! (bool) $existing->get('is_active')) {
                    return ['status' => 'success', 'record' => $existing];
                }
                if ($this->plans->hasCurrentAssignments($id)) {
                    return ['status' => 'assigned'];
                }

                $updated = $this->plans->updateWithVersion($id, $expectedVersion, [
                    'is_active' => false,
                    'updated_by' => $this->currentUser->currentUserId(),
                ]);
                if ($updated === null) {
                    return ['status' => 'version_conflict'];
                }

                $this->audit->recordPlatform(new AuditEventData(
                    eventName: 'tenant.plan.deactivated',
                    eventCategory: AuditEventCategory::ADMINISTRATION,
                    sourceModule: 'tenant',
                    subjectType: 'tenant_plan',
                    subjectId: (string) $updated->id(),
                    subjectReference: (string) $updated->get('slug'),
                    changes: [
                        'old' => ['is_active' => true],
                        'new' => ['is_active' => false],
                    ],
                    tags: ['tenant', 'plan', 'platform'],
                ));

                return ['status' => 'success', 'record' => $updated];
            });

            return match ($outcome['status']) {
                'success' => Result::success($outcome['record']),
                'not_found' => Result::failure(new Error(TenantErrorCode::NOT_FOUND, 'Tenant plan not found.')),
                'assigned' => Result::failure(new Error(
                    TenantErrorCode::CONFLICT,
                    'This plan has active tenant assignments. Move or cancel those subscriptions before deactivating it.',
                )),
                default => Result::failure(new Error(
                    TenantErrorCode::VERSION_CONFLICT,
                    'Tenant plan changed since it was loaded. Refresh and try again.',
                )),
            };
        } catch (Throwable $exception) {
            return Result::failure($this->errors->normalize(
                $exception,
                TenantErrorCode::INVALID_VALUE,
                ['operation' => 'tenant.plan.deactivate', 'plan_id' => (string) $id],
            ));
        }
    }
}
