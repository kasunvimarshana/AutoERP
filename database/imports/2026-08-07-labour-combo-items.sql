-- AutoERP labour/combo item import generated from "labour item price updated.csv".
-- Target: MySQL 8+, tenant 1 / organization unit AUTOERP / LKR / HOUR.
-- Labour items intentionally receive no item_prices rows.
-- Existing items are reused by exact name; new combo items use the CSV code.
-- Existing service prices must already equal the CSV value or the import stops,
-- preserving the immutable item-price history instead of overwriting it.

SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;
SET @tenant_id := 1;
SET @organization_unit_code := 'AUTOERP';
SET @currency_code := 'LKR';
SET @uom_code := 'HOUR';
SET @effective_from := CURRENT_DATE();
SET @recorded_at := CURRENT_TIMESTAMP(6);

DROP TEMPORARY TABLE IF EXISTS tmp_import_guard;
DROP TEMPORARY TABLE IF EXISTS tmp_import_combos;
DROP TEMPORARY TABLE IF EXISTS tmp_import_labour;
DROP TEMPORARY TABLE IF EXISTS tmp_import_bundles;
DROP TEMPORARY TABLE IF EXISTS tmp_import_combo_map;
DROP TEMPORARY TABLE IF EXISTS tmp_import_labour_map;

CREATE TEMPORARY TABLE tmp_import_guard (
    validation_name VARCHAR(120) NOT NULL,
    passed TINYINT NOT NULL,
    CONSTRAINT labour_combo_import_validation_failed CHECK (passed = 1)
) ENGINE = InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TEMPORARY TABLE tmp_import_combos (
    source_code VARCHAR(80) NOT NULL PRIMARY KEY,
    item_name VARCHAR(255) NOT NULL UNIQUE,
    item_type VARCHAR(30) NOT NULL,
    service_price DECIMAL(20, 6) NOT NULL
) ENGINE = InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

INSERT INTO tmp_import_combos (source_code, item_name, item_type, service_price) VALUES
('50994800', 'ACID RAIN REMOVER L', 'combo', 9500.000000),
('38457212', 'ACID RAIN REMOVING S', 'combo', 2500.000000),
('1147789', 'ALTO COOLONG SYSTEM REMOVE AND RE-FITTING', 'combo', 6500.000000),
('60599373', 'ATF/CVT OIL CHANGE WITH SUMP REMOVING', 'combo', 4900.000000),
('40871093', 'AUTO GEAR OIL CHANGE LABOUR', 'combo', 1900.000000),
('71340181', 'AXEL OIL SEAL REPLACEMENT', 'combo', 2000.000000),
('36612703', 'BELT REPLACEMENT LABOUR', 'combo', 3200.000000),
('74219579', 'BRAKE CLENER LABOUR', 'combo', 1000.000000),
('49032392', 'BRAKE CYLINDER REPLACMENT', 'combo', 2500.000000),
('58091422', 'BRAKE LINER REPLACE LABOUR', 'combo', 2900.000000),
('13248978', 'BRAKE OIL CHANGE LABOUR M', 'combo', 3700.000000),
('22008755', 'BRAKE OIL CHANGE LABOUR S', 'combo', 2900.000000),
('78415140', 'BRAKE PAD CHANGE FRONT', 'combo', 2600.000000),
('96292541', 'BRAKE SERVICE LABOUR', 'combo', 4900.000000),
('49511493', 'BRAKE WHEEL CYLINDER REPLCEMENT', 'combo', 1000.000000),
('81590927', 'CALIPER PIN SERVICE', 'combo', 1000.000000),
('51809631', 'CLUCH OIL CHANGE LABOUR', 'combo', 2000.000000),
('27568569', 'COMPLETE ENGINE TIMING BELT REPLACEMENT', 'combo', 12000.000000),
('25927115', 'COOLANT CHANGE LABOUR', 'combo', 1500.000000),
('80436597', 'CUT AND POLISH M', 'combo', 15000.000000),
('82341332', 'CUT AND POLISH PANEL', 'combo', 4500.000000),
('75313762', 'CV BOOT REPLACEMENT', 'combo', 3500.000000),
('40172253', 'DIFFRANCEL OIL CHANGE', 'combo', 750.000000),
('9912339', 'ENGINE  SCAN', 'combo', 1500.000000),
('42712990', 'ENGINE TUNE UP HONDA VEZEL', 'combo', 15000.000000),
('2450539', 'FORKLIFT SERVICE TON 2 1/2', 'combo', 8000.000000),
('24832659', 'FORM CLEANING', 'combo', 1000.000000),
('24303996', 'FUEL SYSTEM CLEANING 1000CC-2000CC', 'combo', 13000.000000),
('59021316', 'FUEL SYSTEM CLEANING BELOW 1000', 'combo', 10950.000000),
('82320175', 'FUEL SYSTEM CLEANING HYBRID SPECIAL', 'combo', 14500.000000),
('52095102', 'FULL SERVICE CAR M', 'combo', 8000.000000),
('36933064', 'FULL SERVICE CAR S', 'combo', 6500.000000),
('88127930', 'FULL SERVICE DOUBLE CAB', 'combo', 10500.000000),
('97680699', 'FULL SERVICE LORRY L', 'combo', 10500.000000),
('18741922', 'FULL SERVICE LORRY M', 'combo', 8500.000000),
('4915323', 'FULL SERVICE LORRY S', 'combo', 7500.000000),
('212955', 'FULL SERVICE LORRY XL', 'combo', 13000.000000),
('1913427', 'FULL SERVICE VAN/JEEP M', 'combo', 8500.000000),
('80712239', 'GEAR BOX SEAL REPLACEMENT', 'combo', 4000.000000),
('82410130', 'HAND WASX S', 'combo', 1000.000000),
('7466952', 'HEAD LAMP CLEANING', 'combo', 2400.000000),
('88926545', 'HOOD INTERIOR CLEANING M', 'combo', 2000.000000),
('80523799', 'HOOD INTERIOR CLEANING S', 'combo', 1500.000000),
('817212', 'HUB REASER REPLACEMENT LABOUR', 'combo', 1750.000000),
('31900837', 'INTERIOR CLEANING CHARGE L', 'combo', 19000.000000),
('23100941', 'INTERIOR CLEANING HALF', 'combo', 6500.000000),
('29031640', 'INTERIOR CLEANING M', 'combo', 16000.000000),
('53340267', 'INTERIOR CLEANING S', 'combo', 14500.000000),
('2348739', 'INTERIOR CLEANING SHEET', 'combo', 1500.000000),
('49824809', 'JCB WASH M', 'combo', 13700.000000),
('75126033', 'LABOUR CHARGE', 'combo', 1000.000000),
('39900212', 'LEATHER TREATMENT', 'combo', 2000.000000),
('27382139', 'LORRY WASH & VACUUM', 'combo', 2300.000000),
('49173557', 'LORRY WASH ONLY', 'combo', 1500.000000),
('4263850', 'MACHINE WAX FOR SERVICE', 'combo', 2000.000000),
('3507124', 'MANUAL GEAR OIL CHANGE', 'combo', 750.000000),
('5211651', 'OIL & FILTER CHANGE LABOUR', 'combo', 2000.000000),
('32679372', 'OIL CHANGE', 'combo', 1700.000000),
('36520421', 'PETROL FILTER CLEANING CHARGES', 'combo', 3000.000000),
('40736119', 'PLASTIC CARE', 'combo', 150.000000),
('70475831', 'RACK BOOT REPLACEMENT', 'combo', 1000.000000),
('55044232', 'RACK REPLACEMENT LABOUR', 'combo', 6900.000000),
('10683914', 'RADIATOR REMOVE AND REFITING', 'combo', 2500.000000),
('90287353', 'RAIN X GLASS TREATMENT LABOUR', 'combo', 800.000000),
('13314659', 'RAT NET FIXING', 'combo', 5500.000000),
('25345883', 'SHOCKABSOBER REPLACEMENT LABOUR', 'combo', 2500.000000),
('74705691', 'SPECAIL PACKAGE FOR NS HOLDING', 'combo', 4500.000000),
('48719852', 'STABILISER BAR CHANGING LABOUR', 'combo', 750.000000),
('50072238', 'SURFACE REFINMENT L', 'combo', 16500.000000),
('10685936', 'SURFACE REFINMENT M', 'combo', 14500.000000),
('21196024', 'SURFACE REFINMENT S', 'combo', 12500.000000),
('21680097', 'TAPET COVER PACKING REPLACE', 'combo', 1850.000000),
('46387229', 'UNDER WASH L', 'combo', 5500.000000),
('39310354', 'UNDER WASH M', 'combo', 3500.000000),
('69740839', 'UNDER WASH S', 'combo', 2500.000000),
('12095176', 'VACCUM ONLY S', 'combo', 500.000000),
('84275941', 'VEHICLE INSPECTION', 'combo', 4000.000000),
('49402912', 'Vehicle Inspection Full', 'combo', 6500.000000),
('40691750', 'WASH & VACUUM', 'combo', 1550.000000),
('62301141', 'WASH & VACUUM L', 'combo', 1800.000000),
('1330061', 'WASH & VACUUM S', 'combo', 1200.000000),
('17953820', 'WASH &VACUUM M', 'combo', 1300.000000),
('29643828', 'WASH ONLY L', 'combo', 900.000000),
('6430961', 'WASH ONLY M', 'combo', 800.000000),
('93073142', 'WASH ONLY S', 'combo', 600.000000),
('91538276', 'WASH/VACUUM/WAX', 'combo', 3500.000000),
('84376151', 'WASH/WAX/VACUUM M', 'combo', 4500.000000),
('29149008', 'WATER GLASS NANO WAX', 'combo', 1500.000000),
('9274961', 'WHEEL ALIGNMENT CAR', 'combo', 1700.000000),
('67026508', 'WILITA HEAD LAMP CLEANING', 'combo', 750.000000),
('95027168', 'WURTH VALUE ADDED SERVICE', 'combo', 1500.000000),
('95027169', 'WHEEL ALIGNMENT VAN', 'combo', 2300.000000),
('95027170', 'WHEEL ALINGMENT LORRY', 'combo', 2600.000000);

CREATE TEMPORARY TABLE tmp_import_labour (
    item_name VARCHAR(255) NOT NULL PRIMARY KEY,
    new_item_code VARCHAR(80) NOT NULL UNIQUE,
    sort_order INT UNSIGNED NOT NULL,
    uses_job_supervisor TINYINT(1) NOT NULL
) ENGINE = InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

INSERT INTO tmp_import_labour (item_name, new_item_code, sort_order, uses_job_supervisor) VALUES
('Supervisor', 'LAB-SUPERVISOR', 1, 1),
('Technician', 'LAB-TECHNICIAN', 2, 0),
('Under Wash', 'LAB-UNDER-WASH', 3, 0),
('Body Wash', 'LAB-BODY-WASH', 4, 0),
('Finishing', 'LAB-FINISHING', 5, 0);

CREATE TEMPORARY TABLE tmp_import_bundles (
    parent_source_code VARCHAR(80) NOT NULL,
    labour_name VARCHAR(255) NOT NULL,
    unit_cost DECIMAL(20, 6) NOT NULL,
    sort_order INT UNSIGNED NOT NULL,
    uses_job_supervisor TINYINT(1) NOT NULL,
    PRIMARY KEY (parent_source_code, labour_name)
) ENGINE = InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

INSERT INTO tmp_import_bundles
    (parent_source_code, labour_name, unit_cost, sort_order, uses_job_supervisor)
VALUES
('50994800', 'Supervisor', 300.000000, 1, 1),
('50994800', 'Technician', 400.000000, 2, 0),
('38457212', 'Supervisor', 70.000000, 1, 1),
('38457212', 'Technician', 130.000000, 2, 0),
('1147789', 'Supervisor', 80.000000, 1, 1),
('1147789', 'Technician', 700.000000, 2, 0),
('60599373', 'Supervisor', 150.000000, 1, 1),
('60599373', 'Technician', 240.000000, 2, 0),
('40871093', 'Supervisor', 70.000000, 1, 1),
('40871093', 'Technician', 80.000000, 2, 0),
('71340181', 'Supervisor', 40.000000, 1, 1),
('71340181', 'Technician', 200.000000, 2, 0),
('36612703', 'Supervisor', 80.000000, 1, 1),
('36612703', 'Technician', 300.000000, 2, 0),
('74219579', 'Supervisor', 20.000000, 1, 1),
('74219579', 'Technician', 60.000000, 2, 0),
('49032392', 'Supervisor', 50.000000, 1, 1),
('49032392', 'Technician', 250.000000, 2, 0),
('58091422', 'Supervisor', 50.000000, 1, 1),
('58091422', 'Technician', 200.000000, 2, 0),
('13248978', 'Supervisor', 90.000000, 1, 1),
('13248978', 'Technician', 160.000000, 2, 0),
('22008755', 'Supervisor', 80.000000, 1, 1),
('22008755', 'Technician', 120.000000, 2, 0),
('78415140', 'Supervisor', 80.000000, 1, 1),
('78415140', 'Technician', 120.000000, 2, 0),
('96292541', 'Supervisor', 100.000000, 1, 1),
('96292541', 'Technician', 200.000000, 2, 0),
('49511493', 'Supervisor', 20.000000, 1, 1),
('49511493', 'Technician', 100.000000, 2, 0),
('81590927', 'Supervisor', 20.000000, 1, 1),
('81590927', 'Technician', 60.000000, 2, 0),
('51809631', 'Supervisor', 80.000000, 1, 1),
('51809631', 'Technician', 100.000000, 2, 0),
('27568569', 'Supervisor', 300.000000, 1, 1),
('27568569', 'Technician', 1100.000000, 2, 0),
('25927115', 'Supervisor', 70.000000, 1, 1),
('25927115', 'Technician', 50.000000, 2, 0),
('80436597', 'Supervisor', 200.000000, 1, 1),
('80436597', 'Technician', 1000.000000, 2, 0),
('82341332', 'Supervisor', 60.000000, 1, 1),
('82341332', 'Technician', 300.000000, 2, 0),
('75313762', 'Supervisor', 50.000000, 1, 1),
('75313762', 'Technician', 370.000000, 2, 0),
('40172253', 'Supervisor', 20.000000, 1, 1),
('40172253', 'Technician', 40.000000, 2, 0),
('9912339', 'Supervisor', 40.000000, 1, 1),
('9912339', 'Technician', 70.000000, 2, 0),
('42712990', 'Supervisor', 300.000000, 1, 1),
('42712990', 'Technician', 900.000000, 2, 0),
('2450539', 'Supervisor', 100.000000, 1, 1),
('2450539', 'Body Wash', 400.000000, 4, 0),
('2450539', 'Finishing', 100.000000, 5, 0),
('24832659', 'Supervisor', 20.000000, 1, 1),
('24832659', 'Technician', 30.000000, 2, 0),
('24303996', 'Supervisor', 400.000000, 1, 1),
('24303996', 'Technician', 500.000000, 2, 0),
('59021316', 'Supervisor', 300.000000, 1, 1),
('59021316', 'Technician', 500.000000, 2, 0),
('82320175', 'Supervisor', 400.000000, 1, 1),
('82320175', 'Technician', 600.000000, 2, 0),
('52095102', 'Supervisor', 80.000000, 1, 1),
('52095102', 'Technician', 100.000000, 2, 0),
('52095102', 'Under Wash', 150.000000, 3, 0),
('52095102', 'Body Wash', 50.000000, 4, 0),
('52095102', 'Finishing', 120.000000, 5, 0),
('36933064', 'Supervisor', 60.000000, 1, 1),
('36933064', 'Technician', 80.000000, 2, 0),
('36933064', 'Under Wash', 120.000000, 3, 0),
('36933064', 'Body Wash', 40.000000, 4, 0),
('36933064', 'Finishing', 100.000000, 5, 0),
('88127930', 'Supervisor', 95.000000, 1, 1),
('88127930', 'Technician', 110.000000, 2, 0),
('88127930', 'Under Wash', 225.000000, 3, 0),
('88127930', 'Body Wash', 70.000000, 4, 0),
('88127930', 'Finishing', 150.000000, 5, 0),
('97680699', 'Supervisor', 95.000000, 1, 1),
('97680699', 'Technician', 110.000000, 2, 0),
('97680699', 'Under Wash', 225.000000, 3, 0),
('97680699', 'Body Wash', 70.000000, 4, 0),
('97680699', 'Finishing', 150.000000, 5, 0),
('18741922', 'Supervisor', 90.000000, 1, 1),
('18741922', 'Technician', 100.000000, 2, 0),
('18741922', 'Under Wash', 200.000000, 3, 0),
('18741922', 'Body Wash', 70.000000, 4, 0),
('18741922', 'Finishing', 140.000000, 5, 0),
('4915323', 'Supervisor', 80.000000, 1, 1),
('4915323', 'Technician', 100.000000, 2, 0),
('4915323', 'Under Wash', 150.000000, 3, 0),
('4915323', 'Body Wash', 50.000000, 4, 0),
('4915323', 'Finishing', 120.000000, 5, 0),
('212955', 'Supervisor', 100.000000, 1, 1),
('212955', 'Technician', 120.000000, 2, 0),
('212955', 'Under Wash', 280.000000, 3, 0),
('212955', 'Body Wash', 150.000000, 4, 0),
('212955', 'Finishing', 150.000000, 5, 0),
('1913427', 'Supervisor', 90.000000, 1, 1),
('1913427', 'Technician', 100.000000, 2, 0),
('1913427', 'Under Wash', 200.000000, 3, 0),
('1913427', 'Body Wash', 70.000000, 4, 0),
('1913427', 'Finishing', 140.000000, 5, 0),
('80712239', 'Supervisor', 80.000000, 1, 1),
('80712239', 'Technician', 400.000000, 2, 0),
('82410130', 'Supervisor', 30.000000, 1, 1),
('82410130', 'Technician', 50.000000, 2, 0),
('7466952', 'Supervisor', 60.000000, 1, 1),
('7466952', 'Technician', 90.000000, 2, 0),
('88926545', 'Supervisor', 60.000000, 1, 1),
('88926545', 'Technician', 100.000000, 2, 0),
('80523799', 'Supervisor', 50.000000, 1, 1),
('80523799', 'Technician', 70.000000, 2, 0),
('817212', 'Supervisor', 40.000000, 1, 1),
('817212', 'Technician', 100.000000, 2, 0),
('31900837', 'Supervisor', 400.000000, 1, 1),
('31900837', 'Technician', 2600.000000, 2, 0),
('23100941', 'Supervisor', 70.000000, 1, 1),
('23100941', 'Technician', 450.000000, 2, 0),
('29031640', 'Supervisor', 360.000000, 1, 1),
('29031640', 'Technician', 2200.000000, 2, 0),
('53340267', 'Supervisor', 360.000000, 1, 1),
('53340267', 'Technician', 1800.000000, 2, 0),
('2348739', 'Supervisor', 20.000000, 1, 1),
('2348739', 'Technician', 100.000000, 2, 0),
('49824809', 'Supervisor', 100.000000, 1, 1),
('49824809', 'Technician', 120.000000, 2, 0),
('49824809', 'Under Wash', 280.000000, 3, 0),
('49824809', 'Body Wash', 150.000000, 4, 0),
('49824809', 'Finishing', 150.000000, 5, 0),
('75126033', 'Supervisor', 30.000000, 1, 1),
('75126033', 'Technician', 50.000000, 2, 0),
('39900212', 'Supervisor', 60.000000, 1, 1),
('39900212', 'Technician', 100.000000, 2, 0),
('27382139', 'Body Wash', 100.000000, 4, 0),
('27382139', 'Finishing', 80.000000, 5, 0),
('49173557', 'Body Wash', 90.000000, 4, 0),
('49173557', 'Finishing', 30.000000, 5, 0),
('4263850', 'Supervisor', 80.000000, 1, 1),
('4263850', 'Technician', 150.000000, 2, 0),
('3507124', 'Supervisor', 20.000000, 1, 1),
('3507124', 'Technician', 40.000000, 2, 0),
('5211651', 'Supervisor', 50.000000, 1, 1),
('5211651', 'Technician', 80.000000, 2, 0),
('32679372', 'Supervisor', 50.000000, 1, 1),
('32679372', 'Technician', 70.000000, 2, 0),
('36520421', 'Supervisor', 40.000000, 1, 1),
('36520421', 'Technician', 200.000000, 2, 0),
('40736119', 'Supervisor', 6.000000, 1, 1),
('40736119', 'Technician', 6.000000, 2, 0),
('70475831', 'Supervisor', 30.000000, 1, 1),
('70475831', 'Technician', 50.000000, 2, 0),
('55044232', 'Supervisor', 80.000000, 1, 1),
('55044232', 'Technician', 450.000000, 2, 0),
('10683914', 'Supervisor', 50.000000, 1, 1),
('10683914', 'Technician', 150.000000, 2, 0),
('90287353', 'Supervisor', 25.000000, 1, 1),
('90287353', 'Technician', 25.000000, 2, 0),
('13314659', 'Supervisor', 120.000000, 1, 1),
('13314659', 'Technician', 300.000000, 2, 0),
('25345883', 'Supervisor', 50.000000, 1, 1),
('25345883', 'Technician', 150.000000, 2, 0),
('48719852', 'Supervisor', 20.000000, 1, 1),
('48719852', 'Technician', 40.000000, 2, 0),
('50072238', 'Supervisor', 400.000000, 1, 1),
('50072238', 'Technician', 2240.000000, 2, 0),
('10685936', 'Supervisor', 320.000000, 1, 1),
('10685936', 'Technician', 2000.000000, 2, 0),
('21196024', 'Supervisor', 300.000000, 1, 1),
('21196024', 'Technician', 1700.000000, 2, 0),
('21680097', 'Supervisor', 50.000000, 1, 1),
('21680097', 'Technician', 100.000000, 2, 0),
('46387229', 'Supervisor', 60.000000, 1, 1),
('46387229', 'Under Wash', 200.000000, 3, 0),
('39310354', 'Supervisor', 50.000000, 1, 1),
('39310354', 'Under Wash', 140.000000, 3, 0),
('69740839', 'Supervisor', 40.000000, 1, 1),
('69740839', 'Under Wash', 120.000000, 3, 0),
('84275941', 'Supervisor', 100.000000, 1, 1),
('84275941', 'Technician', 200.000000, 2, 0),
('49402912', 'Supervisor', 150.000000, 1, 1),
('49402912', 'Technician', 350.000000, 2, 0),
('40691750', 'Body Wash', 50.000000, 4, 0),
('40691750', 'Finishing', 70.000000, 5, 0),
('62301141', 'Body Wash', 60.000000, 4, 0),
('62301141', 'Finishing', 90.000000, 5, 0),
('1330061', 'Body Wash', 30.000000, 4, 0),
('1330061', 'Finishing', 50.000000, 5, 0),
('17953820', 'Body Wash', 30.000000, 4, 0),
('17953820', 'Finishing', 50.000000, 5, 0),
('29643828', 'Body Wash', 30.000000, 4, 0),
('29643828', 'Finishing', 50.000000, 5, 0),
('6430961', 'Body Wash', 25.000000, 4, 0),
('6430961', 'Finishing', 40.000000, 5, 0),
('93073142', 'Body Wash', 20.000000, 4, 0),
('93073142', 'Finishing', 25.000000, 5, 0),
('91538276', 'Supervisor', 50.000000, 1, 1),
('91538276', 'Technician', 120.000000, 2, 0),
('91538276', 'Body Wash', 30.000000, 4, 0),
('91538276', 'Finishing', 50.000000, 5, 0),
('84376151', 'Supervisor', 50.000000, 1, 1),
('84376151', 'Technician', 150.000000, 2, 0),
('84376151', 'Body Wash', 50.000000, 4, 0),
('84376151', 'Finishing', 70.000000, 5, 0),
('29149008', 'Supervisor', 40.000000, 1, 1),
('29149008', 'Technician', 80.000000, 2, 0),
('9274961', 'Supervisor', 40.000000, 1, 1),
('9274961', 'Technician', 140.000000, 2, 0),
('67026508', 'Supervisor', 20.000000, 1, 1),
('67026508', 'Technician', 40.000000, 2, 0),
('95027168', 'Supervisor', 50.000000, 1, 1),
('95027168', 'Technician', 70.000000, 2, 0),
('95027169', 'Supervisor', 35.000000, 1, 1),
('95027169', 'Technician', 150.000000, 2, 0),
('95027170', 'Supervisor', 50.000000, 1, 1),
('95027170', 'Technician', 160.000000, 2, 0);

SET @organization_unit_id := (
    SELECT id FROM organization_units
    WHERE tenant_id = @tenant_id AND code = @organization_unit_code
    LIMIT 1
);
SET @currency_id := (
    SELECT id FROM currencies WHERE code = @currency_code AND is_active = 1 LIMIT 1
);
SET @uom_id := (
    SELECT id FROM unit_of_measures
    WHERE tenant_id = @tenant_id AND code = @uom_code AND is_active = 1
    LIMIT 1
);
SET @labour_category_id := (
    SELECT id FROM item_categories
    WHERE tenant_id = @tenant_id AND code = 'LABOUR' AND is_active = 1
    LIMIT 1
);
SET @combo_category_id := (
    SELECT id FROM item_categories
    WHERE tenant_id = @tenant_id AND code = 'PACKAGES' AND is_active = 1
    LIMIT 1
);
SET @price_scope_key := SHA2(
    CONCAT(@organization_unit_id, '|all_variants|service|', @currency_id, '|', @uom_id),
    256
);

START TRANSACTION;

INSERT INTO tmp_import_guard VALUES ('tenant exists', EXISTS(
    SELECT 1 FROM tenants WHERE id = @tenant_id
));
INSERT INTO tmp_import_guard VALUES ('organization unit exists', @organization_unit_id IS NOT NULL);
INSERT INTO tmp_import_guard VALUES ('active currency exists', @currency_id IS NOT NULL);
INSERT INTO tmp_import_guard VALUES ('active HOUR UOM exists', @uom_id IS NOT NULL);
INSERT INTO tmp_import_guard VALUES ('active LABOUR category exists', @labour_category_id IS NOT NULL);
INSERT INTO tmp_import_guard VALUES ('active PACKAGES category exists', @combo_category_id IS NOT NULL);
INSERT INTO tmp_import_guard VALUES ('all CSV types are combo', NOT EXISTS(
    SELECT 1 FROM tmp_import_combos WHERE item_type <> 'combo'
));

-- Serialize this tenant's item-master import against concurrent writers.
SELECT id FROM items WHERE tenant_id = @tenant_id ORDER BY id FOR UPDATE;

INSERT INTO tmp_import_guard
SELECT 'combo code collision', NOT EXISTS(
    SELECT 1
    FROM tmp_import_combos source
    JOIN items existing
      ON existing.tenant_id = @tenant_id
     AND existing.code = source.source_code
    WHERE existing.name <> source.item_name
);

INSERT INTO tmp_import_guard
SELECT 'combo code/name resolve to different items', NOT EXISTS(
    SELECT 1
    FROM tmp_import_combos source
    JOIN items code_item
      ON code_item.tenant_id = @tenant_id
     AND code_item.code = source.source_code
    JOIN items name_item
      ON name_item.tenant_id = @tenant_id
     AND name_item.name = source.item_name
    WHERE code_item.id <> name_item.id
);

INSERT INTO tmp_import_guard
SELECT 'existing combo has incompatible configuration', NOT EXISTS(
    SELECT 1
    FROM tmp_import_combos source
    JOIN items existing
      ON existing.tenant_id = @tenant_id
     AND existing.name = source.item_name
    WHERE existing.organization_unit_id <> @organization_unit_id
       OR existing.item_type <> 'combo'
       OR existing.is_combo <> 1
       OR existing.is_stockable <> 0
       OR existing.tracking_type <> 'none'
       OR existing.costing_method <> 'none'
       OR existing.base_uom_id <> @uom_id
       OR existing.deleted_at IS NOT NULL
);

INSERT INTO tmp_import_guard
SELECT 'labour code collision', NOT EXISTS(
    SELECT 1
    FROM tmp_import_labour source
    JOIN items existing
      ON existing.tenant_id = @tenant_id
     AND existing.code = source.new_item_code
    WHERE existing.name <> source.item_name
);

INSERT INTO tmp_import_guard
SELECT 'existing labour has incompatible configuration', NOT EXISTS(
    SELECT 1
    FROM tmp_import_labour source
    JOIN items existing
      ON existing.tenant_id = @tenant_id
     AND existing.name = source.item_name
    WHERE existing.organization_unit_id <> @organization_unit_id
       OR existing.item_type <> 'labour'
       OR existing.is_combo <> 0
       OR existing.is_stockable <> 0
       OR existing.tracking_type <> 'none'
       OR existing.costing_method <> 'none'
       OR existing.base_uom_id <> @uom_id
       OR existing.deleted_at IS NOT NULL
);

INSERT INTO items (
    tenant_id, organization_unit_id, item_category_id, item_brand_id,
    code, sku, barcode, name, description, item_type, tracking_type,
    costing_method, base_uom_id, default_tax_group_id, purchase_tax_group_id,
    sales_tax_group_id, is_stockable, is_combo, is_tax_exempt, is_active,
    metadata, created_at, updated_at
)
SELECT
    @tenant_id, @organization_unit_id, @labour_category_id, NULL,
    source.new_item_code, NULL, NULL, source.item_name, NULL, 'labour', 'none',
    'none', @uom_id, NULL, NULL, NULL, 0, 0, 0, 1,
    JSON_OBJECT('import_source', 'labour item price updated.csv'),
    @recorded_at, @recorded_at
FROM tmp_import_labour source
WHERE NOT EXISTS (
    SELECT 1 FROM items existing
    WHERE existing.tenant_id = @tenant_id
      AND existing.name = source.item_name
);

INSERT INTO items (
    tenant_id, organization_unit_id, item_category_id, item_brand_id,
    code, sku, barcode, name, description, item_type, tracking_type,
    costing_method, base_uom_id, default_tax_group_id, purchase_tax_group_id,
    sales_tax_group_id, is_stockable, is_combo, is_tax_exempt, is_active,
    metadata, created_at, updated_at
)
SELECT
    @tenant_id, @organization_unit_id, @combo_category_id, NULL,
    source.source_code, NULL, NULL, source.item_name, NULL, source.item_type, 'none',
    'none', @uom_id, NULL, NULL, NULL, 0, 1, 0, 1,
    JSON_OBJECT('import_source', 'labour item price updated.csv'),
    @recorded_at, @recorded_at
FROM tmp_import_combos source
WHERE NOT EXISTS (
    SELECT 1 FROM items existing
    WHERE existing.tenant_id = @tenant_id
      AND (existing.code = source.source_code OR existing.name = source.item_name)
);

CREATE TEMPORARY TABLE tmp_import_combo_map (
    source_code VARCHAR(80) NOT NULL PRIMARY KEY,
    item_id BIGINT UNSIGNED NOT NULL UNIQUE
) ENGINE = InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

INSERT INTO tmp_import_combo_map (source_code, item_id)
SELECT source.source_code, MIN(items.id)
FROM tmp_import_combos source
JOIN items
  ON items.tenant_id = @tenant_id
 AND (items.code = source.source_code OR items.name = source.item_name)
GROUP BY source.source_code;

CREATE TEMPORARY TABLE tmp_import_labour_map (
    labour_name VARCHAR(255) NOT NULL PRIMARY KEY,
    item_id BIGINT UNSIGNED NOT NULL UNIQUE
) ENGINE = InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

INSERT INTO tmp_import_labour_map (labour_name, item_id)
SELECT source.item_name, MIN(items.id)
FROM tmp_import_labour source
JOIN items
  ON items.tenant_id = @tenant_id
 AND items.name = source.item_name
GROUP BY source.item_name;

INSERT INTO tmp_import_guard VALUES ('all combo items resolved',
    (SELECT COUNT(*) FROM tmp_import_combo_map) = (SELECT COUNT(*) FROM tmp_import_combos)
);
INSERT INTO tmp_import_guard VALUES ('all labour items resolved',
    (SELECT COUNT(*) FROM tmp_import_labour_map) = (SELECT COUNT(*) FROM tmp_import_labour)
);

-- Match ItemCreationService::syncBaseUnit for every imported/reused item.
INSERT INTO item_units (
    tenant_id, organization_unit_id, item_id, uom_id, unit_role,
    conversion_factor, is_default, is_active, created_at, updated_at
)
SELECT
    @tenant_id, @organization_unit_id, targets.item_id, @uom_id, 'base',
    1.000000,
    NOT EXISTS (
        SELECT 1 FROM item_units defaults
        WHERE defaults.item_id = targets.item_id
          AND defaults.is_default = 1
          AND defaults.is_active = 1
    ),
    1, @recorded_at, @recorded_at
FROM (
    SELECT item_id FROM tmp_import_combo_map
    UNION
    SELECT item_id FROM tmp_import_labour_map
) targets
WHERE NOT EXISTS (
    SELECT 1 FROM item_units existing
    WHERE existing.item_id = targets.item_id
      AND existing.uom_id = @uom_id
      AND existing.unit_role = 'base'
);

-- Protect immutable price history: a covering current price may be reused only
-- when its amount already equals the CSV service price.
INSERT INTO tmp_import_guard
SELECT 'existing service price differs from CSV', NOT EXISTS(
    SELECT 1
    FROM tmp_import_combos source
    JOIN tmp_import_combo_map map ON map.source_code = source.source_code
    JOIN item_prices price
      ON price.tenant_id = @tenant_id
     AND price.item_id = map.item_id
     AND price.scope_key = @price_scope_key
     AND price.recorded_to IS NULL
     AND price.effective_from <= @effective_from
     AND (price.effective_to IS NULL OR price.effective_to >= @effective_from)
    WHERE price.amount <> source.service_price
);

INSERT INTO tmp_import_guard
SELECT 'multiple current covering service prices', NOT EXISTS(
    SELECT map.item_id
    FROM tmp_import_combo_map map
    JOIN item_prices price
      ON price.tenant_id = @tenant_id
     AND price.item_id = map.item_id
     AND price.scope_key = @price_scope_key
     AND price.recorded_to IS NULL
     AND price.effective_from <= @effective_from
     AND (price.effective_to IS NULL OR price.effective_to >= @effective_from)
    GROUP BY map.item_id
    HAVING COUNT(*) > 1
);

INSERT INTO tmp_import_guard
SELECT 'future service price overlaps import', NOT EXISTS(
    SELECT 1
    FROM tmp_import_combo_map map
    JOIN item_prices price
      ON price.tenant_id = @tenant_id
     AND price.item_id = map.item_id
     AND price.scope_key = @price_scope_key
     AND price.recorded_to IS NULL
     AND price.effective_from > @effective_from
     AND (price.effective_to IS NULL OR price.effective_to >= @effective_from)
);

INSERT INTO item_prices (
    row_version, tenant_id, organization_unit_id, item_id, item_variant_id,
    price_type, currency_id, uom_id, amount, effective_from, effective_to,
    scope_key, lineage_key, revision_no, supersedes_price_id, recorded_from,
    recorded_to, correction_reason, created_at, updated_at
)
SELECT
    1, @tenant_id, @organization_unit_id, map.item_id, NULL,
    'service', @currency_id, @uom_id, source.service_price,
    @effective_from, NULL, @price_scope_key, UUID(), 1, NULL,
    @recorded_at, NULL, NULL, @recorded_at, @recorded_at
FROM tmp_import_combos source
JOIN tmp_import_combo_map map ON map.source_code = source.source_code
WHERE NOT EXISTS (
    SELECT 1 FROM item_prices existing
    WHERE existing.tenant_id = @tenant_id
      AND existing.item_id = map.item_id
      AND existing.scope_key = @price_scope_key
      AND existing.recorded_to IS NULL
      AND existing.effective_from <= @effective_from
      AND (existing.effective_to IS NULL OR existing.effective_to >= @effective_from)
);

INSERT INTO tmp_import_guard
SELECT 'duplicate labour bundle lines already exist', NOT EXISTS(
    SELECT combo.item_id, labour.item_id
    FROM tmp_import_bundles source
    JOIN tmp_import_combo_map combo ON combo.source_code = source.parent_source_code
    JOIN tmp_import_labour_map labour ON labour.labour_name = source.labour_name
    JOIN item_bundles bundle
      ON bundle.tenant_id = @tenant_id
     AND bundle.parent_item_id = combo.item_id
     AND bundle.child_item_id = labour.item_id
     AND bundle.line_type = 'labour'
    GROUP BY combo.item_id, labour.item_id
    HAVING COUNT(*) > 1
);

UPDATE item_bundles bundle
JOIN tmp_import_combo_map combo ON combo.item_id = bundle.parent_item_id
JOIN tmp_import_bundles source ON source.parent_source_code = combo.source_code
JOIN tmp_import_labour_map labour
  ON labour.labour_name = source.labour_name
 AND labour.item_id = bundle.child_item_id
SET bundle.organization_unit_id = @organization_unit_id,
    bundle.child_variant_id = NULL,
    bundle.quantity = 1.000000,
    bundle.uom_id = @uom_id,
    bundle.unit_cost = source.unit_cost,
    bundle.uses_job_supervisor = source.uses_job_supervisor,
    bundle.is_required = 1,
    bundle.sort_order = source.sort_order,
    bundle.updated_at = @recorded_at
WHERE bundle.tenant_id = @tenant_id
  AND bundle.line_type = 'labour';

INSERT INTO item_bundles (
    tenant_id, organization_unit_id, parent_item_id, child_item_id,
    child_variant_id, quantity, uom_id, line_type, unit_cost,
    uses_job_supervisor, is_required, sort_order, created_at, updated_at
)
SELECT
    @tenant_id, @organization_unit_id, combo.item_id, labour.item_id,
    NULL, 1.000000, @uom_id, 'labour', source.unit_cost,
    source.uses_job_supervisor, 1, source.sort_order, @recorded_at, @recorded_at
FROM tmp_import_bundles source
JOIN tmp_import_combo_map combo ON combo.source_code = source.parent_source_code
JOIN tmp_import_labour_map labour ON labour.labour_name = source.labour_name
WHERE NOT EXISTS (
    SELECT 1 FROM item_bundles existing
    WHERE existing.tenant_id = @tenant_id
      AND existing.parent_item_id = combo.item_id
      AND existing.child_item_id = labour.item_id
      AND existing.line_type = 'labour'
);

-- Final transactional verification.
INSERT INTO tmp_import_guard VALUES ('all service prices present', NOT EXISTS(
    SELECT 1
    FROM tmp_import_combos source
    JOIN tmp_import_combo_map map ON map.source_code = source.source_code
    WHERE NOT EXISTS (
        SELECT 1 FROM item_prices price
        WHERE price.tenant_id = @tenant_id
          AND price.item_id = map.item_id
          AND price.scope_key = @price_scope_key
          AND price.recorded_to IS NULL
          AND price.effective_from <= @effective_from
          AND (price.effective_to IS NULL OR price.effective_to >= @effective_from)
          AND price.amount = source.service_price
    )
));

INSERT INTO tmp_import_guard VALUES ('all labour bundle costs present', NOT EXISTS(
    SELECT 1
    FROM tmp_import_bundles source
    JOIN tmp_import_combo_map combo ON combo.source_code = source.parent_source_code
    JOIN tmp_import_labour_map labour ON labour.labour_name = source.labour_name
    WHERE NOT EXISTS (
        SELECT 1 FROM item_bundles bundle
        WHERE bundle.tenant_id = @tenant_id
          AND bundle.parent_item_id = combo.item_id
          AND bundle.child_item_id = labour.item_id
          AND bundle.line_type = 'labour'
          AND bundle.quantity = 1.000000
          AND bundle.uom_id = @uom_id
          AND bundle.unit_cost = source.unit_cost
          AND bundle.uses_job_supervisor = source.uses_job_supervisor
          AND bundle.is_required = 1
    )
));

SELECT
    (SELECT COUNT(*) FROM tmp_import_combo_map) AS combo_items,
    (SELECT COUNT(*) FROM tmp_import_labour_map) AS labour_items,
    (SELECT COUNT(*) FROM tmp_import_bundles) AS labour_bundle_lines,
    (SELECT COUNT(*)
       FROM tmp_import_combos source
       JOIN tmp_import_combo_map map ON map.source_code = source.source_code
       JOIN item_prices price
         ON price.tenant_id = @tenant_id
        AND price.item_id = map.item_id
        AND price.scope_key = @price_scope_key
        AND price.recorded_to IS NULL
        AND price.effective_from <= @effective_from
        AND (price.effective_to IS NULL OR price.effective_to >= @effective_from)
        AND price.amount = source.service_price
    ) AS matching_service_prices;

COMMIT;

DROP TEMPORARY TABLE tmp_import_labour_map;
DROP TEMPORARY TABLE tmp_import_combo_map;
DROP TEMPORARY TABLE tmp_import_bundles;
DROP TEMPORARY TABLE tmp_import_labour;
DROP TEMPORARY TABLE tmp_import_combos;
DROP TEMPORARY TABLE tmp_import_guard;
