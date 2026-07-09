# Vehicle Service engine test migration map

## Why this exists

`VehicleServiceEngineTest` is a large legacy-style integration test file that still references the removed single job lifecycle concept. It must be migrated without adding production compatibility shims such as a `status` accessor or a `VehicleServiceStatusService::change()` wrapper.

## Remaining source-truth updates

### Imports

Replace the old mixed lifecycle enum:

```php
use Modules\VehicleService\Enums\VehicleServiceJobStatus;
```

with explicit lifecycle enums as needed:

```php
use Modules\VehicleService\Enums\VehicleServiceOperationalStatus;
use Modules\VehicleService\Enums\VehicleServiceBillingStatus;
use Modules\VehicleService\Enums\VehicleServicePaymentStatus;
```

### Operational transitions

Replace test helper calls that currently express workshop flow through the old mixed enum:

```php
$this->changeStatus($job, VehicleServiceJobStatus::InProgress);
$this->changeStatus($job, VehicleServiceJobStatus::Completed);
$this->changeStatus($job, VehicleServiceJobStatus::Cancelled);
```

with operational lifecycle calls:

```php
$this->changeOperational($job, VehicleServiceOperationalStatus::InProgress);
$this->changeOperational($job, VehicleServiceOperationalStatus::Completed);
$this->changeOperational($job, VehicleServiceOperationalStatus::Cancelled);
```

The helper must call:

```php
VehicleServiceStatusService::changeOperational(...)
```

and must pass the current locked row version.

### Billing assertions

Replace invoice-result expectations such as:

```php
$this->assertSame(VehicleServiceJobStatus::Invoiced, $job->status);
```

with:

```php
$this->assertSame(VehicleServiceBillingStatus::Billed, $job->billing_status);
$this->assertSame(VehicleServicePaymentStatus::Unpaid, $job->payment_status);
```

For partial billing scenarios, assert:

```php
VehicleServiceBillingStatus::PartiallyBilled
```

### Payment assertions

Replace payment-result expectations such as:

```php
$this->assertSame(VehicleServiceJobStatus::PartiallyPaid, $job->status);
$this->assertSame(VehicleServiceJobStatus::Paid, $job->status);
```

with:

```php
$this->assertSame(VehicleServicePaymentStatus::PartiallyPaid, $job->payment_status);
$this->assertSame(VehicleServicePaymentStatus::Paid, $job->payment_status);
```

Operational and billing state must be asserted unchanged unless the scenario intentionally changes them.

### Resource assertions

Replace JSON assertions for:

```php
status
status_label
```

with:

```php
operational_status
operational_status_label
billing_status
billing_status_label
payment_status
payment_status_label
```

### Fixture setup

Do not force-fill a removed `status` column. If a fixture bypasses service-layer invoice/payment sync, seed explicit lifecycle fields:

```php
$job->forceFill([
    'operational_status' => VehicleServiceOperationalStatus::Completed,
    'billing_status' => VehicleServiceBillingStatus::Billed,
    'payment_status' => VehicleServicePaymentStatus::Paid,
])->save();
```

## Do not implement

Do not add any of the following as compatibility workarounds:

- `VehicleServiceJob::getStatusAttribute()`
- `VehicleServiceJob::setStatusAttribute()`
- `VehicleServiceStatusService::change()` wrapper
- Mapping `invoiced`, `partially_paid`, or `paid` back into operational status
- A shared status column retained only for tests

## Replacement coverage already added

`VehicleServiceLifecycleBoundaryTest` now covers:

- Initial operational/billing/payment states
- Lifecycle history dimensions
- Operational completion not implying billing/payment completion
- Partial and full billing transitions
- Payment sync to partially paid and paid
- Invalid operational backward transition rejection

## Safe migration strategy

Migrate `VehicleServiceEngineTest` in small commits by scenario group:

1. Import and helper replacement only.
2. Invoice scenario assertions.
3. Payment scenario assertions.
4. Resource JSON assertions.
5. Fixture force-fill cleanup.
6. Runtime test pass.

Each step should be run locally before the next step because the file is large and covers many unrelated engine behaviors.
