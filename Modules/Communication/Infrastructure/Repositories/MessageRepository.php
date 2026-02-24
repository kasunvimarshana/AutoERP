<?php

namespace Modules\Communication\Infrastructure\Repositories;

use Modules\Communication\Domain\Contracts\MessageRepositoryInterface;
use Modules\Communication\Infrastructure\Models\MessageModel;

class MessageRepository implements MessageRepositoryInterface
{
    public function findById(string $id): ?object
    {
        return MessageModel::find($id);
    }

    public function findByChannel(string $channelId, int $limit = 50): iterable
    {
        return MessageModel::where('channel_id', $channelId)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }

    public function create(array $data): object
    {
        return MessageModel::create($data);
    }
}
