# Order to Inventory to Payment Workflow (Saga Example)

## 1. Sequence Diagram
1. **Client** -> `POST /api/v1/orders` (Tenant Context: `tenant_123`)
2. **Order Service**: Creates Order (Status: `PENDING`)
3. **Saga Orchestrator**: Starts `OrderProcessingSaga`
4. **Step 1 (Forward)**: `InventoryService::reserveStock()`
   - Deducts stock with pessimistic lock.
   - Records `RESERVATION` in `InventoryLedger`.
5. **Step 2 (Forward)**: `FinanceService::processPayment()`
   - Communicates with Payment Gateway.
6. **Step 3 (Forward)**: `OrderService::confirmOrder()`
   - Updates Order Status to `CONFIRMED`.
7. **Compensation (Rollback)**: If Step 2 fails:
   - `InventoryService::releaseStock()`: Reverses the stock reservation in `InventoryLedger`.
   - `OrderService::cancelOrder()`: Sets Order Status to `CANCELLED`.

## 2. Distributed Filtering Example
**Requirement**: Filter Inventory by Product Category (Category is in `Product Service`, Inventory is in `Inventory Service`).

### Implementation (Event-Driven Synchronization):
1. `Product Service` emits `ProductCategoryChanged` event.
2. `Inventory Service` consumes the event and updates its local `product_metadata` cache (denormalized view).
3. `Inventory Service` exposes an API: `GET /api/v1/inventory?category=electronics`.
4. The query uses the denormalized `product_metadata` for efficient filtering without cross-service calls.

## 3. API Contract Example (OpenAPI 3.1)
```yaml
openapi: 3.1.0
info:
  title: Order Service API
  version: 1.0.0
paths:
  /api/v1/orders:
    post:
      summary: Create a new order
      security:
        - BearerAuth: []
      parameters:
        - name: X-Tenant-ID
          in: header
          required: true
          schema: { type: string }
      requestBody:
        content:
          application/json:
            schema:
              $ref: '#/components/schemas/OrderCreate'
      responses:
        201:
          description: Order created successfully
```
