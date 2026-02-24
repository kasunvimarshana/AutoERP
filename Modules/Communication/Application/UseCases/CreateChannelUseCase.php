<?php

namespace Modules\Communication\Application\UseCases;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Modules\Communication\Domain\Contracts\ChannelRepositoryInterface;
use Modules\Communication\Domain\Events\ChannelCreated;

class CreateChannelUseCase
{
    public function __construct(
        private ChannelRepositoryInterface $channelRepo,
    ) {}

    public function execute(array $data): object
    {
        return DB::transaction(function () use ($data) {
            $channel = $this->channelRepo->create([
                'tenant_id'   => $data['tenant_id'],
                'name'        => $data['name'],
                'description' => $data['description'] ?? null,
                'type'        => $data['type'] ?? 'channel',
                'created_by'  => $data['created_by'],
            ]);

            Event::dispatch(new ChannelCreated(
                $channel->id,
                $channel->tenant_id,
                $channel->name,
                $channel->type,
            ));

            return $channel;
        });
    }
}
