<?php

namespace Modules\Returns\Application\Contracts;

use Modules\Returns\Domain\Entities\CreditMemo;

interface IssueCreditMemoDirectServiceInterface
{
    public function execute(CreditMemo $memo, int $issuedBy): CreditMemo;
}
