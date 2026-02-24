<?php
namespace Modules\CRM\Application\UseCases;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Modules\CRM\Domain\Contracts\LeadRepositoryInterface;
use Modules\CRM\Domain\Events\LeadCreated;
class CreateLeadUseCase
{
    public function __construct(private LeadRepositoryInterface $repo) {}
    public function execute(array $data): object
    {
        return DB::transaction(function () use ($data) {
            $lead = $this->repo->create($data);
            Event::dispatch(new LeadCreated($lead->id));
            return $lead;
        });
    }
}
