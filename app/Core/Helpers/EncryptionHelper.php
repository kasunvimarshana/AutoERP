<?php

declare(strict_types=1);

namespace App\Core\Helpers;

use Illuminate\Support\Facades\Crypt;

/**
 * Encryption Helper
 * 
 * Provides encryption and decryption utilities
 */
final class EncryptionHelper
{
    /**
     * Encrypt a value
     *
     * @param mixed $value
     * @return string
     */
    public static function encrypt(mixed $value): string
    {
        return Crypt::encryptString((string) $value);
    }

    /**
     * Decrypt a value
     *
     * @param string $encrypted
     * @return string
     * @throws \Illuminate\Contracts\Encryption\DecryptException
     */
    public static function decrypt(string $encrypted): string
    {
        return Crypt::decryptString($encrypted);
    }

    /**
     * Encrypt an array
     *
     * @param array<string, mixed> $data
     * @return string
     */
    public static function encryptArray(array $data): string
    {
        return Crypt::encryptString(json_encode($data, JSON_THROW_ON_ERROR));
    }

    /**
     * Decrypt to array
     *
     * @param string $encrypted
     * @return array<string, mixed>
     * @throws \Illuminate\Contracts\Encryption\DecryptException
     */
    public static function decryptArray(string $encrypted): array
    {
        $decrypted = Crypt::decryptString($encrypted);
        return json_decode($decrypted, true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * Hash a password
     *
     * @param string $password
     * @return string
     */
    public static function hashPassword(string $password): string
    {
        return bcrypt($password);
    }

    /**
     * Verify password hash
     *
     * @param string $password
     * @param string $hash
     * @return bool
     */
    public static function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    /**
     * Generate a secure random token
     *
     * @param int $length
     * @return string
     */
    public static function generateToken(int $length = 32): string
    {
        return bin2hex(random_bytes($length));
    }
}
