<?php

declare(strict_types=1);

namespace Modules\Pricing\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Pricing\Enums\PricingStrategy;

/**
 * StorePriceRequest
 *
 * Validates product price creation
 */
class StorePriceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id' => ['required', 'string', 'uuid', 'exists:products,id'],
            'location_id' => ['nullable', 'string', 'uuid', 'exists:organizations,id'],
            'strategy' => ['required', 'string', Rule::in(array_column(PricingStrategy::cases(), 'value'))],
            'price' => ['required', 'string', 'regex:/^\d+(\.\d+)?$/'],
            'config' => ['required', 'array'],
            'valid_from' => ['nullable', 'date'],
            'valid_until' => ['nullable', 'date', 'after:valid_from'],
            'is_active' => ['boolean'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $strategy = $this->input('strategy');
            $config = $this->input('config', []);

            if (! $this->validateStrategyConfig($strategy, $config)) {
                $validator->errors()->add('config', "Invalid configuration for {$strategy} pricing strategy");
            }
        });
    }

    protected function validateStrategyConfig(string $strategy, array $config): bool
    {
        $pricingService = app(\Modules\Pricing\Services\PricingService::class);
        $engine = $pricingService->getEngine($strategy);

        if (! $engine) {
            return false;
        }

        return $engine->validate($config);
    }

    public function messages(): array
    {
        return [
            'product_id.required' => 'Product is required',
            'product_id.exists' => 'Product not found',
            'location_id.exists' => 'Location not found',
            'strategy.required' => 'Pricing strategy is required',
            'strategy.in' => 'Invalid pricing strategy',
            'price.required' => 'Price is required',
            'price.regex' => 'Price must be a valid decimal number',
            'valid_until.after' => 'Valid until must be after valid from',
        ];
    }
}
