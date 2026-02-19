<?php

declare(strict_types=1);

namespace Modules\Core\Services;

/**
 * Centralized code generation service to eliminate duplication across modules.
 *
 * Provides consistent, unique code generation for entities with configurable
 * prefixes and uniqueness validation.
 */
class CodeGeneratorService
{
    /**
     * Generate a unique code with the given prefix.
     *
     * @param  string  $prefix  Code prefix (e.g., 'CUST-', 'ORD-', 'INV-')
     * @param  callable|null  $uniquenessCheck  Callback to verify code uniqueness.
     *                                          Should return true if code already exists, false otherwise.
     *                                          Signature: function(string $code): bool
     * @param  int  $length  Length of the unique part (default: 8)
     * @param  int  $maxAttempts  Maximum retry attempts for uniqueness (default: 10)
     * @return string Generated unique code
     *
     * @throws \RuntimeException If unable to generate unique code after max attempts
     */
    public function generate(
        string $prefix,
        ?callable $uniquenessCheck = null,
        int $length = 8,
        int $maxAttempts = 10
    ): string {
        $attempts = 0;

        do {
            $code = $this->generateCode($prefix, $length);
            $attempts++;

            // If no uniqueness check provided, return immediately
            if ($uniquenessCheck === null) {
                return $code;
            }

            // Check if code is unique (callback returns true if exists)
            $exists = $uniquenessCheck($code);

            if (! $exists) {
                return $code;
            }

            if ($attempts >= $maxAttempts) {
                throw new \RuntimeException(
                    "Unable to generate unique code with prefix '{$prefix}' after {$maxAttempts} attempts"
                );
            }
        } while (true);
    }

    /**
     * Generate a code without uniqueness validation.
     *
     * @param  string  $prefix  Code prefix
     * @param  int  $length  Length of unique part
     * @return string Generated code
     */
    public function generateCode(string $prefix, int $length = 8): string
    {
        // Generate unique part using uniqid() and timestamp for additional entropy
        $unique = uniqid(mt_rand().'', true);

        // Take last N characters, uppercase
        $uniquePart = strtoupper(substr($unique, -$length));

        return $prefix.$uniquePart;
    }

    /**
     * Generate a sequential code with numeric suffix.
     *
     * @param  string  $prefix  Code prefix
     * @param  int  $sequence  Current sequence number
     * @param  int  $padding  Zero-padding width (default: 6)
     * @return string Generated sequential code
     */
    public function generateSequential(string $prefix, int $sequence, int $padding = 6): string
    {
        return $prefix.str_pad((string) $sequence, $padding, '0', STR_PAD_LEFT);
    }

    /**
     * Generate a date-based code.
     *
     * @param  string  $prefix  Code prefix
     * @param  string  $dateFormat  Date format (default: 'Ymd')
     * @param  int|null  $sequence  Optional sequence number for same-day uniqueness
     * @param  int  $padding  Padding for random suffix or sequence (default: 4)
     * @param  callable|null  $uniquenessCheck  Callback to verify code uniqueness
     * @return string Generated date-based code
     */
    public function generateDateBased(
        string $prefix,
        string $dateFormat = 'Ymd',
        ?int $sequence = null,
        int $padding = 4,
        ?callable $uniquenessCheck = null
    ): string {
        $datePart = date($dateFormat);

        if ($sequence !== null) {
            $seqPart = str_pad((string) $sequence, $padding, '0', STR_PAD_LEFT);

            return $prefix.$datePart.'-'.$seqPart;
        }

        // Generate random suffix with uniqueness check if provided
        $maxAttempts = 10;
        $attempts = 0;

        do {
            $random = strtoupper(substr(uniqid(mt_rand().'', true), -$padding));
            $code = $prefix.$datePart.'-'.$random;
            $attempts++;

            if ($uniquenessCheck === null) {
                return $code;
            }

            $exists = $uniquenessCheck($code);

            if (! $exists) {
                return $code;
            }

            if ($attempts >= $maxAttempts) {
                throw new \RuntimeException(
                    "Unable to generate unique date-based code with prefix '{$prefix}' after {$maxAttempts} attempts"
                );
            }
        } while (true);
    }

    /**
     * Validate a code format.
     *
     * @param  string  $code  Code to validate
     * @param  string  $expectedPrefix  Expected prefix
     * @param  int|null  $expectedLength  Expected total length (null to skip)
     * @return bool True if valid
     */
    public function validateFormat(string $code, string $expectedPrefix, ?int $expectedLength = null): bool
    {
        // Check prefix
        if (! str_starts_with($code, $expectedPrefix)) {
            return false;
        }

        // Check length if specified
        if ($expectedLength !== null && strlen($code) !== $expectedLength) {
            return false;
        }

        return true;
    }

    /**
     * Extract the unique part from a code (removes prefix).
     *
     * @param  string  $code  Full code
     * @param  string  $prefix  Prefix to remove
     * @return string Unique part
     */
    public function extractUniquePart(string $code, string $prefix): string
    {
        if (str_starts_with($code, $prefix)) {
            return substr($code, strlen($prefix));
        }

        return $code;
    }
}
