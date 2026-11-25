<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Hr\Services\Capture;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Hr\Enums\BatchImportStatus;
use Modules\Hr\Enums\CaptureMethod;
use Modules\Hr\Enums\PunchType;
use Modules\Hr\Models\HrAttendanceBatchImport;
use Modules\Hr\Models\HrEmployee;
use SimpleXMLElement;
use ZipArchive;

final class BatchAttendanceImportService
{
    public function __construct(
        private readonly AttendanceCaptureAdapterManager $adapterManager,
    ) {}

    /**
     * Phase 1: Dry run preview validation (supports CSV/TSV content or Excel .xlsx file path).
     *
     * @return array{
     *     total_rows: int,
     *     valid_rows: int,
     *     invalid_rows: int,
     *     rows: list<array<string, mixed>>,
     *     error_summary: list<string>
     * }
     */
    public function preview(int $tenantId, string $csvContent, ?string $filePath = null): array
    {
        $parsed = ($filePath !== null && $this->isXlsxFile($filePath))
            ? $this->parseXlsx($filePath)
            : $this->parseCsv($csvContent);
        $total = count($parsed);
        $validCount = 0;
        $invalidCount = 0;
        $validatedRows = [];
        $errorSummary = [];

        // Preload tenant employees
        $employeesByNumber = HrEmployee::query()
            ->where('tenant_id', $tenantId)
            ->get()
            ->keyBy(fn (HrEmployee $e) => strtoupper(trim((string) $e->employee_number)));

        foreach ($parsed as $index => $row) {
            $rowNum = $index + 2; // Accounting for 1-based index and header
            $errors = [];

            $empCode = strtoupper(trim((string) ($row['employee_code'] ?? $row['employee_number'] ?? '')));
            $date = $this->normalizeImportDate(trim((string) ($row['date'] ?? '')));
            $time = $this->normalizeImportTime(trim((string) ($row['time'] ?? $row['punch_time'] ?? '')));
            $typeStr = strtolower(trim((string) ($row['punch_type'] ?? 'in')));
            $notes = trim((string) ($row['notes'] ?? ''));

            if ($empCode === '') {
                $errors[] = "Row {$rowNum}: Missing employee code.";
            } elseif (! isset($employeesByNumber[$empCode])) {
                $errors[] = "Row {$rowNum}: Employee code [{$empCode}] not found.";
            }

            $dateValid = false;
            if ($date === '') {
                $errors[] = "Row {$rowNum}: Missing punch date.";
            } else {
                try {
                    CarbonImmutable::parse($date);
                    $dateValid = true;
                } catch (\Throwable) {
                    $errors[] = "Row {$rowNum}: Invalid date format [{$date}].";
                }
            }

            if ($time === '') {
                $errors[] = "Row {$rowNum}: Missing punch time.";
            } else {
                try {
                    $probeDate = $dateValid ? $date : '2000-01-01';
                    CarbonImmutable::parse("{$probeDate} {$time}");
                } catch (\Throwable) {
                    $errors[] = "Row {$rowNum}: Invalid time format [{$time}].";
                }
            }

            $punchType = PunchType::tryFrom($typeStr);
            if ($punchType === null) {
                $errors[] = "Row {$rowNum}: Invalid punch type [{$typeStr}]. Allowed: in, out, break_in, break_out.";
            }

            $isValid = empty($errors);
            if ($isValid) {
                $validCount++;
            } else {
                $invalidCount++;
                foreach ($errors as $e) {
                    $errorSummary[] = $e;
                }
            }

            $validatedRows[] = [
                'row_number' => $rowNum,
                'employee_code' => $empCode,
                'employee_name' => isset($employeesByNumber[$empCode]) ? $employeesByNumber[$empCode]->display_name : null,
                'employee_id' => isset($employeesByNumber[$empCode]) ? $employeesByNumber[$empCode]->id : null,
                'date' => $date,
                'time' => $time,
                'punch_type' => $typeStr,
                'notes' => $notes,
                'is_valid' => $isValid,
                'errors' => $errors,
            ];
        }

        return [
            'total_rows' => $total,
            'valid_rows' => $validCount,
            'invalid_rows' => $invalidCount,
            'rows' => $validatedRows,
            'error_summary' => $errorSummary,
        ];
    }

    /**
     * Phase 2: Atomic commit of validated batch.
     */
    public function commit(int $tenantId, ?int $userId, string $fileName, string $csvContent, ?string $filePath = null): HrAttendanceBatchImport
    {
        $preview = $this->preview($tenantId, $csvContent, $filePath);
        if ($preview['invalid_rows'] > 0) {
            throw new InvalidArgumentException(
                'Batch import contains '.count($preview['error_summary']).' validation errors. Review errors and correct the file.',
            );
        }

        if ($preview['valid_rows'] === 0) {
            throw new InvalidArgumentException('Batch import file contains no valid attendance rows.');
        }

        $batchNumber = 'IMP-'.CarbonImmutable::now()->format('YmdHis').'-'.bin2hex(random_bytes(3));

        return DB::transaction(function () use ($tenantId, $userId, $fileName, $preview, $batchNumber): HrAttendanceBatchImport {
            $batchRecord = HrAttendanceBatchImport::create([
                'tenant_id' => $tenantId,
                'batch_number' => $batchNumber,
                'imported_by_user_id' => ($userId !== null && $userId > 0) ? $userId : null,
                'file_name' => $fileName,
                'total_rows' => $preview['total_rows'],
                'processed_rows' => 0,
                'skipped_rows' => 0,
                'failed_rows' => 0,
                'status' => BatchImportStatus::Pending,
            ]);

            $processed = 0;
            $skipped = 0;

            foreach ($preview['rows'] as $row) {
                if (! $row['is_valid'] || empty($row['employee_id'])) {
                    $skipped++;

                    continue;
                }

                $punchTime = CarbonImmutable::parse("{$row['date']} {$row['time']}");
                $punchType = PunchType::from($row['punch_type']);
                $idempotency = hash('sha256', "import:{$tenantId}:{$row['employee_id']}:{$punchType->value}:{$punchTime->toDateTimeString()}");

                $this->adapterManager->capture($tenantId, CaptureMethod::FileUpload, [
                    'employee_id' => (int) $row['employee_id'],
                    'punch_type' => $punchType,
                    'punch_time' => $punchTime->toDateTimeString(),
                    'notes' => "Imported via batch [{$batchNumber}]. ".(string) $row['notes'],
                    'idempotency_hash' => $idempotency,
                ]);

                $processed++;
            }

            $batchRecord->update([
                'processed_rows' => $processed,
                'skipped_rows' => $skipped,
                'status' => BatchImportStatus::Imported,
            ]);

            return $batchRecord->refresh();
        });
    }

    private function normalizeImportDate(string $value): string
    {
        if ($value === '' || ! is_numeric($value)) {
            return $value;
        }

        $serial = (float) $value;
        if ($serial < 1.0) {
            return $value;
        }

        // Excel 1900 date system (with 1900 leap-year bug offset: day 0 = 1899-12-30)
        $days = (int) floor($serial);

        return CarbonImmutable::create(1899, 12, 30, 0, 0, 0, 'UTC')
            ->addDays($days)
            ->toDateString();
    }

    private function normalizeImportTime(string $value): string
    {
        if ($value === '' || ! is_numeric($value)) {
            return $value;
        }

        $serial = (float) $value;
        if ($serial < 0.0) {
            return $value;
        }

        $fraction = $serial - floor($serial);
        $seconds = (int) round($fraction * 86400);
        if ($seconds >= 86400) {
            $seconds = 86399;
        }

        $hours = intdiv($seconds, 3600);
        $minutes = intdiv($seconds % 3600, 60);
        $secs = $seconds % 60;

        return sprintf('%02d:%02d:%02d', $hours, $minutes, $secs);
    }

    private function isXlsxFile(string $filePath): bool
    {
        if (! is_file($filePath)) {
            return false;
        }

        $handle = fopen($filePath, 'rb');
        if ($handle === false) {
            return false;
        }

        $magic = fread($handle, 4);
        fclose($handle);

        return $magic === "PK\x03\x04";
    }

    /**
     * Parse the first worksheet of an Excel .xlsx file into associative rows.
     *
     * @return list<array<string, string>>
     */
    private function parseXlsx(string $filePath): array
    {
        if (! class_exists(ZipArchive::class)) {
            throw new InvalidArgumentException('Excel (.xlsx) import requires the PHP zip extension.');
        }

        $zip = new ZipArchive;
        if ($zip->open($filePath) !== true) {
            throw new InvalidArgumentException('Unable to open Excel (.xlsx) file.');
        }

        try {
            $sharedStrings = [];
            $sharedXml = $zip->getFromName('xl/sharedStrings.xml');
            if ($sharedXml !== false) {
                $sst = @simplexml_load_string($sharedXml);
                if ($sst instanceof SimpleXMLElement) {
                    foreach ($sst->si as $si) {
                        if (isset($si->t)) {
                            $sharedStrings[] = (string) $si->t;
                        } else {
                            $text = '';
                            foreach ($si->r as $run) {
                                $text .= (string) $run->t;
                            }
                            $sharedStrings[] = $text;
                        }
                    }
                }
            }

            $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
            if ($sheetXml === false) {
                throw new InvalidArgumentException('Excel (.xlsx) file does not contain worksheet 1.');
            }

            $sheet = @simplexml_load_string($sheetXml);
            if (! $sheet instanceof SimpleXMLElement || ! isset($sheet->sheetData->row)) {
                return [];
            }

            $rawRows = [];
            foreach ($sheet->sheetData->row as $rowNode) {
                $rowCells = [];
                foreach ($rowNode->c as $cell) {
                    $ref = (string) $cell['r'];
                    $colLetters = preg_replace('/\d+/', '', $ref) ?: 'A';
                    $colIdx = $this->columnLettersToIndex($colLetters);
                    $type = (string) $cell['t'];

                    if ($type === 's') {
                        $idx = (int) $cell->v;
                        $value = $sharedStrings[$idx] ?? '';
                    } elseif ($type === 'inlineStr') {
                        $value = (string) ($cell->is->t ?? '');
                    } else {
                        $value = (string) ($cell->v ?? '');
                    }

                    $rowCells[$colIdx] = trim($value);
                }

                if ($rowCells !== []) {
                    ksort($rowCells);
                    $maxCol = max(array_keys($rowCells));
                    $normalized = [];
                    for ($i = 0; $i <= $maxCol; $i++) {
                        $normalized[] = $rowCells[$i] ?? '';
                    }
                    $rawRows[] = $normalized;
                }
            }

            if ($rawRows === []) {
                return [];
            }

            $headers = array_map(static fn (string $h): string => strtolower(trim($h)), array_shift($rawRows));
            $rows = [];
            foreach ($rawRows as $values) {
                if (implode('', $values) === '') {
                    continue;
                }
                $row = [];
                foreach ($headers as $i => $header) {
                    $row[$header] = $values[$i] ?? '';
                }
                $rows[] = $row;
            }

            return $rows;
        } finally {
            $zip->close();
        }
    }

    private function columnLettersToIndex(string $letters): int
    {
        $letters = strtoupper($letters);
        $index = 0;
        $length = strlen($letters);
        for ($i = 0; $i < $length; $i++) {
            $index = ($index * 26) + (ord($letters[$i]) - ord('A') + 1);
        }

        return max(0, $index - 1);
    }

    /**
     * @return list<array<string, string>>
     */
    private function parseCsv(string $content): array
    {
        $lines = array_filter(array_map('trim', explode("\n", $content)));
        if (empty($lines)) {
            return [];
        }

        $headerLine = array_shift($lines);
        $delimiter = str_contains($headerLine, "\t") ? "\t" : ',';
        $headers = array_map(fn ($h) => strtolower(trim($h, " \"'\r\n")), str_getcsv($headerLine, $delimiter));

        $rows = [];
        foreach ($lines as $line) {
            if (trim($line) === '') {
                continue;
            }
            $values = str_getcsv($line, $delimiter);
            $row = [];
            foreach ($headers as $i => $header) {
                $row[$header] = isset($values[$i]) ? trim($values[$i], " \"'\r\n") : '';
            }
            $rows[] = $row;
        }

        return $rows;
    }
}
