<?php

declare(strict_types=1);

namespace Modules\Reporting\Exporters;

use Illuminate\Http\Response;
use Modules\Reporting\DTOs\ReportData;

final class HtmlExporter
{
    public function render(ReportData $report): string
    {
        return view($report->template, $report->viewData())->render();
    }

    public function preview(ReportData $report): Response
    {
        return response($this->render($report->withMode('preview')), 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'Content-Disposition' => 'inline',
        ]);
    }

    public function print(ReportData $report): Response
    {
        return response($this->render($report->withMode('print')), 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'Content-Disposition' => 'inline',
        ]);
    }
}
