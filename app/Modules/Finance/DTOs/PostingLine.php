<?php

declare(strict_types=1);

namespace Modules\Finance\DTOs;

readonly class PostingLine
{
    public ?string $accountCode;
    public string $accountName;
    public string $debit;
    public string $credit;
    public ?string $description;
    public ?string $profileKey;
    public ?string $dimensionCode;
    public ?string $sourceLineType;
    public ?int $sourceLineId;
    public ?string $account;
    /** @var array<string, string|null> */
    public array $dimensions;

    /**
     * @param  array<string, string|null>  $dimensions
     */
    public function __construct(
        ?string $accountCode = null,
        string $accountName = '',
        string $debit = '0.000000',
        string $credit = '0.000000',
        ?string $description = null,
        ?string $profileKey = null,
        ?string $dimensionCode = null,
        ?string $sourceLineType = null,
        ?int $sourceLineId = null,
        ?string $account = null,
        array $dimensions = [],
    ) {
        $this->account = $account ?? $accountCode;
        $this->accountCode = $accountCode ?? $account;
        $this->accountName = $accountName;
        $this->debit = $debit;
        $this->credit = $credit;
        $this->description = $description;
        $this->profileKey = $profileKey;
        $this->dimensionCode = $dimensionCode ?? ($dimensions['cost_center'] ?? $dimensions['department'] ?? null);
        $this->sourceLineType = $sourceLineType;
        $this->sourceLineId = $sourceLineId;
        $this->dimensions = $dimensions;
    }
}
