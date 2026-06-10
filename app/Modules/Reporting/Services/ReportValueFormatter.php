<?php

declare(strict_types=1);

namespace Modules\Reporting\Services;

use BackedEnum;

final class ReportValueFormatter
{
    public function format(mixed $value, string $format = 'text'): string
    {
        if ($value instanceof BackedEnum) {
            $value = $value->value;
        }

        if ($value === null || $value === '') {
            return '';
        }

        if ($format === 'boolean') {
            return (bool) $value ? 'Yes' : 'No';
        }

        if (in_array($format, ['money', 'currency'], true)) {
            return $this->decimal((string) $value, 2);
        }

        if ($format === 'decimal') {
            return $this->decimal((string) $value, 2, true);
        }

        if ($format === 'enum') {
            return ucwords(str_replace(['_', '-'], ' ', (string) $value));
        }

        if (is_array($value)) {
            return (string) json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }

        return (string) $value;
    }

    private function decimal(string $value, int $minimumScale, bool $trim = false): string
    {
        if (! preg_match('/^(-?)(\d+)(?:\.(\d+))?$/', trim($value), $matches)) {
            return $value;
        }

        $whole = ltrim($matches[2], '0');
        $whole = $whole === '' ? '0' : $whole;
        $fraction = $matches[3] ?? '';

        if ($trim) {
            $fraction = rtrim($fraction, '0');
        }

        $fraction = str_pad($fraction, $minimumScale, '0');
        $whole = preg_replace('/\B(?=(\d{3})+(?!\d))/', ',', $whole) ?? $whole;

        return $matches[1].$whole.($fraction !== '' ? '.'.$fraction : '');
    }
}
