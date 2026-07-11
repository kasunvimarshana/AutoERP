# Vehicle Rental Agreement Clauses Are Optional

## Summary

Vehicle Rental lessee and lessor agreements may now be activated without printable agreement clauses. Clauses remain available as optional draft content and are included in the immutable activation snapshot when present.

## Root cause

`RentalAgreementService::assertReadyForActivation()` required at least one active printable term even though the create and update contracts already accepted an empty terms collection. This made the public draft contract and activation contract inconsistent and blocked vehicle-allocation workflows for agreements that do not use custom clauses.

## Correction

- Removed only the printable-term activation prerequisite from the Vehicle Rental owner service.
- Preserved execution date, legal context, execution-date validity, active-rate-version, optimistic-version and lifecycle checks.
- Preserved optional term persistence and immutable snapshot capture.
- Agreements activated without clauses store an empty `document_snapshot.terms` list instead of generated placeholder content.
- Added a feature regression test covering zero-term activation and the empty immutable snapshot.

## Verification

- `RentalAgreementService.php` passes PHP syntax validation.
- `RentalAgreementOptionalTermsTest.php` passes PHP syntax validation.
- Branch comparison shows the production change is limited to deleting the obsolete eleven-line term requirement.
