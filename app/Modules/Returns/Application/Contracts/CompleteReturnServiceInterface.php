<?php
declare(strict_types=1);
namespace Modules\Returns\Application\Contracts;

use Modules\Returns\Domain\Entities\ReturnRequest;

interface CompleteReturnServiceInterface
{
    public function execute(int $returnRequestId): ReturnRequest;
}
