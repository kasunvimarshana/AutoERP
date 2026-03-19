<?php

namespace Enterprise\Core\Metadata;

/**
 * MetadataEngine - Handles dynamic, runtime configuration for fields, forms, and validation.
 * Supports metadata-driven UI and logic.
 */
class MetadataEngine
{
    /**
     * Resolve dynamic validation rules based on metadata.
     */
    public static function getValidationRules(string $module, string $form, array $tenantConfig = []): array
    {
        // In a real scenario, this would be fetched from Redis or DB-backed metadata store
        // Example structure for metadata:
        // [
        //   'sku' => ['required' => true, 'type' => 'string', 'min' => 5],
        //   'price' => ['required' => true, 'type' => 'decimal', 'precision' => 4]
        // ]
        $metadata = self::fetchMetadata($module, $form);
        
        $rules = [];
        foreach ($metadata['fields'] as $field => $config) {
            $fieldRules = [];
            if ($config['required'] ?? false) $fieldRules[] = 'required';
            if ($config['type'] === 'string') $fieldRules[] = 'string';
            if ($config['type'] === 'decimal') $fieldRules[] = 'numeric';
            
            // Allow tenant-specific overrides
            if (isset($tenantConfig[$field]['rules'])) {
                $fieldRules = array_merge($fieldRules, $tenantConfig[$field]['rules']);
            }
            
            $rules[$field] = implode('|', array_unique($fieldRules));
        }
        
        return $rules;
    }

    protected static function fetchMetadata(string $module, string $form): array
    {
        // Placeholder for metadata repository
        return [
            'fields' => [
                'name' => ['type' => 'string', 'required' => true],
                'sku' => ['type' => 'string', 'required' => true],
                'base_price' => ['type' => 'decimal', 'required' => true]
            ]
        ];
    }
}
