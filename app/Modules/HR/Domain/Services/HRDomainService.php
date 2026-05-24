<?php

declare(strict_types=1);

namespace Modules\HR\Domain\Services;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Modules\HR\Domain\Exceptions\HRIntegrityException;

class HRDomainService
{
    public function normalizeResourceKey(string $resource): string
    {
        return match (str_replace('-', '_', strtolower(trim($resource)))) {
            'contacts', 'employee_contacts' => 'employee_contacts',
            'documents', 'employee_documents' => 'employee_documents',
            'contracts', 'employee_contracts' => 'employee_contracts',
            'salary_assignments', 'employee_salary_assignments' => 'employee_salary_assignments',
            'attendance', 'attendance_records' => 'attendance_records',
            'logs', 'attendance_logs' => 'attendance_logs',
            'policies', 'leave_policies' => 'leave_policies',
            'policy_lines', 'leave_policy_lines' => 'leave_policy_lines',
            'allocations', 'leave_allocations' => 'leave_allocations',
            'applications', 'leave_applications' => 'leave_applications',
            'components', 'salary_components' => 'salary_components',
            'structures', 'salary_structures' => 'salary_structures',
            'structure_lines', 'salary_structure_lines' => 'salary_structure_lines',
            'runs', 'payroll_runs' => 'payroll_runs',
            'lines', 'payslip_lines' => 'payslip_lines',
            'cycles', 'performance_cycles' => 'performance_cycles',
            'reviews', 'performance_reviews' => 'performance_reviews',
            default => str_replace('-', '_', strtolower(trim($resource))),
        };
    }

    public function normalizeText(mixed $value): mixed
    {
        if (! is_string($value)) {
            return $value;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }

    /**
     * @param  array<string, mixed>|null  $metadata
     * @return array<string, mixed>|null
     */
    public function normalizeMetadata(?array $metadata): ?array
    {
        return $metadata === [] ? null : $metadata;
    }

    public function normalizeDecimal(string|int|float|null $value): string
    {
        return number_format((float) ($value ?? 0), (int) config('hr.precision.scale', 4), '.', '');
    }

    /**
     * @param  array<int, string>  $allowed
     */
    public function normalizeEnum(string $family, mixed $value, array $allowed, mixed $default = null): mixed
    {
        if ($value === null || $value === '') {
            return $default;
        }

        $normalized = strtolower((string) $value);

        if (! in_array($normalized, $allowed, true)) {
            throw HRIntegrityException::rule("Unsupported {$family} value [{$value}].");
        }

        return $normalized;
    }

    public function assertRowVersion(?int $expected, Model $record): void
    {
        if ($expected !== null && (int) $record->row_version !== $expected) {
            throw HRIntegrityException::conflict("Record version conflict. Expected [{$expected}], current [{$record->row_version}].");
        }
    }

    public function nextRowVersion(Model $record): int
    {
        return ((int) $record->row_version) + 1;
    }

    /**
     * @param  array<string, mixed>  $definition
     */
    public function ensureMutable(string $resource, Model $record, array $definition, bool $updating = false): void
    {
        $immutable = config("hr.immutable.{$resource}", []);

        if (($immutable['after_create'] ?? false) && $updating) {
            throw HRIntegrityException::rule("{$definition['label']} records cannot be modified after creation.");
        }

        $statusColumn = $immutable['status_column'] ?? null;

        if ($statusColumn !== null && in_array((string) $record->{$statusColumn}, $immutable['statuses'] ?? [], true)) {
            throw HRIntegrityException::rule("{$definition['label']} is locked in status [{$record->{$statusColumn}}].");
        }
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    public function prepareAttendanceRecord(array $attributes): array
    {
        $break = max(0, (int) ($attributes['break_duration'] ?? 0));
        $worked = 0;

        if (! empty($attributes['check_in']) && ! empty($attributes['check_out'])) {
            $worked = max(0, CarbonImmutable::parse($attributes['check_in'])->diffInMinutes(CarbonImmutable::parse($attributes['check_out'])) - $break);
        }

        $attributes['worked_minutes'] = $worked;
        $attributes['overtime_minutes'] = 0;

        return $attributes;
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    public function prepareLeaveApplication(array $attributes): array
    {
        $days = CarbonImmutable::parse($attributes['start_date'])->diffInDays(CarbonImmutable::parse($attributes['end_date'])) + 1;

        if (($attributes['half_day_type'] ?? null) !== null) {
            $days = 0.5;
        }

        $attributes['total_days'] = $this->normalizeDecimal($days);

        return $attributes;
    }

    /**
     * @return array{used_days: string, pending_days: string}
     */
    public function calculateLeaveAllocationUsage(Collection $applications): array
    {
        $used = $applications
            ->filter(fn (Model $application): bool => (string) $application->status === 'approved')
            ->sum(fn (Model $application): float => (float) $application->total_days);
        $pending = $applications
            ->filter(fn (Model $application): bool => (string) $application->status === 'pending')
            ->sum(fn (Model $application): float => (float) $application->total_days);

        return [
            'used_days' => $this->normalizeDecimal($used),
            'pending_days' => $this->normalizeDecimal($pending),
        ];
    }

    /**
     * @return array{total_earnings: string, total_deductions: string, net_salary: string}
     */
    public function calculatePayslipTotals(Model $payslip, Collection $lines): array
    {
        $earnings = (float) $payslip->base_salary + $lines
            ->filter(fn (Model $line): bool => (string) $line->type === 'earning')
            ->sum(fn (Model $line): float => (float) $line->amount);
        $deductions = $lines
            ->filter(fn (Model $line): bool => (string) $line->type === 'deduction')
            ->sum(fn (Model $line): float => (float) $line->amount);

        return [
            'total_earnings' => $this->normalizeDecimal($earnings),
            'total_deductions' => $this->normalizeDecimal($deductions),
            'net_salary' => $this->normalizeDecimal(max(0.0, $earnings - $deductions)),
        ];
    }

    /**
     * @return array{total_gross: string, total_deductions: string, total_net: string, total_employer_contributions: string}
     */
    public function calculatePayrollRunTotals(Collection $payslips, Collection $lines): array
    {
        return [
            'total_gross' => $this->normalizeDecimal($payslips->sum(fn (Model $payslip): float => (float) $payslip->total_earnings)),
            'total_deductions' => $this->normalizeDecimal($payslips->sum(fn (Model $payslip): float => (float) $payslip->total_deductions)),
            'total_net' => $this->normalizeDecimal($payslips->sum(fn (Model $payslip): float => (float) $payslip->net_salary)),
            'total_employer_contributions' => $this->normalizeDecimal($lines
                ->filter(fn (Model $line): bool => (string) $line->type === 'employer_contribution')
                ->sum(fn (Model $line): float => (float) $line->amount)),
        ];
    }
}
