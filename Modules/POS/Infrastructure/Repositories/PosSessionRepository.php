<?php

namespace Modules\POS\Infrastructure\Repositories;

use Illuminate\Support\Str;
use Modules\POS\Infrastructure\Models\PosSessionModel;
use Modules\Shared\Infrastructure\Repositories\BaseEloquentRepository;

class PosSessionRepository extends BaseEloquentRepository
{
    public function __construct()
    {
        parent::__construct(new PosSessionModel());
    }

    public function create(array $data): object
    {
        $data['id'] = $data['id'] ?? (string) Str::uuid();
        return $this->model->newQuery()->create($data);
    }

    /** Columns that callers are allowed to filter on. */
    private const ALLOWED_FILTERS = ['tenant_id', 'terminal_id', 'cashier_id', 'status'];

    public function paginate(array $filters = [], int $perPage = 15): object
    {
        $query = $this->model->newQuery();
        foreach ($filters as $key => $value) {
            if ($value !== null && in_array($key, self::ALLOWED_FILTERS, true)) {
                $query->where($key, $value);
            }
        }
        return $query->paginate($perPage);
    }

    public function findOpenByTerminal(string $tenantId, string $terminalId): ?object
    {
        return $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('terminal_id', $terminalId)
            ->where('status', 'open')
            ->first();
    }
}
