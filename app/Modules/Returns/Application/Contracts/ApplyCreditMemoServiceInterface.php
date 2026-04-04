<?php

namespace Modules\Returns\Application\Contracts;

use Modules\Returns\Domain\Entities\CreditMemo;

interface ApplyCreditMemoServiceInterface
{
    public function execute(CreditMemo $memo): CreditMemo;
}
