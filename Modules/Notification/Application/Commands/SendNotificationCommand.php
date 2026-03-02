<?php

declare(strict_types=1);

namespace Modules\Notification\Application\Commands;

final readonly class SendNotificationCommand
{
    public function __construct(
        public int $tenantId,
        public int $userId,
        public string $channel,
        public string $eventType,
        public string $subject,
        public string $body,
        public ?int $templateId,
    ) {}

    public function rules(): array
    {
        return [
            'tenantId' => ['required', 'integer', 'min:1'],
            'userId' => ['required', 'integer', 'min:1'],
            'channel' => ['required', 'string'],
            'eventType' => ['required', 'string', 'max:100'],
            'subject' => ['required', 'string', 'max:500'],
            'body' => ['required', 'string'],
        ];
    }

    public function toArray(): array
    {
        return [
            'tenantId' => $this->tenantId,
            'userId' => $this->userId,
            'channel' => $this->channel,
            'eventType' => $this->eventType,
            'subject' => $this->subject,
            'body' => $this->body,
            'templateId' => $this->templateId,
        ];
    }
}
