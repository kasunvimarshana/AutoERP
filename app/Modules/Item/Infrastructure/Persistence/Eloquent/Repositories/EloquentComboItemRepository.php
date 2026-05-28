<?php

declare(strict_types=1);

namespace Modules\Item\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Support\Facades\DB;
use Modules\Core\Application\DTO\DataRecord;
use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\Item\Application\Repositories\ComboItemRepositoryInterface;
use Modules\Item\Infrastructure\Persistence\Eloquent\Models\ComboItemModel;

final class EloquentComboItemRepository extends EloquentRepository implements ComboItemRepositoryInterface
{
    public function __construct(ComboItemModel $model)
    {
        parent::__construct($model);
    }

    public function findByIdInTenant(int|string $id, int $tenantId): ?DataRecord
    {
        $model = $this->query()->where('tenant_id', $tenantId)->find($id);

        return $model === null ? null : $this->toRecord($model);
    }

    public function introducesCycle(
        int $tenantId,
        int $comboItemId,
        int $componentItemId,
        ?int $ignoreLinkId = null,
    ): bool {
        if ($comboItemId === $componentItemId) {
            return true;
        }

        $stack = [$componentItemId];
        $visited = [];

        while ($stack !== []) {
            $current = (int) array_pop($stack);
            if (isset($visited[$current])) {
                continue;
            }

            $visited[$current] = true;
            if ($current === $comboItemId) {
                return true;
            }

            $childrenQuery = DB::table('combo_items')
                ->select('component_item_id')
                ->where('tenant_id', $tenantId)
                ->whereNull('deleted_at')
                ->where('combo_item_id', $current);

            if ($ignoreLinkId !== null) {
                $childrenQuery->where('id', '!=', $ignoreLinkId);
            }

            $children = $childrenQuery->pluck('component_item_id')->all();
            foreach ($children as $child) {
                $childId = (int) $child;
                if (! isset($visited[$childId])) {
                    $stack[] = $childId;
                }
            }
        }

        return false;
    }
}
