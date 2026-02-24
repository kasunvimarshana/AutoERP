<?php

namespace Modules\POS\Infrastructure\Repositories;

use Illuminate\Support\Str;
use Modules\POS\Infrastructure\Models\PosTerminalModel;
use Modules\Shared\Infrastructure\Repositories\BaseEloquentRepository;

class PosTerminalRepository extends BaseEloquentRepository
{
    public function __construct()
    {
        parent::__construct(new PosTerminalModel());
    }

    public function create(array $data): object
    {
        $data['id'] = $data['id'] ?? (string) Str::uuid();
        return $this->model->newQuery()->create($data);
    }

    /** Columns that callers are allowed to filter on. */
    private const ALLOWED_FILTERS = ['tenant_id', 'is_active'];

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
}
