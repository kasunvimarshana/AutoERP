<?php

namespace Modules\AssetManagement\Application\UseCases;

use Illuminate\Support\Facades\DB;
use Modules\AssetManagement\Domain\Contracts\AssetCategoryRepositoryInterface;

class CreateAssetCategoryUseCase
{
    public function __construct(
        private AssetCategoryRepositoryInterface $categoryRepo,
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
