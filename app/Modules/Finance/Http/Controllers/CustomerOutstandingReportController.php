<?php

declare(strict_types=1);

namespace Modules\Finance\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Modules\Finance\Http\Requests\CustomerOutstandingReportRequest;
use Modules\Finance\Services\CustomerOutstandingReportService;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class CustomerOutstandingReportController
{
    public function index(CustomerOutstandingReportRequest $request, CustomerOutstandingReportService $report): JsonResponse
    {
        return response()->json($report->page($request));
    }

    public function invoices(
        CustomerOutstandingReportRequest $request,
        CustomerOutstandingReportService $report,
        int $customer,
    ): JsonResponse {
        $request->validate([
            'currency_code' => ['required', 'string', 'size:3'],
        ]);

        return response()->json($report->invoices($request, $customer));
    }

    public function export(CustomerOutstandingReportRequest $request, CustomerOutstandingReportService $report): StreamedResponse
    {
        $rows = $report->exportRows($request);

        return response()->streamDownload(static function () use ($rows): void {
            $output = fopen('php://output', 'wb');
            if ($output === false) {
                throw new \RuntimeException('Could not open the CSV output stream.');
            }

            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, [
                'Customer', 'Customer code', 'Customer number', 'Currency', 'Open invoices',
                'Current', '1-30 days', '31-60 days', '61-90 days', '90+ days', 'Outstanding total',
            ], ',', '"', '');

            foreach ($rows as $row) {
                fputcsv($output, [
                    self::safeCsvText((string) $row->customer_name),
                    self::safeCsvText((string) $row->customer_code),
                    self::safeCsvText((string) $row->customer_number),
                    self::safeCsvText((string) $row->currency_code),
                    (int) $row->invoice_count,
                    (string) $row->current_amount,
                    (string) $row->days_1_30_amount,
                    (string) $row->days_31_60_amount,
                    (string) $row->days_61_90_amount,
                    (string) $row->over_90_amount,
                    (string) $row->outstanding_total,
                ], ',', '"', '');
            }

            fclose($output);
        }, 'customer-outstanding-'.$request->input('as_of_date', now()->toDateString()).'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private static function safeCsvText(string $value): string
    {
        return preg_match('/^[=+\-@\t\r]/', $value) === 1 ? "'".$value : $value;
    }
}
