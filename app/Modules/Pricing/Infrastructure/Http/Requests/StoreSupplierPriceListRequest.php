<?php

declare(strict_types=1);

namespace Modules\Pricing\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSupplierPriceListRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'price_list_id' => 'required|integer|exists:price_lists,id',
            'priority' => 'nullable|integer|min:0|max:100000',
        ];
    }
}
