<?php

declare(strict_types=1);

namespace App\Saga;

/**
 * Carries all data that Saga steps share during a transaction.
 *
 * Using a context object (instead of passing parameters individually)
 * keeps the SagaStepInterface clean and allows any step to store
 * intermediate results for use by downstream steps.
 *
 * Example flow:
 *   ReserveInventoryStep  → writes 'inventory_reserved' = true
 *   ProcessPaymentStep    → reads 'inventory_reserved', writes 'payment_id'
 *   ConfirmOrderStep      → reads 'payment_id'
 *   SendNotificationStep  → reads order details to compose message
 */
final class SagaContext
{
    /** @var array<string, mixed> */
    private array $data = [];

    public function __construct(array $initialData = [])
    {
        $this->data = $initialData;
    }

    /**
     * Store a value in the context by key.
     */
    public function set(string $key, mixed $value): void
    {
        $this->data[$key] = $value;
    }

    /**
     * Retrieve a value from the context.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    /**
     * Check whether a key exists in the context.
     */
    public function has(string $key): bool
    {
        return array_key_exists($key, $this->data);
    }

    /**
     * Return a snapshot of all context data (useful for logging).
     *
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return $this->data;
    }
}
