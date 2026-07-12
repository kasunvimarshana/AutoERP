# Fix RentalAgreementOptionalTermsTest Invalid Enum Value

## Summary

The test `RentalAgreementOptionalTermsTest::test_agreement_can_be_activated_without_printable_terms()` failed with `ValueError: "without_driver" is not a valid backing value for enum Modules\VehicleRental\Enums\RentalMode` because it used an invalid rental mode backing value.

## Root cause

The test was passing `'without_driver'` to the agreement creation, but the `RentalMode` enum only supports:
- `'with_driver'`
- `'self_drive'`
- `'vehicle_only'`

## Correction

Changed the test to use `'self_drive'` for the rental mode, which is a valid backing value and appropriate for testing agreement activation without terms.

## Affected files

- `tests/Feature/VehicleRental/RentalAgreementOptionalTermsTest.php` - Updated rental_mode from `'without_driver'` to `'self_drive'`
