<?php

namespace Modules\Communication\Domain\Contracts;

interface MessageRepositoryInterface
{
    public function findById(string $id): ?object;
    public function findByChannel(string $channelId, int $limit = 50): iterable;
    public function create(array $data): object;
}
