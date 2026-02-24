<?php

namespace Modules\POS\Domain\Contracts;

interface LoyaltyProgramRepositoryInterface
{
    public function findById(string $id): ?object;

    public function findActiveByTenant(string $tenantId): ?object;

    public function create(array $data): object;

    public function update(string $id, array $data): object;

    public function paginate(string $tenantId, int $perPage = 20): object;

    // Loyalty card operations

    public function findCardById(string $cardId): ?object;

    public function findCardByCustomer(string $tenantId, string $customerId, string $programId): ?object;

    public function createCard(array $data): object;

    public function updateCard(string $cardId, array $data): object;

    public function paginateCards(string $tenantId, array $filters = [], int $perPage = 20): object;

    public function delete(string $id): void;

    // Transaction operations

    public function createTransaction(array $data): object;
}
