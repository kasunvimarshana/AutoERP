# Cross-Module Orchestration Implementation Guide

## Overview

This guide demonstrates **production-ready patterns** for orchestrating interactions across multiple modules in the ModularSaaS vehicle service center application. It covers service-layer orchestration, transactional integrity, event-driven communication, and rollback mechanisms.

## Table of Contents

1. [Architecture Overview](#architecture-overview)
2. [Core Components](#core-components)
3. [Orchestration Patterns](#orchestration-patterns)
4. [Event-Driven Communication](#event-driven-communication)
5. [Transaction Management](#transaction-management)
6. [Exception Handling & Rollback](#exception-handling--rollback)
7. [Real-World Examples](#real-world-examples)
8. [Testing Strategies](#testing-strategies)
9. [Best Practices](#best-practices)

---

## Architecture Overview

### Layer Separation

```
┌──────────────────────────────────────────────────────────┐
│               CONTROLLER LAYER                            │
│  • HTTP Request/Response                                 │
│  • Input Validation (FormRequests)                      │
│  • Output Transformation (API Resources)                │
└───────────────────┬──────────────────────────────────────┘
                    │
                    ▼
┌──────────────────────────────────────────────────────────┐
│            ORCHESTRATION LAYER                            │
│  • Cross-Module Coordination     ◄── YOU ARE HERE       │
│  • Transaction Boundaries                                │
│  • Saga Pattern Implementation                          │
│  • Event Dispatching                                    │
└───────────────────┬──────────────────────────────────────┘
                    │
                    ▼
┌──────────────────────────────────────────────────────────┐
│               SERVICE LAYER                               │
│  • Single-Module Business Logic                         │
│  • Data Validation                                      │
│  • Business Rules Enforcement                           │
└───────────────────┬──────────────────────────────────────┘
                    │
                    ▼
┌──────────────────────────────────────────────────────────┐
│             REPOSITORY LAYER                              │
│  • Database Queries                                      │
│  • Data Persistence                                     │
└──────────────────────────────────────────────────────────┘
```

### Communication Patterns

**Synchronous (Transactional):**
- Used for critical business operations
- All-or-nothing execution
- Immediate consistency
- Examples: Invoice generation, inventory updates

**Asynchronous (Event-Driven):**
- Used for non-critical operations
- Eventually consistent
- Better performance and scalability
- Examples: Notifications, reporting, analytics

---

## Core Components

### 1. BaseDomainEvent

**Purpose:** Abstract base class for all domain events

**Location:** `app/Core/Events/BaseDomainEvent.php`

**Features:**
- Automatic timestamp tracking
- User context capture
- Tenant context (multi-tenancy support)
- Event payload abstraction
- Queue integration

**Usage:**
```php
use App\Core\Events\BaseDomainEvent;

class JobCardCompleted extends BaseDomainEvent
{
    public function __construct(
        public readonly JobCard $jobCard,
        public readonly ?Invoice $invoice = null
    ) {
        parent::__construct();
    }

    public function getEventPayload(): array
    {
        return [
            'job_card_id' => $this->jobCard->id,
            'job_number' => $this->jobCard->job_number,
            'grand_total' => $this->jobCard->grand_total,
            'invoice_id' => $this->invoice?->id,
        ];
    }
}
```

### 2. BaseOrchestrator

**Purpose:** Abstract base class for orchestration services

**Location:** `app/Core/Services/BaseOrchestrator.php`

**Features:**
- Automatic transaction management
- Step tracking for compensation
- Retry logic with exponential backoff
- Prerequisite validation
- Structured logging

**Key Methods:**
```php
// Execute with automatic transaction
protected function executeInTransaction(
    callable $operation, 
    string $operationName
): mixed

// Execute multiple steps sequentially
protected function executeSteps(
    array $steps, 
    string $operationName
): array

// Retry operation on failure
protected function executeWithRetry(
    callable $operation, 
    int $maxAttempts = 3
): mixed

// Validate prerequisites before execution
protected function validatePrerequisites(
    array $validations
): void

// Override for custom compensation logic
protected function compensate(): void
```

---

## Orchestration Patterns

### Pattern 1: Simple Cross-Module Orchestration

**Use Case:** Complete a job card and generate invoice

**Implementation:** `Modules/JobCard/Services/JobCardOrchestrator.php`

```php
class JobCardOrchestrator extends BaseOrchestrator
{
    public function __construct(
        private readonly JobCardService $jobCardService,
        private readonly InvoiceService $invoiceService,
        private readonly InventoryService $inventoryService
    ) {}

    public function completeJobCardWithFullOrchestration(
        int $jobCardId
    ): array {
        return $this->executeInTransaction(function () use ($jobCardId) {
            // Step 1: Validate prerequisites
            $this->validatePrerequisites([
                'job_card_exists' => fn() => 
                    $this->jobCardRepository->exists($jobCardId),
                'job_card_not_already_completed' => function () use ($jobCardId) {
                    $jobCard = $this->jobCardRepository->findOrFail($jobCardId);
                    return $jobCard->status !== 'completed';
                },
            ]);
            $this->recordStep('prerequisites_validated');

            // Step 2: Complete job card
            $jobCard = $this->jobCardService->complete($jobCardId);
            $this->recordStep('job_card_completed');

            // Step 3: Generate invoice
            $invoice = $this->invoiceService->generateFromJobCard($jobCardId);
            $this->recordStep('invoice_generated');

            // Step 4: Update inventory (deduct parts)
            foreach ($jobCard->parts as $part) {
                $this->inventoryService->adjustInventory(
                    itemId: $part->inventory_item_id,
                    quantity: -$part->quantity,
                    transactionType: 'job_card_usage',
                    referenceId: $jobCard->id,
                    reason: "Used in Job Card #{$jobCard->job_number}"
                );
            }
            $this->recordStep('inventory_updated');

            // Step 5: Dispatch events for async operations
            event(new JobCardCompleted($jobCard, $invoice));

            return [
                'jobCard' => $jobCard,
                'invoice' => $invoice,
            ];
        }, 'CompleteJobCardOrchestration');
    }
}
```

**Transaction Flow:**
```
START TRANSACTION
  ├─ Validate Prerequisites
  ├─ Complete Job Card (update status, calculate totals)
  ├─ Generate Invoice (create invoice with line items)
  ├─ Update Inventory (deduct parts from stock)
  └─ COMMIT TRANSACTION
      └─ Dispatch Events (notifications, analytics)
```

**Rollback Scenario:**
If ANY step fails (e.g., insufficient inventory), the ENTIRE transaction rolls back:
- Job card status NOT changed
- Invoice NOT created
- Inventory NOT updated
- Events NOT dispatched

### Pattern 2: Complex Multi-Step Orchestration

**Use Case:** Book appointment with customer/vehicle validation and bay reservation

**Implementation:** `Modules/Appointment/Services/AppointmentOrchestrator.php`

```php
public function bookAppointmentWithFullValidation(array $data): array
{
    return $this->executeSteps([
        // Step 1: Handle customer (find existing or create new)
        'handle_customer' => function () use ($data) {
            // Check if customer exists by email/phone
            // Create if not found
            return ['customer' => $customer, 'isNewCustomer' => $isNew];
        },

        // Step 2: Handle vehicle (find existing or register new)
        'handle_vehicle' => function () use ($data, &$customerResult) {
            $customer = $customerResult['customer'];
            // Find or create vehicle
            return ['vehicle' => $vehicle, 'isNewVehicle' => $isNew];
        },

        // Step 3: Validate availability
        'validate_availability' => function () use ($data) {
            // Check branch is active
            // Check bay availability
            return ['branch' => $branch];
        },

        // Step 4: Create appointment
        'create_appointment' => function () use (...) {
            $appointment = $this->appointmentService->create([
                'customer_id' => $customer->id,
                'vehicle_id' => $vehicle->id,
                // ... other fields
            ]);
            return ['appointment' => $appointment];
        },

        // Step 5: Reserve bay slot
        'reserve_bay' => function () use (...) {
            if (isset($data['bay_id'])) {
                $this->bayService->reserveSlot(...);
            }
            return ['bay_reserved' => true];
        },
    ], 'BookAppointmentOrchestration');

    // Dispatch event
    event(new AppointmentBooked($appointment, $isNewCustomer, $isNewVehicle));

    return compact('appointment', 'customer', 'vehicle', ...);
}
```

**Benefits:**
- Each step is isolated and testable
- Automatic step tracking for debugging
- Transaction encompasses all steps
- Clear compensation path if any step fails

---

## Event-Driven Communication

### Event Classes

#### JobCardCompleted Event

**Location:** `Modules/JobCard/app/Events/JobCardCompleted.php`

**Triggered When:** Job card is marked as completed

**Listeners:**
1. **SendInvoiceToCustomer** - Email invoice PDF
2. **NotifyCustomerOfJobCompletion** - SMS/Email notification
3. **UpdateInventoryFromJobCard** - Deduct parts from inventory
4. **UpdateServiceHistory** - Add to vehicle service records
5. **UpdateAnalytics** - Add to reporting/KPI dashboards

#### InvoiceGenerated Event

**Location:** `Modules/Invoice/app/Events/InvoiceGenerated.php`

**Triggered When:** Invoice is created

**Listeners:**
1. **SendInvoiceEmail** - Email invoice to customer
2. **GenerateInvoicePDF** - Create PDF attachment
3. **UpdateAccountingSystem** - Sync with external accounting
4. **UpdateCRM** - Sync customer billing info

#### InventoryAdjusted Event

**Location:** `Modules/Inventory/app/Events/InventoryAdjusted.php`

**Triggered When:** Inventory level changes

**Listeners:**
1. **CheckStockLevels** - Alert if below reorder point
2. **NotifyProcurement** - Send reorder alerts
3. **UpdateAnalytics** - Track inventory movements

### Event Listeners

#### Example: Queued Listener with Retry Logic

**Location:** `Modules/Invoice/app/Listeners/SendInvoiceToCustomer.php`

```php
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendInvoiceToCustomer implements ShouldQueue
{
    use InteractsWithQueue;

    public int $tries = 3;        // Retry up to 3 times
    public int $backoff = 60;     // Wait 60 seconds between retries

    public function handle(JobCardCompleted $event): void
    {
        if (! $event->invoice) {
            Log::warning('No invoice to send');
            return;
        }

        try {
            $invoice = $event->invoice->load('customer');
            
            // Send email
            Mail::to($invoice->customer->email)
                ->send(new InvoiceMail($invoice));

            Log::info('Invoice email sent', [
                'invoice_id' => $invoice->id,
                'customer_email' => $invoice->customer->email,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send invoice email', [
                'error' => $e->getMessage(),
            ]);
            throw $e; // Rethrow to trigger retry
        }
    }

    public function failed(JobCardCompleted $event, \Throwable $exception): void
    {
        // Called after all retries exhausted
        Log::error('Failed to send invoice after all retries', [
            'invoice_id' => $event->invoice?->id,
            'error' => $exception->getMessage(),
        ]);

        // Notify admin, create manual task, etc.
    }
}
```

**Benefits:**
- Automatic retry on failure
- Backoff to avoid overwhelming services
- Failed job handling for manual intervention
- Runs asynchronously in queue workers

---

## Transaction Management

### ACID Guarantees

All orchestrated operations use database transactions to ensure:

- **Atomicity:** All steps succeed or all fail
- **Consistency:** Database always in valid state
- **Isolation:** Concurrent operations don't interfere
- **Durability:** Committed changes are permanent

### Transaction Boundaries

```php
// WRONG: Each service manages its own transaction
public function completeJobCard(int $id): void
{
    $this->jobCardService->complete($id);     // Transaction 1
    $this->invoiceService->generate($id);     // Transaction 2
    $this->inventoryService->adjust($id);     // Transaction 3
}
// Problem: If inventory adjustment fails, job card is still completed!

// CORRECT: Single transaction wraps all operations
public function completeJobCard(int $id): void
{
    DB::beginTransaction();
    try {
        $this->jobCardService->complete($id);
        $this->invoiceService->generate($id);
        $this->inventoryService->adjust($id);
        DB::commit();
    } catch (\Exception $e) {
        DB::rollback();
        throw $e;
    }
}
// Now: If ANY step fails, ALL steps are rolled back
```

### BaseOrchestrator Handles This Automatically

```php
// Using BaseOrchestrator
public function completeJobCard(int $id): void
{
    $this->executeInTransaction(function () use ($id) {
        $this->jobCardService->complete($id);
        $this->invoiceService->generate($id);
        $this->inventoryService->adjust($id);
    }, 'CompleteJobCard');
}
// Transaction management is automatic!
```

---

## Exception Handling & Rollback

### Exception Hierarchy

```
Exception
  └── ServiceException (App\Core\Exceptions\ServiceException)
        ├── RepositoryException
        ├── ValidationException
        └── TenantException
```

### Propagation Pattern

```php
// Repository Layer: Throw specific exceptions
public function create(array $data): Model
{
    try {
        return $this->model->create($data);
    } catch (\Exception $e) {
        throw new RepositoryException("Failed to create record: {$e->getMessage()}");
    }
}

// Service Layer: Add business context
public function createUser(array $data): User
{
    try {
        return $this->repository->create($data);
    } catch (RepositoryException $e) {
        Log::error('User creation failed', ['error' => $e->getMessage()]);
        throw new ServiceException("Could not create user: {$e->getMessage()}", previous: $e);
    }
}

// Orchestrator Layer: Handle rollback and compensation
public function registerUser(array $data): array
{
    return $this->executeInTransaction(function () use ($data) {
        $user = $this->userService->createUser($data);
        $profile = $this->profileService->createProfile($user->id);
        $subscription = $this->subscriptionService->createTrial($user->id);
        
        return compact('user', 'profile', 'subscription');
    }, 'RegisterUser');
}
// If ANY service throws an exception:
// 1. Transaction is rolled back automatically
// 2. Exception is logged with full context
// 3. compensate() method is called for cleanup
// 4. Exception is re-thrown to controller
```

### Compensation (Saga Pattern)

```php
class PaymentOrchestrator extends BaseOrchestrator
{
    private ?string $paymentGatewayTransactionId = null;

    public function processPayment(array $data): array
    {
        return $this->executeInTransaction(function () use ($data) {
            // Step 1: Charge payment gateway
            $gatewayResponse = $this->paymentGateway->charge($data);
            $this->paymentGatewayTransactionId = $gatewayResponse->transaction_id;
            $this->recordStep('payment_charged');

            // Step 2: Create invoice payment record
            $payment = $this->paymentService->create([
                'invoice_id' => $data['invoice_id'],
                'amount' => $data['amount'],
                'gateway_transaction_id' => $gatewayResponse->transaction_id,
            ]);
            $this->recordStep('payment_recorded');

            // Step 3: Update invoice status
            $this->invoiceService->recordPayment($payment->invoice_id, $payment->amount);
            $this->recordStep('invoice_updated');

            return compact('payment');
        }, 'ProcessPayment');
    }

    protected function compensate(): void
    {
        // If transaction failed but payment was charged,
        // we need to refund it
        if ($this->paymentGatewayTransactionId) {
            try {
                $this->paymentGateway->refund($this->paymentGatewayTransactionId);
                Log::info('Payment refunded during compensation', [
                    'transaction_id' => $this->paymentGatewayTransactionId,
                ]);
            } catch (\Exception $e) {
                // Refund failed - critical alert
                Log::critical('Failed to refund payment during compensation', [
                    'transaction_id' => $this->paymentGatewayTransactionId,
                    'error' => $e->getMessage(),
                ]);
                // Send urgent alert to finance team
            }
        }
    }
}
```

---

## Real-World Examples

### Example 1: Job Card Completion Workflow

**Business Requirement:**
When a job card is completed, the system must:
1. Update job card status to "completed"
2. Calculate final totals
3. Generate invoice automatically
4. Deduct used parts from inventory
5. Create service history record for the vehicle
6. Notify customer via email/SMS
7. Update reporting/analytics

**Implementation:**

```php
// Controller
class JobCardController extends Controller
{
    public function complete(
        int $id,
        JobCardOrchestrator $orchestrator
    ): JsonResponse {
        try {
            $result = $orchestrator->completeJobCardWithFullOrchestration($id);
            
            return $this->successResponse([
                'job_card' => new JobCardResource($result['jobCard']),
                'invoice' => new InvoiceResource($result['invoice']),
                'message' => 'Job card completed successfully',
            ]);
        } catch (ServiceException $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }
}

// Routes
Route::post('job-cards/{id}/complete', [JobCardController::class, 'complete'])
    ->middleware(['auth:sanctum', 'permission:job_card.complete']);
```

**What Happens:**

1. **Synchronous (in transaction):**
   - Update job card status ✓
   - Calculate totals ✓
   - Generate invoice ✓
   - Deduct inventory ✓
   - Create service record ✓
   - COMMIT TRANSACTION ✓

2. **Asynchronous (via events):**
   - Send customer notification (queued)
   - Send invoice email (queued)
   - Update analytics (queued)
   - Update CRM (queued)

**Failure Scenarios:**

| Failure Point | Result | Recovery |
|--------------|---------|----------|
| Insufficient inventory | Entire transaction rolled back | Return error to user |
| Invoice generation fails | Entire transaction rolled back | Return error to user |
| Email sending fails | Transaction still committed | Retry up to 3 times via queue |
| SMS notification fails | Transaction still committed | Retry up to 3 times via queue |

### Example 2: Appointment Booking with New Customer

**Business Requirement:**
Book an appointment for a walk-in customer who may not exist in the system.

**Workflow:**
```php
$result = $orchestrator->bookAppointmentWithFullValidation([
    'customer_email' => 'john@example.com',
    'customer_name' => 'John Doe',
    'customer_phone' => '+1234567890',
    'vehicle_license_plate' => 'ABC-123',
    'vehicle_make' => 'Toyota',
    'vehicle_model' => 'Camry',
    'branch_id' => 1,
    'scheduled_date' => '2024-02-15',
    'scheduled_time' => '09:00',
    'service_type' => 'oil_change',
]);

// Result contains:
// - appointment: Created appointment
// - customer: Customer record (existing or new)
// - vehicle: Vehicle record (existing or new)
// - isNewCustomer: true/false
// - isNewVehicle: true/false
```

**Smart Logic:**
- If email exists → Use existing customer
- If email doesn't exist but phone exists → Use that customer
- If neither exists → Create new customer
- Same logic for vehicle (by license plate)

**All happens in ONE transaction:**
- Customer creation (if needed)
- Vehicle registration (if needed)
- Bay availability validation
- Appointment creation
- Bay slot reservation

If ANY step fails → Everything rolls back, no orphaned records.

---

## Testing Strategies

### Unit Tests for Orchestrators

```php
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class JobCardOrchestratorTest extends TestCase
{
    use RefreshDatabase;

    public function test_completes_job_card_with_invoice_generation(): void
    {
        // Arrange
        $jobCard = JobCard::factory()->create([
            'status' => 'in_progress',
        ]);

        // Act
        $result = $this->orchestrator->completeJobCardWithFullOrchestration(
            $jobCard->id
        );

        // Assert
        $this->assertEquals('completed', $result['jobCard']->status);
        $this->assertNotNull($result['invoice']);
        $this->assertDatabaseHas('invoices', [
            'job_card_id' => $jobCard->id,
        ]);
    }

    public function test_rolls_back_on_insufficient_inventory(): void
    {
        // Arrange
        $jobCard = JobCard::factory()->create([
            'status' => 'in_progress',
        ]);
        JobPart::factory()->create([
            'job_card_id' => $jobCard->id,
            'inventory_item_id' => 1,
            'quantity' => 100, // More than available
        ]);

        // Act & Assert
        $this->expectException(ServiceException::class);
        $this->orchestrator->completeJobCardWithFullOrchestration($jobCard->id);

        // Verify rollback
        $this->assertDatabaseHas('job_cards', [
            'id' => $jobCard->id,
            'status' => 'in_progress', // Not changed
        ]);
        $this->assertDatabaseMissing('invoices', [
            'job_card_id' => $jobCard->id, // Not created
        ]);
    }
}
```

### Integration Tests

```php
public function test_complete_job_card_sends_customer_notification(): void
{
    Queue::fake();
    Event::fake();

    $jobCard = JobCard::factory()->create();

    $this->orchestrator->completeJobCardWithFullOrchestration($jobCard->id);

    // Verify event was dispatched
    Event::assertDispatched(JobCardCompleted::class, function ($event) use ($jobCard) {
        return $event->jobCard->id === $jobCard->id;
    });

    // Verify listener was queued
    Queue::assertPushed(NotifyCustomerOfJobCompletion::class);
}
```

---

## Best Practices

### DO ✅

1. **Use orchestrators for cross-module operations**
   ```php
   // GOOD
   $orchestrator->completeJobCardWithInvoice($id);
   ```

2. **Wrap all steps in a single transaction**
   ```php
   $this->executeInTransaction(fn() => /* steps */, 'OperationName');
   ```

3. **Use events for async/non-critical operations**
   ```php
   event(new JobCardCompleted($jobCard));
   ```

4. **Log all important steps**
   ```php
   $this->recordStep('step_name', ['context' => $data]);
   ```

5. **Validate prerequisites early**
   ```php
   $this->validatePrerequisites([
       'check_1' => fn() => $condition1,
       'check_2' => fn() => $condition2,
   ]);
   ```

6. **Handle compensation for external services**
   ```php
   protected function compensate(): void {
       // Refund payment, release reservations, etc.
   }
   ```

### DON'T ❌

1. **Don't create separate transactions per module**
   ```php
   // BAD
   $this->moduleA->doSomething(); // Transaction 1
   $this->moduleB->doSomething(); // Transaction 2
   ```

2. **Don't use events for critical operations**
   ```php
   // BAD - inventory update should be synchronous
   event(new UpdateInventory($jobCard));
   ```

3. **Don't swallow exceptions**
   ```php
   // BAD
   try {
       $this->service->doSomething();
   } catch (\Exception $e) {
       Log::error($e->getMessage());
       // No re-throw - operation appears successful!
   }
   ```

4. **Don't perform I/O in transactions**
   ```php
   // BAD
   DB::transaction(function () {
       $record = Model::create($data);
       Mail::send(new SomeEmail($record)); // Delays commit!
   });
   ```

5. **Don't skip error handling**
   ```php
   // BAD
   public function doSomething(): void {
       $this->service->operation(); // What if it throws?
   }
   ```

---

## Conclusion

This implementation provides a **production-ready, enterprise-grade** foundation for:

✅ **Orchestrating complex workflows** across multiple modules
✅ **Ensuring transactional integrity** with automatic rollback
✅ **Event-driven async processing** for scalability
✅ **Proper exception handling** with context preservation
✅ **Saga pattern compensation** for distributed operations
✅ **Full observability** with structured logging

The patterns demonstrated here can be extended to:
- Payment processing workflows
- Order fulfillment pipelines
- Multi-step approval processes
- Data synchronization across systems
- Any complex business operation requiring coordination

---

## Additional Resources

- [Laravel Database Transactions](https://laravel.com/docs/database#database-transactions)
- [Laravel Events & Listeners](https://laravel.com/docs/events)
- [Laravel Queues](https://laravel.com/docs/queues)
- [Saga Pattern](https://microservices.io/patterns/data/saga.html)
- [CQRS Pattern](https://martinfowler.com/bliki/CQRS.html)
