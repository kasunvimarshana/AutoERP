-- AutoERP service-price backfill from current sales prices
-- Target: run after selecting the intended AutoERP database in phpMyAdmin.
-- Behavior: copy current sales prices only for items that have no current service price.
-- Existing service-priced items are left unchanged.
-- The write lock prevents a concurrent item-price write from creating duplicate current scopes.

SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;
SET time_zone = '+00:00';

LOCK TABLES
  `item_prices` WRITE,
  `item_prices` AS `sales_price` READ,
  `item_prices` AS `service_price` READ;

SET @service_price_recorded_at := UTC_TIMESTAMP();

INSERT INTO `item_prices` (
  `row_version`,
  `tenant_id`,
  `organization_unit_id`,
  `item_id`,
  `item_variant_id`,
  `price_type`,
  `currency_id`,
  `uom_id`,
  `amount`,
  `effective_from`,
  `effective_to`,
  `scope_key`,
  `lineage_key`,
  `revision_no`,
  `supersedes_price_id`,
  `recorded_from`,
  `recorded_to`,
  `correction_reason`,
  `created_at`,
  `updated_at`
)
SELECT
  1,
  sales_price.`tenant_id`,
  sales_price.`organization_unit_id`,
  sales_price.`item_id`,
  sales_price.`item_variant_id`,
  'service',
  sales_price.`currency_id`,
  sales_price.`uom_id`,
  sales_price.`amount`,
  sales_price.`effective_from`,
  sales_price.`effective_to`,
  SHA2(
    CONCAT_WS(
      '|',
      CASE
        WHEN sales_price.`organization_unit_id` IS NULL THEN 'global'
        ELSE CAST(sales_price.`organization_unit_id` AS CHAR)
      END,
      CASE
        WHEN sales_price.`item_variant_id` IS NULL THEN 'all_variants'
        ELSE CAST(sales_price.`item_variant_id` AS CHAR)
      END,
      'service',
      CAST(sales_price.`currency_id` AS CHAR),
      CAST(sales_price.`uom_id` AS CHAR)
    ),
    256
  ),
  UUID(),
  1,
  NULL,
  @service_price_recorded_at,
  NULL,
  NULL,
  @service_price_recorded_at,
  @service_price_recorded_at
FROM `item_prices` AS sales_price
WHERE sales_price.`price_type` = 'sales'
  AND sales_price.`recorded_to` IS NULL
  AND NOT EXISTS (
    SELECT 1
    FROM `item_prices` AS service_price
    WHERE service_price.`tenant_id` = sales_price.`tenant_id`
      AND service_price.`item_id` = sales_price.`item_id`
      AND service_price.`price_type` = 'service'
      AND service_price.`recorded_to` IS NULL
  )
ORDER BY sales_price.`id`;

SET @inserted_service_price_count := ROW_COUNT();

UNLOCK TABLES;

SELECT
  @inserted_service_price_count AS `inserted_service_prices`,
  (SELECT COUNT(*) FROM `item_prices` WHERE `price_type` = 'service' AND `recorded_to` IS NULL) AS `total_current_service_prices`;
