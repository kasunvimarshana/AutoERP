<?php

declare(strict_types=1);

namespace Modules\Reporting\Exporters;

use Illuminate\Http\Response;
use Modules\Reporting\DTOs\ReportData;
use Modules\Reporting\Services\ReportFilename;
use Spatie\Browsershot\Browsershot;

final class PdfExporter
{
    public function __construct(
        private readonly HtmlExporter $html,
        private readonly ReportFilename $filenames,
    ) {}

    public function export(ReportData $report): Response
    {
        $pdfReport = $report->withMode('pdf');
        $browser = Browsershot::html($this->html->render($pdfReport))
            ->setNodeModulePath((string) config('reporting.browsershot.node_modules_path', base_path('node_modules')))
            ->format('A4')
            ->margins(
                (float) config('reporting.pdf.margins.top', 24),
                (float) config('reporting.pdf.margins.right', 10),
                (float) config('reporting.pdf.margins.bottom', 18),
                (float) config('reporting.pdf.margins.left', 10),
            )
            ->showBackground()
            ->showBrowserHeaderAndFooter()
            ->headerHtml(view('reports.shared.browser-header', $pdfReport->viewData())->render())
            ->footerHtml(view('reports.shared.browser-footer', $pdfReport->viewData())->render())
            ->landscape($pdfReport->orientation() === 'landscape')
            ->timeout((int) config('reporting.browsershot.timeout', 120));

        $nodeBinary = config('reporting.browsershot.node_binary');
        if (is_string($nodeBinary) && $nodeBinary !== '') {
            $browser->setNodeBinary($nodeBinary);
        }

        $npmBinary = config('reporting.browsershot.npm_binary');
        if (is_string($npmBinary) && $npmBinary !== '') {
            $browser->setNpmBinary($npmBinary);
        }

        $chromePath = $this->chromePath();
        if ($chromePath !== null) {
            $browser->setChromePath($chromePath);
        }

        return response($browser->pdf(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$this->filenames->make($report->definition, 'pdf').'"',
        ]);
    }

    private function chromePath(): ?string
    {
        $configured = config('reporting.browsershot.chrome_path');
        if (is_string($configured) && $configured !== '') {
            return $configured;
        }

        foreach ([
            'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
            'C:\\Program Files (x86)\\Google\\Chrome\\Application\\chrome.exe',
            'C:\\Program Files\\Microsoft\\Edge\\Application\\msedge.exe',
            'C:\\Program Files (x86)\\Microsoft\\Edge\\Application\\msedge.exe',
        ] as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        return null;
    }
}
