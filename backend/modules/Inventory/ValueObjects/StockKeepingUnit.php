<?php

namespace Modules\Inventory\ValueObjects;

use JsonSerializable;
use Stringable;

/**
 * Stock Keeping Unit (SKU) Value Object for AutoERP Inventory Management
 * Handles SKU validation, generation, and classification for products
 */
final class StockKeepingUnit implements JsonSerializable, Stringable
{
    private readonly string $identifier;
    private readonly ?string $categoryPart;
    private readonly ?string $variantPart;
    private readonly bool $autoCreated;
    
    private const SKU_REGEX = '/^[A-Z0-9\-_]{3,50}$/';
    private const SYSTEM_PREFIXES = ['SYS', 'TMP', 'TEST'];
    
    private function __construct(
        string $skuValue,
        ?string $category = null,
        ?string $variant = null,
        bool $wasAutoCreated = false
    ) {
        $cleaned = $this->cleanSkuString($skuValue);
        $this->checkValidFormat($cleaned);
        $this->checkNotSystemReserved($cleaned);
        
        $this->identifier = $cleaned;
        $this->categoryPart = $category;
        $this->variantPart = $variant;
        $this->autoCreated = $wasAutoCreated;
    }
    
    public static function fromString(string $skuValue): self
    {
        return new self($skuValue);
    }
    
    public static function createForProduct(
        string $categoryName,
        int $sequenceNumber,
        ?string $variantName = null
    ): self {
        $catCode = strtoupper(substr(preg_replace('/[^A-Z0-9]/', '', $categoryName), 0, 3));
        $seqPart = str_pad((string)$sequenceNumber, 6, '0', STR_PAD_LEFT);
        
        $fullSku = sprintf('%s-%s', $catCode, $seqPart);
        
        if ($variantName !== null) {
            $varCode = strtoupper(substr(preg_replace('/[^A-Z0-9]/', '', $variantName), 0, 4));
            $fullSku .= '-' . $varCode;
        }
        
        return new self($fullSku, $catCode, $variantName, true);
    }
    
    public function asString(): string
    {
        return $this->identifier;
    }
    
    public function __toString(): string
    {
        return $this->identifier;
    }
    
    public function jsonSerialize(): mixed
    {
        return [
            'sku' => $this->identifier,
            'category' => $this->categoryPart,
            'variant' => $this->variantPart,
            'auto_created' => $this->autoCreated,
        ];
    }
    
    private function cleanSkuString(string $rawSku): string
    {
        return strtoupper(trim($rawSku));
    }
    
    private function checkValidFormat(string $skuToCheck): void
    {
        if (!preg_match(self::SKU_REGEX, $skuToCheck)) {
            throw new \InvalidArgumentException(
                sprintf('Invalid SKU format: "%s"', $skuToCheck)
            );
        }
    }
    
    private function checkNotSystemReserved(string $skuToCheck): void
    {
        foreach (self::SYSTEM_PREFIXES as $systemPrefix) {
            if (str_starts_with($skuToCheck, $systemPrefix . '-')) {
                throw new \InvalidArgumentException(
                    sprintf('SKU uses reserved prefix: %s', $systemPrefix)
                );
            }
        }
    }
}
