<?php

declare(strict_types=1);

namespace Modules\Service\Application\Services;

use Illuminate\Support\Facades\DB;
use Modules\Service\Application\Contracts\UpdateServiceJobCardStatusServiceInterface;
use Modules\Service\Domain\Entities\ServiceJobCard;
use Modules\Service\Domain\Exceptions\ServiceJobCardNotFoundException;
use Modules\Service\Domain\RepositoryInterfaces\ServiceJobCardRepositoryInterface;

class UpdateServiceJobCardStatusService implements UpdateServiceJobCardStatusServiceInterface
{
    public function __construct(
        private readonly ServiceJobCardRepositoryInterface $jobCardRepository,
    ) {}

    public function execute(int $tenantId, int $id, string $status): ServiceJobCard
    {
        $jobCard = $this->jobCardRepository->findById($tenantId, $id);
        if ($jobCard === null) {
            throw new ServiceJobCardNotFoundException($id);
        }

        $jobCard->updateStatus($status);

        return DB::transaction(fn (): ServiceJobCard => $this->jobCardRepository->save($jobCard));
    }
}
