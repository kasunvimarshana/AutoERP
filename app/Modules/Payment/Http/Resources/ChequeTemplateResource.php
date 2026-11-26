<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Payment\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Payment\Models\ChequeTemplate;

/**
 * @mixin ChequeTemplate
 */
final class ChequeTemplateResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,
            'row_version' => (int) $this->row_version,
            'tenant_id' => (int) $this->tenant_id,
            'organization_unit_id' => $this->organization_unit_id !== null ? (int) $this->organization_unit_id : null,
            'organization_unit' => $this->whenLoaded('organizationUnit', fn () => $this->organizationUnit ? [
                'id' => (int) $this->organizationUnit->id,
                'name' => (string) $this->organizationUnit->name,
                'code' => (string) ($this->organizationUnit->code ?? ''),
            ] : null),
            'bank_name' => $this->bank_name !== null ? (string) $this->bank_name : null,
            'template_name' => (string) $this->template_name,
            'page_width_mm' => (string) $this->page_width_mm,
            'page_height_mm' => (string) $this->page_height_mm,
            'date_x_mm' => (string) $this->date_x_mm,
            'date_y_mm' => (string) $this->date_y_mm,
            'payee_x_mm' => (string) $this->payee_x_mm,
            'payee_y_mm' => (string) $this->payee_y_mm,
            'amount_x_mm' => (string) $this->amount_x_mm,
            'amount_y_mm' => (string) $this->amount_y_mm,
            'amount_words_x_mm' => (string) $this->amount_words_x_mm,
            'amount_words_y_mm' => (string) $this->amount_words_y_mm,
            'cheque_number_x_mm' => $this->cheque_number_x_mm !== null ? (string) $this->cheque_number_x_mm : null,
            'cheque_number_y_mm' => $this->cheque_number_y_mm !== null ? (string) $this->cheque_number_y_mm : null,
            'font_size' => (string) $this->font_size,
            'font_family' => $this->font_family !== null ? (string) $this->font_family : null,
            'is_default' => (bool) $this->is_default,
            'is_active' => (bool) $this->is_active,
            'metadata' => is_array($this->metadata) ? $this->metadata : null,
            'can_delete' => $this->canDelete(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
