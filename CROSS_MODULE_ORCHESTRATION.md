# Cross-Module Orchestration & Event-Driven Architecture

**Enterprise-Grade Guide for Modular SaaS Systems**

---

## Table of Contents

1. [Overview](#overview)
2. [Service Layer Orchestration](#service-layer-orchestration)
3. [Transaction Management](#transaction-management)
4. [Exception Handling & Propagation](#exception-handling--propagation)
5. [Event-Driven Communication](#event-driven-communication)
6. [Real-World Examples](#real-world-examples)
7. [Best Practices](#best-practices)
8. [Testing Strategies](#testing-strategies)

---

## Overview

### Architectural Principles

This system follows a **strict separation of concerns** with clearly defined boundaries:

```
┌─────────────────────────────────────────────────────────────┐
│                    PRESENTATION LAYER                        │
│                      (Controllers)                           │
│  • HTTP Request/Response                                     │
│  • Input Validation (FormRequests)                          │
│  • Output Transformation (API Resources)                    │
└──────────────────┬──────────────────────────────────────────┘
                   │
                   ▼
┌─────────────────────────────────────────────────────────────┐
│                  BUSINESS LOGIC LAYER                        │
│                      (Services)                              │
│  • Business Rules                                            │
│  • Cross-Module Orchestration ◄─── YOU ARE HERE            │
│  • Transaction Coordination                                  │
│  • Event Dispatching                                        │
└──────────────────┬──────────────────────────────────────────┘
                   │
                   ▼
┌─────────────────────────────────────────────────────────────┐
│                   DATA ACCESS LAYER                          │
│                    (Repositories)                            │
│  • Database Queries                                          │
│  • Data Persistence                                         │
│  • No Business Logic                                        │
└─────────────────────────────────────────────────────────────┘
```

### Key Concepts

1. **Service Orchestration**: Coordinating multiple services to complete a business transaction
2. **Transactional Integrity**: Ensuring all-or-nothing execution of related operations
3. **Exception Propagation**: Properly handling and communicating failures
4. **Event-Driven Communication**: Asynchronous, decoupled module interaction
5. **Saga Pattern**: Managing long-running distributed transactions

---

## Service Layer Orchestration

### Pattern: Orchestrator Service

When a business operation spans multiple modules, create an **Orchestrator Service** in the initiating module.

#### Example: Complete Vehicle Service (Job Card → Invoice → Payment)

```php
<?php

declare(strict_types=1);

namespace Modules\JobCard\Services;

use App\Core\Exceptions\ServiceException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Invoice\Services\InvoiceService;
use Modules\JobCard\Repositories\JobCardRepository;
use Modules\JobCard\Events\JobCardCompleted;

/**
 * Job Card Service with Cross-Module Orchestration
 */
class JobCardService extends BaseService
{
    public function __construct(
        JobCardRepository $repository,
        private readonly InvoiceService $invoiceService
    ) {
        parent::__construct($repository);
    }

    /**
     * Complete a job card and automatically generate invoice
     * 
     * This orchestrates multiple operations across modules:
     * 1. Complete the job card (this module)
     * 2. Generate invoice (Invoice module)
     * 3. Update inventory (Inventory module - via events)
     * 4. Notify customer (Notification module - via events)
     *
     * @param int $id Job card ID
     * @return array{jobCard: mixed, invoice: mixed}
     * @throws ServiceException
     */
    public function completeJobCardWithInvoice(int $id): array
    {
        DB::beginTransaction();
        
        try {
            // Step 1: Complete the job card
            $jobCard = $this->repository->findOrFail($id);
            
            if ($jobCard->status === 'completed') {
                throw new ServiceException('Job card is already completed');
            }

            $jobCard->status = 'completed';
            $jobCard->completed_at = now();
            $jobCard->save();

            Log::info('Job card completed', [
                'job_card_id' => $id,
                'total' => $jobCard->grand_total
            ]);

            // Step 2: Generate invoice (cross-module call)
            $invoice = $this->invoiceService->generateFromJobCard($id);

            Log::info('Invoice generated from job card', [
                'job_card_id' => $id,
                'invoice_id' => $invoice->id
            ]);

            // Commit transaction - all database changes are atomic
            DB::commit();

            // Step 3: Dispatch events for async operations
            // These run AFTER the transaction commits to ensure data consistency
            event(new JobCardCompleted($jobCard, $invoice));

            return [
                'jobCard' => $jobCard,
                'invoice' => $invoice
            ];

        } catch (\Exception $e) {
            // Rollback all database changes on any failure
            DB::rollBack();
            
            Log::error('Failed to complete job card with invoice', [
                'job_card_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Re-throw as ServiceException for consistent error handling
            throw new ServiceException(
                "Failed to complete job card: {$e->getMessage()}",
                previous: $e
            );
        }
    }
}
```

### Pattern: Service Injection

Services can inject other services to orchestrate operations:

```php
<?php

namespace Modules\Appointment\Services;

use Modules\Customer\Services\CustomerService;
use Modules\Customer\Services\VehicleService;
use Modules\Organization\Services\BayService;
use Modules\Notification\Services\NotificationService;

class AppointmentService extends BaseService
{
    public function __construct(
        AppointmentRepository $repository,
        private readonly CustomerService $customerService,
        private readonly VehicleService $vehicleService,
        private readonly BayService $bayService,
        private readonly NotificationService $notificationService
    ) {
        parent::__construct($repository);
    }

    /**
     * Create appointment with full orchestration
     */
    public function createWithNotifications(array $data): mixed
    {
        DB::beginTransaction();
        
        try {
            // Validate customer exists
            $customer = $this->customerService->findOrFail($data['customer_id']);
            
            // Validate vehicle belongs to customer
            $vehicle = $this->vehicleService->findOrFail($data['vehicle_id']);
            if ($vehicle->customer_id !== $customer->id) {
                throw new ServiceException('Vehicle does not belong to this customer');
            }

            // Check bay availability
            $availableBay = $this->bayService->findAvailableBay(
                $data['branch_id'],
                $data['scheduled_date_time'],
                $data['duration']
            );

            // Create appointment
            $appointment = $this->create($data);

            // Assign bay if available
            if ($availableBay) {
                $this->bayService->assignToBay($appointment->id, $availableBay->id);
            }

            DB::commit();

            // Send notifications asynchronously
            $this->notificationService->sendAppointmentConfirmation($appointment);

            return $appointment;

        } catch (\Exception $e) {
            DB::rollBack();
            throw new ServiceException("Failed to create appointment: {$e->getMessage()}", previous: $e);
        }
    }
}
```

---

## Transaction Management

### Database Transactions

**Rule of Thumb**: Use transactions when operations span multiple tables or modules.

#### Basic Transaction Pattern

```php
public function complexOperation(array $data): mixed
{
    DB::beginTransaction();
    
    try {
        // Perform multiple operations
        $result1 = $this->operation1($data);
        $result2 = $this->operation2($result1);
        $result3 = $this->operation3($result2);
        
        DB::commit();
        
        // Dispatch events AFTER commit
        event(new OperationCompleted($result3));
        
        return $result3;
        
    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Complex operation failed', [
            'error' => $e->getMessage(),
            'data' => $data
        ]);
        throw new ServiceException($e->getMessage(), previous: $e);
    }
}
```

#### Nested Transactions (Savepoints)

Laravel automatically manages savepoints when nesting transactions:

```php
public function outerOperation(): void
{
    DB::beginTransaction(); // Creates transaction
    
    try {
        $this->innerOperation(); // Creates savepoint
        DB::commit();
    } catch (\Exception $e) {
        DB::rollBack(); // Rolls back entire transaction
        throw $e;
    }
}

public function innerOperation(): void
{
    DB::beginTransaction(); // Creates savepoint
    
    try {
        // Do work
        DB::commit(); // Releases savepoint
    } catch (\Exception $e) {
        DB::rollBack(); // Rolls back to savepoint
        throw $e;
    }
}
```

### Transaction Best Practices

1. **Keep transactions short**: Long-running transactions lock database resources
2. **Avoid external calls inside transactions**: HTTP requests, file I/O, etc.
3. **Dispatch events after commit**: Ensures events only fire on successful transactions
4. **Always rollback on exception**: Maintain data consistency
5. **Log transaction boundaries**: Aid in debugging

#### Example: Payment Processing

```php
<?php

namespace Modules\Invoice\Services;

class PaymentService extends BaseService
{
    /**
     * Process payment with full transaction safety
     */
    public function recordPayment(int $invoiceId, array $paymentData): mixed
    {
        DB::beginTransaction();
        
        try {
            // 1. Validate invoice
            $invoice = $this->invoiceService->findOrFail($invoiceId);
            
            if ($invoice->status === 'paid') {
                throw new ServiceException('Invoice is already paid');
            }

            $paymentAmount = $paymentData['amount'];
            $balance = $invoice->grand_total - $invoice->amount_paid;

            // 2. Validate payment amount
            if ($paymentAmount > $balance) {
                throw new ServiceException('Payment amount exceeds invoice balance');
            }

            // 3. Create payment record
            $payment = $this->repository->create([
                'invoice_id' => $invoiceId,
                'amount' => $paymentAmount,
                'payment_method' => $paymentData['payment_method'],
                'payment_date' => now(),
                'status' => 'completed',
                'transaction_reference' => $paymentData['reference'] ?? null,
            ]);

            // 4. Update invoice
            $newAmountPaid = $invoice->amount_paid + $paymentAmount;
            $newBalance = $invoice->grand_total - $newAmountPaid;

            $invoice->amount_paid = $newAmountPaid;
            $invoice->balance = $newBalance;
            $invoice->status = ($newBalance <= 0) ? 'paid' : 'partial';
            $invoice->save();

            // 5. If driver commission exists, update it
            if ($invoice->job_card && $invoice->job_card->technician_id) {
                $this->commissionService->calculateAndRecord(
                    $invoice->id,
                    $invoice->job_card->technician_id
                );
            }

            DB::commit();

            // 6. Dispatch events for notifications and analytics
            event(new PaymentRecorded($payment, $invoice));

            if ($invoice->status === 'paid') {
                event(new InvoiceFullyPaid($invoice));
            }

            Log::info('Payment recorded successfully', [
                'payment_id' => $payment->id,
                'invoice_id' => $invoiceId,
                'amount' => $paymentAmount
            ]);

            return $payment;

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Payment recording failed', [
                'invoice_id' => $invoiceId,
                'error' => $e->getMessage()
            ]);

            throw new ServiceException(
                "Failed to record payment: {$e->getMessage()}",
                previous: $e
            );
        }
    }
}
```

---

## Exception Handling & Propagation

### Exception Hierarchy

```
Exception
└── App\Core\Exceptions\BaseException
    ├── ServiceException (Business logic errors)
    ├── RepositoryException (Data access errors)
    ├── TenantException (Multi-tenancy errors)
    └── ValidationException (Input validation errors)
```

### Exception Handling Strategy

```php
<?php

namespace Modules\Inventory\Services;

class StockTransferService extends BaseService
{
    /**
     * Transfer stock between branches
     * 
     * Demonstrates proper exception handling and propagation
     */
    public function transferStock(
        int $itemId,
        int $fromBranchId,
        int $toBranchId,
        int $quantity
    ): array {
        DB::beginTransaction();
        
        try {
            // Validate item exists
            $item = $this->itemRepository->findOrFail($itemId);

            // Check source branch stock
            $sourceStock = $this->stockRepository->getStockLevel($itemId, $fromBranchId);
            
            if ($sourceStock < $quantity) {
                throw new ServiceException(
                    "Insufficient stock at source branch. Available: {$sourceStock}, Required: {$quantity}"
                );
            }

            // Deduct from source
            $movementOut = $this->movementRepository->create([
                'item_id' => $itemId,
                'branch_id' => $fromBranchId,
                'movement_type' => 'out',
                'quantity' => $quantity,
                'reference_type' => 'transfer',
                'to_branch_id' => $toBranchId,
            ]);

            $this->stockRepository->adjustStock($itemId, $fromBranchId, -$quantity);

            // Add to destination
            $movementIn = $this->movementRepository->create([
                'item_id' => $itemId,
                'branch_id' => $toBranchId,
                'movement_type' => 'in',
                'quantity' => $quantity,
                'reference_type' => 'transfer',
                'from_branch_id' => $fromBranchId,
            ]);

            $this->stockRepository->adjustStock($itemId, $toBranchId, $quantity);

            DB::commit();

            Log::info('Stock transferred successfully', [
                'item_id' => $itemId,
                'from_branch' => $fromBranchId,
                'to_branch' => $toBranchId,
                'quantity' => $quantity
            ]);

            return [
                'movement_out' => $movementOut,
                'movement_in' => $movementIn
            ];

        } catch (ServiceException $e) {
            // Business logic exception - rollback and re-throw
            DB::rollBack();
            throw $e;
            
        } catch (RepositoryException $e) {
            // Data access exception - rollback and wrap
            DB::rollBack();
            throw new ServiceException("Database error during transfer: {$e->getMessage()}", previous: $e);
            
        } catch (\Exception $e) {
            // Unexpected exception - rollback, log, and wrap
            DB::rollBack();
            
            Log::error('Unexpected error during stock transfer', [
                'item_id' => $itemId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw new ServiceException("Stock transfer failed: {$e->getMessage()}", previous: $e);
        }
    }
}
```

### Global Exception Handler

```php
<?php

namespace App\Exceptions;

use App\Core\Exceptions\ServiceException;
use App\Core\Exceptions\RepositoryException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * Render an exception into an HTTP response
     */
    public function render($request, Throwable $e)
    {
        // API requests get JSON responses
        if ($request->expectsJson()) {
            return $this->handleApiException($request, $e);
        }

        return parent::render($request, $e);
    }

    /**
     * Handle API exceptions consistently
     */
    protected function handleApiException($request, Throwable $e)
    {
        $statusCode = 500;
        $message = 'Internal server error';

        if ($e instanceof ServiceException) {
            $statusCode = 400;
            $message = $e->getMessage();
        } elseif ($e instanceof RepositoryException) {
            $statusCode = 500;
            $message = 'Data access error';
        } elseif ($e instanceof ValidationException) {
            $statusCode = 422;
            $message = $e->getMessage();
        }

        Log::error('API Exception', [
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'exception' => get_class($e),
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);

        return response()->json([
            'success' => false,
            'message' => $message,
            'error' => config('app.debug') ? $e->getMessage() : null
        ], $statusCode);
    }
}
```

---

## Event-Driven Communication

### When to Use Events

Use events for:
- ✅ Asynchronous operations (notifications, emails, analytics)
- ✅ Cross-cutting concerns (audit logs, reporting)
- ✅ Decoupling modules
- ✅ Operations that can tolerate eventual consistency

**DO NOT** use events for:
- ❌ Critical business transactions requiring immediate consistency
- ❌ Operations that need return values
- ❌ Error handling in the main flow

### Event Example: Job Card Completion

#### 1. Define the Event

```php
<?php

declare(strict_types=1);

namespace Modules\JobCard\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\JobCard\Models\JobCard;
use Modules\Invoice\Models\Invoice;

/**
 * Event fired when a job card is completed
 */
class JobCardCompleted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance
     */
    public function __construct(
        public readonly JobCard $jobCard,
        public readonly Invoice $invoice
    ) {}
}
```

#### 2. Create Listeners

```php
<?php

namespace Modules\Notification\Listeners;

use Modules\JobCard\Events\JobCardCompleted;
use Modules\Notification\Services\NotificationService;

class SendJobCardCompletionNotification
{
    public function __construct(
        private readonly NotificationService $notificationService
    ) {}

    /**
     * Handle the event
     */
    public function handle(JobCardCompleted $event): void
    {
        // Send SMS to customer
        $this->notificationService->sendSMS(
            $event->jobCard->customer->mobile,
            "Your vehicle service is complete. Invoice #" . $event->invoice->invoice_number
        );

        // Send email with invoice
        $this->notificationService->sendEmail(
            $event->jobCard->customer->email,
            'Service Complete - Invoice Attached',
            'emails.job-completed',
            ['jobCard' => $event->jobCard, 'invoice' => $event->invoice]
        );
    }
}
```

```php
<?php

namespace Modules\Inventory\Listeners;

use Modules\JobCard\Events\JobCardCompleted;
use Modules\Inventory\Services\StockMovementService;

class UpdateInventoryFromJobCard
{
    public function __construct(
        private readonly StockMovementService $movementService
    ) {}

    /**
     * Deduct parts used from inventory
     */
    public function handle(JobCardCompleted $event): void
    {
        foreach ($event->jobCard->parts as $part) {
            $this->movementService->recordUsage(
                $part->inventory_item_id,
                $event->jobCard->branch_id,
                $part->quantity,
                'job_card',
                $event->jobCard->id
            );
        }
    }
}
```

#### 3. Register Listeners

```php
<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Modules\JobCard\Events\JobCardCompleted;
use Modules\Notification\Listeners\SendJobCardCompletionNotification;
use Modules\Inventory\Listeners\UpdateInventoryFromJobCard;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings
     */
    protected $listen = [
        JobCardCompleted::class => [
            SendJobCardCompletionNotification::class,
            UpdateInventoryFromJobCard::class,
        ],
    ];
}
```

### Queued Listeners

For expensive operations, use queued listeners:

```php
<?php

namespace Modules\Analytics\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\JobCard\Events\JobCardCompleted;

class UpdateJobCardAnalytics implements ShouldQueue
{
    public $queue = 'analytics';
    public $tries = 3;
    public $timeout = 120;

    /**
     * Handle the event
     */
    public function handle(JobCardCompleted $event): void
    {
        // Heavy analytics computation
        $this->analyticsService->computeKPIs($event->jobCard);
    }
}
```

---

## Real-World Examples

### Example 1: Complete Service Flow

```php
<?php

namespace Modules\Appointment\Services;

/**
 * Complete service flow from appointment to payment
 */
class ServiceFlowOrchestrator
{
    public function __construct(
        private readonly AppointmentService $appointmentService,
        private readonly JobCardService $jobCardService,
        private readonly InvoiceService $invoiceService,
        private readonly PaymentService $paymentService
    ) {}

    /**
     * Execute complete service flow
     */
    public function executeCompleteFlow(int $appointmentId, array $paymentData): array
    {
        DB::beginTransaction();
        
        try {
            // 1. Start job card from appointment
            $appointment = $this->appointmentService->findOrFail($appointmentId);
            $appointment->status = 'in_progress';
            $appointment->save();

            $jobCard = $this->jobCardService->createFromAppointment($appointmentId);

            // 2. Complete job card and generate invoice
            $result = $this->jobCardService->completeJobCardWithInvoice($jobCard->id);

            // 3. Record payment
            $payment = $this->paymentService->recordPayment(
                $result['invoice']->id,
                $paymentData
            );

            DB::commit();

            // Events fire automatically from individual services
            
            return [
                'appointment' => $appointment,
                'job_card' => $result['jobCard'],
                'invoice' => $result['invoice'],
                'payment' => $payment
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            throw new ServiceException("Service flow failed: {$e->getMessage()}", previous: $e);
        }
    }
}
```

### Example 2: Saga Pattern for Long-Running Operations

```php
<?php

namespace Modules\JobCard\Services;

/**
 * Saga pattern for managing complex multi-step operations
 */
class JobCardSaga
{
    private array $completedSteps = [];

    public function __construct(
        private readonly JobCardService $jobCardService,
        private readonly InventoryService $inventoryService,
        private readonly InvoiceService $invoiceService
    ) {}

    /**
     * Execute saga with compensation logic
     */
    public function execute(array $data): mixed
    {
        try {
            // Step 1: Create job card
            $jobCard = $this->jobCardService->create($data);
            $this->completedSteps[] = 'job_card_created';

            // Step 2: Reserve parts
            foreach ($data['parts'] as $part) {
                $this->inventoryService->reservePart($part['id'], $part['quantity']);
            }
            $this->completedSteps[] = 'parts_reserved';

            // Step 3: Assign technician
            $this->jobCardService->assignTechnician($jobCard->id, $data['technician_id']);
            $this->completedSteps[] = 'technician_assigned';

            return $jobCard;

        } catch (\Exception $e) {
            // Compensate for completed steps
            $this->compensate($jobCard ?? null, $data);
            throw $e;
        }
    }

    /**
     * Compensation logic to undo completed steps
     */
    private function compensate(?JobCard $jobCard, array $data): void
    {
        Log::warning('Saga compensation started', [
            'completed_steps' => $this->completedSteps
        ]);

        if (in_array('parts_reserved', $this->completedSteps)) {
            foreach ($data['parts'] as $part) {
                $this->inventoryService->releaseReservation($part['id'], $part['quantity']);
            }
        }

        if (in_array('job_card_created', $this->completedSteps) && $jobCard) {
            $this->jobCardService->delete($jobCard->id);
        }
    }
}
```

---

## Best Practices

### 1. Service Layer Guidelines

- ✅ **DO**: Keep services focused on a single domain
- ✅ **DO**: Use dependency injection for service-to-service communication
- ✅ **DO**: Wrap cross-module operations in transactions
- ✅ **DO**: Log all orchestration points
- ❌ **DON'T**: Call repositories from other modules directly
- ❌ **DON'T**: Perform HTTP calls inside transactions
- ❌ **DON'T**: Mix business logic with data access

### 2. Transaction Guidelines

- ✅ **DO**: Use transactions for multi-table operations
- ✅ **DO**: Keep transactions as short as possible
- ✅ **DO**: Dispatch events after commit
- ✅ **DO**: Always handle rollback scenarios
- ❌ **DON'T**: Nest transactions unnecessarily
- ❌ **DON'T**: Perform slow operations in transactions
- ❌ **DON'T**: Forget to rollback on exception

### 3. Event Guidelines

- ✅ **DO**: Use events for async operations
- ✅ **DO**: Make events immutable
- ✅ **DO**: Queue expensive listeners
- ✅ **DO**: Handle listener failures gracefully
- ❌ **DON'T**: Use events for critical business logic
- ❌ **DON'T**: Expect immediate consistency
- ❌ **DON'T**: Pass mutable objects in events

### 4. Exception Guidelines

- ✅ **DO**: Use specific exception types
- ✅ **DO**: Log exceptions with context
- ✅ **DO**: Preserve exception chains
- ✅ **DO**: Return meaningful error messages
- ❌ **DON'T**: Catch exceptions you can't handle
- ❌ **DON'T**: Expose internal details in exceptions
- ❌ **DON'T**: Swallow exceptions silently

---

## Testing Strategies

### Testing Orchestrated Services

```php
<?php

namespace Tests\Feature;

class JobCardOrchestratedTest extends TestCase
{
    use RefreshDatabase;

    public function test_complete_flow_creates_job_card_and_invoice(): void
    {
        // Arrange
        $appointment = Appointment::factory()->create();
        
        // Act
        $result = $this->jobCardService->completeJobCardWithInvoice($appointment->id);

        // Assert
        $this->assertDatabaseHas('job_cards', [
            'appointment_id' => $appointment->id,
            'status' => 'completed'
        ]);

        $this->assertDatabaseHas('invoices', [
            'job_card_id' => $result['jobCard']->id
        ]);
    }

    public function test_transaction_rollback_on_invoice_failure(): void
    {
        // Arrange
        $appointment = Appointment::factory()->create();
        
        // Mock invoice service to fail
        $this->mock(InvoiceService::class)
            ->shouldReceive('generateFromJobCard')
            ->andThrow(new ServiceException('Invoice generation failed'));

        // Act & Assert
        $this->expectException(ServiceException::class);
        $this->jobCardService->completeJobCardWithInvoice($appointment->id);

        // Verify rollback - job card should not be completed
        $this->assertDatabaseHas('job_cards', [
            'appointment_id' => $appointment->id,
            'status' => 'in_progress' // Not completed
        ]);
    }
}
```

### Testing Event Listeners

```php
<?php

namespace Tests\Feature;

class JobCardEventTest extends TestCase
{
    public function test_job_card_completion_triggers_notification(): void
    {
        Event::fake([JobCardCompleted::class]);

        $jobCard = JobCard::factory()->create();
        
        $this->jobCardService->complete($jobCard->id);

        Event::assertDispatched(JobCardCompleted::class, function ($event) use ($jobCard) {
            return $event->jobCard->id === $jobCard->id;
        });
    }

    public function test_notification_listener_sends_email(): void
    {
        Mail::fake();

        $jobCard = JobCard::factory()->create();
        $invoice = Invoice::factory()->create(['job_card_id' => $jobCard->id]);
        
        $event = new JobCardCompleted($jobCard, $invoice);
        $listener = new SendJobCardCompletionNotification($this->notificationService);
        
        $listener->handle($event);

        Mail::assertSent(JobCardCompletedMail::class);
    }
}
```

---

## Summary

This guide demonstrates enterprise-grade patterns for:

1. **Service Orchestration**: Coordinating multiple services with clear boundaries
2. **Transaction Management**: Ensuring atomic operations with proper rollback
3. **Exception Handling**: Proper propagation and recovery strategies
4. **Event-Driven Architecture**: Decoupling modules while maintaining consistency

These patterns ensure the system is:
- ✅ **Scalable**: Loose coupling enables horizontal scaling
- ✅ **Maintainable**: Clear separation of concerns
- ✅ **Testable**: Each layer can be tested independently
- ✅ **Resilient**: Proper error handling and recovery
- ✅ **Consistent**: Transactional integrity maintained

For more details, see:
- [ARCHITECTURE.md](ARCHITECTURE.md) - System architecture overview
- [SECURITY.md](SECURITY.md) - Security best practices
- [CONTRIBUTING.md](CONTRIBUTING.md) - Development guidelines
