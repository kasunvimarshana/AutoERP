<?php

declare(strict_types=1);

namespace Modules\Notification\Application\Commands;

final readonly class UpdateNotificationTemplateCommand
{
    public function __construct(
        public int $id,
        public int $tenantId,
        public string $channel,
        public string $eventType,
        public string $name,
        public string $subject,
        public string $body,
        public bool $isActive,
    ) {}

    public function rules(): array
    {
        return [
            'id' => ['required', 'integer', 'min:1'],
            'tenantId' => ['required', 'integer', 'min:1'],
            'channel' => ['required', 'string'],
            'eventType' => ['required', 'string', 'max:100'],
            'name' => ['required', 'string', 'max:255'],
            'subject' => ['required', 'string', 'max:500'],
            'body' => ['required', 'string'],
        ];
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'tenantId' => $this->tenantId,
            'channel' => $this->channel,
            'eventType' => $this->eventType,
            'name' => $this->name,
            'subject' => $this->subject,
            'body' => $this->body,
            'isActive' => $this->isActive,
        ];
    }
}
