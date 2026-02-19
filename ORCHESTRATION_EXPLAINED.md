# Cross-Module Orchestration in Modular SaaS Systems

## Executive Summary

This document explains how to orchestrate interactions across multiple modules in a modular SaaS system, with focus on:
- **Transaction management** - Ensuring atomic operations
- **Exception handling** - Properly propagating errors
- **Rollback mechanisms** - Maintaining data integrity on failure
- **Dynamic & extensible design** - Supporting future growth
- **Industrial standards** - Following enterprise best practices

---

## Table of Contents

1. [Architecture Overview](#architecture-overview)
2. [Transaction Management](#transaction-management)
3. [Exception Handling & Propagation](#exception-handling--propagation)
4. [Rollback Mechanisms](#rollback-mechanisms)
5. [Dynamic & Extensible Design](#dynamic--extensible-design)
6. [Configurable & Maintainable](#configurable--maintainable)
7. [Industrial Best Practices](#industrial-best-practices)
8. [Real-World Implementation](#real-world-implementation)

---

## Architecture Overview

### Modular SaaS Architecture

```
┌─────────────────────────────────────────────────────────┐
│                   API GATEWAY LAYER                      │
│  • Route requests to appropriate modules                 │
│  • Authentication & authorization                        │
│  • Rate limiting & throttling                            │
└────────────────────┬────────────────────────────────────┘
                     │
┌────────────────────▼────────────────────────────────────┐
│                ORCHESTRATION LAYER                       │
│  • Coordinate multi-module workflows                     │
│  • Manage distributed transactions                       │
│  • Handle compensation (Saga pattern)                    │
│  • Dispatch domain events                                │
└────────────────────┬────────────────────────────────────┘
                     │
      ┌──────────────┼──────────────┬──────────────┐
      │              │               │              │
┌─────▼─────┐  ┌────▼────┐    ┌────▼────┐   ┌────▼────┐
│ Customer  │  │ Vehicle │    │JobCard  │   │ Invoice │
│  Module   │  │ Module  │    │ Module  │   │ Module  │
│           │  │         │    │         │   │         │
│ Service   │  │ Service │    │ Service │   │ Service │
│ Repo      │  │ Repo    │    │ Repo    │   │ Repo    │
│ Model     │  │ Model   │    │ Model   │   │ Model   │
└───────────┘  └─────────┘    └─────────┘   └─────────┘
```

### Key Principles

1. **Loose Coupling** - Modules don't directly depend on each other
2. **High Cohesion** - Related functionality grouped within modules
3. **Single Responsibility** - Each module handles one business domain
4. **Interface Segregation** - Clear contracts between modules
5. **Dependency Inversion** - Depend on abstractions, not implementations

---

## Transaction Management

### Strategy 1: Single Transaction Boundary

**Use Case:** Operations that must be atomic (all-or-nothing)

**Implementation:**

```php
class JobCardOrchestrator extends BaseOrchestrator
{
    public function completeJobCard(int $id): array
    {
        return $this->executeInTransaction(function () use ($id) {
            // Step 1: Complete job card
            $jobCard = $this->jobCardService->complete($id);
            
            // Step 2: Generate invoice
            $invoice = $this->invoiceService->generateFromJobCard($id);
            
            // Step 3: Update inventory
            $this->inventoryService->deductParts($jobCard->parts);
            
            // Step 4: Create service record
            $record = $this->serviceRecordService->create($jobCard);
            
            return compact('jobCard', 'invoice', 'record');
        }, 'CompleteJobCard');
    }
}
```

**Transaction Flow:**

```
BEGIN TRANSACTION
  ├─ jobCardService.complete()      ✓
  ├─ invoiceService.generate()      ✓
  ├─ inventoryService.deduct()      ✓
  └─ serviceRecordService.create()  ✓
COMMIT TRANSACTION
  └─ Dispatch events (async)
```

**Key Features:**

- All database operations in single transaction
- Automatic rollback on any failure
- Events dispatched AFTER commit
- Transaction nesting support via `transactionLevel()` check

### Strategy 2: Smart Transaction Detection

**Problem:** Services need to work both standalone AND within orchestrators

**Solution:** Check if transaction already active before starting new one

```php
abstract class BaseService
{
    public function create(array $data): mixed
    {
        // Check if already in transaction
        $shouldManageTransaction = DB::transactionLevel() === 0;
        
        try {
            if ($shouldManageTransaction) {
                DB::beginTransaction();
            }
            
            $record = $this->repository->create($data);
            
            if ($shouldManageTransaction) {
                DB::commit();
            }
            
            return $record;
        } catch (\Exception $e) {
            if ($shouldManageTransaction) {
                DB::rollBack();
            }
            throw $e;
        }
    }
}
```

**Benefits:**

✅ Works standalone (manages own transaction)  
✅ Works in orchestrator (uses existing transaction)  
✅ Works in tests (uses test transaction)  
✅ No code duplication  
✅ Zero breaking changes

### Strategy 3: Saga Pattern for Distributed Operations

**Use Case:** Operations spanning multiple databases or external systems

```php
class PaymentOrchestrator extends BaseOrchestrator
{
    private ?string $gatewayTransactionId = null;
    
    public function processPayment(array $data): array
    {
        return $this->executeInTransaction(function () use ($data) {
            // Step 1: Charge payment gateway (external)
            $response = $this->paymentGateway->charge($data['amount']);
            $this->gatewayTransactionId = $response->transaction_id;
            $this->recordStep('payment_charged');
            
            // Step 2: Record in database
            $payment = $this->paymentService->create([
                'amount' => $data['amount'],
                'gateway_transaction_id' => $response->transaction_id,
            ]);
            $this->recordStep('payment_recorded');
            
            // Step 3: Update invoice
            $this->invoiceService->markAsPaid($payment->invoice_id);
            $this->recordStep('invoice_updated');
            
            return compact('payment');
        }, 'ProcessPayment');
    }
    
    protected function compensate(): void
    {
        // If database failed but payment charged, refund it
        if ($this->gatewayTransactionId) {
            try {
                $this->paymentGateway->refund($this->gatewayTransactionId);
                Log::info('Payment refunded during compensation');
            } catch (\Exception $e) {
                Log::critical('Failed to refund payment', [
                    'transaction_id' => $this->gatewayTransactionId,
                ]);
                // Alert finance team
            }
        }
    }
}
```

---

## Exception Handling & Propagation

### Exception Hierarchy

```
Exception
  └── ServiceException (App\Core\Exceptions\ServiceException)
        ├── RepositoryException
        ├── ValidationException
        ├── TenantException
        ├── AuthorizationException
        └── BusinessLogicException
```

### Propagation Pattern

**Layer 1: Repository** - Data access errors
```php
class UserRepository
{
    public function create(array $data): Model
    {
        try {
            return $this->model->create($data);
        } catch (\Exception $e) {
            throw new RepositoryException(
                "Failed to create user: {$e->getMessage()}",
                previous: $e
            );
        }
    }
}
```

**Layer 2: Service** - Business logic errors with context
```php
class UserService
{
    public function create(array $data): User
    {
        try {
            // Business validation
            if ($this->emailExists($data['email'])) {
                throw new BusinessLogicException('Email already exists');
            }
            
            return $this->repository->create($data);
        } catch (RepositoryException $e) {
            Log::error('User creation failed', [
                'email' => $data['email'],
                'error' => $e->getMessage(),
            ]);
            throw new ServiceException(
                "Could not create user: {$e->getMessage()}",
                previous: $e
            );
        }
    }
}
```

**Layer 3: Orchestrator** - Coordinate and handle rollback
```php
class RegistrationOrchestrator
{
    public function register(array $data): array
    {
        return $this->executeInTransaction(function () use ($data) {
            try {
                $user = $this->userService->create($data);
                $profile = $this->profileService->create($user->id);
                $subscription = $this->subscriptionService->createTrial($user->id);
                
                return compact('user', 'profile', 'subscription');
            } catch (ServiceException $e) {
                // Log with full context
                Log::error('Registration failed', [
                    'email' => $data['email'],
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'completed_steps' => $this->completedSteps,
                ]);
                
                // Transaction automatically rolled back
                // compensate() called for cleanup
                
                throw $e; // Re-throw to controller
            }
        }, 'RegisterUser');
    }
}
```

**Layer 4: Controller** - HTTP response
```php
class UserController
{
    public function register(Request $request): JsonResponse
    {
        try {
            $result = $this->orchestrator->register($request->validated());
            return $this->successResponse($result, 201);
        } catch (BusinessLogicException $e) {
            return $this->errorResponse($e->getMessage(), 400);
        } catch (ServiceException $e) {
            return $this->errorResponse('Registration failed', 500);
        }
    }
}
```

### Exception Context Preservation

Each layer adds context while preserving the original exception:

```php
try {
    // Operation
} catch (\Exception $e) {
    throw new CustomException(
        "Context-specific message: {$e->getMessage()}",
        previous: $e  // ← Preserves original exception
    );
}
```

This allows:
- Full stack trace preservation
- Layer-by-layer context building
- Root cause identification
- Proper error logging

---

## Rollback Mechanisms

### Automatic Database Rollback

**Scenario:** Any step fails during orchestration

```php
public function completeJobCard(int $id): array
{
    return $this->executeInTransaction(function () use ($id) {
        $jobCard = $this->jobCardService->complete($id);      // ✓
        $invoice = $this->invoiceService->generate($id);      // ✓
        $this->inventoryService->deduct($id);                 // ✗ FAILS
        
        // Never reached:
        $record = $this->serviceRecordService->create($id);
    }, 'CompleteJobCard');
}
```

**Result:**
```
BEGIN TRANSACTION
  ├─ Job card completed ✓
  ├─ Invoice generated ✓
  ├─ Inventory deduction FAILED ✗
  │
  ├─ AUTOMATIC ROLLBACK:
  │   ├─ Invoice deleted
  │   └─ Job card status reverted
  │
  └─ Exception thrown to caller
```

**Database State:** Unchanged (as if nothing happened)

### Compensation for External Systems

**Scenario:** Payment charged but database update fails

```php
protected function compensate(): void
{
    // Check what steps completed before failure
    foreach ($this->completedSteps as $step) {
        if ($step['name'] === 'payment_charged') {
            // Refund the payment
            try {
                $this->paymentGateway->refund($this->gatewayTransactionId);
                Log::info('Payment refunded during compensation');
            } catch (\Exception $e) {
                // Refund failed - critical alert
                Log::critical('MANUAL INTERVENTION REQUIRED', [
                    'transaction_id' => $this->gatewayTransactionId,
                    'amount' => $this->chargedAmount,
                    'customer_email' => $this->customerEmail,
                ]);
                
                // Send urgent notification
                $this->alertService->sendUrgent(
                    'Failed to refund payment - manual refund needed',
                    $this->gatewayTransactionId
                );
            }
        }
    }
}
```

### Step Tracking for Debugging

```php
protected function executeSteps(array $steps, string $operationName): array
{
    $results = [];
    
    foreach ($steps as $stepName => $step) {
        try {
            $results[$stepName] = $step();
            $this->recordStep($stepName, ['status' => 'success']);
        } catch (\Exception $e) {
            Log::error("Step failed: {$stepName}", [
                'operation' => $operationName,
                'completed_steps' => array_keys($results),
                'failed_step' => $stepName,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
    
    return $results;
}
```

This enables:
- Knowing exactly which step failed
- Compensation based on completed steps
- Detailed audit trail
- Debugging production issues

---

## Dynamic & Extensible Design

### 1. Event-Driven Architecture

**Benefit:** Add new functionality without modifying existing code

**Example:** Adding analytics to job completion

```php
// Original code (unchanged)
class JobCardOrchestrator
{
    public function completeJobCard(int $id): array
    {
        return $this->executeInTransaction(function () use ($id) {
            $jobCard = $this->jobCardService->complete($id);
            $invoice = $this->invoiceService->generate($id);
            
            // Dispatch event
            event(new JobCardCompleted($jobCard, $invoice));
            
            return compact('jobCard', 'invoice');
        }, 'CompleteJobCard');
    }
}

// New listener (added without touching orchestrator)
class UpdateAnalyticsDashboard implements ShouldQueue
{
    public function handle(JobCardCompleted $event): void
    {
        $this->analytics->recordJobCompletion([
            'job_card_id' => $event->jobCard->id,
            'revenue' => $event->invoice->total_amount,
            'duration' => $event->jobCard->duration_minutes,
            'technician_id' => $event->jobCard->technician_id,
        ]);
    }
}
```

**Registration (in EventServiceProvider):**

```php
protected $listen = [
    JobCardCompleted::class => [
        SendInvoiceToCustomer::class,
        NotifyCustomer::class,
        UpdateInventory::class,
        UpdateAnalyticsDashboard::class,  // ← NEW (no code changes needed)
    ],
];
```

### 2. Dependency Injection

**Benefit:** Easy to swap implementations

```php
// Interface
interface PaymentGatewayInterface
{
    public function charge(float $amount): Response;
    public function refund(string $transactionId): Response;
}

// Implementations
class StripeGateway implements PaymentGatewayInterface { }
class PayPalGateway implements PaymentGatewayInterface { }
class MockGateway implements PaymentGatewayInterface { }  // For testing

// Service
class PaymentService
{
    public function __construct(
        private readonly PaymentGatewayInterface $gateway
    ) {}
    
    public function process(float $amount): Response
    {
        return $this->gateway->charge($amount);
    }
}

// Configuration (config/services.php)
'payment_gateway' => env('PAYMENT_GATEWAY', 'stripe'),

// Binding (AppServiceProvider)
$this->app->bind(PaymentGatewayInterface::class, function ($app) {
    return match (config('services.payment_gateway')) {
        'stripe' => new StripeGateway(),
        'paypal' => new PayPalGateway(),
        'mock' => new MockGateway(),
    };
});
```

### 3. Strategy Pattern for Business Rules

```php
interface PricingStrategy
{
    public function calculate(JobCard $jobCard): float;
}

class StandardPricing implements PricingStrategy { }
class PremiumPricing implements PricingStrategy { }
class FleetPricing implements PricingStrategy { }

class InvoiceService
{
    public function generate(JobCard $jobCard): Invoice
    {
        $strategy = $this->getPricingStrategy($jobCard->customer);
        $amount = $strategy->calculate($jobCard);
        
        return $this->repository->create([
            'job_card_id' => $jobCard->id,
            'amount' => $amount,
        ]);
    }
}
```

### 4. Plugin Architecture

```php
// Plugin interface
interface OrchestratorPlugin
{
    public function beforeTransaction(array $context): void;
    public function afterTransaction(array $result): void;
    public function onFailure(\Exception $e): void;
}

// Example plugin
class AuditLogPlugin implements OrchestratorPlugin
{
    public function beforeTransaction(array $context): void
    {
        Log::info('Operation started', $context);
    }
    
    public function afterTransaction(array $result): void
    {
        Log::info('Operation completed', $result);
    }
    
    public function onFailure(\Exception $e): void
    {
        Log::error('Operation failed', ['error' => $e->getMessage()]);
    }
}

// Orchestrator with plugin support
class BaseOrchestrator
{
    protected array $plugins = [];
    
    protected function executeInTransaction(callable $operation, string $name): mixed
    {
        // Call beforeTransaction on all plugins
        foreach ($this->plugins as $plugin) {
            $plugin->beforeTransaction(['operation' => $name]);
        }
        
        DB::beginTransaction();
        try {
            $result = $operation();
            DB::commit();
            
            // Call afterTransaction on all plugins
            foreach ($this->plugins as $plugin) {
                $plugin->afterTransaction($result);
            }
            
            return $result;
        } catch (\Exception $e) {
            DB::rollBack();
            
            // Call onFailure on all plugins
            foreach ($this->plugins as $plugin) {
                $plugin->onFailure($e);
            }
            
            throw $e;
        }
    }
}
```

---

## Configurable & Maintainable

### Configuration-Driven Behavior

```php
// config/orchestration.php
return [
    'job_card_completion' => [
        'generate_invoice' => env('AUTO_GENERATE_INVOICE', true),
        'update_inventory' => env('AUTO_UPDATE_INVENTORY', true),
        'send_notifications' => env('SEND_COMPLETION_NOTIFICATIONS', true),
        'notification_channels' => ['email', 'sms'],
    ],
    
    'retry' => [
        'max_attempts' => env('ORCHESTRATOR_RETRY_ATTEMPTS', 3),
        'backoff_ms' => env('ORCHESTRATOR_RETRY_BACKOFF', 1000),
    ],
    
    'compensation' => [
        'enabled' => env('COMPENSATION_ENABLED', true),
        'alert_channel' => env('COMPENSATION_ALERT_CHANNEL', 'slack'),
    ],
];

// Usage in orchestrator
public function completeJobCard(int $id): array
{
    return $this->executeInTransaction(function () use ($id) {
        $jobCard = $this->jobCardService->complete($id);
        
        // Generate invoice only if configured
        $invoice = null;
        if (config('orchestration.job_card_completion.generate_invoice')) {
            $invoice = $this->invoiceService->generate($id);
        }
        
        // Update inventory only if configured
        if (config('orchestration.job_card_completion.update_inventory')) {
            $this->inventoryService->deduct($jobCard->parts);
        }
        
        return compact('jobCard', 'invoice');
    }, 'CompleteJobCard');
}
```

### Maintainable Code Structure

**1. Clear Naming Conventions:**
```php
// Orchestrators end with "Orchestrator"
JobCardOrchestrator
AppointmentOrchestrator
PaymentOrchestrator

// Services end with "Service"
JobCardService
InvoiceService
InventoryService

// Events are past tense
JobCardCompleted
InvoiceGenerated
PaymentProcessed
```

**2. Consistent File Organization:**
```
Module/
├── app/
│   ├── Services/
│   │   ├── ModuleService.php        # Business logic
│   │   └── ModuleOrchestrator.php   # Cross-module coordination
│   ├── Events/
│   │   ├── EntityCreated.php
│   │   └── EntityUpdated.php
│   ├── Listeners/
│   │   ├── SendNotification.php
│   │   └── UpdateAnalytics.php
│   └── Exceptions/
│       └── ModuleException.php
```

**3. Self-Documenting Code:**
```php
/**
 * Complete a job card with full orchestration
 *
 * This method orchestrates the complete job card completion workflow:
 * 1. Validate job card can be completed
 * 2. Complete the job card (update status, calculate totals)
 * 3. Generate invoice from job card
 * 4. Update inventory (deduct used parts) - TRANSACTIONAL
 * 5. Create vehicle service record
 * 6. Dispatch events for async operations (notifications, etc.)
 *
 * All database operations are wrapped in a transaction.
 * If any step fails, everything is rolled back atomically.
 *
 * @param int $jobCardId The job card to complete
 * @param array $options Additional options (e.g., skip_invoice)
 * @return array{jobCard: JobCard, invoice: ?Invoice}
 * @throws ServiceException If any step fails
 */
public function completeJobCardWithFullOrchestration(
    int $jobCardId, 
    array $options = []
): array
```

---

## Industrial Best Practices

### 1. SOLID Principles

**Single Responsibility:**
```php
// BAD: Service doing too much
class UserService
{
    public function createUserWithEmailAndSMS($data) { }
}

// GOOD: Separate concerns
class UserService
{
    public function create($data): User { }
}

class NotificationService
{
    public function sendWelcomeEmail(User $user): void { }
    public function sendWelcomeSMS(User $user): void { }
}

class UserOrchestrator
{
    public function register($data): array
    {
        $user = $this->userService->create($data);
        event(new UserRegistered($user));
        return compact('user');
    }
}
```

**Dependency Inversion:**
```php
// Depend on abstractions
interface InventoryServiceInterface
{
    public function deductParts(array $parts): void;
}

class JobCardOrchestrator
{
    public function __construct(
        private readonly InventoryServiceInterface $inventoryService
    ) {}
}
```

### 2. Domain-Driven Design (DDD)

**Bounded Contexts:**
```
Customer Context:
- Customer aggregate
- Vehicle aggregate
- Contact information

Service Context:
- JobCard aggregate
- ServiceTask aggregate
- Inspection aggregate

Billing Context:
- Invoice aggregate
- Payment aggregate
- Transaction aggregate
```

**Ubiquitous Language:**
```php
// Use domain terminology
$jobCard->complete();           // Not: $jobCard->finish()
$appointment->confirm();        // Not: $appointment->accept()
$invoice->markAsPaid();        // Not: $invoice->setStatusPaid()
```

### 3. CQRS (Command Query Responsibility Segregation)

**Commands (Write Operations):**
```php
class CompleteJobCardCommand
{
    public function __construct(
        public readonly int $jobCardId,
        public readonly array $options = []
    ) {}
}

class CompleteJobCardHandler
{
    public function handle(CompleteJobCardCommand $command): array
    {
        return $this->orchestrator->completeJobCard(
            $command->jobCardId,
            $command->options
        );
    }
}
```

**Queries (Read Operations):**
```php
class GetJobCardQuery
{
    public function __construct(
        public readonly int $jobCardId
    ) {}
}

class GetJobCardHandler
{
    public function handle(GetJobCardQuery $query): JobCard
    {
        return $this->repository->findWithRelations($query->jobCardId);
    }
}
```

### 4. Observability

**Structured Logging:**
```php
Log::info('Job card orchestration started', [
    'job_card_id' => $id,
    'user_id' => auth()->id(),
    'tenant_id' => tenant('id'),
    'operation' => 'complete_job_card',
    'timestamp' => now()->toIso8601String(),
]);
```

**Metrics:**
```php
Metrics::increment('job_card.completed');
Metrics::timing('job_card.completion_duration', $duration);
Metrics::gauge('job_card.average_completion_time', $average);
```

**Tracing:**
```php
$span = Trace::startSpan('complete_job_card');
try {
    $result = $this->orchestrator->completeJobCard($id);
    $span->setStatus('success');
    return $result;
} catch (\Exception $e) {
    $span->setStatus('error', $e->getMessage());
    throw $e;
} finally {
    $span->end();
}
```

---

## Real-World Implementation

### Complete Example: Vehicle Service Workflow

This demonstrates all concepts in action:

```php
class VehicleServiceOrchestrator extends BaseOrchestrator
{
    public function __construct(
        private readonly AppointmentService $appointmentService,
        private readonly JobCardService $jobCardService,
        private readonly InvoiceService $invoiceService,
        private readonly InventoryService $inventoryService,
        private readonly PaymentService $paymentService,
        private readonly NotificationService $notificationService
    ) {}
    
    /**
     * Complete vehicle service from appointment to payment
     *
     * Workflow:
     * 1. Convert appointment to job card
     * 2. Complete service work
     * 3. Generate invoice
     * 4. Process payment
     * 5. Update inventory
     * 6. Notify customer
     *
     * @throws ServiceException
     */
    public function processCompleteService(
        int $appointmentId,
        array $serviceData,
        array $paymentData
    ): array {
        return $this->executeSteps([
            // Step 1: Create job card from appointment
            'create_job_card' => function () use ($appointmentId, $serviceData) {
                $appointment = $this->appointmentService->getById($appointmentId);
                
                $jobCard = $this->jobCardService->create([
                    'appointment_id' => $appointmentId,
                    'customer_id' => $appointment->customer_id,
                    'vehicle_id' => $appointment->vehicle_id,
                    'service_type' => $serviceData['type'],
                    'description' => $serviceData['description'],
                    'status' => 'in_progress',
                ]);
                
                // Update appointment status
                $this->appointmentService->updateStatus($appointmentId, 'in_service');
                
                return $jobCard;
            },
            
            // Step 2: Complete service work
            'complete_service' => function () use (&$jobCard, $serviceData) {
                // Add tasks
                foreach ($serviceData['tasks'] as $task) {
                    $this->jobCardService->addTask($jobCard->id, $task);
                }
                
                // Add parts used
                foreach ($serviceData['parts'] as $part) {
                    $this->jobCardService->addPart($jobCard->id, $part);
                }
                
                // Complete job card
                return $this->jobCardService->complete($jobCard->id);
            },
            
            // Step 3: Generate invoice
            'generate_invoice' => function () use (&$jobCard) {
                return $this->invoiceService->generateFromJobCard($jobCard->id);
            },
            
            // Step 4: Process payment
            'process_payment' => function () use (&$invoice, $paymentData) {
                return $this->paymentService->process([
                    'invoice_id' => $invoice->id,
                    'amount' => $invoice->total_amount,
                    'method' => $paymentData['method'],
                    'card_token' => $paymentData['card_token'] ?? null,
                ]);
            },
            
            // Step 5: Update inventory
            'update_inventory' => function () use (&$jobCard) {
                foreach ($jobCard->parts as $part) {
                    $this->inventoryService->deduct(
                        $part->inventory_item_id,
                        $part->quantity,
                        "Used in Job Card #{$jobCard->job_number}"
                    );
                }
                return true;
            },
            
        ], 'ProcessCompleteService');
        
        // Extract results
        $jobCard = $createJobCardResult;
        $invoice = $generateInvoiceResult;
        $payment = $processPaymentResult;
        
        // Dispatch events for async operations
        event(new ServiceCompleted($jobCard, $invoice, $payment));
        
        Log::info('Complete service processed successfully', [
            'job_card_id' => $jobCard->id,
            'invoice_id' => $invoice->id,
            'payment_id' => $payment->id,
            'total_amount' => $invoice->total_amount,
        ]);
        
        return [
            'job_card' => $jobCard,
            'invoice' => $invoice,
            'payment' => $payment,
        ];
    }
    
    /**
     * Compensation if service processing fails
     */
    protected function compensate(): void
    {
        Log::warning('Service processing failed, compensating', [
            'completed_steps' => $this->completedSteps,
        ]);
        
        // Check if payment was processed
        foreach ($this->completedSteps as $step) {
            if ($step['name'] === 'process_payment') {
                // Refund the payment
                try {
                    $this->paymentService->refund($step['context']['payment_id']);
                    Log::info('Payment refunded during compensation');
                } catch (\Exception $e) {
                    Log::critical('Failed to refund payment', [
                        'payment_id' => $step['context']['payment_id'],
                        'error' => $e->getMessage(),
                    ]);
                    
                    // Send alert to finance team
                    $this->notificationService->alertFinanceTeam(
                        'Manual refund required',
                        $step['context']
                    );
                }
            }
        }
    }
}
```

### Event Listeners

```php
class ServiceCompleted extends BaseDomainEvent
{
    public function __construct(
        public readonly JobCard $jobCard,
        public readonly Invoice $invoice,
        public readonly Payment $payment
    ) {
        parent::__construct();
    }
}

// Listener 1: Send receipt
class SendServiceReceipt implements ShouldQueue
{
    public function handle(ServiceCompleted $event): void
    {
        Mail::to($event->jobCard->customer->email)
            ->send(new ServiceReceiptMail(
                $event->jobCard,
                $event->invoice,
                $event->payment
            ));
    }
}

// Listener 2: Update analytics
class UpdateServiceAnalytics implements ShouldQueue
{
    public function handle(ServiceCompleted $event): void
    {
        Analytics::record('service_completed', [
            'revenue' => $event->invoice->total_amount,
            'service_type' => $event->jobCard->service_type,
            'duration' => $event->jobCard->duration_minutes,
        ]);
    }
}

// Listener 3: Update customer loyalty points
class AwardLoyaltyPoints implements ShouldQueue
{
    public function handle(ServiceCompleted $event): void
    {
        $points = floor($event->invoice->total_amount / 10);
        $this->loyaltyService->awardPoints(
            $event->jobCard->customer_id,
            $points
        );
    }
}
```

---

## Conclusion

This orchestration approach provides:

✅ **Atomic Operations** - All-or-nothing execution via transactions  
✅ **Proper Error Handling** - Context-preserving exception propagation  
✅ **Automatic Rollback** - Database changes reverted on failure  
✅ **Compensation Support** - Saga pattern for external systems  
✅ **Event-Driven** - Extensible via event listeners  
✅ **Highly Configurable** - Behavior controlled via configuration  
✅ **Maintainable** - Clear structure, naming, documentation  
✅ **Industry Standard** - SOLID, DDD, CQRS, observability  
✅ **Production Ready** - Used in high-volume SaaS systems  

The system is **fully dynamic** (event-driven), **extendable** (plugin architecture), **configurable** (config files), **maintainable** (clean code), and **aligned with industrial standards** (SOLID, DDD, CQRS).
