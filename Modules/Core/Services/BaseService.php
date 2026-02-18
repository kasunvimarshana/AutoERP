<?php

declare(strict_types=1);

namespace Modules\Core\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Base Service Class
 *
 * Provides a foundation for application services following Clean Architecture.
 * This layer contains application business rules and use cases, coordinating
 * between repositories and implementing cross-cutting concerns.
 *
 * Services in this layer:
 * - Coordinate multiple repositories
 * - Implement use cases and business workflows
 * - Handle transactions
 * - Perform validation
 * - Dispatch events
 *
 * This follows the Application Business Rules layer from Clean Architecture,
 * sitting between Interface Adapters (Controllers) and Enterprise Business Rules (Domain).
 *
 * Usage:
 * 1. Extend this class in your module's services
 * 2. Inject required repositories via constructor
 * 3. Implement business logic methods
 *
 * Example:
 * class OrderProcessingService extends BaseService {
 *     public function __construct(
 *         private OrderRepository $orders,
 *         private InventoryRepository $inventory
 *     ) {}
 *
 *     public function processOrder(array $data): Order {
 *         return $this->executeInTransaction(function() use ($data) {
 *             $order = $this->orders->create($data);
 *             $this->inventory->reserveStock($order);
 *             event(new OrderCreated($order));
 *             return $order;
 *         });
 *     }
 * }
 */
abstract class BaseService
{
    /**
     * Execute a callback within a database transaction.
     * Automatically rolls back on exception and logs errors.
     *
     * @throws Throwable
     */
    protected function executeInTransaction(callable $callback): mixed
    {
        try {
            DB::beginTransaction();

            $result = $callback();

            DB::commit();

            return $result;
        } catch (Throwable $e) {
            DB::rollBack();

            Log::error('Transaction failed: '.$e->getMessage(), [
                'exception' => $e,
                'service' => static::class,
            ]);

            throw $e;
        }
    }

    /**
     * Log an info message with service context.
     *
     * @param  array<string, mixed>  $context
     */
    protected function logInfo(string $message, array $context = []): void
    {
        Log::info($message, array_merge($context, [
            'service' => static::class,
        ]));
    }

    /**
     * Log an error message with service context.
     *
     * @param  array<string, mixed>  $context
     */
    protected function logError(string $message, array $context = []): void
    {
        Log::error($message, array_merge($context, [
            'service' => static::class,
        ]));
    }

    /**
     * Log a warning message with service context.
     *
     * @param  array<string, mixed>  $context
     */
    protected function logWarning(string $message, array $context = []): void
    {
        Log::warning($message, array_merge($context, [
            'service' => static::class,
        ]));
    }
}
