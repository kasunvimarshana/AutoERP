<?php
namespace Modules\GS1\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Modules\GS1\Domain\Entities\GS1Label;

class GS1LabelResource extends JsonResource
{
    public function __construct(private readonly GS1Label $label)
    {
        parent::__construct($label);
    }

    public function toArray($request): array
    {
        return [
            'id'            => $this->label->id,
            'tenant_id'     => $this->label->tenantId,
            'barcode_id'    => $this->label->barcodeId,
            'label_format'  => $this->label->labelFormat,
            'content'       => $this->label->content,
            'batch_id'      => $this->label->batchId,
            'serial_number' => $this->label->serialNumber,
            'generated_at'  => $this->label->generatedAt?->format('Y-m-d\TH:i:s\Z'),
        ];
    }
}
