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
use Modules\Hr\DTOs\PunchData;
use Modules\Hr\Enums\BatchImportStatus;
use Modules\Hr\Enums\CaptureMethod;
use Modules\Hr\Enums\PunchSource;
use Modules\Hr\Enums\PunchType;
use Modules\Hr\Models\HrAttendanceBatchImport;
use Modules\Hr\Models\HrEmployee;
use Modules\Hr\Services\AttendanceCalculationEngine;

final class BatchAttendanceImportService
{
    public function __construct(
        private readonly AttendanceCalculationEngine $calculationEngine,
    ) {}

    /**
     * Phase 1: Dry run preview validation.
     *
     * @return array{
     *     total_rows: int,
     *     valid_rows: int,
     *     invalid_rows: int,
     *     rows: list<array<string, mixed>>,
     *     error_summary: list<string>
     * }
     */
    public function preview(int $tenantId, string $csvContent): array
    {
        $parsed = $this->parseCsv($csvContent);
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
            $date = trim((string) ($row['date'] ?? ''));
            $time = trim((string) ($row['time'] ?? $row['punch_time'] ?? ''));
            $typeStr = strtolower(trim((string) ($row['punch_type'] ?? 'in')));
            $notes = trim((string) ($row['notes'] ?? ''));

            if ($empCode === '') {
                $errors[] = "Row {$rowNum}: Missing employee code.";
            } elseif (! isset($employeesByNumber[$empCode])) {
                $errors[] = "Row {$rowNum}: Employee code [{$empCode}] not found.";
            }

            if ($date === '') {
                $errors[] = "Row {$rowNum}: Missing punch date.";
            } else {
                try {
                    CarbonImmutable::parse($date);
                } catch (\Throwable) {
                    $errors[] = "Row {$rowNum}: Invalid date format [{$date}].";
                }
            }

            if ($time === '') {
                $errors[] = "Row {$rowNum}: Missing punch time.";
            } else {
                try {
                    CarbonImmutable::parse("{$date} {$time}");
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
    public function commit(int $tenantId, ?int $userId, string $fileName, string $csvContent): HrAttendanceBatchImport
    {
        $preview = $this->preview($tenantId, $csvContent);
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

                $this->calculationEngine->recordPunch($tenantId, new PunchData(
                    employeeId: (int) $row['employee_id'],
                    punchType: $punchType,
                    punchTime: $punchTime->toDateTimeString(),
                    punchSource: PunchSource::ManualEntry,
                    captureMethod: CaptureMethod::FileUpload,
                    notes: "Imported via batch [{$batchNumber}]. ".(string) $row['notes'],
                    idempotencyHash: $idempotency,
                ));

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
