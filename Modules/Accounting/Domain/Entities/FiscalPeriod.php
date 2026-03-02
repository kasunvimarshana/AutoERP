<?php
declare(strict_types=1);
namespace Modules\Accounting\Domain\Entities;
use DateTimeImmutable;
class FiscalPeriod {
    public function __construct(
        private readonly int              $id,
        private readonly int              $tenantId,
        private string                    $name,
        private DateTimeImmutable         $startDate,
        private DateTimeImmutable         $endDate,
        private bool                      $isClosed,
        private ?DateTimeImmutable        $closedAt,
        private ?int                      $closedBy,
    ) {}
    public function getId(): int { return $this->id; }
    public function getTenantId(): int { return $this->tenantId; }
    public function getName(): string { return $this->name; }
    public function getStartDate(): DateTimeImmutable { return $this->startDate; }
    public function getEndDate(): DateTimeImmutable { return $this->endDate; }
    public function isClosed(): bool { return $this->isClosed; }
    public function getClosedAt(): ?DateTimeImmutable { return $this->closedAt; }
    public function getClosedBy(): ?int { return $this->closedBy; }
    public function isDateInPeriod(DateTimeImmutable $date): bool {
        return $date >= $this->startDate && $date <= $this->endDate;
    }
    public function close(int $closedBy): void {
        if ($this->isClosed) throw new \DomainException('Fiscal period is already closed.');
        $this->isClosed = true;
        $this->closedAt = new DateTimeImmutable();
        $this->closedBy = $closedBy;
    }
}
