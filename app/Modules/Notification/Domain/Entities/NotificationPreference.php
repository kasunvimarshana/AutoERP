<?php

declare(strict_types=1);

namespace Modules\Notification\Domain\Entities;

/**
 * A user's opt-in / opt-out preference for a specific notification type
 * delivered via a specific channel.
 */
class NotificationPreference
{
    public function __construct(
        private readonly ?int   $id,
        private readonly ?int   $tenantId,
        private readonly int    $userId,
        private readonly string $notificationType,
        private readonly string $channel,
        private bool            $enabled,
        private readonly ?\DateTimeInterface $createdAt,
        private ?\DateTimeInterface          $updatedAt,
    ) {}

    // ── Getters ───────────────────────────────────────────────────────────────

    public function getId(): ?int               { return $this->id; }
    public function getTenantId(): ?int         { return $this->tenantId; }
    public function getUserId(): int            { return $this->userId; }
    public function getNotificationType(): string { return $this->notificationType; }
    public function getChannel(): string        { return $this->channel; }
    public function isEnabled(): bool           { return $this->enabled; }
    public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }
    public function getUpdatedAt(): ?\DateTimeInterface { return $this->updatedAt; }

    // ── Domain methods ────────────────────────────────────────────────────────

    public function enable(): void
    {
        $this->enabled   = true;
        $this->updatedAt = new \DateTime();
    }

    public function disable(): void
    {
        $this->enabled   = false;
        $this->updatedAt = new \DateTime();
    }
}
