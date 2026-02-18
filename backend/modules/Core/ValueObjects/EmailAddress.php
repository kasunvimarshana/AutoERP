<?php

namespace Modules\Core\ValueObjects;

use JsonSerializable;
use Stringable;

/**
 * Email Address Value Object
 * Ensures email validity and provides comparison operations
 */
final class EmailAddress implements JsonSerializable, Stringable
{
    private readonly string $value;
    
    private function __construct(string $email)
    {
        $normalized = strtolower(trim($email));
        
        if (!filter_var($normalized, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException(
                sprintf('Invalid email address: %s', $email)
            );
        }
        
        // Additional validation for common typos and disposable domains
        if ($this->isDisposableEmail($normalized)) {
            throw new \InvalidArgumentException(
                'Disposable email addresses are not allowed'
            );
        }
        
        $this->value = $normalized;
    }
    
    public static function from(string $email): self
    {
        return new self($email);
    }
    
    public function getValue(): string
    {
        return $this->value;
    }
    
    public function getDomain(): string
    {
        return substr($this->value, strpos($this->value, '@') + 1);
    }
    
    public function getLocalPart(): string
    {
        return substr($this->value, 0, strpos($this->value, '@'));
    }
    
    public function equals(EmailAddress $other): bool
    {
        return $this->value === $other->value;
    }
    
    public function obfuscate(): string
    {
        $localPart = $this->getLocalPart();
        $domain = $this->getDomain();
        
        if (strlen($localPart) <= 2) {
            return substr($localPart, 0, 1) . '***@' . $domain;
        }
        
        return substr($localPart, 0, 2) 
            . str_repeat('*', min(strlen($localPart) - 2, 5)) 
            . '@' . $domain;
    }
    
    public function __toString(): string
    {
        return $this->value;
    }
    
    public function jsonSerialize(): mixed
    {
        return $this->value;
    }
    
    /**
     * Basic check for common disposable email domains
     * In production, this should use a comprehensive API or database
     */
    private function isDisposableEmail(string $email): bool
    {
        $disposableDomains = [
            'tempmail.com',
            '10minutemail.com',
            'guerrillamail.com',
            'mailinator.com',
            'throwaway.email',
        ];
        
        $domain = $this->getDomain();
        
        return in_array($domain, $disposableDomains, true);
    }
    
    private function extractDomain(string $email): string
    {
        // Helper used during construction before getDomain() is available
        $parts = explode('@', $email);
        return $parts[count($parts) - 1];
    }
}
