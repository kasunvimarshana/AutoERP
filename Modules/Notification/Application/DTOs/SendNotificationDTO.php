<?php

declare(strict_types=1);

namespace Modules\Notification\Application\DTOs;

use Modules\Core\Application\DTOs\DataTransferObject;

/**
 * Data Transfer Object for sending a notification.
 */
final class SendNotificationDTO extends DataTransferObject
{
    public function __construct(
        public readonly ?int $templateId,
        public readonly string $channel,
        public readonly string $recipient,
        public readonly array $variables,
        public readonly array $metadata,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): static
    {
        return new self(
            templateId: isset($data['template_id']) ? (int) $data['template_id'] : null,
            channel:    (string) $data['channel'],
            recipient:  (string) $data['recipient'],
            variables:  isset($data['variables']) && is_array($data['variables']) ? $data['variables'] : [],
            metadata:   isset($data['metadata']) && is_array($data['metadata']) ? $data['metadata'] : [],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'template_id' => $this->templateId,
            'channel'     => $this->channel,
            'recipient'   => $this->recipient,
            'variables'   => $this->variables,
            'metadata'    => $this->metadata,
        ];
    }
}
