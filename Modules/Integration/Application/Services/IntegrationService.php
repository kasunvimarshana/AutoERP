<?php

declare(strict_types=1);

namespace Modules\Integration\Application\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Core\Domain\Contracts\ServiceContract;
use Modules\Integration\Application\DTOs\RegisterWebhookDTO;
use Modules\Integration\Domain\Contracts\IntegrationRepositoryContract;
use Modules\Integration\Domain\Entities\IntegrationLog;
use Modules\Integration\Domain\Entities\WebhookDelivery;
use Modules\Integration\Domain\Entities\WebhookEndpoint;

/**
 * Integration service.
 *
 * Orchestrates webhook registration, dispatch scheduling, and integration log retrieval.
 */
class IntegrationService implements ServiceContract
{
    public function __construct(
        private readonly IntegrationRepositoryContract $repository,
    ) {}

    /**
     * List all registered webhook endpoints for the current tenant.
     */
    public function listWebhooks(): Collection
    {
        return $this->repository->all();
    }

    /**
     * Register a new webhook endpoint.
     */
    public function registerWebhook(RegisterWebhookDTO $dto): WebhookEndpoint
    {
        return DB::transaction(function () use ($dto): WebhookEndpoint {
            /** @var WebhookEndpoint $endpoint */
            $endpoint = $this->repository->create([
                'name'      => $dto->name,
                'url'       => $dto->url,
                'events'    => $dto->events,
                'secret'    => $dto->secret,
                'headers'   => $dto->headers,
                'is_active' => true,
            ]);

            return $endpoint;
        });
    }

    /**
     * Update an existing webhook endpoint.
     *
     * @param array<string, mixed> $data
     */
    public function updateWebhook(int $id, array $data): WebhookEndpoint
    {
        return DB::transaction(function () use ($id, $data): WebhookEndpoint {
            /** @var WebhookEndpoint $endpoint */
            $endpoint = $this->repository->update($id, $data);

            return $endpoint;
        });
    }

    /**
     * Delete a webhook endpoint by ID.
     */
    public function deleteWebhook(int $id): void
    {
        DB::transaction(function () use ($id): void {
            $this->repository->delete($id);
        });
    }

    /**
     * Create a pending webhook delivery for the given endpoint and event.
     *
     * @param array<string, mixed> $payload
     */
    public function dispatchWebhook(int $endpointId, string $eventName, array $payload): WebhookDelivery
    {
        return DB::transaction(function () use ($endpointId, $eventName, $payload): WebhookDelivery {
            /** @var WebhookEndpoint $endpoint */
            $endpoint = $this->repository->findOrFail($endpointId);

            /** @var WebhookDelivery $delivery */
            $delivery = WebhookDelivery::create([
                'tenant_id'           => $endpoint->tenant_id,
                'webhook_endpoint_id' => $endpoint->id,
                'event_name'          => $eventName,
                'payload'             => $payload,
                'status'              => 'pending',
                'attempt_count'       => 0,
            ]);

            return $delivery;
        });
    }

    /**
     * List all integration log entries for the current tenant.
     *
     * Uses the model's query builder so the HasTenant global scope
     * enforces tenant isolation on every call.
     */
    public function listIntegrationLogs(): Collection
    {
        return IntegrationLog::query()->get();
    }

    /**
     * Show a single webhook endpoint by ID.
     */
    public function showWebhook(int|string $id): \Illuminate\Database\Eloquent\Model
    {
        return $this->repository->findOrFail($id);
    }

    /**
     * List all webhook deliveries.
     */
    public function listDeliveries(): Collection
    {
        return $this->repository->allDeliveries();
    }
}
