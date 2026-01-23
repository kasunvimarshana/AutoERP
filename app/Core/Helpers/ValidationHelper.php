<?php

declare(strict_types=1);

namespace App\Core\Helpers;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Validation Helper
 * 
 * Provides validation utilities
 */
final class ValidationHelper
{
    /**
     * Validate data against rules
     *
     * @param array<string, mixed> $data
     * @param array<string, mixed> $rules
     * @param array<string, string> $messages
     * @return array<string, mixed>
     * @throws ValidationException
     */
    public static function validate(array $data, array $rules, array $messages = []): array
    {
        $validator = Validator::make($data, $rules, $messages);
        
        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
        
        return $validator->validated();
    }

    /**
     * Check if data passes validation
     *
     * @param array<string, mixed> $data
     * @param array<string, mixed> $rules
     * @return bool
     */
    public static function passes(array $data, array $rules): bool
    {
        return Validator::make($data, $rules)->passes();
    }

    /**
     * Get validation errors
     *
     * @param array<string, mixed> $data
     * @param array<string, mixed> $rules
     * @return array<string, array<string>>
     */
    public static function errors(array $data, array $rules): array
    {
        $validator = Validator::make($data, $rules);
        
        if ($validator->fails()) {
            return $validator->errors()->toArray();
        }
        
        return [];
    }

    /**
     * Validate email address
     *
     * @param string $email
     * @return bool
     */
    public static function isValidEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Validate URL
     *
     * @param string $url
     * @return bool
     */
    public static function isValidUrl(string $url): bool
    {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }

    /**
     * Validate UUID
     *
     * @param string $uuid
     * @return bool
     */
    public static function isValidUuid(string $uuid): bool
    {
        $pattern = '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i';
        return preg_match($pattern, $uuid) === 1;
    }

    /**
     * Sanitize string
     *
     * @param string $value
     * @return string
     */
    public static function sanitize(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Validate strong password
     *
     * @param string $password
     * @param int $minLength
     * @return bool
     */
    public static function isStrongPassword(string $password, int $minLength = 8): bool
    {
        if (strlen($password) < $minLength) {
            return false;
        }

        // Must contain at least one uppercase, lowercase, number, and special character
        return preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]/', $password) === 1;
    }
}
