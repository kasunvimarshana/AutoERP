<?php

declare(strict_types=1);

namespace Modules\VehicleService\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;
use Modules\Core\Application\DTO\DataRecord;

final class VehicleServiceLaborItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if ($this->resource instanceof DataRecord) {
            return $this->withLabels($this->resource->toArray());
        }

        if (is_array($this->resource)) {
            return $this->withLabels($this->resource);
        }

        return [];
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function withLabels(array $payload): array
    {
        $tenantId = isset($payload['tenant_id']) ? (int) $payload['tenant_id'] : 0;
        $itemId = isset($payload['item_id']) ? (int) $payload['item_id'] : 0;
        $uomId = isset($payload['uom_id']) ? (int) $payload['uom_id'] : 0;

        if ($tenantId > 0 && $itemId > 0) {
            $item = DB::table('items')->where('tenant_id', $tenantId)->where('id', $itemId)->first();
            if ($item !== null) {
                $payload['item'] = [
                    'id' => $itemId,
                    'code' => $item->sku ?? null,
                    'name' => $item->name ?? null,
                    'type' => $item->type ?? null,
                    'display_name' => trim((string) ($item->sku ?? '') . ' - ' . (string) ($item->name ?? ''), ' -'),
                ];
                $payload['item_label'] = $payload['item']['display_name'];
            }
        }

        if ($uomId > 0) {
            $uom = DB::table('unit_of_measures')->where('id', $uomId)->first();
            if ($uom !== null) {
                $payload['uom'] = [
                    'id' => $uomId,
                    'code' => $uom->code ?? null,
                    'name' => $uom->name ?? null,
                    'symbol' => $uom->symbol ?? null,
                    'display_name' => trim((string) ($uom->symbol ?? $uom->code ?? '') . ' - ' . (string) ($uom->name ?? ''), ' -'),
                ];
                $payload['uom_label'] = $payload['uom']['display_name'];
            }
        }

        return $payload;
    }
}
