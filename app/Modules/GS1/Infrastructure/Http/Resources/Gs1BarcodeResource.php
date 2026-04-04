<?php
namespace Modules\GS1\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Modules\GS1\Domain\Entities\GS1Barcode;

class GS1BarcodeResource extends JsonResource
{
    public function __construct(private readonly GS1Barcode $barcode)
    {
        parent::__construct($barcode);
    }

    public function toArray($request): array
    {
        return [
            'id'                 => $this->barcode->id,
            'tenant_id'          => $this->barcode->tenantId,
            'product_id'         => $this->barcode->productId,
            'variant_id'         => $this->barcode->variantId,
            'gs1_company_prefix' => $this->barcode->gs1CompanyPrefix,
            'item_reference'     => $this->barcode->itemReference,
            'check_digit'        => $this->barcode->checkDigit,
            'gtin'               => $this->barcode->gtin,
            'barcode_type'       => $this->barcode->barcodeType,
            'is_active'          => $this->barcode->isActive,
        ];
    }
}
