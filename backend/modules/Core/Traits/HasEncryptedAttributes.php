<?php

namespace Modules\Core\Traits;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Contracts\Encryption\DecryptException;

/**
 * HasEncryptedAttributes Trait
 * Automatically encrypts/decrypts specified model attributes
 * 
 * Usage:
 * class User extends Model {
 *     use HasEncryptedAttributes;
 *     
 *     protected array $encrypted = ['ssn', 'tax_id'];
 * }
 */
trait HasEncryptedAttributes
{
    /**
     * Get an attribute from the model.
     * Automatically decrypt if it's an encrypted field
     */
    public function getAttribute($key)
    {
        $value = parent::getAttribute($key);
        
        if ($this->isEncryptedAttribute($key) && !is_null($value)) {
            try {
                return Crypt::decryptString($value);
            } catch (DecryptException $e) {
                // Log error and return null if decryption fails
                logger()->error('Failed to decrypt attribute', [
                    'model' => static::class,
                    'attribute' => $key,
                    'error' => $e->getMessage(),
                ]);
                return null;
            }
        }
        
        return $value;
    }
    
    /**
     * Set a given attribute on the model.
     * Automatically encrypt if it's an encrypted field
     */
    public function setAttribute($key, $value)
    {
        if ($this->isEncryptedAttribute($key) && !is_null($value)) {
            $value = Crypt::encryptString($value);
        }
        
        return parent::setAttribute($key, $value);
    }
    
    /**
     * Get the attributes that should be converted to native types.
     */
    public function attributesToArray()
    {
        $attributes = parent::attributesToArray();
        
        foreach ($this->getEncryptedAttributes() as $key) {
            if (isset($attributes[$key])) {
                try {
                    $attributes[$key] = Crypt::decryptString($attributes[$key]);
                } catch (DecryptException $e) {
                    $attributes[$key] = null;
                }
            }
        }
        
        return $attributes;
    }
    
    /**
     * Check if an attribute is encrypted
     */
    protected function isEncryptedAttribute(string $key): bool
    {
        return in_array($key, $this->getEncryptedAttributes(), true);
    }
    
    /**
     * Get the encrypted attributes for the model
     */
    protected function getEncryptedAttributes(): array
    {
        return property_exists($this, 'encrypted') ? $this->encrypted : [];
    }
    
    /**
     * Cast encrypted attributes to array without decrypting
     * Useful for exports or when you want to keep data encrypted
     */
    public function toArrayEncrypted(): array
    {
        return parent::attributesToArray();
    }
}
