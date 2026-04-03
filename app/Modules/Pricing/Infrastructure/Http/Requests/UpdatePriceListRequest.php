<?php

declare(strict_types=1);

namespace Modules\Pricing\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Pricing\Domain\ValueObjects\PriceListType;
use Modules\Pricing\Domain\ValueObjects\PricingMethod;

class UpdatePriceListRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'           => 'sometimes|required|string|max:255',
            'code'           => 'sometimes|required|string|max:100',
            'type'           => 'sometimes|required|string|in:'.implode(',', PriceListType::values()),
            'pricing_method' => 'sometimes|required|string|in:'.implode(',', PricingMethod::values()),
            'currency_code'  => 'sometimes|required|string|size:3',
            'start_date'     => 'nullable|date',
            'end_date'       => 'nullable|date',
            'is_active'      => 'nullable|boolean',
            'description'    => 'nullable|string',
            'metadata'       => 'nullable|array',
        ];
    }
}
