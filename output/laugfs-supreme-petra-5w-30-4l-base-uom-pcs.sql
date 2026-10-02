-- MySQL/MariaDB maintenance script for item LAUGFS SUPREME PETRA 5W 30 4L.
-- The supplied dump identifies item 683 in tenant 1 / organization unit 1,
-- with PCS UOM 1, current stock 0.500000, an active PCS service item unit,
-- and an active service price currently recorded against LTR.
--
-- Back up the live database first. This script rechecks every target before
-- writing, runs in one transaction, preserves quantities, and leaves completed
-- service lines, invoices, inventory movements, and historical prices intact.

DELIMITER $$

DROP PROCEDURE IF EXISTS `codex_set_laugfs_petra_base_uom_pcs`$$

CREATE PROCEDURE `codex_set_laugfs_petra_base_uom_pcs`()
BEGIN
    DECLARE v_item_count INT DEFAULT 0;
    DECLARE v_uom_count INT DEFAULT 0;
    DECLARE v_base_count INT DEFAULT 0;
    DECLARE v_bad_base_count INT DEFAULT 0;
    DECLARE v_bad_balance_count INT DEFAULT 0;
    DECLARE v_default_count INT DEFAULT 0;
    DECLARE v_service_source_count INT DEFAULT 0;
    DECLARE v_service_price_conflict_count INT DEFAULT 0;
    DECLARE v_inserted_price_count INT DEFAULT 0;
    DECLARE v_closed_price_count INT DEFAULT 0;
    DECLARE v_item_id BIGINT UNSIGNED DEFAULT NULL;
    DECLARE v_tenant_id BIGINT UNSIGNED DEFAULT 1;
    DECLARE v_organization_unit_id BIGINT UNSIGNED DEFAULT 1;
    DECLARE v_pcs_uom_id BIGINT UNSIGNED DEFAULT NULL;
    DECLARE v_existing_item_base_uom_id BIGINT UNSIGNED DEFAULT NULL;
    DECLARE v_recorded_at DATETIME DEFAULT CURRENT_TIMESTAMP;

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

    START TRANSACTION;

    SELECT COUNT(*), MIN(`id`)
      INTO v_item_count, v_item_id
      FROM `items`
     WHERE `tenant_id` = v_tenant_id
       AND `organization_unit_id` = v_organization_unit_id
       AND `code` = 'LAUGFS SUPREME PETRA 5W 30 4L'
       AND `name` = 'LAUGFS SUPREME PETRA 5W 30 4L'
       AND `deleted_at` IS NULL;

    IF v_item_count <> 1 OR v_item_id <> 683 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Expected exactly item 683 for LAUGFS SUPREME PETRA 5W 30 4L in tenant 1 / organization unit 1.';
    END IF;

    SELECT COUNT(*), MIN(`id`)
      INTO v_uom_count, v_pcs_uom_id
      FROM `unit_of_measures`
     WHERE `tenant_id` = v_tenant_id
       AND `organization_unit_id` = v_organization_unit_id
       AND `code` = 'PCS'
       AND `is_active` = 1
       AND `deleted_at` IS NULL;

    IF v_uom_count <> 1 OR v_pcs_uom_id <> 1 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Expected exactly active PCS UOM 1 in tenant 1 / organization unit 1.';
    END IF;

    SELECT `base_uom_id`
      INTO v_existing_item_base_uom_id
      FROM `items`
     WHERE `id` = v_item_id
       AND `tenant_id` = v_tenant_id
     FOR UPDATE;

    IF v_existing_item_base_uom_id IS NOT NULL
       AND v_existing_item_base_uom_id <> v_pcs_uom_id THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Item already has a different Base UOM; no changes were made.';
    END IF;

    SELECT COUNT(*)
      INTO v_bad_base_count
      FROM `item_units`
     WHERE `tenant_id` = v_tenant_id
       AND `item_id` = v_item_id
       AND `unit_role` = 'base'
       AND (`uom_id` <> v_pcs_uom_id
            OR `conversion_factor` <> 1.000000
            OR `is_active` <> 1);

    IF v_bad_base_count <> 0 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Item has a conflicting base item-unit row; review it manually.';
    END IF;

    SELECT COUNT(*)
      INTO v_base_count
      FROM `item_units`
     WHERE `tenant_id` = v_tenant_id
       AND `item_id` = v_item_id
       AND `uom_id` = v_pcs_uom_id
       AND `unit_role` = 'base';

    SELECT COUNT(*)
      INTO v_default_count
      FROM `item_units`
     WHERE `tenant_id` = v_tenant_id
       AND `item_id` = v_item_id
       AND `is_active` = 1
       AND `is_default` = 1;

    IF v_base_count = 0 THEN
        INSERT INTO `item_units` (
            `tenant_id`, `organization_unit_id`, `item_id`, `uom_id`,
            `unit_role`, `conversion_factor`, `is_default`, `is_active`,
            `created_at`, `updated_at`
        ) VALUES (
            v_tenant_id, v_organization_unit_id, v_item_id, v_pcs_uom_id,
            'base', 1.000000, IF(v_default_count = 0, 1, 0), 1,
            v_recorded_at, v_recorded_at
        );
    END IF;

    UPDATE `items`
       SET `base_uom_id` = v_pcs_uom_id,
           `updated_at` = CURRENT_TIMESTAMP
     WHERE `id` = v_item_id
       AND `tenant_id` = v_tenant_id
       AND (`base_uom_id` IS NULL OR `base_uom_id` = v_pcs_uom_id);

    SELECT COUNT(*)
      INTO v_bad_balance_count
      FROM `inventory_stock_balances`
     WHERE `tenant_id` = v_tenant_id
       AND `item_id` = v_item_id
       AND `base_uom_id` IS NOT NULL
       AND `base_uom_id` <> v_pcs_uom_id;

    IF v_bad_balance_count <> 0 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Current stock balances have a different UOM; no changes were made.';
    END IF;

    UPDATE `inventory_stock_balances`
       SET `base_uom_id` = v_pcs_uom_id,
           `updated_at` = CURRENT_TIMESTAMP
     WHERE `tenant_id` = v_tenant_id
       AND `item_id` = v_item_id
       AND `base_uom_id` IS NULL;

    SELECT COUNT(*)
      INTO v_service_source_count
      FROM `item_prices` AS `source_price`
      JOIN `unit_of_measures` AS `source_uom`
        ON `source_uom`.`id` = `source_price`.`uom_id`
       AND `source_uom`.`tenant_id` = `source_price`.`tenant_id`
     WHERE `source_price`.`tenant_id` = v_tenant_id
       AND `source_price`.`item_id` = v_item_id
       AND `source_price`.`price_type` = 'service'
       AND `source_price`.`recorded_to` IS NULL
       AND `source_uom`.`code` = 'LTR';

    SELECT COUNT(*)
      INTO v_service_price_conflict_count
      FROM `item_prices` AS `source_price`
      JOIN `unit_of_measures` AS `source_uom`
        ON `source_uom`.`id` = `source_price`.`uom_id`
       AND `source_uom`.`tenant_id` = `source_price`.`tenant_id`
      JOIN `item_prices` AS `pcs_price`
        ON `pcs_price`.`tenant_id` = `source_price`.`tenant_id`
       AND `pcs_price`.`item_id` = `source_price`.`item_id`
       AND `pcs_price`.`organization_unit_id` <=> `source_price`.`organization_unit_id`
       AND `pcs_price`.`item_variant_id` <=> `source_price`.`item_variant_id`
       AND `pcs_price`.`price_type` = `source_price`.`price_type`
       AND `pcs_price`.`currency_id` = `source_price`.`currency_id`
       AND `pcs_price`.`uom_id` = v_pcs_uom_id
       AND `pcs_price`.`recorded_to` IS NULL
       AND `pcs_price`.`effective_from` <= COALESCE(`source_price`.`effective_to`, '9999-12-31')
       AND COALESCE(`pcs_price`.`effective_to`, '9999-12-31') >= `source_price`.`effective_from`
     WHERE `source_price`.`tenant_id` = v_tenant_id
       AND `source_price`.`item_id` = v_item_id
       AND `source_price`.`price_type` = 'service'
       AND `source_price`.`recorded_to` IS NULL
       AND `source_uom`.`code` = 'LTR';

    IF v_service_price_conflict_count <> 0 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'A current PCS service price overlaps the LTR price period; review prices before retrying.';
    END IF;

    IF v_service_source_count > 0 THEN
        INSERT INTO `item_prices` (
            `row_version`, `tenant_id`, `organization_unit_id`, `item_id`,
            `item_variant_id`, `price_type`, `currency_id`, `uom_id`, `amount`,
            `effective_from`, `effective_to`, `scope_key`, `lineage_key`,
            `revision_no`, `supersedes_price_id`, `recorded_from`, `recorded_to`,
            `correction_reason`, `created_at`, `updated_at`
        )
        SELECT
            1,
            `source_price`.`tenant_id`,
            `source_price`.`organization_unit_id`,
            `source_price`.`item_id`,
            `source_price`.`item_variant_id`,
            `source_price`.`price_type`,
            `source_price`.`currency_id`,
            v_pcs_uom_id,
            `source_price`.`amount`,
            `source_price`.`effective_from`,
            `source_price`.`effective_to`,
            SHA2(CONCAT(
                COALESCE(CAST(`source_price`.`organization_unit_id` AS CHAR), 'global'), '|',
                COALESCE(CAST(`source_price`.`item_variant_id` AS CHAR), 'all_variants'), '|',
                `source_price`.`price_type`, '|',
                CAST(`source_price`.`currency_id` AS CHAR), '|',
                CAST(v_pcs_uom_id AS CHAR)
            ), 256),
            `source_price`.`lineage_key`,
            `source_price`.`revision_no` + 1,
            `source_price`.`id`,
            v_recorded_at,
            NULL,
            'UOM correction: 4L pack service price is per PCS; prior revision retained.',
            v_recorded_at,
            v_recorded_at
        FROM `item_prices` AS `source_price`
        JOIN `unit_of_measures` AS `source_uom`
          ON `source_uom`.`id` = `source_price`.`uom_id`
         AND `source_uom`.`tenant_id` = `source_price`.`tenant_id`
        WHERE `source_price`.`tenant_id` = v_tenant_id
          AND `source_price`.`item_id` = v_item_id
          AND `source_price`.`price_type` = 'service'
          AND `source_price`.`recorded_to` IS NULL
          AND `source_uom`.`code` = 'LTR';

        SET v_inserted_price_count = ROW_COUNT();

        IF v_inserted_price_count <> v_service_source_count THEN
            SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = 'Service price revision count changed during setup; transaction cancelled.';
        END IF;

        UPDATE `item_prices` AS `source_price`
        JOIN `item_prices` AS `pcs_price`
          ON `pcs_price`.`supersedes_price_id` = `source_price`.`id`
         AND `pcs_price`.`tenant_id` = v_tenant_id
         AND `pcs_price`.`item_id` = v_item_id
         AND `pcs_price`.`uom_id` = v_pcs_uom_id
         AND `pcs_price`.`recorded_from` = v_recorded_at
         AND `pcs_price`.`correction_reason` = 'UOM correction: 4L pack service price is per PCS; prior revision retained.'
           SET `source_price`.`recorded_to` = v_recorded_at,
               `source_price`.`row_version` = `source_price`.`row_version` + 1,
               `source_price`.`updated_at` = v_recorded_at
         WHERE `source_price`.`recorded_to` IS NULL;

        SET v_closed_price_count = ROW_COUNT();

        IF v_closed_price_count <> v_inserted_price_count THEN
            SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = 'Could not close every superseded service price revision; transaction cancelled.';
        END IF;
    END IF;

    COMMIT;

    SELECT
        'Base UOM setup completed' AS `result`,
        v_item_id AS `item_id`,
        v_pcs_uom_id AS `base_uom_id`,
        v_inserted_price_count AS `new_pcs_service_price_revisions`;
END$$

CALL `codex_set_laugfs_petra_base_uom_pcs`()$$
DROP PROCEDURE `codex_set_laugfs_petra_base_uom_pcs`$$

DELIMITER ;

-- Review the resulting current item setup and service price:
SELECT `i`.`id`, `i`.`name`, `u`.`code` AS `base_uom_code`, `b`.`quantity_on_hand`
  FROM `items` AS `i`
  LEFT JOIN `unit_of_measures` AS `u`
    ON `u`.`id` = `i`.`base_uom_id`
   AND `u`.`tenant_id` = `i`.`tenant_id`
  LEFT JOIN `inventory_stock_balances` AS `b`
    ON `b`.`item_id` = `i`.`id`
   AND `b`.`tenant_id` = `i`.`tenant_id`
 WHERE `i`.`id` = 683
   AND `i`.`tenant_id` = 1;

SELECT `price_type`, `amount`, `currency_id`, `uom_id`, `effective_from`, `effective_to`, `recorded_to`
  FROM `item_prices`
 WHERE `item_id` = 683
   AND `tenant_id` = 1
   AND `price_type` = 'service'
 ORDER BY `id` DESC;
