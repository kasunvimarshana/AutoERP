<?php

namespace Modules\Returns\Application\Contracts;

use Modules\Returns\Domain\Entities\ReturnAuthorization;

interface ApproveReturnAuthorizationServiceInterface
{
    public function execute(ReturnAuthorization $rma, int $approvedBy): ReturnAuthorization;
}
