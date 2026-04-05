<?php
declare(strict_types=1);
namespace Modules\Accounting\Domain\Entities;

class TransactionRule
{
    // apply_to: all|debit|credit
    public function __construct(
        private ?int $id,
        private int $tenantId,
        private string $name,
        private bool $isActive,
        private int $priority,
        private array $conditions,
        private array $actions,
        private string $applyTo,
        private int $matchCount,
        private ?\DateTimeInterface $createdAt,
        private ?\DateTimeInterface $updatedAt,
    ) {}

    public function getId(): ?int { return $this->id; }
    public function getTenantId(): int { return $this->tenantId; }
    public function getName(): string { return $this->name; }
    public function isActive(): bool { return $this->isActive; }
    public function getPriority(): int { return $this->priority; }
    public function getConditions(): array { return $this->conditions; }
    public function getActions(): array { return $this->actions; }
    public function getApplyTo(): string { return $this->applyTo; }
    public function getMatchCount(): int { return $this->matchCount; }
    public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }
    public function getUpdatedAt(): ?\DateTimeInterface { return $this->updatedAt; }
    public function incrementMatchCount(): void { $this->matchCount++; }
    public function activate(): void { $this->isActive = true; }
    public function deactivate(): void { $this->isActive = false; }

    public function matches(BankTransaction $transaction): bool
    {
        if (!$this->isActive) return false;
        if ($this->applyTo === 'debit' && !$transaction->isDebit()) return false;
        if ($this->applyTo === 'credit' && !$transaction->isCredit()) return false;
        foreach ($this->conditions as $condition) {
            if (!$this->evaluateCondition($condition, $transaction)) return false;
        }
        return true;
    }

    private function evaluateCondition(array $condition, BankTransaction $transaction): bool
    {
        $field = $condition['field'] ?? '';
        $operator = $condition['operator'] ?? 'contains';
        $value = $condition['value'] ?? '';
        $fieldValue = match ($field) {
            'description' => $transaction->getDescription(),
            'amount'      => (string) $transaction->getAmount(),
            'reference'   => (string) $transaction->getReference(),
            default       => '',
        };
        return match ($operator) {
            'contains'       => str_contains(strtolower($fieldValue), strtolower((string)$value)),
            'starts_with'    => str_starts_with(strtolower($fieldValue), strtolower((string)$value)),
            'ends_with'      => str_ends_with(strtolower($fieldValue), strtolower((string)$value)),
            'equals'         => strtolower($fieldValue) === strtolower((string)$value),
            'greater_than'   => (float)$fieldValue > (float)$value,
            'less_than'      => (float)$fieldValue < (float)$value,
            default          => false,
        };
    }
}
