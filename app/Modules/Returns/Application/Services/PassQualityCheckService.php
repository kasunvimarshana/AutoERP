<?php

namespace Modules\Returns\Application\Services;

use Illuminate\Support\Facades\Event;
use Modules\Returns\Application\Contracts\PassQualityCheckServiceInterface;
use Modules\Returns\Domain\Entities\StockReturnLine;
use Modules\Returns\Domain\Events\ReturnLineQualityCheckPassed;
use Modules\Returns\Domain\RepositoryInterfaces\StockReturnLineRepositoryInterface;
use Modules\Returns\Domain\RepositoryInterfaces\StockReturnRepositoryInterface;
use Modules\Returns\Domain\ValueObjects\QualityCheckResult;

class PassQualityCheckService implements PassQualityCheckServiceInterface
{
    public function __construct(
        private readonly StockReturnLineRepositoryInterface $lineRepository,
        private readonly StockReturnRepositoryInterface $returnRepository,
    ) {}

    public function execute(StockReturnLine $line, int $checkedBy): StockReturnLine
    {
        $updated = $this->lineRepository->update($line, [
            'quality_check_result' => QualityCheckResult::PASS,
            'quality_checked_by'   => $checkedBy,
            'quality_checked_at'   => now(),
        ]);

        $stockReturn = $this->returnRepository->findById($updated->stockReturnId);
        if (!$stockReturn) {
            throw new \DomainException("Stock return not found for line {$updated->id}.");
        }

        Event::dispatch(new ReturnLineQualityCheckPassed($stockReturn->tenantId, $updated->id));

        return $updated;
    }
}
