<?php

declare(strict_types=1);

namespace Modules\Voucher\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Voucher\Enums\VoucherType;

final class VoucherResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $row = is_array($this->resource)
            ? $this->resource
            : get_object_vars($this->resource);

        $type = VoucherType::tryFrom((string) ($row['voucher_type'] ?? ''));

        return array_replace([
            'voucher_type' => $type?->value,
            'voucher_label' => $type?->label(),
            'available_actions' => ['view_source', 'print'],
            'print_available' => true,
        ], $row, [
            'source_document_url' => $row['source_document_url'] ?? $this->sourceUrl($row),
            'voucher_url' => $this->voucherUrl($row),
        ]);
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function sourceUrl(array $row): ?string
    {
        return match ((string) ($row['source_kind'] ?? '')) {
            'payment' => '/payments/'.(string) $row['source_id'],
            'finance_journal' => '/finance/journals/'.(string) $row['source_id'],
            'payment_reversal' => isset($row['external_reference']) ? null : null,
            default => null,
        };
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function voucherUrl(array $row): ?string
    {
        if (($row['voucher_type'] ?? null) === null || ($row['source_id'] ?? null) === null) {
            return null;
        }

        $query = ($row['source_kind'] ?? null) !== null
            ? '?source_kind='.(string) $row['source_kind']
            : '';

        return '/vouchers/'.(string) $row['voucher_type'].'/'.(string) $row['source_id'].$query;
    }
}
