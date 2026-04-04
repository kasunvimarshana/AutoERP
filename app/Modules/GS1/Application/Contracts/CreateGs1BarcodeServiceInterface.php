<?php
namespace Modules\GS1\Application\Contracts;

use Modules\GS1\Application\DTOs\GS1BarcodeData;
use Modules\GS1\Domain\Entities\GS1Barcode;

interface CreateGS1BarcodeServiceInterface
{
    public function execute(GS1BarcodeData $data): GS1Barcode;
}
