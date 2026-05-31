<?php

declare(strict_types=1);

namespace Modules\Inventory\Presentation\Http\Resources;

use Illuminate\Support\Facades\DB;

final class InventoryResourceLabels
{
    /** @var array<string, array<int, array<string, mixed>|null>> */
    private static array $cache = [];

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    public static function enrich(array $row): array
    {
        return self::withUsers(self::withSerials(self::withBatches(self::withUoms(self::withLocations(self::withWarehouses(self::withItems($row)))))));
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private static function withItems(array $row): array
    {
        foreach (['item_id', 'component_item_id'] as $field) {
            $item = self::lookup('items', $row[$field] ?? null, ['id', 'sku', 'name']);
            if ($item === null) {
                continue;
            }

            $key = $field === 'item_id' ? 'item' : str_replace('_id', '', $field);
            $row[$key] = [
                'id' => $item['id'],
                'code' => $item['sku'],
                'sku' => $item['sku'],
                'name' => $item['name'],
            ];
            $row[$key.'_label'] = self::codeName($item['sku'], $item['name']);
        }

        return $row;
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private static function withWarehouses(array $row): array
    {
        foreach (['warehouse_id', 'from_warehouse_id', 'to_warehouse_id', 'source_warehouse_id', 'destination_warehouse_id', 'target_warehouse_id'] as $field) {
            $warehouse = self::lookup('warehouses', $row[$field] ?? null, ['id', 'code', 'name']);
            if ($warehouse === null) {
                continue;
            }

            $key = match ($field) {
                'from_warehouse_id' => 'from_warehouse',
                'to_warehouse_id' => 'to_warehouse',
                'source_warehouse_id' => 'source_warehouse',
                'destination_warehouse_id' => 'destination_warehouse',
                'target_warehouse_id' => 'target_warehouse',
                default => 'warehouse',
            };
            $row[$key] = [
                'id' => $warehouse['id'],
                'code' => $warehouse['code'],
                'name' => $warehouse['name'],
            ];
            $row[$key.'_label'] = self::codeName($warehouse['code'], $warehouse['name']);
        }

        return $row;
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private static function withLocations(array $row): array
    {
        foreach (['location_id', 'from_location_id', 'to_location_id', 'source_location_id', 'destination_location_id', 'target_location_id', 'current_location_id'] as $field) {
            $location = self::lookup('warehouse_locations', $row[$field] ?? null, ['id', 'code', 'name']);
            if ($location === null) {
                continue;
            }

            $key = match ($field) {
                'from_location_id' => 'from_location',
                'to_location_id' => 'to_location',
                'source_location_id' => 'source_location',
                'destination_location_id' => 'destination_location',
                'target_location_id' => 'target_location',
                'current_location_id' => 'current_location',
                default => 'location',
            };
            $row[$key] = [
                'id' => $location['id'],
                'code' => $location['code'],
                'name' => $location['name'],
            ];
            $row[$key.'_label'] = self::codeName($location['code'], $location['name']);
        }

        return $row;
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private static function withUoms(array $row): array
    {
        foreach (['uom_id', 'base_uom_id', 'transaction_uom_id'] as $field) {
            $uom = self::lookup('unit_of_measures', $row[$field] ?? null, ['id', 'code', 'symbol', 'name']);
            if ($uom === null) {
                continue;
            }

            $key = match ($field) {
                'base_uom_id' => 'base_uom',
                'transaction_uom_id' => 'transaction_uom',
                default => 'uom',
            };
            $row[$key] = [
                'id' => $uom['id'],
                'code' => $uom['code'],
                'symbol' => $uom['symbol'],
                'name' => $uom['name'],
            ];
            $row[$key.'_label'] = (string) ($uom['code'] ?: $uom['symbol'] ?: $uom['name']);
        }

        return $row;
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private static function withBatches(array $row): array
    {
        $batch = self::lookup('batches', $row['batch_id'] ?? null, ['id', 'batch_number', 'lot_number']);
        if ($batch !== null) {
            $row['batch'] = [
                'id' => $batch['id'],
                'batch_number' => $batch['batch_number'],
                'lot_number' => $batch['lot_number'],
            ];
            $row['batch_label'] = (string) ($batch['batch_number'] ?: $batch['lot_number'] ?: '');
        }

        return $row;
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private static function withSerials(array $row): array
    {
        $serial = self::lookup('serials', $row['serial_id'] ?? null, ['id', 'serial_number']);
        if ($serial !== null) {
            $row['serial'] = [
                'id' => $serial['id'],
                'serial_number' => $serial['serial_number'],
            ];
            $row['serial_label'] = (string) ($serial['serial_number'] ?: '');
        }

        $row['batch_serial_label'] = trim((string) (($row['batch_label'] ?? '') ?: ($row['serial_label'] ?? '')));

        return $row;
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private static function withUsers(array $row): array
    {
        foreach (['performed_by', 'approved_by', 'requested_by', 'reserved_by', 'released_by', 'consumed_by', 'counted_by', 'inspected_by', 'counted_by_user_id', 'approved_by_user_id'] as $field) {
            $user = self::lookup('users', $row[$field] ?? null, ['id', 'first_name', 'last_name', 'email']);
            if ($user === null) {
                continue;
            }

            $key = match ($field) {
                'performed_by' => 'performed_by_user',
                'approved_by', 'approved_by_user_id' => 'approved_by_user',
                'requested_by' => 'requested_by_user',
                'reserved_by' => 'reserved_by_user',
                'released_by' => 'released_by_user',
                'consumed_by' => 'consumed_by_user',
                'counted_by', 'counted_by_user_id' => 'counted_by_user',
                'inspected_by' => 'inspected_by_user',
                default => 'user',
            };
            $name = trim((string) (($user['first_name'] ?? '').' '.($user['last_name'] ?? '')));
            $row[$key] = [
                'id' => $user['id'],
                'name' => $name,
                'email' => $user['email'],
            ];
            $row[$key.'_label'] = self::codeName($user['email'], $name);
        }

        return $row;
    }

    /**
     * @param list<string> $columns
     * @return array<string, mixed>|null
     */
    private static function lookup(string $table, mixed $id, array $columns): ?array
    {
        if ($id === null || $id === '' || ! is_numeric($id)) {
            return null;
        }

        $numericId = (int) $id;
        if ($numericId < 1) {
            return null;
        }

        if (array_key_exists($numericId, self::$cache[$table] ?? [])) {
            return self::$cache[$table][$numericId];
        }

        $row = DB::table($table)->where('id', $numericId)->first($columns);
        self::$cache[$table][$numericId] = $row === null ? null : (array) $row;

        return self::$cache[$table][$numericId];
    }

    private static function codeName(mixed $code, mixed $name): string
    {
        $code = trim((string) ($code ?? ''));
        $name = trim((string) ($name ?? ''));

        if ($code !== '' && $name !== '') {
            return $code.' - '.$name;
        }

        return $name !== '' ? $name : $code;
    }
}
