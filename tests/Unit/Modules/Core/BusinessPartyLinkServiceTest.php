<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Core;

use Modules\Core\Application\Contracts\ClockInterface;
use Modules\Core\Application\Repositories\BusinessPartyLinkRepositoryInterface;
use Modules\Core\Application\Services\BusinessPartyLinkService;
use Tests\TestCase;

final class BusinessPartyLinkServiceTest extends TestCase
{
    public function testListUsesSourceAndTargetWhenBothFiltersProvided(): void
    {
        $repository = $this->createMock(BusinessPartyLinkRepositoryInterface::class);
        $repository->expects(self::once())
            ->method('listForSourceAndTarget')
            ->with(10, 'customer', 5, 'supplier', 7)
            ->willReturn([]);

        $clock = $this->createMock(ClockInterface::class);

        $service = new BusinessPartyLinkService($repository, $clock);
        $result = $service->list([
            'tenant_id' => 10,
            'source_party_type' => 'customer',
            'source_party_id' => 5,
            'target_party_type' => 'supplier',
            'target_party_id' => 7,
        ]);

        self::assertTrue($result->isSuccess());
        self::assertSame([], $result->value());
    }

    public function testListReturnsInvalidWhenNoSourceOrTargetFiltersAreProvided(): void
    {
        $repository = $this->createMock(BusinessPartyLinkRepositoryInterface::class);
        $clock = $this->createMock(ClockInterface::class);

        $service = new BusinessPartyLinkService($repository, $clock);
        $result = $service->list(['tenant_id' => 10]);

        self::assertFalse($result->isSuccess());
        self::assertSame('BUSINESS_PARTY_LINK_INVALID', $result->errorOrFail()->code);
    }
}
