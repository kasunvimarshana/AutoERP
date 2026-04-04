<?php

namespace Modules\Returns\Application\Contracts;

use Modules\Returns\Application\DTOs\ReturnAuthorizationData;
use Modules\Returns\Domain\Entities\ReturnAuthorization;

interface CreateReturnAuthorizationServiceInterface
{
    public function execute(ReturnAuthorizationData $data): ReturnAuthorization;
}
