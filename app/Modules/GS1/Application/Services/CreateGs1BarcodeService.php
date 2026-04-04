<?php
namespace Modules\GS1\Application\Services;

use Modules\GS1\Application\Contracts\CreateGS1BarcodeServiceInterface;
use Modules\GS1\Application\DTOs\GS1BarcodeData;
use Modules\GS1\Domain\Entities\GS1Barcode;
use Modules\GS1\Domain\Events\GS1BarcodeCreated;
use Modules\GS1\Domain\RepositoryInterfaces\GS1BarcodeRepositoryInterface;

class CreateGS1BarcodeService implements CreateGS1BarcodeServiceInterface
{
    public function __construct(
        private readonly GS1BarcodeRepositoryInterface $repository,
    ) {}

    public function execute(GS1BarcodeData $data): GS1Barcode
    {
        $digits     = $data->gs1CompanyPrefix . $data->itemReference;
        $checkDigit = $this->calculateCheckDigit($digits);
        $gtin       = $digits . $checkDigit;

        $barcode = $this->repository->create([
            'tenant_id'          => $data->tenantId,
            'product_id'         => $data->productId,
            'gs1_company_prefix' => $data->gs1CompanyPrefix,
            'item_reference'     => $data->itemReference,
            'check_digit'        => $checkDigit,
            'gtin'               => $gtin,
            'barcode_type'       => $data->barcodeType,
            'variant_id'         => $data->variantId,
            'is_active'          => true,
        ]);

        event(new GS1BarcodeCreated($data->tenantId, $barcode->id));

        return $barcode;
    }

    private function calculateCheckDigit(string $digits): string
    {
        $sum        = 0;
        $multiplier = 3;
        for ($i = strlen($digits) - 1; $i >= 0; $i--) {
            $sum       += (int) $digits[$i] * $multiplier;
            $multiplier = ($multiplier === 3) ? 1 : 3;
        }
        return (string) ((10 - ($sum % 10)) % 10);
    }
}
