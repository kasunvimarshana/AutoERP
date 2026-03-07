<?php

namespace App\Services;

use App\Jobs\DeliverWebhookJob;
use App\Models\Webhook;
use App\Repositories\WebhookRepository;
use Illuminate\Support\Str;

class WebhookService extends BaseService
{
    public function __construct(protected WebhookRepository $repository)
    {
        parent::__construct($repository);
    }

    public function registerWebhook(array $data): Webhook
    {
        $data['secret'] = $data['secret'] ?? Str::random(40);
        $data['status'] = 'active';

        return $this->repository->create($data);
    }

    public function updateWebhook(int $id, array $data): Webhook
    {
        return $this->repository->update($id, $data);
    }

    public function deleteWebhook(int $id): bool
    {
        return $this->repository->delete($id);
    }

    /**
     * Dispatch webhook delivery jobs for all active subscribers of an event.
     */
    public function dispatchWebhook(string $event, array $payload, ?int $tenantId = null): void
    {
        if (!$tenantId) {
            $tenantId = app()->bound('current_tenant') ? app('current_tenant')->id : null;
        }

        if (!$tenantId) {
            return;
        }

        $webhooks = $this->repository->getActiveForEvent($event, $tenantId);

        foreach ($webhooks as $webhook) {
            DeliverWebhookJob::dispatch($webhook, $event, $payload)
                ->onQueue('webhooks');
        }
    }

    /**
     * Build the HMAC signature for a webhook payload.
     */
    public function buildSignature(string $secret, array $payload): string
    {
        return 'sha256=' . hash_hmac('sha256', json_encode($payload), $secret);
    }

    /**
     * Verify an incoming webhook signature.
     */
    public function verifySignature(string $secret, array $payload, string $signature): bool
    {
        $expected = $this->buildSignature($secret, $payload);
        return hash_equals($expected, $signature);
    }
}
