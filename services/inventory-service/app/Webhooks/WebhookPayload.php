<?php

namespace App\Webhooks;

/**
 * Structured payload for outgoing webhooks.
 */
final class WebhookPayload
{
    public function __construct(
        public readonly string          $event,
        public readonly array           $data,
        public readonly string          $timestamp,
        public readonly string          $webhookId,
        public readonly int|string|null $tenantId = null,
    ) {}

    // -------------------------------------------------------------------------
    // Factory
    // -------------------------------------------------------------------------

    public static function create(
        string          $event,
        array           $data,
        int|string|null $tenantId = null,
    ): self {
        return new self(
            event:     $event,
            data:      $data,
            timestamp: now()->toIso8601String(),
            webhookId: \Illuminate\Support\Str::uuid()->toString(),
            tenantId:  $tenantId,
        );
    }

    // -------------------------------------------------------------------------
    // Serialization
    // -------------------------------------------------------------------------

    public function toArray(): array
    {
        return [
            'webhook_id' => $this->webhookId,
            'event'      => $this->event,
            'tenant_id'  => $this->tenantId,
            'timestamp'  => $this->timestamp,
            'data'       => $this->data,
        ];
    }

    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
    }

    // -------------------------------------------------------------------------
    // HMAC signature
    // -------------------------------------------------------------------------

    /**
     * Compute an HMAC-SHA256 signature over the JSON payload using the provided secret.
     * Consumers can verify: hash_equals(expected_sig, webhook_sig_header)
     */
    public function sign(string $secret): string
    {
        return hash_hmac('sha256', $this->toJson(), $secret);
    }
}
