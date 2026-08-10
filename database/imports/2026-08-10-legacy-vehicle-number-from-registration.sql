-- AutoERP correction for vehicles imported from the legacy POS database.
-- Sets Vehicle Number to the existing Registration value for LEGACY-VEH-* records only.
-- Target: MySQL 8+, tenant 1. Safe to rerun.

SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;
SET @tenant_id := 1;
SET @legacy_vehicle_code_pattern := 'LEGACY-VEH-%';
SET @expected_legacy_vehicle_count := 1888;
SET @recorded_at := CURRENT_TIMESTAMP(6);

DROP TEMPORARY TABLE IF EXISTS tmp_legacy_vehicle_number_guard;

CREATE TEMPORARY TABLE tmp_legacy_vehicle_number_guard (
    validation_name VARCHAR(191) NOT NULL,
    passed TINYINT NOT NULL,
    CONSTRAINT legacy_vehicle_number_sync_failed CHECK (passed = 1)
) ENGINE = InnoDB
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

START TRANSACTION;

INSERT INTO tmp_legacy_vehicle_number_guard VALUES (
    'tenant exists',
    EXISTS(SELECT 1 FROM tenants WHERE id = @tenant_id)
);

-- Serialize the update against concurrent vehicle changes.
SELECT id
FROM vehicles
WHERE tenant_id = @tenant_id
ORDER BY id
FOR UPDATE;

INSERT INTO tmp_legacy_vehicle_number_guard VALUES (
    'all 1888 imported legacy vehicles are present',
    (
        SELECT COUNT(*)
        FROM vehicles
        WHERE tenant_id = @tenant_id
          AND code LIKE @legacy_vehicle_code_pattern
    ) = @expected_legacy_vehicle_count
);

INSERT INTO tmp_legacy_vehicle_number_guard VALUES (
    'every imported legacy vehicle has a registration value',
    NOT EXISTS (
        SELECT 1
        FROM vehicles
        WHERE tenant_id = @tenant_id
          AND code LIKE @legacy_vehicle_code_pattern
          AND (registration_number IS NULL OR TRIM(registration_number) = '')
    )
);

INSERT INTO tmp_legacy_vehicle_number_guard VALUES (
    'legacy registration values are unique',
    NOT EXISTS (
        SELECT registration_number
        FROM vehicles
        WHERE tenant_id = @tenant_id
          AND code LIKE @legacy_vehicle_code_pattern
        GROUP BY registration_number
        HAVING COUNT(*) > 1
    )
);

INSERT INTO tmp_legacy_vehicle_number_guard VALUES (
    'registration values do not conflict with another vehicle number',
    NOT EXISTS (
        SELECT 1
        FROM vehicles source
        JOIN vehicles conflicting
          ON conflicting.tenant_id = source.tenant_id
         AND conflicting.vehicle_number = source.registration_number
         AND conflicting.id <> source.id
        WHERE source.tenant_id = @tenant_id
          AND source.code LIKE @legacy_vehicle_code_pattern
    )
);

UPDATE vehicles
SET vehicle_number = registration_number,
    row_version = row_version + 1,
    updated_at = @recorded_at
WHERE tenant_id = @tenant_id
  AND code LIKE @legacy_vehicle_code_pattern
  AND vehicle_number <> registration_number;

INSERT INTO tmp_legacy_vehicle_number_guard VALUES (
    'every imported legacy vehicle number now matches its registration',
    (
        SELECT COUNT(*)
        FROM vehicles
        WHERE tenant_id = @tenant_id
          AND code LIKE @legacy_vehicle_code_pattern
          AND vehicle_number = registration_number
    ) = @expected_legacy_vehicle_count
);

COMMIT;

SELECT
    COUNT(*) AS legacy_vehicles,
    SUM(vehicle_number = registration_number) AS matching_vehicle_numbers
FROM vehicles
WHERE tenant_id = @tenant_id
  AND code LIKE @legacy_vehicle_code_pattern;
