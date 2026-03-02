<?php

declare(strict_types=1);

namespace Modules\Notification\Application\Commands;

final readonly class MarkNotificationReadCommand
{
    public function __construct(
        public int $id,
        public int $tenantId,
    ) {}

    public function rules(): array
    {
        return [
            'id' => ['required', 'integer', 'min:1'],
            'tenantId' => ['required', 'integer', 'min:1'],
        ];
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'tenantId' => $this->tenantId,
        ];
    }
}
