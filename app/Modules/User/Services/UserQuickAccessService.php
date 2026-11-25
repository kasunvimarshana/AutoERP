<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\User\Services;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Modules\Core\Results\Error;
use Modules\Core\Results\Result;
use Modules\User\Constants\UserErrorCode;
use Modules\User\Models\UserQuickAccessModel;

final class UserQuickAccessService
{
    /** @var list<array{id: string, label: string, to: string, group: string, icon: string, enabled: bool, order: int}> */
    public const DEFAULT_ITEMS = [
        [
            'id' => 'create-service-job',
            'label' => 'Create Service Job',
            'to' => '/vehicle-service/jobs/create',
            'group' => 'Vehicle Service',
            'icon' => 'service',
            'enabled' => true,
            'order' => 1,
        ],
        [
            'id' => 'service-jobs',
            'label' => 'Service Job List',
            'to' => '/vehicle-service/jobs',
            'group' => 'Vehicle Service',
            'icon' => 'list',
            'enabled' => true,
            'order' => 2,
        ],
        [
            'id' => 'selling-create',
            'label' => 'Create Selling',
            'to' => '/selling/create',
            'group' => 'Selling',
            'icon' => 'purchase',
            'enabled' => true,
            'order' => 3,
        ],
        [
            'id' => 'selling-list',
            'label' => 'Selling List',
            'to' => '/selling',
            'group' => 'Selling',
            'icon' => 'list',
            'enabled' => true,
            'order' => 4,
        ],
    ];

    /**
     * @return array{items: list<array<string, mixed>>, is_default: bool, row_version: int|null}
     */
    public function get(int $tenantId, int $userId): array
    {
        $record = UserQuickAccessModel::where('tenant_id', $tenantId)
            ->where('user_id', $userId)
            ->first();

        if ($record === null) {
            return [
                'items' => self::DEFAULT_ITEMS,
                'is_default' => true,
                'row_version' => null,
            ];
        }

        return [
            'items' => is_array($record->items) ? $record->items : [],
            'is_default' => false,
            'row_version' => (int) $record->row_version,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $rawItems
     * @return Result<array{items: list<array<string, mixed>>, is_default: bool, row_version: int}>
     */
    public function update(int $tenantId, int $userId, array $rawItems, ?int $expectedVersion = null): Result
    {
        $sanitized = [];
        $seenPaths = [];
        $seenIds = [];
        $order = 1;
        foreach ($rawItems as $item) {
            if (! is_array($item)) {
                continue;
            }
            $to = trim((string) ($item['to'] ?? ''));
            if ($to === '') {
                continue;
            }
            $to = '/'.ltrim($to, "/\\ \t\n\r\0\x0B");
            $queryPos = strpos($to, '?');
            $hashPos = strpos($to, '#');
            $cutPos = ($queryPos !== false && $hashPos !== false)
                ? min($queryPos, $hashPos)
                : ($queryPos !== false ? $queryPos : $hashPos);

            $pathOnly = $cutPos !== false ? substr($to, 0, $cutPos) : $to;
            $remainder = $cutPos !== false ? substr($to, $cutPos) : '';
            if (strlen($pathOnly) > 1) {
                $pathOnly = rtrim($pathOnly, '/');
            }
            $cleanTo = ($pathOnly !== '' ? $pathOnly : '/').$remainder;

            if (isset($seenPaths[$cleanTo])) {
                continue;
            }
            $seenPaths[$cleanTo] = true;

            $rawId = trim((string) ($item['id'] ?? 'item-'.$order));
            $resolvedId = $rawId !== '' ? $rawId : 'item-'.$order;
            if (isset($seenIds[$resolvedId])) {
                $resolvedId = $resolvedId.'-'.$order;
            }
            $seenIds[$resolvedId] = true;

            $sanitized[] = [
                'id' => $resolvedId,
                'label' => trim((string) ($item['label'] ?? '')),
                'to' => $cleanTo,
                'group' => trim((string) ($item['group'] ?? 'General')),
                'icon' => trim((string) ($item['icon'] ?? 'list')),
                'enabled' => (bool) ($item['enabled'] ?? true),
                'order' => $order++,
            ];
        }

        return DB::transaction(function () use ($tenantId, $userId, $sanitized, $expectedVersion): Result {
            $record = UserQuickAccessModel::where('tenant_id', $tenantId)
                ->where('user_id', $userId)
                ->lockForUpdate()
                ->first();

            if ($record !== null) {
                if ($expectedVersion === null || (int) $record->row_version !== $expectedVersion) {
                    return Result::failure(new Error(
                        UserErrorCode::STALE_RECORD,
                        'Quick Access settings were updated by another session. Please reload to see the latest changes.'
                    ));
                }

                $record->items = $sanitized;
                $record->row_version = ((int) $record->row_version) + 1;
                $record->save();

                return Result::success([
                    'items' => $record->items,
                    'is_default' => false,
                    'row_version' => (int) $record->row_version,
                ]);
            }

            if ($expectedVersion !== null) {
                return Result::failure(new Error(
                    UserErrorCode::STALE_RECORD,
                    'Quick Access settings were reset by another session. Please reload to see the latest changes.'
                ));
            }

            try {
                $created = UserQuickAccessModel::create([
                    'tenant_id' => $tenantId,
                    'user_id' => $userId,
                    'items' => $sanitized,
                    'row_version' => 1,
                ]);

                return Result::success([
                    'items' => $created->items,
                    'is_default' => false,
                    'row_version' => (int) $created->row_version,
                ]);
            } catch (QueryException) {
                return Result::failure(new Error(
                    UserErrorCode::STALE_RECORD,
                    'Quick Access settings were updated by another session. Please reload to see the latest changes.'
                ));
            }
        });
    }

    /**
     * @return Result<array{items: list<array<string, mixed>>, is_default: bool, row_version: null}>
     */
    public function reset(int $tenantId, int $userId): Result
    {
        UserQuickAccessModel::where('tenant_id', $tenantId)
            ->where('user_id', $userId)
            ->delete();

        return Result::success([
            'items' => self::DEFAULT_ITEMS,
            'is_default' => true,
            'row_version' => null,
        ]);
    }
}
