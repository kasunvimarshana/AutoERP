<?php

declare(strict_types=1);

namespace Modules\Finance\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Modules\Finance\Http\Requests\CustomerOutstandingReportRequest;
use Modules\Finance\Services\CustomerOutstandingReportService;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class CustomerOutstandingReportController
{
    public function locations(CustomerOutstandingReportRequest $request, CustomerOutstandingReportService $report): JsonResponse
    {
        return response()->json(['data' => $report->locations($request)]);
    }

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
                'Location', 'Customer', 'Invoice', 'Invoice date', 'No. of days', 'Due days', 'Currency', 'Balance',
            ], ',', '"', '');

            foreach ($rows as $row) {
                fputcsv($output, [
                    self::safeCsvText($row['location']),
                    self::safeCsvText($row['customer']),
                    self::safeCsvText($row['invoice']),
                    $row['invoice_date'],
                    $row['invoice_age_days'],
                    $row['due_days'],
                    self::safeCsvText($row['currency']),
                    $row['balance'],
                ], ',', '"', '');
            }

            fclose($output);
        }, 'customer-outstanding-as-at-'.$request->input('as_of_date', $request->reportToday()).'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private static function safeCsvText(string $value): string
    {
        return preg_match('/^[=+\-@\t\r]/', $value) === 1 ? "'".$value : $value;
    }
}
