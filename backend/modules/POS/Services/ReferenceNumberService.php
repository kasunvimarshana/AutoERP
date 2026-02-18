<?php

declare(strict_types=1);

namespace Modules\POS\Services;

use Modules\POS\Models\ReferenceCounter;

class ReferenceNumberService
{
    public function generate(string $type, ?string $prefix = null): string
    {
        $counter = ReferenceCounter::firstOrCreate(
            [
                'reference_type' => $type,
                'prefix' => $prefix,
            ],
            [
                'current_number' => 0,
                'padding' => 6,
            ]
        );

        $counter->increment('current_number');
        $counter->refresh();

        $number = str_pad((string) $counter->current_number, $counter->padding, '0', STR_PAD_LEFT);

        return $prefix ? "{$prefix}{$number}" : $number;
    }

    public function reset(string $type, ?string $prefix = null): void
    {
        ReferenceCounter::where('reference_type', $type)
            ->where('prefix', $prefix)
            ->update(['current_number' => 0]);
    }
}
