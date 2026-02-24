<?php

namespace Modules\POS\Infrastructure\Repositories;

use Modules\POS\Domain\Contracts\LoyaltyProgramRepositoryInterface;
use Modules\POS\Infrastructure\Models\LoyaltyCardModel;
use Modules\POS\Infrastructure\Models\LoyaltyProgramModel;
use Modules\POS\Infrastructure\Models\LoyaltyTransactionModel;

class LoyaltyProgramRepository implements LoyaltyProgramRepositoryInterface
{
    public function findById(string $id): ?object
    {
        return LoyaltyProgramModel::find($id);
    }

    public function findActiveByTenant(string $tenantId): ?object
    {
        return LoyaltyProgramModel::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->first();
    }

    public function create(array $data): object
    {
        return LoyaltyProgramModel::create($data);
    }

    public function update(string $id, array $data): object
    {
        $program = LoyaltyProgramModel::findOrFail($id);
        $program->update($data);
        return $program->fresh();
    }

    public function paginate(string $tenantId, int $perPage = 20): object
    {
        return LoyaltyProgramModel::where('tenant_id', $tenantId)
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    public function delete(string $id): void
    {
        LoyaltyProgramModel::findOrFail($id)->delete();
    }

    public function findCardById(string $cardId): ?object
    {
        return LoyaltyCardModel::find($cardId);
    }

    public function findCardByCustomer(string $tenantId, string $customerId, string $programId): ?object
    {
        return LoyaltyCardModel::where('tenant_id', $tenantId)
            ->where('customer_id', $customerId)
            ->where('program_id', $programId)
            ->first();
    }

    public function createCard(array $data): object
    {
        return LoyaltyCardModel::create($data);
    }

    public function updateCard(string $cardId, array $data): object
    {
        $card = LoyaltyCardModel::findOrFail($cardId);
        $card->update($data);
        return $card->fresh();
    }

    public function paginateCards(string $tenantId, array $filters = [], int $perPage = 20): object
    {
        $query = LoyaltyCardModel::where('tenant_id', $tenantId)
            ->orderByDesc('updated_at');

        if (! empty($filters['program_id'])) {
            $query->where('program_id', $filters['program_id']);
        }

        if (! empty($filters['customer_id'])) {
            $query->where('customer_id', $filters['customer_id']);
        }

        return $query->paginate($perPage);
    }

    public function createTransaction(array $data): object
    {
        return LoyaltyTransactionModel::create($data);
    }
}
