<?php

namespace Modules\Helpdesk\Application\UseCases;

use Illuminate\Support\Facades\DB;
use Modules\Helpdesk\Domain\Contracts\TicketCategoryRepositoryInterface;

class CreateTicketCategoryUseCase
{
    public function __construct(
        private TicketCategoryRepositoryInterface $categoryRepo,
    ) {}

    public function execute(array $data): object
    {
        return DB::transaction(function () use ($data) {
            return $this->categoryRepo->create([
                'tenant_id'   => $data['tenant_id'],
                'name'        => $data['name'],
                'description' => $data['description'] ?? null,
                'is_active'   => true,
            ]);
        });
    }
}
