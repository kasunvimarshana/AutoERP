<?php

namespace Modules\Returns\Application\Contracts;

use Modules\Returns\Application\DTOs\CreditMemoData;
use Modules\Returns\Domain\Entities\CreditMemo;

interface CreateCreditMemoServiceInterface
{
    public function execute(CreditMemoData $data): CreditMemo;
}
