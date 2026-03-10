<?php

declare(strict_types=1);

namespace App\Application\Order\DTOs;

/**
 * CreateOrderDTO
 */
final class CreateOrderDTO
{
    public function __construct(
        public readonly string  $tenantId,
        public readonly string  $userId,
        public readonly array   $items,
        public readonly string  $currency        = 'USD',
        public readonly ?string $notes           = null,
        public readonly array   $metadata        = [],
        public readonly array   $shippingAddress = [],
        public readonly ?string $serviceToken    = null,
    ) {}

    /**
     * @param  array<string, mixed> $data
     * @return static
     */
    public static function fromArray(array $data): static
    {
        return new static(
            tenantId:        $data['tenant_id'],
            userId:          $data['user_id'],
            items:           $data['items'],
            currency:        $data['currency']         ?? 'USD',
            notes:           $data['notes']            ?? null,
            metadata:        $data['metadata']         ?? [],
            shippingAddress: $data['shipping_address'] ?? [],
            serviceToken:    $data['service_token']    ?? null,
        );
    }
}
