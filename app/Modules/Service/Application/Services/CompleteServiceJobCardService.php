<?php

declare(strict_types=1);

namespace Modules\Service\Application\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Modules\Service\Application\Contracts\CompleteServiceJobCardServiceInterface;
use Modules\Service\Domain\Entities\ServiceJobCard;
use Modules\Service\Domain\Events\ServiceJobCardCompleted;
use Modules\Service\Domain\Exceptions\ServiceJobCardNotFoundException;
use Modules\Service\Domain\RepositoryInterfaces\ServiceJobCardRepositoryInterface;

class CompleteServiceJobCardService implements CompleteServiceJobCardServiceInterface
{
    public function __construct(
        private readonly ServiceJobCardRepositoryInterface $jobCardRepository,
    ) {}

    public function execute(int $tenantId, int $id): ServiceJobCard
    {
        $jobCard = $this->jobCardRepository->findById($tenantId, $id);
        if ($jobCard === null) {
            throw new ServiceJobCardNotFoundException($id);
        }

        $jobCard->complete();

        $saved = DB::transaction(fn (): ServiceJobCard => $this->jobCardRepository->save($jobCard));

        Event::dispatch(new ServiceJobCardCompleted($saved));

        return $saved;
    }
}
