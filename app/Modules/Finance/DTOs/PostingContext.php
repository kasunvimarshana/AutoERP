<?php

declare(strict_types=1);

namespace Modules\Finance\DTOs;

readonly class PostingContext
{
    /**
     * @param  list<PostingLine>  $lines
     */
    public function __construct(
        public PostingSourceData $source,
        public string $postingDate,
        public ?int $currencyId = null,
        public string $exchangeRate = '1.000000',
        public array $lines = [],
        public ?string $description = null,
        public ?string $postingProfileCode = null,
    ) {}
}
