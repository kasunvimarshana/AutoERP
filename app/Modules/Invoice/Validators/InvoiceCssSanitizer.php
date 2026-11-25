<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Invoice\Validators;

final class InvoiceCssSanitizer
{
    private const DANGEROUS_PATTERNS = [
        '/<\s*\/?\s*style\b/i',
        '/<\s*\/?\s*script\b/i',
        '/<\s*\/?\s*[a-zA-Z!?]/',
        '/javascript\s*:/i',
        '/vbscript\s*:/i',
        '/data\s*:\s*text\/html/i',
        '/expression\s*\(/i',
    ];

    public static function containsDangerousConstructs(string $css): bool
    {
        foreach (self::DANGEROUS_PATTERNS as $pattern) {
            if (preg_match($pattern, $css) === 1) {
                return true;
            }
        }

        return false;
    }

    public static function sanitize(?string $css): ?string
    {
        if ($css === null) {
            return null;
        }

        $trimmed = trim($css);
        if ($trimmed === '') {
            return null;
        }

        $sanitized = preg_replace('/<\s*script\b[^>]*>.*?<\s*\/\s*script\s*>/is', '', $trimmed) ?? $trimmed;
        $sanitized = preg_replace('/<\s*\/?\s*[a-zA-Z!?][^>]*>?/', '', $sanitized) ?? $sanitized;
        $sanitized = preg_replace([
            '/javascript\s*:/i',
            '/vbscript\s*:/i',
            '/data\s*:\s*text\/html/i',
            '/expression\s*\(/i',
        ], '', $sanitized) ?? $sanitized;

        $sanitized = str_ireplace(['</style', '<script'], '', $sanitized);
        $sanitized = trim($sanitized);

        return $sanitized === '' ? null : $sanitized;
    }
}
