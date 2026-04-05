<?php

declare(strict_types=1);

namespace Modules\Notification\Domain\Entities;

/**
 * A reusable notification template with variable interpolation support.
 *
 * Variables are declared as a list of string keys, e.g. ['order_id', 'customer_name'].
 * The body and subject may contain {{ variable_name }} placeholders.
 */
class NotificationTemplate
{
    public function __construct(
        private readonly ?int    $id,
        private readonly ?int    $tenantId,
        private readonly string  $type,
        private readonly string  $name,
        private readonly string  $channel,
        private readonly string  $subject,
        private readonly string  $body,
        private readonly array   $variables,
        private bool             $isActive,
        private readonly ?\DateTimeInterface $createdAt,
        private ?\DateTimeInterface          $updatedAt,
    ) {}

    // ── Getters ───────────────────────────────────────────────────────────────

    public function getId(): ?int       { return $this->id; }
    public function getTenantId(): ?int { return $this->tenantId; }
    public function getType(): string   { return $this->type; }
    public function getName(): string   { return $this->name; }
    public function getChannel(): string { return $this->channel; }
    public function getSubject(): string { return $this->subject; }
    public function getBody(): string   { return $this->body; }
    public function getVariables(): array { return $this->variables; }
    public function isActive(): bool    { return $this->isActive; }
    public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }
    public function getUpdatedAt(): ?\DateTimeInterface { return $this->updatedAt; }

    // ── Domain methods ────────────────────────────────────────────────────────

    public function activate(): void
    {
        $this->isActive  = true;
        $this->updatedAt = new \DateTime();
    }

    public function deactivate(): void
    {
        $this->isActive  = false;
        $this->updatedAt = new \DateTime();
    }

    /**
     * Render the subject and body by replacing {{ var }} placeholders with
     * the supplied values.
     *
     * @param  array<string, string|int|float> $variables
     * @return array{subject: string, body: string}
     */
    public function render(array $variables): array
    {
        $subject = $this->subject;
        $body    = $this->body;

        foreach ($variables as $key => $value) {
            $placeholder = '{{ ' . $key . ' }}';
            $subject     = str_replace($placeholder, (string) $value, $subject);
            $body        = str_replace($placeholder, (string) $value, $body);
        }

        return ['subject' => $subject, 'body' => $body];
    }

    /**
     * Validate that all required variables are present in the supplied map.
     *
     * @param array<string, mixed> $provided
     * @return string[] Missing variable names.
     */
    public function missingVariables(array $provided): array
    {
        return array_values(array_diff($this->variables, array_keys($provided)));
    }
}
