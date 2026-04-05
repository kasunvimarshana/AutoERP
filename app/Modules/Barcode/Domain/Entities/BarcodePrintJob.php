<?php

declare(strict_types=1);

namespace Modules\Barcode\Domain\Entities;

/**
 * Represents a queued or completed barcode label print job.
 *
 * A print job links a BarcodeDefinition to a LabelTemplate and
 * tracks the lifecycle from creation (pending) through processing
 * to completion or failure. The rendered output is stored for
 * audit and re-print purposes.
 */
class BarcodePrintJob
{
    public const STATUS_PENDING    = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED  = 'completed';
    public const STATUS_FAILED     = 'failed';
    public const STATUS_CANCELLED  = 'cancelled';

    public function __construct(
        private readonly ?int    $id,
        private readonly int     $tenantId,
        private readonly int     $barcodeDefinitionId,
        private readonly ?int    $labelTemplateId,
        private string           $status,
        private readonly ?string $printerTarget,       // e.g. "192.168.1.50:9100" or queue name
        private readonly int     $copies,
        private ?string          $renderedOutput,      // final rendered label (ZPL/EPL/SVG string)
        private readonly array   $variables,           // extra {{ }} substitution data
        private ?string          $errorMessage,
        private readonly ?\DateTimeInterface $queuedAt,
        private ?\DateTimeInterface          $completedAt,
    ) {}

    // ── Getters ───────────────────────────────────────────────────────────────

    public function getId(): ?int                       { return $this->id; }
    public function getTenantId(): int                  { return $this->tenantId; }
    public function getBarcodeDefinitionId(): int       { return $this->barcodeDefinitionId; }
    public function getLabelTemplateId(): ?int          { return $this->labelTemplateId; }
    public function getStatus(): string                 { return $this->status; }
    public function getPrinterTarget(): ?string         { return $this->printerTarget; }
    public function getCopies(): int                    { return $this->copies; }
    public function getRenderedOutput(): ?string        { return $this->renderedOutput; }
    public function getVariables(): array               { return $this->variables; }
    public function getErrorMessage(): ?string          { return $this->errorMessage; }
    public function getQueuedAt(): ?\DateTimeInterface  { return $this->queuedAt; }
    public function getCompletedAt(): ?\DateTimeInterface { return $this->completedAt; }

    // ── Status helpers ────────────────────────────────────────────────────────

    public function isPending(): bool    { return $this->status === self::STATUS_PENDING; }
    public function isProcessing(): bool { return $this->status === self::STATUS_PROCESSING; }
    public function isCompleted(): bool  { return $this->status === self::STATUS_COMPLETED; }
    public function isFailed(): bool     { return $this->status === self::STATUS_FAILED; }
    public function isCancelled(): bool  { return $this->status === self::STATUS_CANCELLED; }

    // ── Domain methods ────────────────────────────────────────────────────────

    public function markProcessing(): void
    {
        $this->status = self::STATUS_PROCESSING;
    }

    public function markCompleted(string $renderedOutput): void
    {
        $this->status         = self::STATUS_COMPLETED;
        $this->renderedOutput = $renderedOutput;
        $this->completedAt    = new \DateTime();
    }

    public function markFailed(string $errorMessage): void
    {
        $this->status       = self::STATUS_FAILED;
        $this->errorMessage = $errorMessage;
        $this->completedAt  = new \DateTime();
    }

    public function cancel(): void
    {
        if ($this->isCompleted() || $this->isProcessing()) {
            throw new \LogicException(
                sprintf('Cannot cancel a print job with status "%s".', $this->status)
            );
        }

        $this->status      = self::STATUS_CANCELLED;
        $this->completedAt = new \DateTime();
    }
}
