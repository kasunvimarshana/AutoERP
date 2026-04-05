<?php

declare(strict_types=1);

namespace Modules\Notification\Domain\Entities;

use Modules\Notification\Domain\ValueObjects\NotificationChannel;
use Modules\Notification\Domain\ValueObjects\NotificationStatus;

/**
 * A single notification sent (or to be sent) to a user.
 *
 * Immutable after creation except for status transitions and read-at timestamp.
 */
class Notification
{
    public function __construct(
        private readonly ?int             $id,
        private readonly ?int             $tenantId,
        private readonly int              $userId,
        private readonly string           $type,
        private readonly NotificationChannel $channel,
        private readonly string           $title,
        private readonly string           $body,
        private readonly ?array           $data,
        private NotificationStatus        $status,
        private ?\DateTimeInterface       $readAt,
        private ?\DateTimeInterface       $sentAt,
        private readonly ?\DateTimeInterface $createdAt,
        private ?\DateTimeInterface       $updatedAt,
    ) {}

    // ── Getters ───────────────────────────────────────────────────────────────

    public function getId(): ?int                     { return $this->id; }
    public function getTenantId(): ?int               { return $this->tenantId; }
    public function getUserId(): int                  { return $this->userId; }
    public function getType(): string                 { return $this->type; }
    public function getChannel(): NotificationChannel { return $this->channel; }
    public function getTitle(): string                { return $this->title; }
    public function getBody(): string                 { return $this->body; }
    public function getData(): ?array                 { return $this->data; }
    public function getStatus(): NotificationStatus   { return $this->status; }
    public function getReadAt(): ?\DateTimeInterface  { return $this->readAt; }
    public function getSentAt(): ?\DateTimeInterface  { return $this->sentAt; }
    public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }
    public function getUpdatedAt(): ?\DateTimeInterface { return $this->updatedAt; }

    // ── Domain methods ────────────────────────────────────────────────────────

    public function markAsSent(\DateTimeInterface $sentAt): void
    {
        $this->status = NotificationStatus::sent();
        $this->sentAt = $sentAt;
        $this->updatedAt = $sentAt;
    }

    public function markAsFailed(): void
    {
        $this->status = NotificationStatus::failed();
        $this->updatedAt = new \DateTime();
    }

    public function markAsRead(\DateTimeInterface $readAt): void
    {
        $this->status = NotificationStatus::read();
        $this->readAt = $readAt;
        $this->updatedAt = $readAt;
    }

    public function isRead(): bool
    {
        return $this->readAt !== null;
    }

    public function isSent(): bool
    {
        return $this->status->isSent() || $this->status->isRead();
    }
}
