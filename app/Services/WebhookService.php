<?php

namespace App\Services;

use App\Enums\WebhookDeliveryStatus;
use App\Models\Webhook;
use App\Models\WebhookDelivery;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class WebhookService
{
    private const MAX_RESPONSE_BODY_LENGTH = 1000;

    public function paginate(string $tenantId, int $perPage = 15): LengthAwarePaginator
    {
        return Webhook::where('tenant_id', $tenantId)
            ->orderBy('name')
            ->paginate($perPage);
    }

    public function create(array $data): Webhook
    {
        return DB::transaction(function () use ($data) {
            return Webhook::create($data);
        });
    }

    public function update(string $id, array $data): Webhook
    {
        return DB::transaction(function () use ($id, $data) {
            $webhook = Webhook::findOrFail($id);
            $webhook->update($data);

            return $webhook->fresh();
        });
    }

    public function delete(string $id): void
    {
        $webhook = Webhook::findOrFail($id);
        $webhook->delete();
    }

    public function dispatch(string $tenantId, string $eventName, array $payload): void
    {
        $webhooks = Webhook::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->get()
            ->filter(fn (Webhook $wh) => in_array($eventName, $wh->events ?? [], true));

        foreach ($webhooks as $webhook) {
            $delivery = WebhookDelivery::create([
                'tenant_id' => $tenantId,
                'webhook_id' => $webhook->id,
                'event_name' => $eventName,
                'payload' => $payload,
                'status' => WebhookDeliveryStatus::Pending,
                'attempt_count' => 0,
            ]);

            try {
                $headers = array_merge(
                    ['Content-Type' => 'application/json', 'X-Webhook-Event' => $eventName],
                    $webhook->headers ?? []
                );

                $response = Http::timeout(10)->withHeaders($headers)->post($webhook->url, $payload);

                if ($response->successful()) {
                    $delivery->update([
                        'status' => WebhookDeliveryStatus::Success,
                        'response_status' => $response->status(),
                        'delivered_at' => now(),
                        'attempt_count' => 1,
                    ]);
                } else {
                    $delivery->update([
                        'status' => WebhookDeliveryStatus::Failed,
                        'response_status' => $response->status(),
                        'response_body' => substr($response->body(), 0, self::MAX_RESPONSE_BODY_LENGTH),
                        'attempt_count' => 1,
                    ]);
                }
            } catch (\Throwable $e) {
                $delivery->update([
                    'status' => WebhookDeliveryStatus::Failed,
                    'response_body' => substr($e->getMessage(), 0, self::MAX_RESPONSE_BODY_LENGTH),
                    'attempt_count' => 1,
                ]);
            }
        }
    }

    public function paginateDeliveries(string $tenantId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = WebhookDelivery::where('tenant_id', $tenantId)
            ->with(['webhook']);

        if (isset($filters['webhook_id'])) {
            $query->where('webhook_id', $filters['webhook_id']);
        }
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (isset($filters['event_name'])) {
            $query->where('event_name', $filters['event_name']);
        }

        return $query->orderByDesc('created_at')->paginate($perPage);
    }
}
