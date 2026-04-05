<?php

declare(strict_types=1);

namespace Modules\Barcode\Infrastructure\Printing\Drivers;

use Modules\Barcode\Domain\Entities\BarcodeDefinition;
use Modules\Barcode\Domain\Entities\LabelTemplate;
use Modules\Barcode\Infrastructure\Printing\BarcodePrinterDriverInterface;

/**
 * Renders label output in Zebra Programming Language (ZPL II) format.
 *
 * ZPL is the native language of Zebra label printers. The generated output
 * is a complete ZPL program that encodes the barcode using the ^BC (Code 128),
 * ^BQ (QR Code), or ^BE (EAN/UPC) commands, with a human-readable text line.
 *
 * When a LabelTemplate is provided its content is used as the ZPL body with
 * {{ placeholder }} substitution; otherwise a sensible default layout is
 * generated automatically.
 */
class ZplPrinterDriver implements BarcodePrinterDriverInterface
{
    public function getFormat(): string
    {
        return 'zpl';
    }

    public function render(
        BarcodeDefinition $definition,
        ?LabelTemplate    $template,
        array             $variables,
    ): string {
        $variables = $this->buildVariables($definition, $variables);

        if ($template !== null) {
            return $template->render($variables);
        }

        return $this->buildDefaultZpl($variables);
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    /** @return array<string,string> */
    private function buildVariables(BarcodeDefinition $def, array $extra): array
    {
        return array_merge([
            'barcode_value'  => $def->getValue(),
            'barcode_label'  => $def->getLabel() ?? $def->getValue(),
            'barcode_type'   => $def->getType(),
        ], $extra);
    }

    /** @param array<string,string> $vars */
    private function buildDefaultZpl(array $vars): string
    {
        $value = addslashes($vars['barcode_value']);
        $label = addslashes($vars['barcode_label']);
        $type  = strtolower($vars['barcode_type']);

        $barcodeCmd = match (true) {
            str_starts_with($type, 'qr')         => "^BQN,2,4\n^FDQA,{$value}^FS",
            str_starts_with($type, 'ean13')       => "^BEN,,Y,N\n^FD{$value}^FS",
            str_starts_with($type, 'ean8')        => "^BE8,,Y,N\n^FD{$value}^FS",
            str_starts_with($type, 'upca')        => "^BUN,,Y,N\n^FD{$value}^FS",
            default                                => "^BCN,80,Y,N,N\n^FD{$value}^FS",
        };

        return implode("\n", [
            '^XA',
            '^FO50,30',
            $barcodeCmd,
            '^FO50,130^A0N,28,28',
            "^FD{$label}^FS",
            '^XZ',
        ]);
    }
}
