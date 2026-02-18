<?php

namespace Modules\Core\ValueObjects;

use JsonSerializable;
use Stringable;

/**
 * Phone Number Value Object
 * Handles phone number validation and formatting
 * Supports international format with country code
 */
final class PhoneNumber implements JsonSerializable, Stringable
{
    private readonly string $value;
    private readonly ?string $countryCode;
    
    private function __construct(string $phoneNumber, ?string $countryCode = null)
    {
        $normalized = $this->normalize($phoneNumber);
        
        if (!$this->isValid($normalized)) {
            throw new \InvalidArgumentException(
                sprintf('Invalid phone number: %s', $phoneNumber)
            );
        }
        
        $this->value = $normalized;
        $this->countryCode = $countryCode ? strtoupper($countryCode) : null;
    }
    
    public static function from(string $phoneNumber, ?string $countryCode = null): self
    {
        return new self($phoneNumber, $countryCode);
    }
    
    public function getValue(): string
    {
        return $this->value;
    }
    
    public function getCountryCode(): ?string
    {
        return $this->countryCode;
    }
    
    /**
     * Format as E.164 international format
     */
    public function toE164(): string
    {
        if (str_starts_with($this->value, '+')) {
            return $this->value;
        }
        
        return '+' . $this->value;
    }
    
    /**
     * Format for human readability
     */
    public function format(): string
    {
        $digits = preg_replace('/\D/', '', $this->value);
        
        // Simple formatting for demonstration
        // In production, use a library like libphonenumber-for-php
        if (strlen($digits) === 10) {
            return sprintf('(%s) %s-%s', 
                substr($digits, 0, 3),
                substr($digits, 3, 3),
                substr($digits, 6)
            );
        }
        
        return $this->value;
    }
    
    public function equals(PhoneNumber $other): bool
    {
        return $this->value === $other->value;
    }
    
    public function __toString(): string
    {
        return $this->value;
    }
    
    public function jsonSerialize(): mixed
    {
        return [
            'value' => $this->value,
            'formatted' => $this->format(),
            'country_code' => $this->countryCode,
        ];
    }
    
    private function normalize(string $phoneNumber): string
    {
        // Remove all non-digit characters except +
        $normalized = preg_replace('/[^\d+]/', '', $phoneNumber);
        
        // Remove leading zeros if not part of country code
        if (str_starts_with($normalized, '00')) {
            $normalized = '+' . substr($normalized, 2);
        }
        
        return $normalized;
    }
    
    private function isValid(string $phoneNumber): bool
    {
        // Basic validation - must have at least 7 digits
        $digits = preg_replace('/\D/', '', $phoneNumber);
        
        if (strlen($digits) < 7 || strlen($digits) > 15) {
            return false;
        }
        
        // If starts with +, next character must be digit
        if (str_starts_with($phoneNumber, '+')) {
            return isset($phoneNumber[1]) && ctype_digit($phoneNumber[1]);
        }
        
        return true;
    }
}
