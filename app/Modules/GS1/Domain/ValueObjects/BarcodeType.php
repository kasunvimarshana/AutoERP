<?php

declare(strict_types=1);

namespace Modules\GS1\Domain\ValueObjects;

class BarcodeType
{
    public const GS1_128    = 'gs1_128';
    public const EAN_13     = 'ean_13';
    public const EAN_8      = 'ean_8';
    public const UPC_A      = 'upc_a';
    public const DATAMATRIX = 'datamatrix';
    public const QR_CODE    = 'qr_code';

    public static function values(): array
    {
        return ['gs1_128', 'ean_13', 'ean_8', 'upc_a', 'datamatrix', 'qr_code'];
    }
}
