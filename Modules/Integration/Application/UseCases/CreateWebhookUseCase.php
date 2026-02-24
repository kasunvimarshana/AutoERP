<?php

namespace Modules\Integration\Application\UseCases;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Modules\Integration\Domain\Contracts\WebhookRepositoryInterface;
use Modules\Integration\Domain\Events\WebhookCreated;

class CreateWebhookUseCase
{
    public function __construct(
        private WebhookRepositoryInterface $webhookRepo,
    ) {}

    public function execute(array $data): object
    {
        return DB::transaction(function () use ($data) {
            $webhook = $this->webhookRepo->create([
                'tenant_id'     => $data['tenant_id'],
                'name'          => $data['name'],
                'url'           => $data['url'],
                'events'        => $data['events'] ?? [],
                'signing_secret'=> $data['signing_secret'] ?? null,
                'is_active'     => true,
            ]);

            Event::dispatch(new WebhookCreated(
                $webhook->id,
                $webhook->tenant_id,
                $webhook->name,
                $webhook->url,
            ));

            return $webhook;
        });
    }
}
