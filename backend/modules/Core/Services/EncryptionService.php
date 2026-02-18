<?php

namespace Modules\Core\Services;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Contracts\Encryption\EncryptException;

/**
 * Field-Level Encryption Service
 * Provides encryption/decryption for sensitive data fields
 * Uses Laravel's encryption which implements AES-256-CBC
 */
class EncryptionService extends BaseService
{
    /**
     * Fields that should always be encrypted at rest
     */
    private const SENSITIVE_FIELDS = [
        'ssn',
        'tax_id',
        'bank_account_number',
        'credit_card_number',
        'passport_number',
        'driver_license',
        'mfa_secret',
    ];
    
    /**
     * Encrypt a value
     *
     * @param mixed $value
     * @return string
     * @throws EncryptException
     */
    public function encrypt(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }
        
        return Crypt::encryptString((string) $value);
    }
    
    /**
     * Decrypt a value
     *
     * @param string $encryptedValue
     * @return string|null
     */
    public function decrypt(string $encryptedValue): ?string
    {
        if ($encryptedValue === '') {
            return null;
        }
        
        try {
            return Crypt::decryptString($encryptedValue);
        } catch (DecryptException $e) {
            // Log the error but don't expose details
            logger()->error('Decryption failed', [
                'error' => $e->getMessage(),
                'tenant_id' => $this->getTenantId(),
            ]);
            
            return null;
        }
    }
    
    /**
     * Encrypt an array of data, only encrypting sensitive fields
     *
     * @param array $data
     * @return array
     */
    public function encryptArray(array $data): array
    {
        $encrypted = [];
        
        foreach ($data as $key => $value) {
            if ($this->isSensitiveField($key) && !empty($value)) {
                $encrypted[$key] = $this->encrypt($value);
            } else {
                $encrypted[$key] = $value;
            }
        }
        
        return $encrypted;
    }
    
    /**
     * Decrypt an array of data
     *
     * @param array $data
     * @param array $fieldsToDecrypt
     * @return array
     */
    public function decryptArray(array $data, array $fieldsToDecrypt = []): array
    {
        $decrypted = [];
        $fields = empty($fieldsToDecrypt) ? self::SENSITIVE_FIELDS : $fieldsToDecrypt;
        
        foreach ($data as $key => $value) {
            if (in_array($key, $fields, true) && is_string($value) && !empty($value)) {
                $decrypted[$key] = $this->decrypt($value);
            } else {
                $decrypted[$key] = $value;
            }
        }
        
        return $decrypted;
    }
    
    /**
     * Hash a value (one-way) with salt
     * Use for data that needs to be compared but never retrieved
     * Uses bcrypt for strong password-like hashing
     *
     * @param string $value
     * @return string
     */
    public function hash(string $value): string
    {
        return \Hash::make($value);
    }
    
    /**
     * Verify a hashed value
     *
     * @param string $value
     * @param string $hash
     * @return bool
     */
    public function verifyHash(string $value, string $hash): bool
    {
        return \Hash::check($value, $hash);
    }
    
    /**
     * Check if a field name is sensitive and should be encrypted
     *
     * @param string $fieldName
     * @return bool
     */
    public function isSensitiveField(string $fieldName): bool
    {
        return in_array($fieldName, self::SENSITIVE_FIELDS, true) ||
            str_contains(strtolower($fieldName), 'password') ||
            str_contains(strtolower($fieldName), 'secret') ||
            str_contains(strtolower($fieldName), 'token');
    }
    
    /**
     * Mask a value for display (show only last 4 characters)
     *
     * @param string $value
     * @param int $visibleChars
     * @return string
     */
    public function mask(string $value, int $visibleChars = 4): string
    {
        if (strlen($value) <= $visibleChars) {
            return str_repeat('*', strlen($value));
        }
        
        return str_repeat('*', strlen($value) - $visibleChars) . substr($value, -$visibleChars);
    }
}
