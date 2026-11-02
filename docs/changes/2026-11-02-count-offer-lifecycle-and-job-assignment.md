# Count offer lifecycle and job assignment

## Changed

- Made the end date optional for count offers and their assignments. An empty end date means the offer does not expire; dated offers still stop on their end date.
- Added version checked deactivate, reactivate, and delete actions. An offer with assignments or card benefit references cannot be deleted; it can be deactivated.
- Showed eligible unassigned count offers after selecting the job customer and vehicle. Authorized service staff can add one from the job, then see its Added state and paid/free progress.
- Allowed users with promotion redeem or manage permission to view available job promotions and assign offers. Offer lifecycle actions remain restricted to promotion manage.
- Kept the card validity period as the limit when an associated count offer has no end date.

## Verification

- PHP syntax checks passed for the changed promotion services and controller.
- TypeScript type check passed before the final UI adjustments; rerun after final changes.
- `git diff --check` passed.

## Deployment

- Run `php artisan migrate` to make the two offer end-date columns nullable.
