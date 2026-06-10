<?php

declare(strict_types=1);

namespace Modules\Reporting\DTOs;

use Closure;

final readonly class ReportColumn
{
    /**
     * @param  Closure(mixed): mixed|null  $value
     */
    public function __construct(
        public string $key,
        public string $label,
        public ?string $path = null,
        public ?string $sortBy = null,
        public string $format = 'text',
        public bool $summarize = false,
        public ?Closure $value = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'label' => $this->label,
            'sortable' => $this->sortBy !== null,
            'format' => $this->format,
            'summarized' => $this->summarize,
        ];
    }
}
