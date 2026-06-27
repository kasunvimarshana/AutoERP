<?php

declare(strict_types=1);

namespace Modules\Finance\DTOs;

readonly class PostingContext
{
    public PostingSourceData $source;
    public string $postingDate;
    public ?int $currencyId;
    public string $exchangeRate;
    /** @var list<PostingLine> */
    public array $lines;
    public ?string $description;
    public ?string $postingProfileCode;
    public ?string $sourceModule;
    public string $sourceType;
    public int $sourceId;
    public ?string $sourceNumber;
    public ?string $sourceDate;

    /**
     * @param  list<PostingLine>  $lines
     */
    public function __construct(
        ?PostingSourceData $source = null,
        ?string $postingDate = null,
        ?int $currencyId = null,
        string $exchangeRate = '1.000000',
        array $lines = [],
        ?string $description = null,
        ?string $postingProfileCode = null,
        ?string $sourceModule = null,
        ?string $sourceType = null,
        ?int $sourceId = null,
        ?string $sourceNumber = null,
        ?string $sourceDate = null,
        string $postingKey = PostingSourceData::DEFAULT_POSTING_KEY,
        ?int $tenantId = null,
        ?int $organizationUnitId = null,
    ) {
        $resolvedSourceType = $source?->sourceType ?? (string) $sourceType;
        $resolvedSourceId = $source?->sourceId ?? (int) ($sourceId ?? 0);

        $this->source = $source ?? new PostingSourceData(
            sourceType: $resolvedSourceType,
            sourceId: $resolvedSourceId,
            tenantId: $tenantId,
            organizationUnitId: $organizationUnitId,
            sourceModule: $sourceModule,
            sourceNumber: $sourceNumber,
            sourceDate: $sourceDate,
            postingKey: $postingKey,
        );
        $this->postingDate = (string) $postingDate;
        $this->currencyId = $currencyId;
        $this->exchangeRate = $exchangeRate;
        $this->lines = $lines;
        $this->description = $description;
        $this->postingProfileCode = $postingProfileCode;
        $this->sourceModule = $this->source->sourceModule;
        $this->sourceType = $this->source->sourceType;
        $this->sourceId = $this->source->sourceId;
        $this->sourceNumber = $this->source->sourceNumber;
        $this->sourceDate = $this->source->sourceDate;
    }
}
