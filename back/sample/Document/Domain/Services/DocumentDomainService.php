<?php

namespace Modules\Document\Domain\Services;

use Illuminate\Support\Facades\DB;
use Modules\Document\Domain\Exceptions\DocumentValidationException;

class DocumentDomainService
{
    public function validateHeaderDefinition(array $data, array $schema): void
    {
        foreach ($schema as $field => $rules) {
            $value = $data[$field] ?? null;

            if (($rules['required'] ?? false) && ($value === null || $value === '')) {
                throw new DocumentValidationException("Header field [{$field}] is required.");
            }
        }
    }

    public function validateItemDefinition(array $data, string $itemType, int $tenantId): void
    {
        $itemTypeRow = DB::table('document_item_types')->where('code', $itemType)->orWhere('name', $itemType)->first();
        if (! $itemTypeRow) {
            throw new DocumentValidationException("Unknown item type [{$itemType}].");
        }

        $definition = DB::table('document_item_definitions')
            ->where('tenant_id', $tenantId)
            ->where('item_type_id', $itemTypeRow->id)
            ->where('is_active', true)
            ->orderByDesc('version')
            ->first();

        if (! $definition) {
            throw new DocumentValidationException("No active definition found for item type [{$itemType}].");
        }

        $schema = json_decode((string) $definition->field_schema, true) ?: [];

        foreach ($schema as $field => $rules) {
            $value = $data[$field] ?? null;

            if (($rules['required'] ?? false) && ($value === null || $value === '')) {
                throw new DocumentValidationException("Item field [{$field}] is required for item type [{$itemType}].");
            }
        }
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public function calculateItemTotal(array $input, string $itemType, int $tenantId): string
    {
        $itemTypeRow = DB::table('document_item_types')->where('code', $itemType)->orWhere('name', $itemType)->first();
        if (! $itemTypeRow) {
            return $this->fallbackCalculation($input);
        }

        $definition = DB::table('document_item_definitions')
            ->where('tenant_id', $tenantId)
            ->where('item_type_id', $itemTypeRow->id)
            ->where('is_active', true)
            ->orderByDesc('version')
            ->first();

        if (! $definition || blank($definition->calculation_rule)) {
            return $this->fallbackCalculation($input);
        }

        $expression = (string) $definition->calculation_rule;
        $flattened = $this->flattenVariables($input);

        foreach ($flattened as $key => $value) {
            $expression = str_replace($key, $this->normalizeNumber($value), $expression);
        }

        $expression = preg_replace('/\s+/', '', $expression) ?? $expression;

        if (! preg_match('/^[0-9+\-*\/().]+$/', $expression)) {
            throw new DocumentValidationException("Unsafe calculation expression for item type [{$itemType}].");
        }

        /** @var float|int $result */
        $result = eval("return {$expression};");

        return $this->normalizeNumber($result);
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, string>
     */
    private function flattenVariables(array $context): array
    {
        $variables = [];

        foreach ($context as $key => $value) {
            if (is_array($value)) {
                foreach ($value as $childKey => $childValue) {
                    $variables["data.{$childKey}"] = $this->normalizeNumber($childValue);
                }

                continue;
            }

            $variables[$key] = $this->normalizeNumber($value);
        }

        return $variables;
    }

    /**
     * @param  array<string, mixed>  $input
     */
    private function fallbackCalculation(array $input): string
    {
        $quantity = $this->normalizeNumber($input['quantity'] ?? $input['data']['quantity'] ?? 1);
        $unitPrice = $this->normalizeNumber(
            $input['unit_price']
            ?? $input['amount']
            ?? $input['data']['unit_price']
            ?? $input['data']['amount']
            ?? 0,
        );
        $discount = $this->normalizeNumber($input['discount_amount'] ?? $input['data']['discount_amount'] ?? 0);
        $tax = $this->normalizeNumber($input['tax_amount'] ?? $input['data']['tax_amount'] ?? 0);

        return bcadd(bcsub(bcmul($quantity, $unitPrice, 4), $discount, 4), $tax, 4);
    }

    private function normalizeNumber(mixed $value): string
    {
        return number_format((float) $value, 4, '.', '');
    }
}
