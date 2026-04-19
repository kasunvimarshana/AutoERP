<?php

declare(strict_types=1);

namespace Tests\Unit\Finance;

use Modules\Core\Domain\Exceptions\NotFoundException;
use Modules\Finance\Domain\Exceptions\FiscalPeriodNotFoundException;
use Tests\TestCase;

class FiscalPeriodNotFoundExceptionTest extends TestCase
{
    public function test_by_id_factory_uses_standard_not_found_contract(): void
    {
        $exception = FiscalPeriodNotFoundException::byId(77);

        $this->assertInstanceOf(NotFoundException::class, $exception);
        $this->assertSame("Fiscal period with id '77' not found", $exception->getMessage());
        $this->assertSame(404, $exception->getCode());
    }

    public function test_open_period_for_id_factory_uses_standard_not_found_contract(): void
    {
        $exception = FiscalPeriodNotFoundException::openPeriodForId(88);

        $this->assertInstanceOf(NotFoundException::class, $exception);
        $this->assertSame("Fiscal period with id '88' not found", $exception->getMessage());
        $this->assertSame(404, $exception->getCode());
    }
}
