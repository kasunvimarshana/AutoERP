<?php
namespace Modules\GS1\Application\Services;

use Modules\GS1\Application\Contracts\GenerateGS1LabelServiceInterface;
use Modules\GS1\Application\DTOs\GS1LabelData;
use Modules\GS1\Domain\Entities\GS1Label;
use Modules\GS1\Domain\Events\GS1LabelGenerated;
use Modules\GS1\Domain\RepositoryInterfaces\GS1BarcodeRepositoryInterface;
use Modules\GS1\Domain\RepositoryInterfaces\GS1LabelRepositoryInterface;

class GenerateGS1LabelService implements GenerateGS1LabelServiceInterface
{
    public function __construct(
        private readonly GS1BarcodeRepositoryInterface $barcodeRepository,
        private readonly GS1LabelRepositoryInterface $labelRepository,
    ) {}

    public function execute(GS1LabelData $data): GS1Label
    {
        $barcode = $this->barcodeRepository->findById($data->barcodeId);
        if (!$barcode) {
            throw new \DomainException("GS1 Barcode not found: {$data->barcodeId}");
        }

        $content = json_encode([
            'gtin'          => $barcode->gtin,
            'barcode_type'  => $barcode->barcodeType,
            'product_id'    => $barcode->productId,
            'variant_id'    => $barcode->variantId,
            'batch_id'      => $data->batchId,
            'serial_number' => $data->serialNumber,
        ]);

        $label = $this->labelRepository->create([
            'tenant_id'     => $data->tenantId,
            'barcode_id'    => $data->barcodeId,
            'label_format'  => $data->labelFormat,
            'content'       => $content,
            'batch_id'      => $data->batchId,
            'serial_number' => $data->serialNumber,
            'generated_at'  => now(),
        ]);

        event(new GS1LabelGenerated($data->tenantId, $label->id));

        return $label;
    }
}
