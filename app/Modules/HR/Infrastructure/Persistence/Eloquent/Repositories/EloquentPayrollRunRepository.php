<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\HR\Domain\Entities\PayrollRun;
use Modules\HR\Domain\RepositoryInterfaces\PayrollRunRepositoryInterface;
use Modules\HR\Domain\ValueObjects\PayrollRunStatus;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\PayrollRunModel;

class EloquentPayrollRunRepository extends EloquentRepository implements PayrollRunRepositoryInterface
{
    public function __construct(PayrollRunModel $model)
    {
        parent::__construct($model);
        $this->setDomainEntityMapper(fn (PayrollRunModel $m): PayrollRun => $this->map($m));
    }

    public function save(PayrollRun $e): PayrollRun
    {
        $data = ['tenant_id' => $e->getTenantId(), 'period_start' => $e->getPeriodStart()->format('Y-m-d'), 'period_end' => $e->getPeriodEnd()->format('Y-m-d'), 'status' => $e->getStatus()->value, 'processed_at' => $e->getProcessedAt()?->format('Y-m-d H:i:s'), 'approved_at' => $e->getApprovedAt()?->format('Y-m-d H:i:s'), 'approved_by' => $e->getApprovedBy(), 'total_gross' => $e->getTotalGross(), 'total_deductions' => $e->getTotalDeductions(), 'total_net' => $e->getTotalNet(), 'metadata' => $e->getMetadata()];
        $m = $e->getId() ? $this->update($e->getId(), $data) : $this->create($data);

        return $this->toDomainEntity($m);
    }

    public function find(int|string $id, array $columns = ['*']): ?PayrollRun
    {
        return parent::find($id, $columns);
    }

    public function findByTenantAndPeriod(int $tenantId, string $periodStart, string $periodEnd): ?PayrollRun
    {
        $m = $this->model->where('tenant_id', $tenantId)->where('period_start', $periodStart)->where('period_end', $periodEnd)->first();

        return $m ? $this->toDomainEntity($m) : null;
    }

    private function map(PayrollRunModel $m): PayrollRun
    {
        $dt = fn ($v) => $v ? ($v instanceof \DateTimeInterface ? $v : new \DateTimeImmutable($v)) : null;
        $now = fn ($v) => $v instanceof \DateTimeInterface ? $v : new \DateTimeImmutable($v ?? 'now');

        return new PayrollRun($m->tenant_id, $now($m->period_start), $now($m->period_end), PayrollRunStatus::from($m->status), $dt($m->processed_at), $dt($m->approved_at), $m->approved_by, $m->total_gross ?? '0', $m->total_deductions ?? '0', $m->total_net ?? '0', $m->metadata ?? [], $now($m->created_at), $now($m->updated_at), $m->id);
    }
}
