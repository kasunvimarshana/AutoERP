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

final class ActivateTenantPlanService
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
            $existing = $this->plans->findById($id);
            if ($existing === null) {
                return Result::failure(new Error(TenantErrorCode::NOT_FOUND, 'Tenant plan not found.'));
            }
            if ((int) $existing->require('row_version') !== $expectedVersion) {
                return Result::failure(new Error(
                    TenantErrorCode::VERSION_CONFLICT,
                    'Tenant plan changed since it was loaded. Refresh and try again.',
                ));
            }
            if ((bool) $existing->get('is_active')) {
                return Result::success($existing);
            }

            /** @var DataRecord|null $updated */
            $updated = $this->transactions->runInTransaction(function () use ($id, $expectedVersion, $existing): ?DataRecord {
                $updated = $this->plans->updateWithVersion($id, $expectedVersion, [
                    'is_active' => true,
                    'updated_by' => $this->currentUser->currentUserId(),
                ]);
                if ($updated === null) {
                    return null;
                }

                $this->audit->recordPlatform(new AuditEventData(
                    eventName: 'tenant.plan.activated',
                    eventCategory: AuditEventCategory::ADMINISTRATION,
                    sourceModule: 'tenant',
                    subjectType: 'tenant_plan',
                    subjectId: (string) $updated->id(),
                    subjectReference: (string) $updated->get('slug'),
                    changes: [
                        'old' => ['is_active' => (bool) $existing->get('is_active')],
                        'new' => ['is_active' => true],
                    ],
                    tags: ['tenant', 'plan', 'platform'],
                ));

                return $updated;
            });

            return $updated === null
                ? Result::failure(new Error(
                    TenantErrorCode::VERSION_CONFLICT,
                    'Tenant plan changed since it was loaded. Refresh and try again.',
                ))
                : Result::success($updated);
        } catch (Throwable $exception) {
            return Result::failure($this->errors->normalize(
                $exception,
                TenantErrorCode::INVALID_VALUE,
                ['operation' => 'tenant.plan.activate', 'plan_id' => (string) $id],
            ));
        }
    }
}
