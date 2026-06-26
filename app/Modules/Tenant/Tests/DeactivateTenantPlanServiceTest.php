<?php

declare(strict_types=1);

namespace Modules\Tenant\Tests;

use Modules\Audit\Contracts\AuditRecorderInterface;
use Modules\Core\Contracts\CurrentUserContextAccessorInterface;
use Modules\Core\Contracts\ErrorNormalizerInterface;
use Modules\Core\Contracts\TransactionManagerInterface;
use Modules\Core\DTOs\DataRecord;
use Modules\Tenant\Repositories\TenantPlanRepositoryInterface;
use Modules\Tenant\Services\Plans\DeactivateTenantPlanService;
use PHPUnit\Framework\TestCase;

final class DeactivateTenantPlanServiceTest extends TestCase
{
    public function test_deactivation_blocks_new_assignments_without_requiring_existing_assignments_to_be_removed(): void
    {
        $existing = new DataRecord([
            'id' => 5,
            'slug' => 'growth',
            'is_active' => true,
            'row_version' => 3,
        ]);
        $updated = new DataRecord([
            'id' => 5,
            'slug' => 'growth',
            'is_active' => false,
            'row_version' => 4,
        ]);

        $plans = $this->createMock(TenantPlanRepositoryInterface::class);
        $plans->expects(self::once())->method('findById')->with(5, true)->willReturn($existing);
        $plans->expects(self::never())->method('hasCurrentAssignments');
        $plans->expects(self::once())->method('updateWithVersion')->with(5, 3, [
            'is_active' => false,
            'updated_by' => 9,
        ])->willReturn($updated);

        $currentUser = $this->createMock(CurrentUserContextAccessorInterface::class);
        $currentUser->method('currentUserId')->willReturn(9);

        $transactions = $this->createMock(TransactionManagerInterface::class);
        $transactions->method('runInTransaction')->willReturnCallback(static fn (callable $callback): mixed => $callback());

        $audit = $this->createMock(AuditRecorderInterface::class);
        $audit->expects(self::once())->method('recordPlatform');

        $service = new DeactivateTenantPlanService(
            $plans,
            $currentUser,
            $audit,
            $transactions,
            $this->createMock(ErrorNormalizerInterface::class),
        );

        $result = $service->execute(5, 3);

        self::assertTrue($result->isSuccess());
        self::assertSame($updated, $result->valueOrFail());
    }
}
