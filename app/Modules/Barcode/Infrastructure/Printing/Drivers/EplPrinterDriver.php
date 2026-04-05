<?php

declare(strict_types=1);

namespace Modules\Barcode\Infrastructure\Printing\Drivers;

use Modules\Barcode\Domain\Entities\BarcodeDefinition;
use Modules\Barcode\Domain\Entities\LabelTemplate;
use Modules\Barcode\Infrastructure\Printing\BarcodePrinterDriverInterface;

/**
 * Renders label output in Eltron Programming Language (EPL2) format.
 *
 * EPL is used by Eltron / legacy Zebra printers. The generated output is a
 * complete EPL2 program. When a LabelTemplate is provided its content (with
 * {{ placeholder }} substitution) is used directly; otherwise a default
 * single-barcode label is generated.
 */
class EplPrinterDriver implements BarcodePrinterDriverInterface
{
    public function getFormat(): string
    {
        return 'epl';
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

        return $this->buildDefaultEpl($variables);
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    /** @return array<string,string> */
    private function buildVariables(BarcodeDefinition $def, array $extra): array
    {
        return array_merge([
            'barcode_value' => $def->getValue(),
            'barcode_label' => $def->getLabel() ?? $def->getValue(),
            'barcode_type'  => $def->getType(),
        ], $extra);
    }

    /** @param array<string,string> $vars */
    private function buildDefaultEpl(array $vars): string
    {
        $value = $vars['barcode_value'];
        $label = $vars['barcode_label'];

        // EPL2 default: 4"×2" label at 203 dpi
        return implode("\n", [
            'N',
            'q812',          // label width in dots (4" × 203 dpi)
            "B50,30,0,1,3,7,60,B,\"{$value}\"",   // barcode command
            "A50,110,0,3,1,1,N,\"{$label}\"",      // human-readable text
            'P1',            // print 1 copy
        ]);
    }
}
