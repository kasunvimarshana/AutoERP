<?php

declare(strict_types=1);

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\DB;

require dirname(__DIR__, 2).'/vendor/autoload.php';

$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$sourcePath = $argv[1] ?? null;
$outputPath = $argv[2] ?? null;
if (! is_string($sourcePath) || ! is_file($sourcePath) || ! is_string($outputPath) || trim($outputPath) === '') {
    fwrite(STDERR, "Usage: php database/imports/generate_requested_master_import.php <source.sql> <output.sql>\n");
    exit(1);
}

$sourceTables = [
    'currencies',
    'unit_of_measures',
    'customers',
    'customer_addresses',
    'suppliers',
    'vehicle_makes',
    'vehicle_models',
    'vehicle_types',
    'vehicle_categories',
    'vehicles',
    'vehicle_ownerships',
    'item_categories',
    'item_brands',
    'items',
    'item_units',
    'item_variants',
    'item_bundles',
    'item_prices',
];
$selected = array_fill_keys($sourceTables, true);
$sourceInserts = array_fill_keys($sourceTables, []);

foreach (sqlStatements($sourcePath) as $statement) {
    if (! preg_match('/(?:^|\R)INSERT\s+INTO\s+`([^`]+)`/i', $statement, $match, PREG_OFFSET_CAPTURE)) {
        continue;
    }

    $table = $match[1][0];
    if (! isset($selected[$table])) {
        continue;
    }

    $insertOffset = $match[0][1] + strlen($match[0][0]) - strlen('INSERT INTO `'.$table.'`');
    $insert = substr($statement, $insertOffset);
    $rewritten = preg_replace(
        '/^INSERT\s+INTO\s+`'.preg_quote($table, '/').'`/i',
        'INSERT INTO `'.sourceTable($table).'`',
        $insert,
        1,
    );
    if (! is_string($rewritten)) {
        throw new RuntimeException("Unable to rewrite source insert for {$table}.");
    }
    $sourceInserts[$table][] = trim($rewritten);
}

/** @var ConnectionInterface $connection */
$connection = DB::connection();
$parts = [];
$parts[] = <<<'SQL'
-- AutoERP selected master-data import generated from the supplied database dump.
-- Scope: customers and addresses, suppliers, vehicles, customer/vehicle ownerships, stock/labour/combo items,
--        item units and bundle lines, and item purchase/service/sales price revisions.
--
-- Safety:
--   * This file does not DELETE, TRUNCATE, DROP, or overwrite persistent business records.
--   * Existing records win when their natural key already exists.
--   * Item-price lineages are skipped when they overlap an existing current target price scope.
--   * All writes are atomic and protected by a named import lock.
--   * Run while the local application/workers are stopped, after taking a database backup.
--
-- Target context can be changed here before running the file.
SET @target_tenant_code := 'AUTOERP';
SET @target_organization_code := 'AUTOERP';

SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;
SET time_zone = '+00:00';
SET @import_lock_acquired := GET_LOCK('autoerp:selected-master-data-import', 30);

CREATE TEMPORARY TABLE `_import_context` (
  `lock_acquired` TINYINT UNSIGNED NOT NULL CHECK (`lock_acquired` = 1),
  `tenant_id` BIGINT UNSIGNED NOT NULL,
  `organization_unit_id` BIGINT UNSIGNED NOT NULL,
  PRIMARY KEY (`tenant_id`, `organization_unit_id`)
);

INSERT INTO `_import_context` (`lock_acquired`, `tenant_id`, `organization_unit_id`)
VALUES (
  @import_lock_acquired,
  (SELECT `id` FROM `tenants` WHERE `code` = @target_tenant_code LIMIT 1),
  (
    SELECT organization_unit.`id`
    FROM `organization_units` AS organization_unit
    INNER JOIN `tenants` AS tenant ON tenant.`id` = organization_unit.`tenant_id`
    WHERE tenant.`code` = @target_tenant_code
      AND organization_unit.`code` = @target_organization_code
    LIMIT 1
  )
);
SQL;

foreach ($sourceTables as $table) {
    $parts[] = sprintf(
        "CREATE TEMPORARY TABLE `%s` LIKE `%s`;\n%s",
        sourceTable($table),
        $table,
        implode("\n", $sourceInserts[$table]),
    );
}

$parts[] = auxiliaryStagingSql();
$parts[] = 'START TRANSACTION;';

$parts[] = insertMissingSql(
    $connection,
    'currencies',
    [],
    '1 = 1',
    'existing.`code` = src.`code`',
    'currencies',
);

$parts[] = insertMissingSql(
    $connection,
    'unit_of_measures',
    tenantOverrides(),
    'src.`deleted_at` IS NULL',
    'existing.`tenant_id` = ctx.`tenant_id` AND existing.`code` = src.`code`',
    'uoms',
);

$parts[] = insertMissingSql(
    $connection,
    'customers',
    tenantOverrides([
        'default_currency_id' => 'target_currency.`id`',
        'payment_term_id' => 'NULL',
        'approved_by' => 'NULL',
    ]),
    'src.`deleted_at` IS NULL',
    'existing.`tenant_id` = ctx.`tenant_id` AND (existing.`code` = src.`code` OR existing.`customer_number` = src.`customer_number`)',
    'customers',
    <<<'SQL'
LEFT JOIN `_import_source_currencies` AS source_currency ON source_currency.`id` = src.`default_currency_id`
LEFT JOIN `currencies` AS target_currency ON target_currency.`code` = source_currency.`code`
SQL,
);
$parts[] = idMapSql('customers', 'code', 'customer');
$parts[] = insertMissingSql(
    $connection,
    'customer_addresses',
    tenantOverrides(['customer_id' => 'customer_map.`target_id`']),
    'src.`deleted_at` IS NULL',
    <<<'SQL'
existing.`tenant_id` = ctx.`tenant_id`
AND existing.`customer_id` = customer_map.`target_id`
AND existing.`address_type` = src.`address_type`
AND existing.`address_line_1` = src.`address_line_1`
AND existing.`address_line_2` <=> src.`address_line_2`
AND existing.`city` <=> src.`city`
AND existing.`postal_code` <=> src.`postal_code`
SQL,
    'customer_addresses',
    'INNER JOIN `_import_customer_id_map` AS customer_map ON customer_map.`source_id` = src.`customer_id`',
);

$parts[] = insertMissingSql(
    $connection,
    'suppliers',
    tenantOverrides([
        'default_currency_id' => 'target_currency.`id`',
        'payment_term_id' => 'NULL',
        'approved_by' => 'NULL',
    ]),
    'src.`deleted_at` IS NULL',
    'existing.`tenant_id` = ctx.`tenant_id` AND (existing.`code` = src.`code` OR existing.`supplier_number` = src.`supplier_number`)',
    'suppliers',
    <<<'SQL'
LEFT JOIN `_import_source_currencies` AS source_currency ON source_currency.`id` = src.`default_currency_id`
LEFT JOIN `currencies` AS target_currency ON target_currency.`code` = source_currency.`code`
SQL,
);

foreach ([
    ['vehicle_makes', 'vehicle_makes'],
    ['vehicle_types', 'vehicle_types'],
    ['vehicle_categories', 'vehicle_categories'],
    ['item_categories', 'item_categories'],
    ['item_brands', 'item_brands'],
] as [$table, $counter]) {
    $overrides = tenantOverrides();
    if (in_array($table, ['vehicle_categories', 'item_categories'], true)) {
        $overrides['parent_id'] = 'NULL';
    }
    $parts[] = insertMissingSql(
        $connection,
        $table,
        $overrides,
        'src.`deleted_at` IS NULL',
        'existing.`tenant_id` = ctx.`tenant_id` AND existing.`code` = src.`code`',
        $counter,
    );
}

$parts[] = insertMissingSql(
    $connection,
    'vehicle_models',
    tenantOverrides(['vehicle_make_id' => 'target_make.`id`']),
    'src.`deleted_at` IS NULL',
    'existing.`tenant_id` = ctx.`tenant_id` AND existing.`vehicle_make_id` = target_make.`id` AND existing.`code` = src.`code`',
    'vehicle_models',
    <<<'SQL'
INNER JOIN `_import_source_vehicle_makes` AS source_make ON source_make.`id` = src.`vehicle_make_id`
INNER JOIN `vehicle_makes` AS target_make ON target_make.`tenant_id` = ctx.`tenant_id` AND target_make.`code` = source_make.`code`
SQL,
);

$parts[] = parentUpdateSql('vehicle_categories', 'vehicle_categories');
$parts[] = parentUpdateSql('item_categories', 'item_categories');

$parts[] = insertMissingSql(
    $connection,
    'vehicles',
    tenantOverrides([
        'vehicle_make_id' => 'target_make.`id`',
        'vehicle_model_id' => 'target_model.`id`',
        'vehicle_type_id' => 'target_type.`id`',
        'vehicle_category_id' => 'target_category.`id`',
        'approved_by' => 'NULL',
    ]),
    'src.`deleted_at` IS NULL',
    <<<'SQL'
existing.`tenant_id` = ctx.`tenant_id`
AND (
  existing.`vehicle_number` = src.`vehicle_number`
  OR (src.`code` IS NOT NULL AND existing.`code` = src.`code`)
  OR (src.`registration_number` IS NOT NULL AND existing.`registration_number` = src.`registration_number`)
  OR (src.`chassis_number` IS NOT NULL AND existing.`chassis_number` = src.`chassis_number`)
  OR (src.`engine_number` IS NOT NULL AND existing.`engine_number` = src.`engine_number`)
  OR (src.`vin_number` IS NOT NULL AND existing.`vin_number` = src.`vin_number`)
)
SQL,
    'vehicles',
    <<<'SQL'
LEFT JOIN `_import_source_vehicle_makes` AS source_make ON source_make.`id` = src.`vehicle_make_id`
LEFT JOIN `vehicle_makes` AS target_make ON target_make.`tenant_id` = ctx.`tenant_id` AND target_make.`code` = source_make.`code`
LEFT JOIN `_import_source_vehicle_models` AS source_model ON source_model.`id` = src.`vehicle_model_id`
LEFT JOIN `vehicle_models` AS target_model ON target_model.`tenant_id` = ctx.`tenant_id` AND target_model.`vehicle_make_id` = target_make.`id` AND target_model.`code` = source_model.`code`
LEFT JOIN `_import_source_vehicle_types` AS source_type ON source_type.`id` = src.`vehicle_type_id`
LEFT JOIN `vehicle_types` AS target_type ON target_type.`tenant_id` = ctx.`tenant_id` AND target_type.`code` = source_type.`code`
LEFT JOIN `_import_source_vehicle_categories` AS source_category ON source_category.`id` = src.`vehicle_category_id`
LEFT JOIN `vehicle_categories` AS target_category ON target_category.`tenant_id` = ctx.`tenant_id` AND target_category.`code` = source_category.`code`
SQL,
);
$parts[] = idMapSql('vehicles', 'vehicle_number', 'vehicle');

$parts[] = insertMissingSql(
    $connection,
    'items',
    tenantOverrides([
        'item_category_id' => 'target_category.`id`',
        'item_brand_id' => 'target_brand.`id`',
        'base_uom_id' => 'target_uom.`id`',
        'default_tax_group_id' => 'NULL',
        'purchase_tax_group_id' => 'NULL',
        'sales_tax_group_id' => 'NULL',
    ]),
    'src.`deleted_at` IS NULL',
    <<<'SQL'
existing.`tenant_id` = ctx.`tenant_id`
AND (
  existing.`code` = src.`code`
  OR (src.`sku` IS NOT NULL AND existing.`sku` = src.`sku`)
  OR (src.`barcode` IS NOT NULL AND existing.`barcode` = src.`barcode`)
)
SQL,
    'items',
    <<<'SQL'
LEFT JOIN `_import_source_item_categories` AS source_category ON source_category.`id` = src.`item_category_id`
LEFT JOIN `item_categories` AS target_category ON target_category.`tenant_id` = ctx.`tenant_id` AND target_category.`code` = source_category.`code`
LEFT JOIN `_import_source_item_brands` AS source_brand ON source_brand.`id` = src.`item_brand_id`
LEFT JOIN `item_brands` AS target_brand ON target_brand.`tenant_id` = ctx.`tenant_id` AND target_brand.`code` = source_brand.`code`
LEFT JOIN `_import_source_unit_of_measures` AS source_uom ON source_uom.`id` = src.`base_uom_id`
LEFT JOIN `unit_of_measures` AS target_uom ON target_uom.`tenant_id` = ctx.`tenant_id` AND target_uom.`code` = source_uom.`code`
SQL,
);

$parts[] = insertMissingSql(
    $connection,
    'item_variants',
    tenantOverrides(['item_id' => 'target_item.`id`']),
    'src.`deleted_at` IS NULL',
    'existing.`tenant_id` = ctx.`tenant_id` AND existing.`code` = src.`code`',
    'item_variants',
    <<<'SQL'
INNER JOIN `_import_source_items` AS source_item ON source_item.`id` = src.`item_id`
INNER JOIN `items` AS target_item ON target_item.`tenant_id` = ctx.`tenant_id` AND target_item.`code` = source_item.`code`
SQL,
);

$parts[] = insertMissingSql(
    $connection,
    'item_units',
    tenantOverrides([
        'item_id' => 'target_item.`id`',
        'uom_id' => 'target_uom.`id`',
    ]),
    '1 = 1',
    'existing.`item_id` = target_item.`id` AND existing.`uom_id` = target_uom.`id` AND existing.`unit_role` = src.`unit_role`',
    'item_units',
    <<<'SQL'
INNER JOIN `_import_source_items` AS source_item ON source_item.`id` = src.`item_id`
INNER JOIN `items` AS target_item ON target_item.`tenant_id` = ctx.`tenant_id` AND target_item.`code` = source_item.`code`
INNER JOIN `_import_source_unit_of_measures` AS source_uom ON source_uom.`id` = src.`uom_id`
INNER JOIN `unit_of_measures` AS target_uom ON target_uom.`tenant_id` = ctx.`tenant_id` AND target_uom.`code` = source_uom.`code`
SQL,
);

$parts[] = insertMissingSql(
    $connection,
    'item_bundles',
    tenantOverrides([
        'parent_item_id' => 'target_parent.`id`',
        'child_item_id' => 'target_child.`id`',
        'child_variant_id' => 'target_variant.`id`',
        'uom_id' => 'target_uom.`id`',
    ]),
    '1 = 1',
    <<<'SQL'
existing.`tenant_id` = ctx.`tenant_id`
AND existing.`parent_item_id` = target_parent.`id`
AND existing.`child_item_id` = target_child.`id`
AND existing.`child_variant_id` <=> target_variant.`id`
AND existing.`line_type` = src.`line_type`
AND existing.`sort_order` = src.`sort_order`
SQL,
    'item_bundles',
    <<<'SQL'
INNER JOIN `_import_source_items` AS source_parent ON source_parent.`id` = src.`parent_item_id`
INNER JOIN `items` AS target_parent ON target_parent.`tenant_id` = ctx.`tenant_id` AND target_parent.`code` = source_parent.`code`
INNER JOIN `_import_source_items` AS source_child ON source_child.`id` = src.`child_item_id`
INNER JOIN `items` AS target_child ON target_child.`tenant_id` = ctx.`tenant_id` AND target_child.`code` = source_child.`code`
LEFT JOIN `_import_source_item_variants` AS source_variant ON source_variant.`id` = src.`child_variant_id`
LEFT JOIN `item_variants` AS target_variant ON target_variant.`tenant_id` = ctx.`tenant_id` AND target_variant.`code` = source_variant.`code`
LEFT JOIN `_import_source_unit_of_measures` AS source_uom ON source_uom.`id` = src.`uom_id`
LEFT JOIN `unit_of_measures` AS target_uom ON target_uom.`tenant_id` = ctx.`tenant_id` AND target_uom.`code` = source_uom.`code`
SQL,
);

$parts[] = priceImportSql();
$parts[] = ownershipImportSql();
$parts[] = summarySql();

$directory = dirname($outputPath);
if (! is_dir($directory) && ! mkdir($directory, 0777, true) && ! is_dir($directory)) {
    throw new RuntimeException("Unable to create output directory {$directory}.");
}

$sql = implode("\n\n", array_filter($parts, static fn (string $part): bool => trim($part) !== ''))."\n";
if (file_put_contents($outputPath, $sql) === false) {
    throw new RuntimeException("Unable to write {$outputPath}.");
}

printf("Generated %s (%d bytes).\n", $outputPath, strlen($sql));

if (($argv[3] ?? null) === '--validate') {
    validateWithRollback($connection, $sql);
    echo "Validation completed with a full rollback; the local database was not changed.\n";
}

/** @param array<string, string> $extra */
function tenantOverrides(array $extra = []): array
{
    return array_merge([
        'tenant_id' => 'ctx.`tenant_id`',
        'organization_unit_id' => 'CASE WHEN src.`organization_unit_id` IS NULL THEN NULL ELSE ctx.`organization_unit_id` END',
    ], $extra);
}

/**
 * @param  array<string, string>  $overrides
 */
function insertMissingSql(
    ConnectionInterface $connection,
    string $table,
    array $overrides,
    string $where,
    string $match,
    string $counter,
    string $joins = '',
): string {
    $columns = array_values(array_filter(
        schemaColumns($connection, $table),
        static fn (string $column): bool => $column !== 'id',
    ));
    $quotedColumns = implode(",\n  ", array_map(static fn (string $column): string => "`{$column}`", $columns));
    $expressions = implode(",\n  ", array_map(
        static fn (string $column): string => $overrides[$column] ?? "src.`{$column}`",
        $columns,
    ));

    return sprintf(
        <<<'SQL'
INSERT INTO `%s` (
  %s
)
SELECT
  %s
FROM `%s` AS src
CROSS JOIN `_import_context` AS ctx
%s
WHERE (%s)
  AND NOT EXISTS (
    SELECT 1
    FROM `%s` AS existing
    WHERE %s
  )
ORDER BY src.`id`;
SET @imported_%s := ROW_COUNT();
SQL,
        $table,
        $quotedColumns,
        $expressions,
        sourceTable($table),
        trim($joins),
        trim($where),
        $table,
        trim($match),
        $counter,
    );
}

/** @return list<string> */
function schemaColumns(ConnectionInterface $connection, string $table): array
{
    return array_map(
        static fn (object $row): string => (string) $row->COLUMN_NAME,
        $connection->select(
            'SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? ORDER BY ORDINAL_POSITION',
            [$table],
        ),
    );
}

function parentUpdateSql(string $table, string $counter): string
{
    return sprintf(
        <<<'SQL'
UPDATE `%1$s` AS target
CROSS JOIN `_import_context` AS ctx
INNER JOIN `%2$s` AS source_category ON source_category.`code` = target.`code`
INNER JOIN `%2$s` AS source_parent ON source_parent.`id` = source_category.`parent_id`
INNER JOIN `%1$s` AS target_parent ON target_parent.`tenant_id` = ctx.`tenant_id` AND target_parent.`code` = source_parent.`code`
SET target.`parent_id` = target_parent.`id`, target.`updated_at` = UTC_TIMESTAMP()
WHERE target.`tenant_id` = ctx.`tenant_id`
  AND target.`parent_id` IS NULL;
SET @linked_%3$s_parents := ROW_COUNT();
SQL,
        $table,
        sourceTable($table),
        $counter,
    );
}

function priceImportSql(): string
{
    return <<<'SQL'
INSERT INTO `_import_mapped_item_prices`
SELECT
  src.*,
  target_item.`id` AS `target_item_id`,
  target_variant.`id` AS `target_variant_id`,
  target_currency.`id` AS `target_currency_id`,
  target_uom.`id` AS `target_uom_id`,
  CASE WHEN src.`organization_unit_id` IS NULL THEN NULL ELSE ctx.`organization_unit_id` END AS `target_organization_unit_id`,
  SHA2(CONCAT_WS(
    '|',
    CASE WHEN src.`organization_unit_id` IS NULL THEN 'global' ELSE CAST(ctx.`organization_unit_id` AS CHAR) END,
    CASE WHEN target_variant.`id` IS NULL THEN 'all_variants' ELSE CAST(target_variant.`id` AS CHAR) END,
    src.`price_type`,
    CAST(target_currency.`id` AS CHAR),
    CAST(target_uom.`id` AS CHAR)
  ), 256) AS `target_scope_key`
FROM `_import_source_item_prices` AS src
CROSS JOIN `_import_context` AS ctx
INNER JOIN `_import_source_items` AS source_item ON source_item.`id` = src.`item_id`
INNER JOIN `items` AS target_item ON target_item.`tenant_id` = ctx.`tenant_id` AND target_item.`code` = source_item.`code`
LEFT JOIN `_import_source_item_variants` AS source_variant ON source_variant.`id` = src.`item_variant_id`
LEFT JOIN `item_variants` AS target_variant ON target_variant.`tenant_id` = ctx.`tenant_id` AND target_variant.`code` = source_variant.`code`
INNER JOIN `_import_source_currencies` AS source_currency ON source_currency.`id` = src.`currency_id`
INNER JOIN `currencies` AS target_currency ON target_currency.`code` = source_currency.`code`
INNER JOIN `_import_source_unit_of_measures` AS source_uom ON source_uom.`id` = src.`uom_id`
INNER JOIN `unit_of_measures` AS target_uom ON target_uom.`tenant_id` = ctx.`tenant_id` AND target_uom.`code` = source_uom.`code`;

INSERT IGNORE INTO `_import_blocked_price_lineages` (`lineage_key`)
SELECT DISTINCT source_price.`lineage_key`
FROM `_import_mapped_item_prices` AS source_price
CROSS JOIN `_import_context` AS ctx
INNER JOIN `item_prices` AS existing
  ON existing.`tenant_id` = ctx.`tenant_id`
 AND existing.`item_id` = source_price.`target_item_id`
 AND existing.`scope_key` = source_price.`target_scope_key`
 AND existing.`recorded_to` IS NULL
 AND existing.`effective_from` <= COALESCE(source_price.`effective_to`, '9999-12-31')
 AND COALESCE(existing.`effective_to`, '9999-12-31') >= source_price.`effective_from`
WHERE source_price.`recorded_to` IS NULL
  AND NOT (
    existing.`lineage_key` = source_price.`lineage_key`
    AND existing.`revision_no` = source_price.`revision_no`
  );

INSERT INTO `item_prices` (
  `row_version`, `tenant_id`, `organization_unit_id`, `item_id`, `item_variant_id`,
  `price_type`, `currency_id`, `uom_id`, `amount`, `effective_from`, `effective_to`,
  `scope_key`, `lineage_key`, `revision_no`, `supersedes_price_id`, `recorded_from`,
  `recorded_to`, `correction_reason`, `created_at`, `updated_at`
)
SELECT
  source_price.`row_version`, ctx.`tenant_id`, source_price.`target_organization_unit_id`,
  source_price.`target_item_id`, source_price.`target_variant_id`, source_price.`price_type`,
  source_price.`target_currency_id`, source_price.`target_uom_id`, source_price.`amount`,
  source_price.`effective_from`, source_price.`effective_to`, source_price.`target_scope_key`,
  source_price.`lineage_key`, source_price.`revision_no`, NULL, source_price.`recorded_from`,
  source_price.`recorded_to`, source_price.`correction_reason`, source_price.`created_at`, source_price.`updated_at`
FROM `_import_mapped_item_prices` AS source_price
CROSS JOIN `_import_context` AS ctx
LEFT JOIN `_import_blocked_price_lineages` AS blocked ON blocked.`lineage_key` = source_price.`lineage_key`
WHERE blocked.`lineage_key` IS NULL
  AND NOT EXISTS (
    SELECT 1 FROM `item_prices` AS existing
    WHERE existing.`tenant_id` = ctx.`tenant_id`
      AND existing.`lineage_key` = source_price.`lineage_key`
      AND existing.`revision_no` = source_price.`revision_no`
  )
ORDER BY source_price.`lineage_key`, source_price.`revision_no`;
SET @imported_item_prices := ROW_COUNT();

UPDATE `item_prices` AS imported
CROSS JOIN `_import_context` AS ctx
INNER JOIN `_import_mapped_item_prices` AS source_price
  ON source_price.`lineage_key` = imported.`lineage_key`
 AND source_price.`revision_no` = imported.`revision_no`
INNER JOIN `_import_mapped_item_prices` AS source_parent
  ON source_parent.`id` = source_price.`supersedes_price_id`
INNER JOIN `item_prices` AS target_parent
  ON target_parent.`tenant_id` = ctx.`tenant_id`
 AND target_parent.`lineage_key` = source_parent.`lineage_key`
 AND target_parent.`revision_no` = source_parent.`revision_no`
SET imported.`supersedes_price_id` = target_parent.`id`
WHERE imported.`tenant_id` = ctx.`tenant_id`
  AND imported.`supersedes_price_id` IS NULL;
SET @linked_price_revisions := ROW_COUNT();
SET @skipped_price_lineages := (SELECT COUNT(*) FROM `_import_blocked_price_lineages`);
SQL;
}

function ownershipImportSql(): string
{
    return <<<'SQL'
INSERT INTO `_import_mapped_vehicle_ownerships`
SELECT
  source_ownership.`id`, source_ownership.`row_version`,
  CASE WHEN source_ownership.`organization_unit_id` IS NULL THEN NULL ELSE ctx.`organization_unit_id` END,
  target_vehicle.`id`, target_customer.`id`, CONCAT('customer:', target_customer.`id`),
  target_customer.`code`, COALESCE(NULLIF(target_customer.`display_name`, ''), target_customer.`name`),
  source_ownership.`ownership_type`, source_ownership.`started_at`, source_ownership.`ended_at`,
  source_ownership.`is_current`, source_ownership.`current_guard`, source_ownership.`active_guard`,
  source_ownership.`notes`, source_ownership.`created_at`, source_ownership.`updated_at`
FROM `_import_source_vehicle_ownerships` AS source_ownership
CROSS JOIN `_import_context` AS ctx
INNER JOIN `_import_vehicle_id_map` AS vehicle_map ON vehicle_map.`source_id` = source_ownership.`vehicle_id`
INNER JOIN `_import_customer_id_map` AS customer_map ON customer_map.`source_id` = source_ownership.`owner_id` AND source_ownership.`owner_type` = 'customer'
INNER JOIN `vehicles` AS target_vehicle ON target_vehicle.`id` = vehicle_map.`target_id` AND target_vehicle.`tenant_id` = ctx.`tenant_id`
INNER JOIN `customers` AS target_customer ON target_customer.`id` = customer_map.`target_id` AND target_customer.`tenant_id` = ctx.`tenant_id`;

INSERT INTO `vehicle_ownerships` (
  `row_version`, `tenant_id`, `organization_unit_id`, `vehicle_id`, `owner_type`, `owner_id`,
  `owner_key`, `owner_code_snapshot`, `owner_name_snapshot`, `ownership_type`, `started_at`,
  `ended_at`, `is_current`, `current_guard`, `active_guard`, `notes`, `created_at`, `updated_at`
)
SELECT
  mapped.`row_version`, ctx.`tenant_id`, mapped.`organization_unit_id`, mapped.`vehicle_id`,
  'customer', mapped.`customer_id`, mapped.`owner_key`, mapped.`owner_code_snapshot`,
  mapped.`owner_name_snapshot`, mapped.`ownership_type`, mapped.`started_at`, mapped.`ended_at`,
  mapped.`is_current`, mapped.`current_guard`, mapped.`active_guard`, mapped.`notes`,
  mapped.`created_at`, mapped.`updated_at`
FROM `_import_mapped_vehicle_ownerships` AS mapped
CROSS JOIN `_import_context` AS ctx
LEFT JOIN `vehicle_ownerships` AS exact_match
  ON exact_match.`tenant_id` = ctx.`tenant_id`
 AND exact_match.`vehicle_id` = mapped.`vehicle_id`
 AND exact_match.`owner_type` = 'customer'
 AND exact_match.`owner_id` = mapped.`customer_id`
 AND exact_match.`started_at` = mapped.`started_at`
LEFT JOIN `vehicle_ownerships` AS current_owner
  ON mapped.`current_guard` IS NOT NULL
 AND current_owner.`vehicle_id` = mapped.`vehicle_id`
 AND current_owner.`owner_type` = 'customer'
 AND current_owner.`current_guard` = 1
LEFT JOIN `vehicle_ownerships` AS active_pair
  ON mapped.`active_guard` IS NOT NULL
 AND active_pair.`vehicle_id` = mapped.`vehicle_id`
 AND active_pair.`owner_key` = mapped.`owner_key`
 AND active_pair.`active_guard` = 1
WHERE exact_match.`id` IS NULL
  AND (mapped.`current_guard` IS NULL OR current_owner.`id` IS NULL)
  AND (mapped.`active_guard` IS NULL OR active_pair.`id` IS NULL)
ORDER BY mapped.`vehicle_id`, mapped.`started_at`, mapped.`source_id`;
SET @imported_vehicle_ownerships := ROW_COUNT();
SQL;
}

function auxiliaryStagingSql(): string
{
    return <<<'SQL'
CREATE TEMPORARY TABLE `_import_mapped_item_prices` LIKE `_import_source_item_prices`;
ALTER TABLE `_import_mapped_item_prices`
  ADD COLUMN `target_item_id` BIGINT UNSIGNED NOT NULL,
  ADD COLUMN `target_variant_id` BIGINT UNSIGNED NULL,
  ADD COLUMN `target_currency_id` BIGINT UNSIGNED NOT NULL,
  ADD COLUMN `target_uom_id` BIGINT UNSIGNED NOT NULL,
  ADD COLUMN `target_organization_unit_id` BIGINT UNSIGNED NULL,
  ADD COLUMN `target_scope_key` CHAR(64) NOT NULL,
  ADD INDEX `_import_mapped_price_lineage_ix` (`lineage_key`, `revision_no`),
  ADD INDEX `_import_mapped_price_scope_ix` (`target_item_id`, `target_scope_key`);

CREATE TEMPORARY TABLE `_import_blocked_price_lineages` (
  `lineage_key` CHAR(36) NOT NULL,
  PRIMARY KEY (`lineage_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TEMPORARY TABLE `_import_mapped_vehicle_ownerships` (
  `source_id` BIGINT UNSIGNED NOT NULL,
  `row_version` BIGINT UNSIGNED NOT NULL,
  `organization_unit_id` BIGINT UNSIGNED NULL,
  `vehicle_id` BIGINT UNSIGNED NOT NULL,
  `customer_id` BIGINT UNSIGNED NOT NULL,
  `owner_key` VARCHAR(100) NOT NULL,
  `owner_code_snapshot` VARCHAR(100) NOT NULL,
  `owner_name_snapshot` VARCHAR(191) NOT NULL,
  `ownership_type` VARCHAR(40) NOT NULL,
  `started_at` DATETIME NOT NULL,
  `ended_at` DATETIME NULL,
  `is_current` TINYINT(1) NOT NULL,
  `current_guard` TINYINT UNSIGNED NULL,
  `active_guard` TINYINT UNSIGNED NULL,
  `notes` TEXT NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`source_id`),
  INDEX `_import_mapped_ownership_exact_ix` (`vehicle_id`, `customer_id`, `started_at`),
  INDEX `_import_mapped_ownership_current_ix` (`vehicle_id`, `current_guard`),
  INDEX `_import_mapped_ownership_active_ix` (`vehicle_id`, `owner_key`, `active_guard`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TEMPORARY TABLE `_import_customer_id_map` (
  `source_id` BIGINT UNSIGNED NOT NULL,
  `target_id` BIGINT UNSIGNED NOT NULL,
  PRIMARY KEY (`source_id`),
  INDEX `_import_customer_target_ix` (`target_id`)
);

CREATE TEMPORARY TABLE `_import_vehicle_id_map` (
  `source_id` BIGINT UNSIGNED NOT NULL,
  `target_id` BIGINT UNSIGNED NOT NULL,
  PRIMARY KEY (`source_id`),
  INDEX `_import_vehicle_target_ix` (`target_id`)
);
SQL;
}

function idMapSql(string $table, string $naturalKey, string $mapName): string
{
    return sprintf(
        <<<'SQL'
INSERT INTO `_import_%1$s_id_map` (`source_id`, `target_id`)
SELECT src.`id`, target.`id`
FROM `%2$s` AS src
CROSS JOIN `_import_context` AS ctx
INNER JOIN `%3$s` AS target
  ON target.`tenant_id` = ctx.`tenant_id`
 AND target.`%4$s` = src.`%4$s`;
SQL,
        $mapName,
        sourceTable($table),
        $table,
        $naturalKey,
    );
}

function summarySql(): string
{
    return <<<'SQL'
COMMIT;
SELECT RELEASE_LOCK('autoerp:selected-master-data-import') INTO @import_lock_released;

SELECT
  @imported_customers AS `customers_inserted`,
  @imported_customer_addresses AS `customer_addresses_inserted`,
  @imported_suppliers AS `suppliers_inserted`,
  @imported_vehicle_makes AS `vehicle_makes_inserted`,
  @imported_vehicle_models AS `vehicle_models_inserted`,
  @imported_vehicle_types AS `vehicle_types_inserted`,
  @imported_vehicle_categories AS `vehicle_categories_inserted`,
  @imported_vehicles AS `vehicles_inserted`,
  @imported_vehicle_ownerships AS `vehicle_customer_links_inserted`,
  @imported_item_categories AS `item_categories_inserted`,
  @imported_item_brands AS `item_brands_inserted`,
  @imported_items AS `items_inserted`,
  @imported_item_units AS `item_units_inserted`,
  @imported_item_variants AS `item_variants_inserted`,
  @imported_item_bundles AS `item_bundle_lines_inserted`,
  @imported_item_prices AS `item_prices_inserted`,
  @linked_price_revisions AS `price_revision_links_restored`,
  @skipped_price_lineages AS `price_lineages_skipped_due_to_overlap`;
SQL;
}

function sourceTable(string $table): string
{
    return '_import_source_'.$table;
}

function validateWithRollback(ConnectionInterface $connection, string $sql): void
{
    $validationSql = str_replace(
        "\nCOMMIT;\nSELECT RELEASE_LOCK('autoerp:selected-master-data-import') INTO @import_lock_released;",
        "\nROLLBACK;\nSELECT RELEASE_LOCK('autoerp:selected-master-data-import') INTO @import_lock_released;",
        $sql,
        $replacementCount,
    );
    if ($replacementCount !== 1) {
        throw new RuntimeException('Refusing validation because the COMMIT statement was not replaced exactly once.');
    }

    $temporaryPath = tempnam(sys_get_temp_dir(), 'autoerp-import-validation-');
    if ($temporaryPath === false || file_put_contents($temporaryPath, $validationSql) === false) {
        throw new RuntimeException('Unable to create the rollback validation SQL file.');
    }

    try {
        foreach (sqlStatements($temporaryPath) as $statement) {
            if (trim($statement) !== '') {
                $connection->unprepared($statement);
            }
        }
    } catch (Throwable $exception) {
        $connection->unprepared('ROLLBACK');
        $connection->select("SELECT RELEASE_LOCK('autoerp:selected-master-data-import')");
        throw $exception;
    } finally {
        unlink($temporaryPath);
    }
}

/** @return Generator<int, string> */
function sqlStatements(string $path): Generator
{
    $handle = fopen($path, 'rb');
    if ($handle === false) {
        throw new RuntimeException("Unable to open {$path}.");
    }
    $statement = '';
    $quote = null;
    $escaped = false;
    try {
        while (($chunk = fgets($handle)) !== false) {
            $length = strlen($chunk);
            for ($index = 0; $index < $length; $index++) {
                $character = $chunk[$index];
                $statement .= $character;
                if ($escaped) {
                    $escaped = false;

                    continue;
                }
                if ($quote !== null && $character === '\\') {
                    $escaped = true;

                    continue;
                }
                if ($quote !== null) {
                    if ($character === $quote) {
                        if ($index + 1 < $length && $chunk[$index + 1] === $quote) {
                            $statement .= $chunk[++$index];
                        } else {
                            $quote = null;
                        }
                    }

                    continue;
                }
                if ($character === "'" || $character === '"' || $character === '`') {
                    $quote = $character;

                    continue;
                }
                if ($character === ';') {
                    yield $statement;
                    $statement = '';
                }
            }
        }
        if (trim($statement) !== '') {
            yield $statement;
        }
    } finally {
        fclose($handle);
    }
}
