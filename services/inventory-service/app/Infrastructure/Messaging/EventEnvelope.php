<?php
namespace App\Infrastructure\Messaging;
use Ramsey\Uuid\Uuid;

final class EventEnvelope
{
    private function __construct(
        public readonly string $id,
        public readonly string $source,
        public readonly string $type,
        public readonly string $specversion,
        public readonly string $time,
        public readonly array  $data,
        public readonly ?string $tenantId,
        public readonly ?string $correlationId,
        public readonly ?string $sagaId,
    ) {}

    public static function create(string $type, string $source, array $data, ?string $tenantId = null, ?string $correlationId = null, ?string $sagaId = null): self
    {
        return new self(
            id: Uuid::uuid4()->toString(),
            source: $source,
            type: $type,
            specversion: '1.0',
            time: now()->toISOString(),
            data: $data,
            tenantId: $tenantId,
            correlationId: $correlationId,
            sagaId: $sagaId,
        );
    }

    public function toArray(): array
    {
        return [
            'id'            => $this->id,
            'source'        => $this->source,
            'type'          => $this->type,
            'specversion'   => $this->specversion,
            'time'          => $this->time,
            'data'          => $this->data,
            'tenant_id'     => $this->tenantId,
            'correlation_id'=> $this->correlationId,
            'saga_id'       => $this->sagaId,
        ];
    }
}
