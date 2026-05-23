<?php

declare(strict_types=1);

namespace Modules\Invoice\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;
use Modules\Invoice\Domain\Services\InvoiceDomainService;

class InvoiceRecordRequest extends FormRequest
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
        $resource = app(InvoiceDomainService::class)->normalizeResourceKey((string) $this->route('resource'));
        $rules = config("invoice.resources.{$resource}.rules");

        if (! is_array($rules)) {
            throw ValidationException::withMessages([
                'resource' => ["Invoice resource [{$resource}] is not configured."],
            ]);
        }

        return $rules;
    }
}
