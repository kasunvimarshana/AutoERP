<?php

declare(strict_types=1);

namespace Modules\Accounting\Application\Contracts;

use Modules\Accounting\Domain\Entities\Refund;

interface CreateRefundServiceInterface
{
    public function execute(array $data): Refund;
}
