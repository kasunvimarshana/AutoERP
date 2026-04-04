<?php

namespace Modules\Returns\Application\Contracts;

use Modules\Returns\Domain\Entities\ReturnAuthorization;

interface CancelReturnAuthorizationServiceInterface
{
    public function execute(ReturnAuthorization $rma): ReturnAuthorization;
}
