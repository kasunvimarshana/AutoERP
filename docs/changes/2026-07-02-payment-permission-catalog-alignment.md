# Payment permission catalog alignment

Date: 2026-07-02

## Problem

Payment authorization had drifted between two backend permission sources. The tenant permission catalog registered old underscore-style Payment permission names, while Payment controllers and the frontend used the current granular names such as `payment-methods.view` and `cheque-templates.view`. As a result, seeded administrator access could pass route-level checks but fail controller authorization for Payment setup and cheque template screens.

The direct tenant permission seeder also attempted tenant-owned permission writes without entering a tenant execution context, which blocked a local catalog repair.

## Correction

Made `Modules\Payment\Constants\PaymentPermission` the Payment module's canonical permission catalog and updated Payment routes/controllers/tests to use those definitions consistently. Payment method and cheque template mutations now use explicit create/update/delete permissions instead of legacy manage permissions, and cheque preview uses `cheques.preview`.

Updated the tenant permission seeder to run each tenant's access provisioning inside that tenant's execution context. The local tenant permission catalog was reseeded after the fix, activating the corrected Payment permissions and deactivating the old underscore-style names.

## Verification

- PHP syntax validation for changed PHP files.
- `vendor\bin\pint.bat` on changed PHP files.
- `php artisan test app\Modules\Payment\Tests --stop-on-failure`
- `php artisan test tests\Feature\Api\CoreModulesApiTest.php --filter=payment_creation_and_invoice_allocation_api --stop-on-failure`
- `php artisan test tests\Feature\Database\TenantAccessProvisionerTest.php --stop-on-failure`
- `npm run typecheck`
- `php artisan route:list --path=api/v1/payments -v`
- `php artisan db:seed --class=Modules\User\Database\Seeders\TenantPermissionSeeder --no-interaction`
- Local database query confirmed `payment-methods.view` and `cheque-templates.view` are active and assigned to the protected Super Admin role, while old underscore-style names are inactive.
- `git diff --check`
