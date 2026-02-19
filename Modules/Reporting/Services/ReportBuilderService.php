<?php

declare(strict_types=1);

namespace Modules\Reporting\Services;

use Illuminate\Support\Facades\DB;
use Modules\Reporting\Enums\AggregateFunction;
use Modules\Reporting\Models\Report;
use Modules\Reporting\Repositories\ExecutionRepository;

/**
 * ReportBuilderService
 *
 * Handles dynamic query building and execution for reports
 */
class ReportBuilderService
{
    public function __construct(
        private ExecutionRepository $executionRepository
    ) {}

    /**
     * Build and execute a report query
     */
    public function execute(Report $report, array $filters = [], ?int $userId = null): array
    {
        $startTime = microtime(true);
        $execution = $this->createExecution($report, $filters, $userId);

        try {
            $query = $this->buildQuery($report, $filters);
            $results = $query->get();

            $executionTime = microtime(true) - $startTime;
            $this->executionRepository->markAsCompleted(
                $execution,
                $results->count(),
                $executionTime
            );

            return [
                'data' => $results,
                'count' => $results->count(),
                'execution_time' => $executionTime,
                'execution_id' => $execution->id,
            ];
        } catch (\Exception $e) {
            $this->executionRepository->markAsFailed($execution, $e->getMessage());
            throw $e;
        }
    }

    /**
     * Build query from report configuration
     */
    public function buildQuery(Report $report, array $filters = [])
    {
        $config = $report->query_config;

        // Start with base table
        $query = DB::table($config['table'] ?? 'reports');

        // Apply tenant/organization scope
        if (isset($config['tenant_scoped']) && $config['tenant_scoped']) {
            $query->where('tenant_id', auth()->user()->tenant_id);

            if (isset($config['organization_scoped']) && $config['organization_scoped']) {
                $query->where('organization_id', auth()->user()->organization_id);
            }
        }

        // Apply joins
        if (isset($config['joins'])) {
            foreach ($config['joins'] as $join) {
                $this->applyJoin($query, $join);
            }
        }

        // Select fields
        $fields = $report->fields ?? ['*'];
        $query->select($fields);

        // Apply filters from report config
        if (! empty($report->filters)) {
            $this->applyFilters($query, $report->filters);
        }

        // Apply runtime filters
        if (! empty($filters)) {
            $this->applyFilters($query, $filters);
        }

        // Apply grouping
        if (! empty($report->grouping)) {
            $this->applyGrouping($query, $report->grouping);
        }

        // Apply aggregations
        if (! empty($report->aggregations)) {
            $this->applyAggregations($query, $report->aggregations);
        }

        // Apply sorting
        if (! empty($report->sorting)) {
            foreach ($report->sorting as $sort) {
                $query->orderBy($sort['field'], $sort['direction'] ?? 'asc');
            }
        }

        return $query;
    }

    /**
     * Apply join to query
     */
    private function applyJoin($query, array $join): void
    {
        $type = $join['type'] ?? 'inner';
        $table = $join['table'];
        $first = $join['first'];
        $operator = $join['operator'] ?? '=';
        $second = $join['second'];

        match ($type) {
            'left' => $query->leftJoin($table, $first, $operator, $second),
            'right' => $query->rightJoin($table, $first, $operator, $second),
            default => $query->join($table, $first, $operator, $second),
        };
    }

    /**
     * Apply filters to query
     */
    private function applyFilters($query, array $filters): void
    {
        foreach ($filters as $filter) {
            $field = $filter['field'];
            $operator = $filter['operator'] ?? '=';
            $value = $filter['value'];

            match ($operator) {
                'like' => $query->where($field, 'like', "%{$value}%"),
                'in' => $query->whereIn($field, (array) $value),
                'not_in' => $query->whereNotIn($field, (array) $value),
                'between' => $query->whereBetween($field, $value),
                'null' => $query->whereNull($field),
                'not_null' => $query->whereNotNull($field),
                '>', '>=', '<', '<=', '!=', '<>' => $query->where($field, $operator, $value),
                default => $query->where($field, '=', $value),
            };
        }
    }

    /**
     * Apply grouping to query
     */
    private function applyGrouping($query, array $grouping): void
    {
        foreach ($grouping as $field) {
            $query->groupBy($field);
        }
    }

    /**
     * Apply aggregations to query
     */
    private function applyAggregations($query, array $aggregations): void
    {
        foreach ($aggregations as $agg) {
            $function = $agg['function'];
            $field = $agg['field'];
            $alias = $agg['alias'] ?? "{$function}_{$field}";

            $query->selectRaw($this->buildAggregateExpression($function, $field, $alias));
        }
    }

    /**
     * Build aggregate expression
     */
    private function buildAggregateExpression(string $function, string $field, string $alias): string
    {
        return match (strtoupper($function)) {
            'SUM' => "SUM({$field}) as {$alias}",
            'AVG' => "AVG({$field}) as {$alias}",
            'COUNT' => "COUNT({$field}) as {$alias}",
            'MIN' => "MIN({$field}) as {$alias}",
            'MAX' => "MAX({$field}) as {$alias}",
            default => "{$function}({$field}) as {$alias}",
        };
    }

    /**
     * Aggregate data using BCMath for precision
     */
    public function aggregateWithBCMath(array $data, string $field, AggregateFunction $function, int $scale = 2): string
    {
        $values = array_column($data, $field);

        return match ($function) {
            AggregateFunction::SUM => $this->bcSum($values, $scale),
            AggregateFunction::AVG => $this->bcAvg($values, $scale),
            AggregateFunction::COUNT => (string) count($values),
            AggregateFunction::MIN => $this->bcMin($values),
            AggregateFunction::MAX => $this->bcMax($values),
        };
    }

    /**
     * Calculate sum using BCMath
     */
    private function bcSum(array $values, int $scale): string
    {
        $sum = '0';
        foreach ($values as $value) {
            $sum = bcadd($sum, (string) $value, $scale);
        }

        return $sum;
    }

    /**
     * Calculate average using BCMath
     */
    private function bcAvg(array $values, int $scale): string
    {
        if (empty($values)) {
            return '0';
        }

        $sum = $this->bcSum($values, $scale);

        return bcdiv($sum, (string) count($values), $scale);
    }

    /**
     * Find minimum using BCMath
     */
    private function bcMin(array $values): string
    {
        if (empty($values)) {
            return '0';
        }

        $min = (string) $values[0];
        foreach ($values as $value) {
            if (bccomp((string) $value, $min, 10) < 0) {
                $min = (string) $value;
            }
        }

        return $min;
    }

    /**
     * Find maximum using BCMath
     */
    private function bcMax(array $values): string
    {
        if (empty($values)) {
            return '0';
        }

        $max = (string) $values[0];
        foreach ($values as $value) {
            if (bccomp((string) $value, $max, 10) > 0) {
                $max = (string) $value;
            }
        }

        return $max;
    }

    /**
     * Create execution record
     */
    private function createExecution(Report $report, array $filters, ?int $userId): \Modules\Reporting\Models\ReportExecution
    {
        return $this->executionRepository->create([
            'tenant_id' => auth()->user()->tenant_id,
            'organization_id' => auth()->user()->organization_id,
            'report_id' => $report->id,
            'user_id' => $userId ?? auth()->id(),
            'parameters' => [],
            'filters' => $filters,
            'started_at' => now(),
        ]);
    }
}
