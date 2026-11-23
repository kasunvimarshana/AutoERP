<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Invoice\Services;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Log;
use Modules\Invoice\Validators\InvoiceBladeSecurityValidator;
use RuntimeException;
use Throwable;

final readonly class InvoiceCustomBladeRenderer
{
    public function __construct(
        private InvoiceBladeSecurityValidator $validator,
    ) {}

    /**
     * Renders a custom Blade template string with the provided view data in a secure sandbox.
     *
     * @param  array<string, mixed>  $viewData
     *
     * @throws RuntimeException
     */
    public function render(string $bladeContent, array $viewData, bool $isSafeMode = false): string
    {
        // 1. Security & syntax validation
        $this->validator->validate($bladeContent);

        try {
            // 2. Render within Blade engine
            return Blade::render($bladeContent, $viewData, deleteCachedView: true);
        } catch (Throwable $e) {
            Log::warning('Custom invoice Blade rendering failure', [
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
            ]);

            if ($isSafeMode) {
                return sprintf(
                    '<div style="background:#fef2f2;border:2px dashed #dc2626;color:#991b1b;padding:16px;border-radius:8px;font-family:sans-serif;margin:12px 0;">'
                    .'<h4 style="margin:0 0 6px;font-size:14px;font-weight:700;">Template Rendering Error (Safe Mode Preview)</h4>'
                    .'<p style="margin:0;font-size:12px;line-height:1.4;">%s</p>'
                    .'</div>',
                    htmlspecialchars($e->getMessage(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
                );
            }

            throw new RuntimeException('Failed to render custom invoice template: '.$e->getMessage(), 0, $e);
        }
    }
}
