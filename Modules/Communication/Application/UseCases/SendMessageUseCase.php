<?php

namespace Modules\Communication\Application\UseCases;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Modules\Communication\Domain\Contracts\MessageRepositoryInterface;
use Modules\Communication\Domain\Events\MessageSent;

class SendMessageUseCase
{
    public function __construct(
        private MessageRepositoryInterface $messageRepo,
    ) {}

    public function execute(array $data): object
    {
        return DB::transaction(function () use ($data) {
            $message = $this->messageRepo->create([
                'tenant_id'  => $data['tenant_id'],
                'channel_id' => $data['channel_id'],
                'sender_id'  => $data['sender_id'],
                'body'       => $data['body'],
                'type'       => $data['type'] ?? 'text',
            ]);

            Event::dispatch(new MessageSent(
                $message->id,
                $message->tenant_id,
                $message->channel_id,
                $message->sender_id,
            ));

            return $message;
        });
    }
}
