<?php

namespace Modules\Document\Domain\Services;

use Modules\Document\Domain\Exceptions\DocumentValidationException;
use Modules\Document\Domain\Repositories\DocumentItemDefinitionRepositoryInterface;

class DocumentDomainService
{
    public function __construct(
        private readonly DocumentItemDefinitionRepositoryInterface $itemDefinitionRepository,
    ) {}

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
        $definition = $this->itemDefinitionRepository->findActiveByItemType($tenantId, $itemType);

        if (! $definition) {
            throw new DocumentValidationException("No active definition found for item type [{$itemType}].");
        }

        $schema = $definition['field_schema'];

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
        $definition = $this->itemDefinitionRepository->findActiveByItemType($tenantId, $itemType);

        if (! $definition || blank($definition['calculation_rule'])) {
            return $this->fallbackCalculation($input);
        }

        $expression = (string) $definition['calculation_rule'];
        $flattened = $this->flattenVariables($input);

        uksort($flattened, static fn (string $left, string $right): int => strlen($right) <=> strlen($left));

        foreach ($flattened as $key => $value) {
            $escapedKey = preg_quote($key, '/');
            $expression = preg_replace(
                "/(?<![A-Za-z0-9_.]){$escapedKey}(?![A-Za-z0-9_.])/",
                $this->normalizeNumber($value),
                $expression,
            ) ?? $expression;
        }

        $expression = preg_replace('/\s+/', '', $expression) ?? $expression;

        if (! preg_match('/^[0-9+\-*\/().]+$/', $expression)) {
            throw new DocumentValidationException("Unsafe calculation expression for item type [{$itemType}].");
        }

        return $this->evaluateArithmeticExpression($expression, $itemType);
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

    private function evaluateArithmeticExpression(string $expression, string $itemType): string
    {
        $tokens = $this->tokenizeExpression($expression, $itemType);
        $rpn = $this->toReversePolishNotation($tokens, $itemType);

        return $this->evaluateRpn($rpn, $itemType);
    }

    /**
     * @return array<int, string>
     */
    private function tokenizeExpression(string $expression, string $itemType): array
    {
        preg_match_all('/\d+(?:\.\d+)?|[+\-*\/()]/', $expression, $matches);
        $tokens = $matches[0] ?? [];

        if (implode('', $tokens) !== $expression) {
            throw new DocumentValidationException("Invalid calculation expression for item type [{$itemType}].");
        }

        return $tokens;
    }

    /**
     * @param  array<int, string>  $tokens
     * @return array<int, string>
     */
    private function toReversePolishNotation(array $tokens, string $itemType): array
    {
        $output = [];
        $operators = [];
        $precedence = ['+' => 1, '-' => 1, '*' => 2, '/' => 2];

        foreach ($tokens as $token) {
            if (is_numeric($token)) {
                $output[] = $token;

                continue;
            }

            if ($token === '(') {
                $operators[] = $token;

                continue;
            }

            if ($token === ')') {
                while ($operators !== [] && end($operators) !== '(') {
                    $output[] = array_pop($operators);
                }

                if ($operators === []) {
                    throw new DocumentValidationException("Unbalanced expression for item type [{$itemType}].");
                }

                array_pop($operators);

                continue;
            }

            while (
                $operators !== []
                && end($operators) !== '('
                && $precedence[end($operators)] >= $precedence[$token]
            ) {
                $output[] = array_pop($operators);
            }

            $operators[] = $token;
        }

        while ($operators !== []) {
            $operator = array_pop($operators);
            if ($operator === '(') {
                throw new DocumentValidationException("Unbalanced expression for item type [{$itemType}].");
            }
            $output[] = $operator;
        }

        return $output;
    }

    /**
     * @param  array<int, string>  $rpn
     */
    private function evaluateRpn(array $rpn, string $itemType): string
    {
        $stack = [];

        foreach ($rpn as $token) {
            if (is_numeric($token)) {
                $stack[] = $this->normalizeNumber($token);

                continue;
            }

            if (count($stack) < 2) {
                throw new DocumentValidationException("Invalid expression stack for item type [{$itemType}].");
            }

            $right = array_pop($stack);
            $left = array_pop($stack);

            $stack[] = match ($token) {
                '+' => bcadd($left, $right, 4),
                '-' => bcsub($left, $right, 4),
                '*' => bcmul($left, $right, 4),
                '/' => $this->divide($left, $right, $itemType),
                default => throw new DocumentValidationException("Unsupported operator [{$token}] for item type [{$itemType}]."),
            };
        }

        if (count($stack) !== 1) {
            throw new DocumentValidationException("Expression evaluation failed for item type [{$itemType}].");
        }

        return $stack[0];
    }

    private function divide(string $left, string $right, string $itemType): string
    {
        if (bccomp($right, '0', 4) === 0) {
            throw new DocumentValidationException("Division by zero in calculation rule for item type [{$itemType}].");
        }

        return bcdiv($left, $right, 4);
    }
}
