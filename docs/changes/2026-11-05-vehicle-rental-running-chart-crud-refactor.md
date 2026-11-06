# Vehicle Rental Running Chart CRUD Refactor & Hardening

## Overview
Comprehensive audit and refactoring of the Running Chart CRUD functionality within the `VehicleRental` module to ensure data integrity, consistent numeric validation, automatic reference generation, bidirectional correction lineage, and enhanced user experience.

## Key Changes

### 1. Automatic Running Chart Sequence Generation
- **`RunningChartNumberService.php`**: Introduced sequence generation service for Running Charts using document type `vehicle_rental_running_chart`, prefix `RC-{PERIOD}-`, yearly period type, and 5-digit padding (`RC-{YEAR}-XXXXX`).
- **`RunningChartService.php`**: Integrated `RunningChartNumberService` into `create()`. When chart reference is omitted, empty, or whitespace, a unique sequence number is automatically generated and verified.
- **`RunningChartEditor.tsx`**: Made chart reference optional during initial creation and correction drafting, accompanied by guided hint text: `"Leave blank to automatically generate a unique chart reference"`. Maintained strict reference requirement when editing existing draft charts.

### 2. Distance and Odometer Numeric Input Normalization
- **`RunningChartValidation.php`**: Added automatic normalization for numeric integer and float values across distance fields (`start_odometer`, `end_odometer`, `garage_km`, `commercial_km`) before regex pattern validation, preventing valid numeric payloads from being rejected with string validation errors.

### 3. Bidirectional Correction Lineage
- **`RunningChart.php`**: Defined `correctedByChart(): HasOne` mapping `corrects_chart_id` to allow forward traversal from a reversed chart to its replacement.
- **`RunningChartResource.php`**: Exposed `corrected_by_chart` payload when relation is loaded.
- **`RunningChartsPanel.tsx`**:
  - Displayed `"Corrected by chart {reference}"` badge on reversed charts.
  - Guarded the `"Create correction"` action to prevent duplicate correction attempts when a chart already has an associated correction record, avoiding database unique constraint conflicts.
- **`runningCharts.ts`**: Updated TypeScript types `RunningChart` and `ChartFacts` with `corrected_by_chart` and optional reference on creation.

### 4. Register Search Parity and Deep Linking
- **`RunningChartRegisterService.php`**: Extended search filter closure to query underlying `Vehicle` model's `registration_number` and `vehicle_number` in addition to snapshot labels. Eager loaded `correctedByChart`.
- **`RunningChartRegisterPage.tsx`**:
  - Added URL parameter initialization for `search`, `status`/`chart_status`, `from`, and `until` for shareable bookmarking and deep linking.
  - Linked customer and owner agreement references to their respective management views via `<Link>` components.
  - Rendered `"Corrected by chart {reference}"` lineage indicator.

### 5. Invariant Enforcement & Automated Tests
- **`RunningChartTest.php`**:
  - Added `test_reference_is_automatically_generated_when_omitted` to test sequential generation (`RC-2026-00001`, `RC-2026-00002`).
  - Added `test_numeric_odometer_and_distance_inputs_are_accepted` validating integer and float distances.
  - Added `test_running_chart_delete_is_strictly_prohibited` verifying hard deletes trigger `LogicException: Preserve Running Chart evidence. Use reversal.`.
  - Added `test_corrected_by_chart_relation_and_resource_lineage` verifying bidirectional API and relation resolution.
- **Vitest Suites**:
  - Updated `RunningChartEditor.test.tsx` to verify optional reference behaviour and edit validation.
  - Updated `RunningChartRegisterPage.test.tsx` to verify navigation links and correction badges wrapped in `MemoryRouter`.
  - Updated `RunningChartsPanel.test.tsx` to verify `corrected_by_chart` badge and button guarding.
