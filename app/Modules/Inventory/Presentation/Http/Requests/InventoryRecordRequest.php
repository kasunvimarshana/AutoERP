<?php

declare(strict_types=1);

namespace Modules\Inventory\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;
use Modules\Inventory\Domain\Services\InventoryDomainService;

class InventoryRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $resource = app(InventoryDomainService::class)->normalizeResourceKey((string) $this->route('resource'));
        $rules = config("inventory.resources.{$resource}.rules");

        if (! is_array($rules)) {
            throw ValidationException::withMessages([
                'resource' => ["Inventory resource [{$resource}] is not configured."],
            ]);
        }

        return $rules;
    }
}
