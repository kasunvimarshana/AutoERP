<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreInventorySettingRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'tenant_id'             => 'required|integer',
            'valuation_method'      => 'string|in:fifo,lifo,avco,standard_cost,specific_identification',
            'management_method'     => 'string|in:perpetual,periodic',
            'rotation_strategy'     => 'string|in:fefo,fifo,lifo,manual',
            'allocation_algorithm'  => 'string|in:fefo,fifo,lifo,nearest_expiry,manual',
            'cycle_count_method'    => 'string|in:abc,frequency,random,manual',
            'negative_stock_allowed'=> 'boolean',
            'track_lots'            => 'boolean',
            'track_serial_numbers'  => 'boolean',
            'track_expiry'          => 'boolean',
            'auto_reorder'          => 'boolean',
            'low_stock_alert'       => 'boolean',
            'metadata'              => 'nullable|array',
            'is_active'             => 'boolean',
        ];
    }
}
