<?php
declare(strict_types=1);
namespace Modules\Returns\Application\Contracts;
use Modules\Returns\Domain\Entities\ReturnRequest;
interface ProcessReturnServiceInterface {
    public function approve(int $id, int $processedBy): ReturnRequest;
    public function reject(int $id, int $processedBy): ReturnRequest;
}
