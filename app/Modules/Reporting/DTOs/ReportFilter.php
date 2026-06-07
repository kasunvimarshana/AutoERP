<?php

declare(strict_types=1);

namespace Modules\Reporting\DTOs;

final readonly class ReportFilter
{
    /**
     * @param array<int, array{value: string, label: string}> $options
     */
    public function __construct(
        public string $key,
        public string $label,
        public string $field,
        public string $type = 'text',
        public string $operator = '=',
        public array $options = [],
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'label' => $this->label,
            'type' => $this->type,
            'options' => $this->options,
        ];
    }
}
