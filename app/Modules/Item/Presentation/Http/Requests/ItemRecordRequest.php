<?php

declare(strict_types=1);

namespace Modules\Item\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;
use Modules\Item\Domain\Services\ItemDomainService;

class ItemRecordRequest extends FormRequest
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
        $resource = app(ItemDomainService::class)->normalizeResourceKey((string) $this->route('resource'));
        $rules = config("item.resources.{$resource}.rules");

        if (! is_array($rules)) {
            throw ValidationException::withMessages([
                'resource' => ["Item resource [{$resource}] is not configured."],
            ]);
        }

        return $rules;
    }
}
