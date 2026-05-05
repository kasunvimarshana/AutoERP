<?php

declare(strict_types=1);

namespace Tests\Unit\Asset;

use Modules\Asset\Application\Services\SyncAssetAvailabilityService;
use Modules\Asset\Domain\Entities\AssetAvailabilityState;
use Modules\Asset\Domain\RepositoryInterfaces\AssetAvailabilityStateRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\TestCase;

class SyncAssetAvailabilityServiceTest extends TestCase
{
    /** @var AssetAvailabilityStateRepositoryInterface&MockObject */
    private AssetAvailabilityStateRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->createMock(AssetAvailabilityStateRepositoryInterface::class);
    }

    public function test_execute_updates_state_and_appends_transition_event(): void
    {
        $service = new SyncAssetAvailabilityService($this->repository);

        $current = new AssetAvailabilityState(
            id: 11,
            tenantId: 9,
            orgUnitId: 4,
            assetId: 88,
            availabilityStatus: 'reserved',
            rowVersion: 2,
        );

        $saved = new AssetAvailabilityState(
            id: 11,
            tenantId: 9,
            orgUnitId: 4,
            assetId: 88,
            availabilityStatus: 'rented',
            rowVersion: 3,
        );

        $this->repository
            ->expects($this->once())
            ->method('findAssetUsageProfile')
            ->with(9, 88)
            ->willReturn('dual_use');

        $this->repository
            ->expects($this->once())
            ->method('findCurrentState')
            ->with(9, 88)
            ->willReturn($current);

        $this->repository
            ->expects($this->once())
            ->method('saveCurrentState')
            ->with($this->callback(function (mixed $state): bool {
                if (! $state instanceof AssetAvailabilityState) {
                    return false;
                }

                return $state->getTenantId() === 9
                    && $state->getAssetId() === 88
                    && $state->getAvailabilityStatus() === 'rented'
                    && $state->getRowVersion() === 3;
            }))
            ->willReturn($saved);

        $this->repository
            ->expects($this->once())
            ->method('appendTransitionEvent')
            ->with(
                9,
                4,
                88,
                'reserved',
                'rented',
                'rental_started',
                'rental_booking',
                101,
                22,
                null,
            );

        $result = $service->execute([
            'tenant_id' => 9,
            'org_unit_id' => 4,
            'asset_id' => 88,
            'target_status' => 'rented',
            'reason_code' => 'rental_started',
            'source_type' => 'rental_booking',
            'source_id' => 101,
            'changed_by' => 22,
        ]);

        $this->assertSame('rented', $result->getAvailabilityStatus());
        $this->assertSame(3, $result->getRowVersion());
    }

    public function test_execute_throws_when_usage_profile_disallows_target_status(): void
    {
        $service = new SyncAssetAvailabilityService($this->repository);

        $this->repository
            ->expects($this->once())
            ->method('findAssetUsageProfile')
            ->with(9, 88)
            ->willReturn('service_only');

        $this->repository
            ->expects($this->never())
            ->method('saveCurrentState');

        $this->expectException(\InvalidArgumentException::class);

        $service->execute([
            'tenant_id' => 9,
            'asset_id' => 88,
            'target_status' => 'reserved',
        ]);
    }
}
