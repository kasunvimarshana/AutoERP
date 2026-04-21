## Low Stock Query


```
Product::whereHas('stocks', function ($q) {

   $q->selectRaw('SUM(quantity) as total')

     ->havingRaw('total <= reorder_level');

});
```



---





## BatchQueryService


```
class BatchQueryService

{

   public function getAvailableBatches(int $productId, int $warehouseId)

   {

       return \DB::table('stocks')

           ->join('batches', 'stocks.batch_id', '=', 'batches.id')

           ->where('stocks.product_id', $productId)

           ->where('stocks.warehouse_id', $warehouseId)

           ->where('stocks.quantity', '>', 0)

           ->whereDate('batches.expires_at', '>', now())

           ->orderBy('batches.expires_at', 'asc') // FEFO

           ->select(

               'stocks.id as stock_id',

               'stocks.batch_id',

               'stocks.quantity',

               'batches.expires_at'

           )

           ->lockForUpdate() // 🔒 prevents race conditions

           ->get();

   }

}
```


---



## StockAllocatorService




```
class StockAllocatorService

{

   public function allocate($batches, int $requiredQty): array

   {

       $allocations = [];

       $remaining = $requiredQty;



       foreach ($batches as $batch) {

           if ($remaining <= 0) break;



           $allocQty = min($batch->quantity, $remaining);



           if ($allocQty > 0) {

               $allocations[] = new AllocationItem(

                   batchId: $batch->batch_id,

                   allocatedQty: $allocQty

               );



               $remaining -= $allocQty;

           }

       }



       if ($remaining > 0) {

           throw new \DomainException("Insufficient stock for allocation");

       }



       return $allocations;

   }

}
```


---



## StockService (Update + Movements)


```
class StockService

{

   public function deductStock(

       int $productId,

       int $warehouseId,

       array $allocations,

       string $reference

   ): void {

       foreach ($allocations as $allocation) {



           $stock = \DB::table('stocks')

               ->where('product_id', $productId)

               ->where('batch_id', $allocation->batchId)

               ->where('warehouse_id', $warehouseId)

               ->lockForUpdate()

               ->first();



           if (!$stock || $stock->quantity < $allocation->allocatedQty) {

               throw new \RuntimeException("Stock inconsistency detected");

           }



           // Update stock

           \DB::table('stocks')

               ->where('id', $stock->id)

               ->update([

                   'quantity' => $stock->quantity - $allocation->allocatedQty,

                   'updated_at' => now()

               ]);



           // Insert movement

           \DB::table('stock_movements')->insert([

               'product_id'   => $productId,

               'batch_id'     => $allocation->batchId,

               'warehouse_id' => $warehouseId,

               'type'         => 'sale',

               'quantity'     => -$allocation->allocatedQty,

               'reference'    => $reference,

               'moved_at'     => now(),

               'created_at'   => now(),

               'updated_at'   => now(),

           ]);

       }

   }

}
```


---



## SaleService (Orchestration Layer)


```
class SaleService

{

   public function __construct(

       private BatchQueryService $batchQuery,

       private StockAllocatorService $allocator,

       private StockService $stockService

   ) {}



   public function processSale(

       int $productId,

       int $warehouseId,

       int $quantity,

       string $reference

   ): array {

       return \DB::transaction(function () use (

           $productId,

           $warehouseId,

           $quantity,

           $reference

       ) {

           // Step 1: Fetch batches

           $batches = $this->batchQuery

               ->getAvailableBatches($productId, $warehouseId);



           // Step 2: Allocate

           $allocations = $this->allocator

               ->allocate($batches, $quantity);



           // Step 3: Deduct stock

           $this->stockService->deductStock(

               $productId,

               $warehouseId,

               $allocations,

               $reference

           );



           return $allocations;

       });

   }

}
```


---



## PurchaseOrderController


```
namespace App\Http\Controllers\Api;



use App\Services\PurchaseOrderService;

use Illuminate\Http\Request;



class PurchaseOrderController extends BaseController

{

   public function __construct(private PurchaseOrderService $service) {}



   public function store(Request $request)

   {

       $data = $request->validate([

           'supplier_id' => 'required|exists:suppliers,id',

           'items' => 'required|array',

           'items.\*.product_id' => 'required',

           'items.\*.quantity' => 'required|integer',

           'items.\*.batch_number' => 'required',

           'items.\*.expires_at' => 'required|date',

       ]);



       return $this->success(

           $this->service->create($data),

           'Purchase Order created'

       );

   }

}
```




---



## SaleController (FEFO Integrated)


```
namespace App\Http\Controllers\Api;



use App\Services\SaleService;

use Illuminate\Http\Request;



class SaleController extends BaseController

{

   public function __construct(private SaleService $service) {}



   public function store(Request $request)

   {

       $data = $request->validate([

           'warehouse_id' => 'required|exists:warehouses,id',

           'items' => 'required|array',

           'items.\*.product_id' => 'required|exists:products,id',

           'items.\*.quantity' => 'required|integer|min:1',

       ]);



       $result = $this->service->processSaleTransaction($data);



       return $this->success($result, 'Sale completed (FEFO applied)');

   }

}
```


---



## BatchQueryService (FEFO Source)


```
namespace App\Services;



use Illuminate\Support\Facades\DB;



class BatchQueryService

{

   public function getAvailableBatches(int $productId, int $warehouseId)

   {

       return DB::table('stocks')

           ->join('batches', 'stocks.batch_id', '=', 'batches.id')

           ->where('stocks.product_id', $productId)

           ->where('stocks.warehouse_id', $warehouseId)

           ->where('stocks.quantity', '>', 0)

           ->whereDate('batches.expires_at', '>', now())

           ->orderBy('batches.expires_at', 'asc')

           ->select('stocks.\*', 'batches.expires_at')

           ->lockForUpdate()

           ->get();

   }

}
```


---



## StockAllocatorService (FEFO Strategy)


```
namespace App\Services;



use App\Services\Contracts\StockAllocationStrategy;

use DomainException;



class StockAllocatorService implements StockAllocationStrategy

{

   public function allocate($batches, int $requiredQty): array

   {

       $allocations = [];

       $remaining = $requiredQty;



       foreach ($batches as $batch) {

           if ($remaining <= 0) break;



           $allocQty = min($batch->quantity, $remaining);



           if ($allocQty > 0) {

               $allocations[] = [

                   'batch_id' => $batch->batch_id,

                   'qty' => $allocQty

               ];

               $remaining -= $allocQty;

           }

       }



       if ($remaining > 0) {

           throw new DomainException('Insufficient stock');

       }



       return $allocations;

   }

}

```

---



## StockService (Write Operations)


```
namespace App\Services;



use Illuminate\Support\Facades\DB;



class StockService

{

   public function increase(int $productId, int $batchId, int $warehouseId, int $qty, string $ref)

   {

       $stock = DB::table('stocks')

           ->where(compact('productId','batchId','warehouseId'))

           ->lockForUpdate()

           ->first();



       if ($stock) {

           DB::table('stocks')->where('id', $stock->id)->update([

               'quantity' => $stock->quantity + $qty,

               'updated_at' => now()

           ]);

       } else {

           DB::table('stocks')->insert([

               'product_id' => $productId,

               'batch_id' => $batchId,

               'warehouse_id' => $warehouseId,

               'quantity' => $qty,

               'created_at' => now(),

               'updated_at' => now()

           ]);

       }



       $this->movement($productId, $batchId, $warehouseId, $qty, 'purchase', $ref);

   }



   public function decrease(int $productId, int $warehouseId, array $allocations, string $ref)

   {

       foreach ($allocations as $a) {



           $stock = DB::table('stocks')

               ->where([

                   'product_id' => $productId,

                   'batch_id' => $a['batch_id'],

                   'warehouse_id' => $warehouseId

               ])

               ->lockForUpdate()

               ->first();



           if (!$stock || $stock->quantity < $a['qty']) {

               throw new \RuntimeException('Stock inconsistency');

           }



           DB::table('stocks')->where('id', $stock->id)->update([

               'quantity' => $stock->quantity - $a['qty'],

               'updated_at' => now()

           ]);



           $this->movement($productId, $a['batch_id'], $warehouseId, -$a['qty'], 'sale', $ref);

       }

   }



   private function movement($productId, $batchId, $warehouseId, $qty, $type, $ref)

   {

       DB::table('stock_movements')->insert([

           'product_id' => $productId,

           'batch_id' => $batchId,

           'warehouse_id' => $warehouseId,

           'type' => $type,

           'quantity' => $qty,

           'reference' => $ref,

           'moved_at' => now(),

           'created_at' => now(),

           'updated_at' => now(),

       ]);

   }

}

```

---



## StockQueryService (Read Optimized)

```

namespace App\Services;



use Illuminate\Support\Facades\DB;



class StockQueryService

{

   public function getCurrentStock()

   {

       return DB::table('stocks')

           ->join('products', 'stocks.product_id', '=', 'products.id')

           ->select('products.name', DB::raw('SUM(stocks.quantity) as qty'))

           ->groupBy('products.name')

           ->get();

   }



   public function getLowStock()

   {

       return DB::table('products')

           ->join('stocks', 'products.id', '=', 'stocks.product_id')

           ->groupBy('products.id')

           ->havingRaw('SUM(stocks.quantity) <= products.reorder_level')

           ->select('products.name', DB::raw('SUM(stocks.quantity) as qty'))

           ->get();

   }



   public function getExpiringSoon(int $days = 30)

   {

       return DB::table('batches')

           ->whereDate('expires_at', '<=', now()->addDays($days))

           ->get();

   }

}

```

---



## PurchaseOrderService


```
namespace App\Services;



use Illuminate\Support\Facades\DB;



class PurchaseOrderService

{

   public function __construct(

       private StockService $stockService

   ) {}



   public function create(array $data)

   {

       return DB::transaction(function () use ($data) {



           $poId = DB::table('purchase_orders')->insertGetId([

               'supplier_id' => $data['supplier_id'],

               'po_number' => uniqid('PO-'),

               'order_date' => now(),

               'created_at' => now(),

               'updated_at' => now(),

           ]);



           foreach ($data['items'] as $item) {



               $batchId = DB::table('batches')->insertGetId([

                   'product_id' => $item['product_id'],

                   'batch_number' => $item['batch_number'],

                   'expires_at' => $item['expires_at'],

                   'purchase_price' => $item['purchase_price'] ?? 0,

                   'selling_price' => $item['selling_price'] ?? 0,

                   'created_at' => now(),

                   'updated_at' => now(),

               ]);



               DB::table('purchase_order_items')->insert([

                   'purchase_order_id' => $poId,

                   'product_id' => $item['product_id'],

                   'batch_id' => $batchId,

                   'quantity' => $item['quantity'],

                   'unit_price' => $item['purchase_price'] ?? 0,

                   'created_at' => now(),

                   'updated_at' => now(),

               ]);



               $this->stockService->increase(

                   $item['product_id'],

                   $batchId,

                   $data['warehouse_id'] ?? 1,

                   $item['quantity'],

                   "PO:$poId"

               );

           }



           return $poId;

       });

   }

}

```

---



## SaleService (FEFO Orchestrator)

```

namespace App\Services;



use Illuminate\Support\Facades\DB;



class SaleService

{

   public function __construct(

       private BatchQueryService $batchQuery,

       private StockAllocatorService $allocator,

       private StockService $stockService

   ) {}



   public function processSaleTransaction(array $data)

   {

       return DB::transaction(function () use ($data) {



           $saleId = DB::table('sales')->insertGetId([

               'invoice_number' => uniqid('INV-'),

               'sale_date' => now(),

               'total_amount' => 0,

               'created_at' => now(),

               'updated_at' => now(),

           ]);



           foreach ($data['items'] as $item) {



               $batches = $this->batchQuery->getAvailableBatches(

                   $item['product_id'],

                   $data['warehouse_id']

               );



               $allocations = $this->allocator->allocate(

                   $batches,

                   $item['quantity']

               );



               $this->stockService->decrease(

                   $item['product_id'],

                   $data['warehouse_id'],

                   $allocations,

                   "SALE:$saleId"

               );



               foreach ($allocations as $a) {

                   DB::table('sale_items')->insert([

                       'sale_id' => $saleId,

                       'product_id' => $item['product_id'],

                       'batch_id' => $a['batch_id'],

                       'quantity' => $a['qty'],

                       'unit_price' => 0,

                       'created_at' => now(),

                       'updated_at' => now(),

                   ]);

               }

           }



           return ['sale_id' => $saleId];

       });

   }

}

```

---



## BarcodeTraceService


```
namespace App\Services;



use Illuminate\Support\Facades\DB;



class BarcodeTraceService

{

   public function trace(string $code): array

   {

       $barcode = DB::table('barcodes')

           ->where('code', $code)

           ->first();



       if (!$barcode) {

           throw new \DomainException('Barcode not found');

       }



       return match ($barcode->type) {

           'product'     => $this->traceProduct($barcode),

           'batch'       => $this->traceBatch($barcode),

           'unit'        => $this->traceUnit($barcode),

           'transaction' => $this->traceTransaction($barcode),

       };

   }



   private function traceProduct($barcode)

   {

       return DB::table('products')

           ->where('id', $barcode->barcodeable_id)

           ->first();

   }



   private function traceBatch($barcode)

   {

       return [

           'batch' => DB::table('batches')->find($barcode->barcodeable_id),



           'movements' => DB::table('stock_movements')

               ->where('batch_id', $barcode->barcodeable_id)

               ->orderBy('moved_at', 'desc')

               ->get(),



           'stock' => DB::table('stocks')

               ->where('batch_id', $barcode->barcodeable_id)

               ->get(),

       ];

   }



   private function traceUnit($barcode)

   {

       return DB::table('serial_numbers')

           ->where('id', $barcode->barcodeable_id)

           ->first();

   }



   private function traceTransaction($barcode)

   {

       return DB::table('transactions')

           ->where('id', $barcode->barcodeable_id)

           ->first();

   }

}

```

---



## Rotation Strategies (FIFO)


```
class FIFOAllocationStrategy implements StockRotationStrategy

{

   public function allocate($batches, int $qty): array

   {

       return collect($batches)

           ->sortBy('created_at')

           ->values()

           ->pipe(fn($b) => app(BaseAllocator::class)->allocate($b, $qty));

   }

}

```

## Rotation Strategies (FEFO)


```
class FEFOAllocationStrategy implements StockRotationStrategy

{

   public function allocate($batches, int $qty): array

   {

       return collect($batches)

           ->sortBy('expires_at')

           ->values()

           ->pipe(fn($b) => app(BaseAllocator::class)->allocate($b, $qty));

   }

}

```

## Rotation Strategies (LIFO)


```
class LIFOAllocationStrategy implements StockRotationStrategy

{

   public function allocate($batches, int $qty): array

   {

       return collect($batches)

           ->sortByDesc('created_at')

           ->values()

           ->pipe(fn($b) => app(BaseAllocator::class)->allocate($b, $qty));

   }

}

```

---



## Valuation Strategies (FIFO Cost)


```
class FIFOValuationStrategy implements InventoryValuationStrategy

{

   public function calculateCost(array $allocations): float

   {

       return collect($allocations)->sum(fn($a) =>

           $a['qty'] \* $a['purchase_price']

       );

   }

}

```



## Valuation Strategies (Weighted Average)


```
class WeightedAverageStrategy implements InventoryValuationStrategy

{

   public function calculateCost(array $allocations): float

   {

       $totalQty = collect($allocations)->sum('qty');

       $totalCost = collect($allocations)->sum(fn($a) =>

           $a['qty'] \* $a['purchase_price']

       );



       return $totalQty ? $totalCost / $totalQty : 0;

   }

}

```

## Valuation Strategies (Standard Cost)


```
class StandardCostStrategy implements InventoryValuationStrategy

{

   public function calculateCost(array $allocations): float

   {

       return collect($allocations)->sum(fn($a) =>

           $a['qty'] \* $a['standard_cost']

       );

   }

}
```


---



## Strategy Resolver (Dynamic Runtime Selection)


```
class InventoryStrategyFactory

{

   public function rotation(string $type): StockRotationStrategy

   {

       return match ($type) {

           'fifo' => new FIFOAllocationStrategy(),

           'lifo' => new LIFOAllocationStrategy(),

           'fefo' => new FEFOAllocationStrategy(),

           default => throw new \Exception('Invalid rotation')

       };

   }



   public function valuation(string $type): InventoryValuationStrategy

   {

       return match ($type) {

           'fifo' => new FIFOValuationStrategy(),

           'weighted_average' => new WeightedAverageStrategy(),

           'standard_cost' => new StandardCostStrategy(),

           default => throw new \Exception('Invalid valuation')

       };

   }

}

```

---



## Integration with SaleService (Dynamic Allocation + Costing)


```
$settings = $this->settingsService->resolve($productId, $warehouseId);



$rotation = $this->factory->rotation($settings->rotation_strategy);

$valuation = $this->factory->valuation($settings->valuation_method);



// Allocate stock

$allocations = $rotation->allocate($batches, $qty);



// Calculate cost

$cost = $valuation->calculateCost($allocations);

```

---



## Auditable Trait


```
namespace App\Models\Traits;



use Illuminate\Support\Facades\Auth;



trait Auditable

{

   protected static function bootAuditable()

   {

       static::created(fn($model) => self::log('created', $model));

       static::updated(fn($model) => self::log('updated', $model));

       static::deleted(fn($model) => self::log('deleted', $model));

   }



   protected static function log($action, $model)

   {

       \DB::table('audit_logs')->insert([

           'entity_type' => get_class($model),

           'entity_id'   => $model->id,

           'action'      => $action,

           'old_values'  => json_encode($model->getOriginal()),

           'new_values'  => json_encode($model->getAttributes()),

           'user_id'     => Auth::id(),

           'created_at'  => now(),

           'updated_at'  => now(),

       ]);

   }

}


```


---



## StockMovement


```
class StockMovement extends Model

{

   protected $fillable = [

       'product_id','product_variant_id','batch_id',

       'warehouse_id','type','quantity',

       'reference_type','reference_id','meta','moved_at'

   ];



   protected $casts = [

       'meta' => 'array',

       'moved_at' => 'datetime'

   ];



   public function product()

   {

       return $this->belongsTo(Product::class);

   }



   public function batch()

   {

       return $this->belongsTo(Batch::class);

   }



   public function warehouse()

   {

       return $this->belongsTo(Warehouse::class);

   }



   public function reference()

   {

       return $this->morphTo(__FUNCTION__, 'reference_type', 'reference_id');

   }

}

```

---



## InventoryStrategyFactory

```

class InventoryStrategyFactory

{

   public function rotation(string $type): RotationStrategy

   {

       return match ($type) {

           'fifo' => new FIFO(),

           'lifo' => new LIFO(),

           'fefo' => new FEFO(),

       };

   }



   public function valuation(string $type): ValuationStrategy

   {

       return match ($type) {

           'fifo' => new FIFOValuation(),

           'weighted_average' => new WeightedAverage(),

           'standard_cost' => new StandardCost(),

       };

   }



   public function allocation(string $type): AllocationStrategy

   {

       return match ($type) {

           'default' => new DefaultAllocator(),

           'strict_batch' => new StrictBatchAllocator(),

       };

   }

}

```

---





## BatchQueryService



```

class BatchQueryService

{

   public function getAvailable($productId, $variantId, $warehouseId)

   {

       return DB::table('stocks')

           ->join('batches', 'stocks.batch_id', '=', 'batches.id')

           ->where('stocks.product_id', $productId)

           ->where('stocks.product_variant_id', $variantId)

           ->where('stocks.warehouse_id', $warehouseId)

           ->where('stocks.quantity', '>', 0)

           ->lockForUpdate()

           ->get();

   }

}

```

---





## StockQueryService



```

class StockQueryService

{

   public function currentStock()

   {

       return Stock::selectRaw('product_id, SUM(quantity) as qty')

           ->groupBy('product_id')

           ->get();

   }

}

```

---



## StockService (Ledger Safe)

```

class StockService

{

   public function increase($data)

   {

       $stock = Stock::lockForUpdate()->firstOrCreate([

           'product_id' => $data['product_id'],

           'product_variant_id' => $data['variant_id'],

           'batch_id' => $data['batch_id'],

           'warehouse_id' => $data['warehouse_id'],

       ]);



       $stock->increment('quantity', $data['qty']);



       $this->movement($data, 'purchase');

   }



   public function decrease($data, array $allocations)

   {

       foreach ($allocations as $a) {



           $stock = Stock::lockForUpdate()->where([

               'product_id' => $data['product_id'],

               'product_variant_id' => $data['variant_id'],

               'batch_id' => $a['batch_id'],

               'warehouse_id' => $data['warehouse_id'],

           ])->first();



           if (!$stock || $stock->quantity < $a['qty']) {

               throw new \RuntimeException('Stock error');

           }



           $stock->decrement('quantity', $a['qty']);



           $this->movement([

               ...$data,

               'batch_id' => $a['batch_id'],

               'qty' => -$a['qty']

           ], 'sale');

       }

   }



   private function movement($data, $type)

   {

       StockMovement::create([

           ...$data,

           'type' => $type,

           'moved_at' => now(),

       ]);

   }

}

```

---



## AllocationService (Strategy Engine)

```

class AllocationService

{

   public function __construct(

       private InventoryStrategyFactory $factory

   ) {}



   public function allocate($settings, $batches, $qty)

   {

       $rotation = $this->factory->rotation($settings->rotation_strategy);

       $allocator = $this->factory->allocation($settings->allocation_algorithm);



       $sorted = $rotation->sort($batches);



       return $allocator->allocate($sorted, $qty);

   }

}

```

---



## ComboService (BOM Explosion)

```

class ComboService

{

   public function explode($productId, $qty)

   {

       return ProductComponent::where('parent_product_id', $productId)

           ->get()

           ->map(fn($c) => [

               'product_id' => $c->component_product_id,

               'qty' => $c->quantity \* $qty

           ]);

   }

}



```

---



## FULL ORCHESTRATION

```

class TransactionService

{

   public function __construct(

       private BatchQueryService $batchQuery,

       private AllocationService $allocation,

       private StockService $stock,

       private DigitalAssetService $digital,

       private ComboService $combo,

       private SettingsResolver $settings,

       private InventoryStrategyFactory $factory

   ) {}



   public function process(array $data)

   {

       return DB::transaction(function () use ($data) {



           $transaction = Transaction::create([

               'type' => $data['type'],

               'warehouse_id' => $data['warehouse_id'],

               'reference_no' => uniqid(),

               'transaction_date' => now(),

           ]);



           foreach ($data['items'] as $item) {



               $product = Product::findOrFail($item['product_id']);



               match ($product->type) {



                   'service' => $this->handleService($transaction, $item),



                   'digital' => $this->handleDigital($transaction, $item),



                   'combo' => $this->handleCombo($transaction, $item),



                   default => $this->handleStock($transaction, $item)

               };

           }



           return $transaction;

       });

   }

```

---





## Handle Physical / Variable


```


private function handleStock($transaction, $item)

{

   $settings = $this->settings->resolve(

       $item['product_id'],

       $transaction->warehouse_id

   );



   $batches = $this->batchQuery->getAvailable(

       $item['product_id'],

       $item['variant_id'] ?? null,

       $transaction->warehouse_id

   );



   $allocations = $this->allocation->allocate(

       $settings,

       $batches,

       $item['quantity']

   );



   $valuation = $this->factory->valuation($settings->valuation_method);

   $cost = $valuation->calculate($allocations);



   $this->stock->decrease([

       'product_id' => $item['product_id'],

       'variant_id' => $item['variant_id'] ?? null,

       'warehouse_id' => $transaction->warehouse_id

   ], $allocations);



   foreach ($allocations as $a) {

       TransactionItem::create([

           'transaction_id' => $transaction->id,

           'product_id' => $item['product_id'],

           'product_variant_id' => $item['variant_id'] ?? null,

           'batch_id' => $a['batch_id'],

           'quantity' => $a['qty'],

           'unit_price' => $cost,

       ]);

   }

}

```

---



## Handle Digital

```

private function handleDigital($transaction, $item)

{

   $asset = $this->digital->assign($item['product_id']);



   TransactionItem::create([

       'transaction_id' => $transaction->id,

       'product_id' => $item['product_id'],

       'quantity' => 1,

       'meta' => ['license' => $asset->license_key]

   ]);

}

```

---



## Handle Combo

```

private function handleCombo($transaction, $item)

{

   $components = $this->combo->explode(

       $item['product_id'],

       $item['quantity']

   );



   foreach ($components as $c) {

       $this->handleStock($transaction, [

           'product_id' => $c['product_id'],

           'quantity' => $c['qty']

       ]);

   }

}

```

---



## Handle Service

```

private function handleService($transaction, $item)

{

   TransactionItem::create([

       'transaction_id' => $transaction->id,

       'product_id' => $item['product_id'],

       'quantity' => $item['quantity'],

       'unit_price' => $item['price']

   ]);

}


```


---



## app/Http/Middleware/ResolveTenant.php

```

public function handle($request, Closure $next)

{

   $tenantId = $request->header('X-Tenant-ID') ?? $request->route('tenant');

   $tenant = TenantConfig::find($tenantId); // from central store



   if (!$tenant) {

       abort(404, 'Tenant not found');

   }



   // Dynamically set database connection

   config(["database.connections.tenant" => [

       'driver'   => $tenant->db_driver,

       'host'     => $tenant->db_host,

       'database' => $tenant->db_name,

       'username' => $tenant->db_user,

       'password' => decrypt($tenant->db_password),

       // ...

   ]]);

   DB::purge('tenant');

   DB::reconnect('tenant');



   // Set mail, cache, queue, etc.

   config(["mail.mailers.smtp" => array_merge(

       config("mail.mailers.smtp"),

       $tenant->mail_config

   )]);

   config(["cache.default" => $tenant->cache_driver]);

   config(["queue.default" => $tenant->queue_driver]);



   // Feature flags

   Feature::define('advanced-reports', fn() => $tenant->feature_flags['advanced-reports'] ?? false);



   // Bind tenant to service container

   app()->instance('current_tenant', $tenant);



   return $next($request);

}

```

---



```

class FiscalYearSeeder extends Seeder

{

   public function run(): void

   {

       FiscalYear::create([

           'tenant_id' => 1,

           'name' => 'FY 2025',

           'start_date' => '2025-01-01',

           'end_date' => '2025-12-31',

           'is_closed' => false,

       ]);

   }

}



class AccountingPeriodSeeder extends Seeder

{

   public function run(): void

   {

       $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

       foreach ($months as $i => $month) {

           AccountingPeriod::create([

               'tenant_id' => 1,

               'fiscal_year_id' => 1,

               'name' => $month . ' 2025',

               'period_number' => $i + 1,

               'start_date' => "2025-" . str_pad($i+1, 2, '0', STR_PAD_LEFT) . "-01",

               'end_date' => date("Y-m-t", strtotime("2025-" . str_pad($i+1, 2, '0', STR_PAD_LEFT) . "-01")),

               'status' => 'open',

           ]);

       }

   }

}



class ChartOfAccountSeeder extends Seeder

{

   public function run(): void

   {

       $accounts = [

           ['code' => '1000', 'name' => 'Cash on Hand', 'type' => 'asset', 'normal_balance' => 'debit', 'level' => 1, 'path' => '/1000', 'is_leaf' => true],

           ['code' => '1100', 'name' => 'Main Checking', 'type' => 'asset', 'normal_balance' => 'debit', 'is_bank' => true, 'level' => 1, 'path' => '/1100', 'is_leaf' => true],

           ['code' => '1200', 'name' => 'Accounts Receivable', 'type' => 'asset', 'normal_balance' => 'debit', 'level' => 1, 'path' => '/1200', 'is_leaf' => true],

           ['code' => '1300', 'name' => 'Inventory', 'type' => 'asset', 'normal_balance' => 'debit', 'level' => 1, 'path' => '/1300', 'is_leaf' => true],

           ['code' => '2000', 'name' => 'Accounts Payable', 'type' => 'liability', 'normal_balance' => 'credit', 'level' => 1, 'path' => '/2000', 'is_leaf' => true],

           ['code' => '3000', 'name' => 'Retained Earnings', 'type' => 'equity', 'normal_balance' => 'credit', 'level' => 1, 'path' => '/3000', 'is_leaf' => true],

           ['code' => '4000', 'name' => 'Sales Revenue', 'type' => 'income', 'normal_balance' => 'credit', 'level' => 1, 'path' => '/4000', 'is_leaf' => true],

           ['code' => '5000', 'name' => 'Cost of Goods Sold', 'type' => 'expense', 'normal_balance' => 'debit', 'level' => 1, 'path' => '/5000', 'is_leaf' => true],

           ['code' => '6000', 'name' => 'Operating Expenses', 'type' => 'expense', 'normal_balance' => 'debit', 'level' => 1, 'path' => '/6000', 'is_leaf' => true],

           ['code' => '7000', 'name' => 'Tax Expense', 'type' => 'expense', 'normal_balance' => 'debit', 'level' => 1, 'path' => '/7000', 'is_leaf' => true],

       ];

       foreach ($accounts as $acc) {

           ChartOfAccount::create(array_merge($acc, ['tenant_id' => 1, 'is_active' => true]));

       }

   }

}

```

---

```

class StockMovementService implements StockMovementServiceInterface

{

   protected StockMovementRepositoryInterface $movementRepo;

   protected StockBalanceRepositoryInterface $balanceRepo;



   public function __construct(

       StockMovementRepositoryInterface $movementRepo,

       StockBalanceRepositoryInterface $balanceRepo

   ) {

       $this->movementRepo = $movementRepo;

       $this->balanceRepo = $balanceRepo;

   }



   public function recordReceipt(array $data)

   {

       return DB::transaction(function () use ($data) {

           $movement = $this->movementRepo->create(array_merge($data, [

               'movement_type' => 'receipt',

               'movement_number' => $this->generateMovementNumber()

           ]));

           $this->updateStockBalances(

               $data['product_id'],

               $data['to_location_id'],

               $data['batch_id'] ?? null,

               $data['quantity']

           );

           Event::dispatch(new StockMovementCreated($movement->toArray()));

           return $movement;

       });

   }



   public function recordIssue(array $data)

   {

       return DB::transaction(function () use ($data) {

           $movement = $this->movementRepo->create(array_merge($data, [

               'movement_type' => 'issue',

               'movement_number' => $this->generateMovementNumber()

           ]));

           $this->updateStockBalances(

               $data['product_id'],

               $data['from_location_id'],

               $data['batch_id'] ?? null,

               -$data['quantity']

           );

           Event::dispatch(new StockMovementCreated($movement->toArray()));

           return $movement;

       });

   }



   public function recordTransfer(array $data)

   {

       return DB::transaction(function () use ($data) {

           // Out movement

           $outMovement = $this->movementRepo->create(array_merge($data, [

               'movement_type' => 'transfer',

               'from_location_id' => $data['from_location_id'],

               'to_location_id' => null,

               'movement_number' => $this->generateMovementNumber()

           ]));

           $this->updateStockBalances(

               $data['product_id'],

               $data['from_location_id'],

               $data['batch_id'] ?? null,

               -$data['quantity']

           );



           // In movement

           $inMovement = $this->movementRepo->create(array_merge($data, [

               'movement_type' => 'transfer',

               'from_location_id' => null,

               'to_location_id' => $data['to_location_id'],

               'movement_number' => $this->generateMovementNumber()

           ]));

           $this->updateStockBalances(

               $data['product_id'],

               $data['to_location_id'],

               $data['batch_id'] ?? null,

               $data['quantity']

           );



           return ['out' => $outMovement, 'in' => $inMovement];

       });

   }



   public function recordAdjustment(array $data)

   {

       return DB::transaction(function () use ($data) {

           $movement = $this->movementRepo->create(array_merge($data, [

               'movement_type' => 'adjustment',

               'movement_number' => $this->generateMovementNumber()

           ]));

           $this->updateStockBalances(

               $data['product_id'],

               $data['to_location_id'] ?? $data['from_location_id'],

               $data['batch_id'] ?? null,

               $data['quantity']

           );

           Event::dispatch(new StockMovementCreated($movement->toArray()));

           return $movement;

       });

   }



   public function updateStockBalances(int $productId, int $locationId, ?int $batchId, float $quantityChange)

   {

       $balance = $this->balanceRepo->findByProductLocationBatch($productId, $locationId, $batchId);

       if ($balance) {

           $newQty = $balance->qty_on_hand + $quantityChange;

           $this->balanceRepo->update($balance->id, [

               'qty_on_hand' => $newQty,

               'qty_available' => $newQty - $balance->qty_reserved,

               'updated_at' => now()

           ]);

       } else if ($quantityChange > 0) {

           $this->balanceRepo->create([

               'tenant_id' => auth()->user()->tenant_id,

               'product_id' => $productId,

               'location_id' => $locationId,

               'batch_id' => $batchId,

               'uom_id' => 1, // default

               'qty_on_hand' => $quantityChange,

               'qty_available' => $quantityChange,

               'avg_cost' => 0

           ]);

       }

       // Negative balance not allowed – would throw exception in real validation

   }



   protected function generateMovementNumber(): string

   {

       return 'MOV-' . str_pad(rand(1, 99999), 5, '0', STR_PAD_LEFT);

   }

}



class JournalEntryService implements JournalEntryServiceInterface

{

   protected JournalEntryRepositoryInterface $repository;



   public function __construct(JournalEntryRepositoryInterface $repository)

   {

       $this->repository = $repository;

   }



   public function createFromTransaction(string $sourceType, int $sourceId, array $entries)

   {

       return DB::transaction(function () use ($sourceType, $sourceId, $entries) {

           $totalDebit = array_sum(array_column($entries, 'debit'));

           $totalCredit = array_sum(array_column($entries, 'credit'));

           if ($totalDebit !== $totalCredit) {

               throw new \Exception('Journal entry must balance: Debits must equal Credits');

           }



           $period = AccountingPeriod::where('start_date', '<=', now())

               ->where('end_date', '>=', now())

               ->where('status', 'open')

               ->first();

           if (!$period) {

               throw new \Exception('No open accounting period found');

           }



           $journalEntry = $this->repository->create([

               'tenant_id' => auth()->user()->tenant_id,

               'period_id' => $period->id,

               'entry_number' => $this->generateEntryNumber(),

               'entry_date' => now(),

               'post_date' => now(),

               'source_type' => $sourceType,

               'source_id' => $sourceId,

               'description' => "Auto-generated from {$sourceType} #{$sourceId}",

               'currency_id' => 1,

               'exchange_rate' => 1,

               'status' => 'draft',

               'created_by' => auth()->id()

           ]);



           foreach ($entries as $line) {

               $journalEntry->lines()->create([

                   'account_id' => $line['account_id'],

                   'debit' => $line['debit'],

                   'credit' => $line['credit'],

                   'line_number' => $line['line_number'] ?? 0,

                   'party_id' => $line['party_id'] ?? null,

                   'cost_center_id' => $line['cost_center_id'] ?? null,

                   'description' => $line['description'] ?? null

               ]);

           }



           return $journalEntry;

       });

   }



   public function postJournalEntry(int $journalEntryId)

   {

       $entry = $this->repository->find($journalEntryId);

       if ($entry->status !== 'draft') {

           throw new \Exception('Only draft entries can be posted');

       }

       return $this->repository->update($journalEntryId, [

           'status' => 'posted',

           'posted_by' => auth()->id(),

           'posted_at' => now()

       ]);

   }



   public function reverseJournalEntry(int $journalEntryId, string $reason)

   {

       $original = $this->repository->find($journalEntryId);

       if ($original->status !== 'posted') {

           throw new \Exception('Only posted entries can be reversed');

       }



       return DB::transaction(function () use ($original, $reason) {

           // Create reversing entry with opposite signs

           $reversalLines = [];

           foreach ($original->lines as $line) {

               $reversalLines[] = [

                   'account_id' => $line->account_id,

                   'debit' => $line->credit,

                   'credit' => $line->debit,

                   'line_number' => $line->line_number,

                   'party_id' => $line->party_id,

                   'cost_center_id' => $line->cost_center_id,

                   'description' => "Reversal: {$reason}"

               ];

           }



           $reversal = $this->createFromTransaction(

               'reversal',

               $original->id,

               $reversalLines

           );

           $this->postJournalEntry($reversal->id);



           $this->repository->update($original->id, [

               'status' => 'reversed',

               'reversed_by' => auth()->id()

           ]);



           return $reversal;

       });

   }



   protected function generateEntryNumber(): string

   {

       return 'JE-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);

   }

}



class PurchaseOrderService implements PurchaseOrderServiceInterface

{

   protected PurchaseOrderRepositoryInterface $poRepo;

   protected GoodsReceiptRepositoryInterface $grRepo;

   protected StockMovementServiceInterface $stockMovementService;

   protected JournalEntryServiceInterface $journalEntryService;



   public function __construct(

       PurchaseOrderRepositoryInterface $poRepo,

       GoodsReceiptRepositoryInterface $grRepo,

       StockMovementServiceInterface $stockMovementService,

       JournalEntryServiceInterface $journalEntryService

   ) {

       $this->poRepo = $poRepo;

       $this->grRepo = $grRepo;

       $this->stockMovementService = $stockMovementService;

       $this->journalEntryService = $journalEntryService;

   }



   public function createPurchaseOrder(array $data)

   {

       return $this->poRepo->create($data);

   }



   public function receiveGoods(int $goodsReceiptId, array $lines)

   {

       return DB::transaction(function () use ($goodsReceiptId, $lines) {

           $gr = $this->grRepo->find($goodsReceiptId);

           if ($gr->status !== 'draft') {

               throw new \Exception('Goods receipt can only be received from draft status');

           }



           foreach ($lines as $line) {

               // Record stock movement

               $movement = $this->stockMovementService->recordReceipt([

                   'tenant_id' => $gr->tenant_id,

                   'product_id' => $line['product_id'],

                   'variant_id' => $line['variant_id'] ?? null,

                   'batch_id' => $line['batch_id'] ?? null,

                   'serial_id' => $line['serial_id'] ?? null,

                   'to_location_id' => $line['location_id'],

                   'uom_id' => $line['uom_id'],

                   'quantity' => $line['received_qty'],

                   'unit_cost' => $line['unit_cost'],

                   'source_type' => 'goods_receipt',

                   'source_id' => $goodsReceiptId,

                   'created_by' => auth()->id()

               ]);



               // Create goods receipt line

               $gr->lines()->create([

                   'po_line_id' => $line['po_line_id'] ?? null,

                   'product_id' => $line['product_id'],

                   'variant_id' => $line['variant_id'] ?? null,

                   'batch_id' => $line['batch_id'] ?? null,

                   'serial_id' => $line['serial_id'] ?? null,

                   'location_id' => $line['location_id'],

                   'uom_id' => $line['uom_id'],

                   'received_qty' => $line['received_qty'],

                   'unit_cost' => $line['unit_cost'],

                   'total_cost' => $line['received_qty'] \* $line['unit_cost'],

                   'stock_movement_id' => $movement->id

               ]);



               // Update purchase order line received quantity

               if ($line['po_line_id']) {

                   $poLine = $gr->purchaseOrder->lines()->find($line['po_line_id']);

                   $newReceived = $poLine->received_qty + $line['received_qty'];

                   $poLine->update(['received_qty' => $newReceived]);

               }

           }



           $gr->update(['status' => 'received']);



           // If entire PO is received, update PO status

           $po = $gr->purchaseOrder;

           $totalOrdered = $po->lines->sum('ordered_qty');

           $totalReceived = $po->lines->sum('received_qty');

           if ($totalReceived >= $totalOrdered) {

               $po->update(['status' => 'received']);

           } elseif ($totalReceived > 0) {

               $po->update(['status' => 'partially_received']);

           }



           return $gr;

       });

   }



   public function approvePurchaseOrder(int $purchaseOrderId)

   {

       return $this->poRepo->update($purchaseOrderId, ['status' => 'approved']);

   }

}



class SalesOrderService implements SalesOrderServiceInterface

{

   protected SalesOrderRepositoryInterface $soRepo;

   protected DeliveryOrderRepositoryInterface $doRepo;

   protected StockMovementServiceInterface $stockMovementService;

   protected JournalEntryServiceInterface $journalEntryService;



   public function __construct(

       SalesOrderRepositoryInterface $soRepo,

       DeliveryOrderRepositoryInterface $doRepo,

       StockMovementServiceInterface $stockMovementService,

       JournalEntryServiceInterface $journalEntryService

   ) {

       $this->soRepo = $soRepo;

       $this->doRepo = $doRepo;

       $this->stockMovementService = $stockMovementService;

       $this->journalEntryService = $journalEntryService;

   }



   public function createSalesOrder(array $data)

   {

       return $this->soRepo->create($data);

   }



   public function shipOrder(int $deliveryOrderId, array $lines)

   {

       return DB::transaction(function () use ($deliveryOrderId, $lines) {

           $do = $this->doRepo->find($deliveryOrderId);

           if ($do->status !== 'draft') {

               throw new \Exception('Delivery order can only be shipped from draft status');

           }



           foreach ($lines as $line) {

               // Record stock issue

               $movement = $this->stockMovementService->recordIssue([

                   'tenant_id' => $do->tenant_id,

                   'product_id' => $line['product_id'],

                   'variant_id' => $line['variant_id'] ?? null,

                   'batch_id' => $line['batch_id'] ?? null,

                   'serial_id' => $line['serial_id'] ?? null,

                   'from_location_id' => $line['from_location_id'],

                   'uom_id' => $line['uom_id'],

                   'quantity' => $line['delivered_qty'],

                   'unit_cost' => $line['unit_cost'] ?? 0,

                   'source_type' => 'delivery_order',

                   'source_id' => $deliveryOrderId,

                   'created_by' => auth()->id()

               ]);



               $do->lines()->create([

                   'so_line_id' => $line['so_line_id'],

                   'product_id' => $line['product_id'],

                   'variant_id' => $line['variant_id'] ?? null,

                   'batch_id' => $line['batch_id'] ?? null,

                   'serial_id' => $line['serial_id'] ?? null,

                   'from_location_id' => $line['from_location_id'],

                   'uom_id' => $line['uom_id'],

                   'delivered_qty' => $line['delivered_qty'],

                   'stock_movement_id' => $movement->id

               ]);



               // Update sales order line shipped quantity

               $soLine = $do->salesOrder->lines()->find($line['so_line_id']);

               $newShipped = $soLine->shipped_qty + $line['delivered_qty'];

               $soLine->update(['shipped_qty' => $newShipped]);

           }



           $do->update(['status' => 'shipped']);



           $so = $do->salesOrder;

           $totalOrdered = $so->lines->sum('ordered_qty');

           $totalShipped = $so->lines->sum('shipped_qty');

           if ($totalShipped >= $totalOrdered) {

               $so->update(['status' => 'shipped']);

           }



           return $do;

       });

   }



   public function invoiceOrder(int $customerInvoiceId)

   {

       return DB::transaction(function () use ($customerInvoiceId) {

           $invoice = CustomerInvoice::findOrFail($customerInvoiceId);

           if ($invoice->status !== 'draft') {

               throw new \Exception('Invoice can only be generated from draft status');

           }



           // Create journal entry for revenue recognition

           $journalLines = [

               [

                   'account_id' => 3, // Accounts Receivable

                   'debit' => $invoice->total,

                   'credit' => 0,

                   'line_number' => 1,

                   'party_id' => $invoice->customer_id

               ],

               [

                   'account_id' => 7, // Sales Revenue

                   'debit' => 0,

                   'credit' => $invoice->total,

                   'line_number' => 2,

                   'party_id' => $invoice->customer_id

               ]

           ];



           $journalEntry = $this->journalEntryService->createFromTransaction(

               'customer_invoice',

               $customerInvoiceId,

               $journalLines

           );

           $this->journalEntryService->postJournalEntry($journalEntry->id);



           $invoice->update([

               'status' => 'sent',

               'journal_entry_id' => $journalEntry->id

           ]);



           // Also create COGS journal entry if needed (in a real system, from inventory layers)

           // ...



           return $invoice;

       });

   }

}

```

---



## Unify Transactional Documents

```

documents (

 id, tenant_id, period_id, document_type ENUM('PO','SO','GRN','SUP_INV','CUST_INV','RETURN','CREDIT_NOTE','PAYMENT'),

 document_number, party_id, warehouse_id, currency_id, exchange_rate,

 document_date, accounting_date, due_date, status, total_amount, paid_amount,

 created_by, approved_by, journal_entry_id, ...

)



document_lines (

 id, document_id, line_number, product_id, variant_id, uom_id, quantity,

 unit_price, discount_pct, tax_code_id, subtotal, tax_amount, total,

 batch_id, serial_id, location_id, stock_movement_id, ...

)


```
---





## Purchase (Transactions)

```

class PurchaseOrderSeeder extends Seeder

{

   public function run(): void

   {

       $tenants = Tenant::all();

       $usd = Currency::where('code', 'USD')->first();



       foreach ($tenants as $tenant) {

           $supplier = Supplier::where('tenant_id', $tenant->id)->first();

           $warehouse = Warehouse::where('tenant_id', $tenant->id)->first();

           $user = User::where('tenant_id', $tenant->id)->first();

           $products = Product::where('tenant_id', $tenant->id)->where('type', 'physical')->take(2)->get();



           if (!$supplier || !$warehouse || !$user || $products->isEmpty()) continue;



           $po = PurchaseOrder::create([

               'tenant_id' => $tenant->id,

               'supplier_id' => $supplier->id,

               'org_unit_id' => $warehouse->org_unit_id,

               'warehouse_id' => $warehouse->id,

               'po_number' => 'PO-' . date('Ymd') . '-001',

               'status' => 'confirmed',

               'currency_id' => $usd->id,

               'exchange_rate' => 1,

               'order_date' => now()->subDays(5),

               'expected_date' => now()->addDays(7),

               'subtotal' => 0,

               'grand_total' => 0,

               'created_by' => $user->id,

           ]);



           $subtotal = 0;

           foreach ($products as $product) {

               $qty = rand(10, 50);

               $price = 12.50;

               $lineTotal = $qty \* $price;

               $subtotal += $lineTotal;



               PurchaseOrderLine::create([

                   'purchase_order_id' => $po->id,

                   'product_id' => $product->id,

                   'uom_id' => $product->base_uom_id,

                   'ordered_qty' => $qty,

                   'received_qty' => 0,

                   'unit_price' => $price,

                   'line_total' => $lineTotal,

               ]);

           }



           $po->update(['subtotal' => $subtotal, 'grand_total' => $subtotal]);

       }

   }

}



class GrnSeeder extends Seeder

{

   public function run(): void

   {

       $pos = PurchaseOrder::where('status', 'confirmed')->get();

       $usd = Currency::where('code', 'USD')->first();



       foreach ($pos as $po) {

           $user = User::where('tenant_id', $po->tenant_id)->first();

           $location = WarehouseLocation::where('warehouse_id', $po->warehouse_id)->where('type', 'bin')->first();



           $grn = GrnHeader::create([

               'tenant_id' => $po->tenant_id,

               'supplier_id' => $po->supplier_id,

               'warehouse_id' => $po->warehouse_id,

               'purchase_order_id' => $po->id,

               'grn_number' => 'GRN-' . date('Ymd') . '-' . $po->id,

               'status' => 'complete',

               'received_date' => now()->subDays(2),

               'currency_id' => $usd->id,

               'created_by' => $user->id,

           ]);



           foreach ($po->lines as $line) {

               $grnLine = GrnLine::create([

                   'grn_header_id' => $grn->id,

                   'purchase_order_line_id' => $line->id,

                   'product_id' => $line->product_id,

                   'variant_id' => $line->variant_id,

                   'location_id' => $location?->id,

                   'uom_id' => $line->uom_id,

                   'expected_qty' => $line->ordered_qty,

                   'received_qty' => $line->ordered_qty,

                   'unit_cost' => $line->unit_price,

               ]);



               // Record stock movement

               StockMovement::create([

                   'tenant_id' => $po->tenant_id,

                   'product_id' => $line->product_id,

                   'variant_id' => $line->variant_id,

                   'to_location_id' => $location?->id,

                   'movement_type' => 'receipt',

                   'reference_type' => GrnHeader::class,

                   'reference_id' => $grn->id,

                   'uom_id' => $line->uom_id,

                   'quantity' => $line->ordered_qty,

                   'unit_cost' => $line->unit_price,

                   'performed_by' => $user->id,

               ]);



               // Update stock level

               StockLevel::updateOrCreate(

                   [

                       'tenant_id' => $po->tenant_id,

                       'product_id' => $line->product_id,

                       'variant_id' => $line->variant_id,

                       'location_id' => $location?->id,

                   ],

                   [

                       'uom_id' => $line->uom_id,

                       'unit_cost' => $line->unit_price,

                   ]

               )->increment('quantity_on_hand', $line->ordered_qty);



               $line->update(['received_qty' => $line->ordered_qty]);

           }



           $po->update(['status' => 'received']);

       }

   }

}

```

---



## Finance (Transactions)

```

class PaymentSeeder extends Seeder

{

   public function run(): void

   {

       $tenants = Tenant::all();

       $usd = Currency::where('code', 'USD')->first();



       foreach ($tenants as $tenant) {

           $bankAccount = Account::where('tenant_id', $tenant->id)->where('is_bank_account', true)->first();

           $cashAccount = Account::where('tenant_id', $tenant->id)->where('code', '1000')->first();

           $apAccount = Account::where('tenant_id', $tenant->id)->where('code', '2000')->first();



           $bankMethod = PaymentMethod::firstOrCreate(

               ['tenant_id' => $tenant->id, 'name' => 'Bank Transfer'],

               ['type' => 'bank_transfer', 'account_id' => $bankAccount?->id, 'is_active' => true]

           );



           // Pay a purchase invoice

           $purchaseInvoice = PurchaseInvoice::where('tenant_id', $tenant->id)->where('status', 'approved')->first();

           if ($purchaseInvoice) {

               Payment::create([

                   'tenant_id' => $tenant->id,

                   'payment_number' => 'PAY-OUT-' . date('Ymd') . '-001',

                   'direction' => 'outbound',

                   'party_type' => 'supplier',

                   'party_id' => $purchaseInvoice->supplier_id,

                   'payment_method_id' => $bankMethod->id,

                   'account_id' => $bankAccount?->id ?? $cashAccount->id,

                   'amount' => $purchaseInvoice->grand_total,

                   'currency_id' => $usd->id,

                   'exchange_rate' => 1,

                   'base_amount' => $purchaseInvoice->grand_total,

                   'payment_date' => now(),

                   'status' => 'posted',

               ]);

               $purchaseInvoice->update(['status' => 'paid']);

           }



           // Receive payment for a sales invoice

           $salesInvoice = SalesInvoice::where('tenant_id', $tenant->id)->where('status', 'sent')->first();

           if ($salesInvoice) {

               Payment::create([

                   'tenant_id' => $tenant->id,

                   'payment_number' => 'PAY-IN-' . date('Ymd') . '-001',

                   'direction' => 'inbound',

                   'party_type' => 'customer',

                   'party_id' => $salesInvoice->customer_id,

                   'payment_method_id' => $bankMethod->id,

                   'account_id' => $bankAccount?->id ?? $cashAccount->id,

                   'amount' => $salesInvoice->grand_total,

                   'currency_id' => $usd->id,

                   'exchange_rate' => 1,

                   'base_amount' => $salesInvoice->grand_total,

                   'payment_date' => now(),

                   'status' => 'posted',

               ]);

               $salesInvoice->update(['status' => 'paid']);

           }

       }

   }

}

```

---



## GoodsReceivedListener



Create PO → Confirm PO → Goods Receipt (GRN) → Stock Movement (receipt) → Journal Entry (Dr Inventory, Cr AP)

                                                        ↓

                                               Purchase Invoice → Journal Entry (optional adjustment)

                                                        ↓

                                                  Payment (outbound) → Journal Entry (Dr AP, Cr Bank)

                                                        ↓

                                               Purchase Return (optional) → Stock Movement (return_out) → Journal Entry (Dr AP, Cr Inventory)

```

class GoodsReceivedListener

{

   public function handle(GoodsReceived $event): void

   {

       DB::transaction(function () use ($event) {

           $grn = $event->grn;

           foreach ($grn->lines as $line) {

               // 1. Stock Movement (receipt)

               $movement = StockMovement::create([

                   'tenant_id' => $grn->tenant_id,

                   'product_id' => $line->product_id,

                   'variant_id' => $line->variant_id,

                   'batch_id' => $line->batch_id,

                   'serial_id' => $line->serial_id,

                   'to_location_id' => $line->location_id,

                   'movement_type' => 'receipt',

                   'reference_type' => GrnHeader::class,

                   'reference_id' => $grn->id,

                   'uom_id' => $line->uom_id,

                   'quantity' => $line->received_qty,

                   'unit_cost' => $line->unit_cost,

                   'performed_by' => $grn->created_by,

               ]);



               // 2. Update Stock Level

               StockLevel::updateOrCreate([...])->increment('quantity_on_hand', $line->received_qty);



               // 3. Create Cost Layer (FIFO/LIFO/FEFO)

               InventoryCostLayer::create([

                   'tenant_id' => $grn->tenant_id,

                   'product_id' => $line->product_id,

                   'variant_id' => $line->variant_id,

                   'batch_id' => $line->batch_id,

                   'location_id' => $line->location_id,

                   'valuation_method' => $line->product->valuation_method,

                   'layer_date' => $grn->received_date,

                   'quantity_in' => $line->received_qty,

                   'quantity_remaining' => $line->received_qty,

                   'unit_cost' => $line->unit_cost,

                   'reference_type' => StockMovement::class,

                   'reference_id' => $movement->id,

               ]);

           }



           // 4. Post Journal Entry (if configured to do so at GRN)

           $this->postReceiptJournalEntry($grn);

       });

   }



   protected function postReceiptJournalEntry(GrnHeader $grn): void

   {

       $journalEntry = JournalEntry::create([

           'tenant_id' => $grn->tenant_id,

           'fiscal_period_id' => FiscalPeriod::current()->id,

           'entry_type' => 'auto',

           'reference_type' => GrnHeader::class,

           'reference_id' => $grn->id,

           'entry_date' => $grn->received_date,

           'status' => 'posted',

           'created_by' => $grn->created_by,

       ]);



       $inventoryAccount = Account::where('code', '1300')->first(); // Inventory

       $apAccount = Account::where('code', '2000')->first();       // Accounts Payable

       $totalCost = $grn->lines->sum('line_cost');



       // Debit Inventory

       JournalEntryLine::create([

           'journal_entry_id' => $journalEntry->id,

           'account_id' => $inventoryAccount->id,

           'debit_amount' => $totalCost,

       ]);

       // Credit AP

       JournalEntryLine::create([

           'journal_entry_id' => $journalEntry->id,

           'account_id' => $apAccount->id,

           'credit_amount' => $totalCost,

       ]);



       $grn->update(['status' => 'posted']);

   }

}

```

---



## ShipmentShippedListener



Create SO → Confirm SO → Reserve Stock → Pick/Pack → Shipment → Stock Movement (issue) → Journal Entry (Dr COGS, Cr Inventory)

                                                                    ↓

                                                             Sales Invoice → Journal Entry (Dr AR, Cr Revenue)

                                                                    ↓

                                                               Payment (inbound) → Journal Entry (Dr Bank, Cr AR)

                                                                    ↓

                                                             Sales Return (optional) → Stock Movement (return_in) → Journal Entry (Dr Revenue/Inventory, Cr AR)

```

class ShipmentShippedListener

{

   public function handle(ShipmentShipped $event): void

   {

       DB::transaction(function () use ($event) {

           $shipment = $event->shipment;

           $cogsAccount = Account::where('code', '5000')->first(); // COGS

           $inventoryAccount = Account::where('code', '1300')->first();

           $totalCogs = 0;



           foreach ($shipment->lines as $line) {

               // 1. Allocate stock via FIFO/FEFO cost layers

               $layers = $this->allocateLayers($line->product_id, $line->shipped_qty, $line->batch_id, $line->from_location_id);

               $unitCost = $layers->avg('unit_cost');

               $totalLineCost = $line->shipped_qty \* $unitCost;

               $totalCogs += $totalLineCost;



               // 2. Create Stock Movement (issue)

               $movement = StockMovement::create([

                   'tenant_id' => $shipment->tenant_id,

                   'product_id' => $line->product_id,

                   'variant_id' => $line->variant_id,

                   'batch_id' => $line->batch_id,

                   'serial_id' => $line->serial_id,

                   'from_location_id' => $line->from_location_id,

                   'movement_type' => 'shipment',

                   'reference_type' => Shipment::class,

                   'reference_id' => $shipment->id,

                   'uom_id' => $line->uom_id,

                   'quantity' => $line->shipped_qty,

                   'unit_cost' => $unitCost,

                   'performed_by' => $shipment->created_by,

               ]);



               // 3. Update Stock Level

               StockLevel::where([...])->decrement('quantity_on_hand', $line->shipped_qty);



               // 4. Release reservations

               StockReservation::where('reserved_for_type', SalesOrderLine::class)

                   ->where('reserved_for_id', $line->sales_order_line_id)

                   ->delete();

           }



           // 5. Post COGS Journal Entry

           $journalEntry = JournalEntry::create([...]);

           JournalEntryLine::create(['journal_entry_id' => $journalEntry->id, 'account_id' => $cogsAccount->id, 'debit_amount' => $totalCogs]);

           JournalEntryLine::create(['journal_entry_id' => $journalEntry->id, 'account_id' => $inventoryAccount->id, 'credit_amount' => $totalCogs]);

       });

   }



   protected function allocateLayers($productId, $qty, $batchId, $locationId): Collection

   {

       $remaining = $qty;

       $layers = InventoryCostLayer::where('product_id', $productId)

           ->where('location_id', $locationId)

           ->when($batchId, fn($q) => $q->where('batch_id', $batchId))

           ->where('quantity_remaining', '>', 0)

           ->orderBy('layer_date') // FIFO; for FEFO order by expiry_date via batch

           ->get();



       foreach ($layers as $layer) {

           $consume = min($remaining, $layer->quantity_remaining);

           $layer->decrement('quantity_remaining', $consume);

           $layer->update(['is_closed' => $layer->quantity_remaining == 0]);

           $remaining -= $consume;

           if ($remaining <= 0) break;

       }

       if ($remaining > 0) throw new InsufficientStockException();

       return $layers;

   }

}

```

---



## SalesReturnApprovedListener

```

class SalesReturnApprovedListener

{

   public function handle(SalesReturnApproved $event): void

   {

       DB::transaction(function () use ($event) {

           $return = $event->salesReturn;

           $totalInventoryCredit = 0;

           $totalRevenueDebit = 0;

           $totalRestockingFee = 0;



           foreach ($return->lines as $line) {

               $disposition = $line->disposition;

               $originalCost = $line->original_sales_order_line_id

                   ? $this->getOriginalCost($line->original_sales_order_line_id)

                   : $this->getCurrentCost($line->product_id);



               if ($disposition === 'restock') {

                   // 1. Stock Movement (return_in)

                   $movement = StockMovement::create([

                       'tenant_id' => $return->tenant_id,

                       'product_id' => $line->product_id,

                       'variant_id' => $line->variant_id,

                       'batch_id' => $line->batch_id,

                       'serial_id' => $line->serial_id,

                       'to_location_id' => $line->to_location_id,

                       'movement_type' => 'return_in',

                       'reference_type' => SalesReturn::class,

                       'reference_id' => $return->id,

                       'uom_id' => $line->uom_id,

                       'quantity' => $line->return_qty,

                       'unit_cost' => $originalCost,

                       'performed_by' => $return->created_by,

                   ]);



                   // 2. Update Stock Level

                   StockLevel::updateOrCreate([...])->increment('quantity_on_hand', $line->return_qty);



                   // 3. Re-insert Cost Layer (at original cost)

                   InventoryCostLayer::create([

                       'tenant_id' => $return->tenant_id,

                       'product_id' => $line->product_id,

                       'variant_id' => $line->variant_id,

                       'batch_id' => $line->batch_id,

                       'location_id' => $line->to_location_id,

                       'valuation_method' => $line->product->valuation_method,

                       'layer_date' => $return->return_date,

                       'quantity_in' => $line->return_qty,

                       'quantity_remaining' => $line->return_qty,

                       'unit_cost' => $originalCost,

                       'reference_type' => StockMovement::class,

                       'reference_id' => $movement->id,

                   ]);



                   $totalInventoryCredit += $line->return_qty \* $originalCost;

               }



               $totalRevenueDebit += $line->line_total;

               $totalRestockingFee += $line->restocking_fee;

           }



           // 4. Post Journal Entry

           $journalEntry = JournalEntry::create([...]);

           // Debit Sales Returns (Revenue contra)

           JournalEntryLine::create(['account_id' => $salesReturnsAccount->id, 'debit_amount' => $totalRevenueDebit]);

           // Credit Accounts Receivable

           JournalEntryLine::create(['account_id' => $arAccount->id, 'credit_amount' => $totalRevenueDebit + $totalRestockingFee]);

           // If restocked, Credit Inventory (reversal of COGS) and Debit COGS reversal

           if ($totalInventoryCredit > 0) {

               JournalEntryLine::create(['account_id' => $inventoryAccount->id, 'debit_amount' => $totalInventoryCredit]);

               JournalEntryLine::create(['account_id' => $cogsAccount->id, 'credit_amount' => $totalInventoryCredit]);

           }

           // Restocking fee as revenue

           if ($totalRestockingFee > 0) {

               JournalEntryLine::create(['account_id' => $restockingFeeRevenueAccount->id, 'credit_amount' => $totalRestockingFee]);

           }



           // 5. Create Credit Memo

           CreditMemo::create([

               'tenant_id' => $return->tenant_id,

               'party_type' => 'customer',

               'party_id' => $return->customer_id,

               'return_order_type' => SalesReturn::class,

               'return_order_id' => $return->id,

               'credit_memo_number' => 'CM-' . $return->return_number,

               'amount' => $totalRevenueDebit + $totalRestockingFee,

               'status' => 'issued',

               'issued_date' => now(),

               'journal_entry_id' => $journalEntry->id,

           ]);



           $return->update(['status' => 'closed']);

       });

   }

}

```

---



## PAYMENTS AND REFUNDS (Payment Processing)

```

class PaymentService

{

   public function process(Payment $payment): void

   {

       DB::transaction(function () use ($payment) {

           // 1. Update payment status

           $payment->status = 'posted';

           $payment->save();



           // 2. Post Journal Entry

           $journalEntry = $this->createPaymentJournalEntry($payment);



           // 3. Update Party Balance (AR/AP)

           if ($payment->direction === 'inbound') {

               $this->applyToInvoices($payment, SalesInvoice::class);

           } else {

               $this->applyToInvoices($payment, PurchaseInvoice::class);

           }



           // 4. Reconcile bank transaction if matched

           $this->reconcileBankTransaction($payment);

       });

   }



   protected function createPaymentJournalEntry(Payment $payment): JournalEntry

   {

       $journalEntry = JournalEntry::create([...]);

       $bankAccount = $payment->account; // Bank/Cash account

       $partyAccount = $payment->direction === 'inbound'

           ? Account::where('code', '1200')->first() // AR

           : Account::where('code', '2000')->first(); // AP



       if ($payment->direction === 'inbound') {

           // Debit Bank, Credit AR

           JournalEntryLine::create(['account_id' => $bankAccount->id, 'debit_amount' => $payment->amount]);

           JournalEntryLine::create(['account_id' => $partyAccount->id, 'credit_amount' => $payment->amount]);

       } else {

           // Debit AP, Credit Bank

           JournalEntryLine::create(['account_id' => $partyAccount->id, 'debit_amount' => $payment->amount]);

           JournalEntryLine::create(['account_id' => $bankAccount->id, 'credit_amount' => $payment->amount]);

       }

       return $journalEntry;

   }



   protected function applyToInvoices(Payment $payment, string $invoiceClass): void

   {

       $remaining = $payment->amount;

       $invoices = $invoiceClass::where('party_id', $payment->party_id)

           ->where('status', '!=', 'paid')

           ->orderBy('due_date')

           ->get();



       foreach ($invoices as $invoice) {

           $allocate = min($remaining, $invoice->grand_total - $invoice->paid_amount);

           PaymentAllocation::create([

               'payment_id' => $payment->id,

               'invoice_type' => $invoiceClass,

               'invoice_id' => $invoice->id,

               'allocated_amount' => $allocate,

           ]);

           $invoice->paid_amount += $allocate;

           $invoice->status = ($invoice->paid_amount >= $invoice->grand_total) ? 'paid' : 'partial_paid';

           $invoice->save();

           $remaining -= $allocate;

           if ($remaining <= 0) break;

       }

   }

}

```

---



## Purchase Module Seeders (Complete Scenarios)

```

<?php

namespace Modules\Purchase\Database\Seeders;



use Illuminate\Database\Seeder;

use Modules\Purchase\Infrastructure\Persistence\Eloquent\PurchaseOrder;

use Modules\Purchase\Infrastructure\Persistence\Eloquent\PurchaseOrderLine;

use Modules\Supplier\Infrastructure\Persistence\Eloquent\Supplier;

use Modules\Product\Infrastructure\Persistence\Eloquent\Product;

use Modules\Warehouse\Infrastructure\Persistence\Eloquent\Warehouse;

use Modules\User\Infrastructure\Persistence\Eloquent\User;

use Modules\Core\Infrastructure\Persistence\Eloquent\Tenant;

use Modules\Core\Infrastructure\Persistence\Eloquent\Currency;



class PurchaseOrderSeeder extends Seeder

{

   public function run(): void

   {

       $tenants = Tenant::all();

       $usd = Currency::where('code', 'USD')->first();



       foreach ($tenants as $tenant) {

           $supplier = Supplier::where('tenant_id', $tenant->id)->first();

           $warehouse = Warehouse::where('tenant_id', $tenant->id)->where('is_default', true)->first();

           $user = User::where('tenant_id', $tenant->id)->whereHas('roles', fn($q) => $q->where('name', 'Manager'))->first();

           if (!$supplier || !$warehouse || !$user) continue;



           // Create 2 POs

           for ($i = 1; $i <= 2; $i++) {

               $po = PurchaseOrder::create([

                   'tenant_id' => $tenant->id,

                   'supplier_id' => $supplier->id,

                   'org_unit_id' => $warehouse->org_unit_id,

                   'warehouse_id' => $warehouse->id,

                   'po_number' => 'PO-' . date('Ymd') . '-' . str_pad($i, 3, '0', STR_PAD_LEFT),

                   'status' => 'confirmed',

                   'currency_id' => $usd->id,

                   'exchange_rate' => 1,

                   'order_date' => now()->subDays(10 + $i),

                   'expected_date' => now()->addDays(5),

                   'created_by' => $user->id,

                   'approved_by' => $user->id,

               ]);



               $products = Product::where('tenant_id', $tenant->id)->where('type', 'physical')->take(2)->get();

               $subtotal = 0;

               foreach ($products as $product) {

                   $qty = rand(5, 20) \* ($i == 1 ? 1 : 2);

                   $price = 12.50;

                   $lineTotal = $qty \* $price;

                   $subtotal += $lineTotal;

                   PurchaseOrderLine::create([

                       'purchase_order_id' => $po->id,

                       'product_id' => $product->id,

                       'uom_id' => $product->base_uom_id,

                       'ordered_qty' => $qty,

                       'unit_price' => $price,

                       'line_total' => $lineTotal,

                   ]);

               }

               $po->update(['subtotal' => $subtotal, 'grand_total' => $subtotal]);

           }

       }

   }

}

```

---



## GrnFromPoSeeder.php – Receive goods against PO (creates stock \& journal entry)

```

<?php

namespace Modules\Purchase\Database\Seeders;



use Illuminate\Database\Seeder;

use Modules\Purchase\Infrastructure\Persistence\Eloquent\PurchaseOrder;

use Modules\Purchase\Infrastructure\Persistence\Eloquent\GrnHeader;

use Modules\Purchase\Infrastructure\Persistence\Eloquent\GrnLine;

use Modules\Inventory\Infrastructure\Persistence\Eloquent\StockMovement;

use Modules\Inventory\Infrastructure\Persistence\Eloquent\StockLevel;

use Modules\Inventory\Infrastructure\Persistence\Eloquent\InventoryCostLayer;

use Modules\Finance\Infrastructure\Persistence\Eloquent\JournalEntry;

use Modules\Finance\Infrastructure\Persistence\Eloquent\JournalEntryLine;

use Modules\Finance\Infrastructure\Persistence\Eloquent\Account;

use Modules\Finance\Infrastructure\Persistence\Eloquent\FiscalPeriod;

use Modules\Warehouse\Infrastructure\Persistence\Eloquent\WarehouseLocation;

use Modules\User\Infrastructure\Persistence\Eloquent\User;

use Modules\Core\Infrastructure\Persistence\Eloquent\Currency;

use Illuminate\Support\Facades\DB;



class GrnFromPoSeeder extends Seeder

{

   public function run(): void

   {

       $pos = PurchaseOrder::where('status', 'confirmed')->get();

       $usd = Currency::where('code', 'USD')->first();

       $inventoryAccount = Account::where('code', '1300')->first();

       $grIrAccount = Account::firstOrCreate(['code' => '1500', 'name' => 'GR/IR', 'type' => 'liability', 'normal_balance' => 'credit']);

       $fiscalPeriod = FiscalPeriod::where('status', 'open')->first();



       foreach ($pos as $po) {

           DB::transaction(function () use ($po, $usd, $inventoryAccount, $grIrAccount, $fiscalPeriod) {

               $user = User::where('tenant_id', $po->tenant_id)->first();

               $location = WarehouseLocation::where('warehouse_id', $po->warehouse_id)->where('type', 'bin')->first();

               if (!$user || !$location) return;



               // Create GRN

               $grn = GrnHeader::create([

                   'tenant_id' => $po->tenant_id,

                   'supplier_id' => $po->supplier_id,

                   'warehouse_id' => $po->warehouse_id,

                   'purchase_order_id' => $po->id,

                   'grn_number' => 'GRN-PO-' . $po->id,

                   'status' => 'complete',

                   'received_date' => now()->subDays(3),

                   'currency_id' => $usd->id,

                   'created_by' => $user->id,

               ]);



               $totalCost = 0;

               foreach ($po->lines as $line) {

                   $grnLine = GrnLine::create([

                       'grn_header_id' => $grn->id,

                       'purchase_order_line_id' => $line->id,

                       'product_id' => $line->product_id,

                       'variant_id' => $line->variant_id,

                       'location_id' => $location->id,

                       'uom_id' => $line->uom_id,

                       'expected_qty' => $line->ordered_qty,

                       'received_qty' => $line->ordered_qty,

                       'unit_cost' => $line->unit_price,

                   ]);



                   // Stock Movement

                   StockMovement::create([

                       'tenant_id' => $po->tenant_id,

                       'product_id' => $line->product_id,

                       'to_location_id' => $location->id,

                       'movement_type' => 'receipt',

                       'reference_type' => GrnHeader::class,

                       'reference_id' => $grn->id,

                       'uom_id' => $line->uom_id,

                       'quantity' => $line->ordered_qty,

                       'unit_cost' => $line->unit_price,

                       'performed_by' => $user->id,

                   ]);



                   // Update Stock Level

                   StockLevel::updateOrCreate([

                       'tenant_id' => $po->tenant_id,

                       'product_id' => $line->product_id,

                       'variant_id' => $line->variant_id,

                       'location_id' => $location->id,

                   ], [

                       'uom_id' => $line->uom_id,

                       'unit_cost' => $line->unit_price,

                   ])->increment('quantity_on_hand', $line->ordered_qty);



                   // Cost Layer

                   InventoryCostLayer::create([

                       'tenant_id' => $po->tenant_id,

                       'product_id' => $line->product_id,

                       'location_id' => $location->id,

                       'valuation_method' => $line->product->valuation_method,

                       'layer_date' => $grn->received_date,

                       'quantity_in' => $line->ordered_qty,

                       'quantity_remaining' => $line->ordered_qty,

                       'unit_cost' => $line->unit_price,

                       'reference_type' => StockMovement::class,

                       'reference_id' => $grn->id,

                   ]);



                   $totalCost += $line->ordered_qty \* $line->unit_price;

               }



               // Journal Entry (Dr Inventory, Cr GR/IR)

               $journalEntry = JournalEntry::create([

                   'tenant_id' => $po->tenant_id,

                   'fiscal_period_id' => $fiscalPeriod->id,

                   'entry_type' => 'auto',

                   'reference_type' => GrnHeader::class,

                   'reference_id' => $grn->id,

                   'entry_date' => $grn->received_date,

                   'status' => 'posted',

                   'created_by' => $user->id,

                   'posted_by' => $user->id,

                   'posted_at' => now(),

               ]);

               JournalEntryLine::create(['journal_entry_id' => $journalEntry->id, 'account_id' => $inventoryAccount->id, 'debit_amount' => $totalCost]);

               JournalEntryLine::create(['journal_entry_id' => $journalEntry->id, 'account_id' => $grIrAccount->id, 'credit_amount' => $totalCost]);



               $po->update(['status' => 'received']);

               $grn->update(['status' => 'posted']);

           });

       }

   }

}

```

---



## PurchaseInvoiceSeeder.php – Create invoices and clear GR/IR


```


<?php

namespace Modules\Purchase\Database\Seeders;



use Illuminate\Database\Seeder;

use Modules\Purchase\Infrastructure\Persistence\Eloquent\GrnHeader;

use Modules\Purchase\Infrastructure\Persistence\Eloquent\PurchaseInvoice;

use Modules\Purchase\Infrastructure\Persistence\Eloquent\PurchaseInvoiceLine;

use Modules\Finance\Infrastructure\Persistence\Eloquent\JournalEntry;

use Modules\Finance\Infrastructure\Persistence\Eloquent\JournalEntryLine;

use Modules\Finance\Infrastructure\Persistence\Eloquent\Account;

use Modules\Finance\Infrastructure\Persistence\Eloquent\FiscalPeriod;

use Modules\Core\Infrastructure\Persistence\Eloquent\Currency;

use Illuminate\Support\Facades\DB;



class PurchaseInvoiceSeeder extends Seeder

{

   public function run(): void

   {

       $grns = GrnHeader::where('status', 'posted')->get();

       $usd = Currency::where('code', 'USD')->first();

       $apAccount = Account::where('code', '2000')->first();

       $grIrAccount = Account::where('code', '1500')->first();

       $fiscalPeriod = FiscalPeriod::where('status', 'open')->first();



       foreach ($grns as $grn) {

           DB::transaction(function () use ($grn, $usd, $apAccount, $grIrAccount, $fiscalPeriod) {

               $total = $grn->lines->sum('line_cost');



               $invoice = PurchaseInvoice::create([

                   'tenant_id' => $grn->tenant_id,

                   'supplier_id' => $grn->supplier_id,

                   'grn_header_id' => $grn->id,

                   'purchase_order_id' => $grn->purchase_order_id,

                   'invoice_number' => 'INV-' . $grn->grn_number,

                   'supplier_invoice_number' => 'SUPP-INV-' . rand(1000,9999),

                   'status' => 'approved',

                   'invoice_date' => now()->subDays(1),

                   'due_date' => now()->addDays(30),

                   'currency_id' => $usd->id,

                   'subtotal' => $total,

                   'grand_total' => $total,

                   'ap_account_id' => $apAccount->id,

               ]);



               foreach ($grn->lines as $line) {

                   PurchaseInvoiceLine::create([

                       'purchase_invoice_id' => $invoice->id,

                       'grn_line_id' => $line->id,

                       'product_id' => $line->product_id,

                       'uom_id' => $line->uom_id,

                       'quantity' => $line->received_qty,

                       'unit_price' => $line->unit_cost,

                       'line_total' => $line->line_cost,

                   ]);

               }



               // Journal Entry: Dr GR/IR, Cr AP

               $journalEntry = JournalEntry::create([

                   'tenant_id' => $grn->tenant_id,

                   'fiscal_period_id' => $fiscalPeriod->id,

                   'entry_type' => 'auto',

                   'reference_type' => PurchaseInvoice::class,

                   'reference_id' => $invoice->id,

                   'entry_date' => $invoice->invoice_date,

                   'status' => 'posted',

                   'created_by' => $grn->created_by,

                   'posted_by' => $grn->created_by,

                   'posted_at' => now(),

               ]);

               JournalEntryLine::create(['journal_entry_id' => $journalEntry->id, 'account_id' => $grIrAccount->id, 'debit_amount' => $total]);

               JournalEntryLine::create(['journal_entry_id' => $journalEntry->id, 'account_id' => $apAccount->id, 'credit_amount' => $total]);



               $invoice->update(['journal_entry_id' => $journalEntry->id]);

           });

       }

   }

}

```

---



## PurchasePaymentSeeder.php – Pay supplier invoices

```

<?php

namespace Modules\Purchase\Database\Seeders;



use Illuminate\Database\Seeder;

use Modules\Purchase\Infrastructure\Persistence\Eloquent\PurchaseInvoice;

use Modules\Finance\Infrastructure\Persistence\Eloquent\Payment;

use Modules\Finance\Infrastructure\Persistence\Eloquent\PaymentMethod;

use Modules\Finance\Infrastructure\Persistence\Eloquent\PaymentAllocation;

use Modules\Finance\Infrastructure\Persistence\Eloquent\JournalEntry;

use Modules\Finance\Infrastructure\Persistence\Eloquent\JournalEntryLine;

use Modules\Finance\Infrastructure\Persistence\Eloquent\Account;

use Modules\Finance\Infrastructure\Persistence\Eloquent\FiscalPeriod;

use Modules\Core\Infrastructure\Persistence\Eloquent\Currency;

use Modules\User\Infrastructure\Persistence\Eloquent\User;



class PurchasePaymentSeeder extends Seeder

{

   public function run(): void

   {

       $invoices = PurchaseInvoice::where('status', 'approved')->get();

       $usd = Currency::where('code', 'USD')->first();

       $bankAccount = Account::where('is_bank_account', true)->first();

       $apAccount = Account::where('code', '2000')->first();

       $fiscalPeriod = FiscalPeriod::where('status', 'open')->first();



       foreach ($invoices as $invoice) {

           $user = User::where('tenant_id', $invoice->tenant_id)->first();

           $method = PaymentMethod::firstOrCreate(['tenant_id' => $invoice->tenant_id, 'name' => 'Bank Transfer'], ['type' => 'bank_transfer', 'account_id' => $bankAccount->id, 'is_active' => true]);



           $payment = Payment::create([

               'tenant_id' => $invoice->tenant_id,

               'payment_number' => 'PAY-OUT-' . date('Ymd') . '-' . $invoice->id,

               'direction' => 'outbound',

               'party_type' => 'supplier',

               'party_id' => $invoice->supplier_id,

               'payment_method_id' => $method->id,

               'account_id' => $bankAccount->id,

               'amount' => $invoice->grand_total,

               'currency_id' => $usd->id,

               'exchange_rate' => 1,

               'base_amount' => $invoice->grand_total,

               'payment_date' => now(),

               'status' => 'posted',

           ]);



           PaymentAllocation::create([

               'payment_id' => $payment->id,

               'invoice_type' => PurchaseInvoice::class,

               'invoice_id' => $invoice->id,

               'allocated_amount' => $invoice->grand_total,

           ]);



           // Journal Entry: Dr AP, Cr Bank

           $journalEntry = JournalEntry::create([

               'tenant_id' => $invoice->tenant_id,

               'fiscal_period_id' => $fiscalPeriod->id,

               'entry_type' => 'auto',

               'reference_type' => Payment::class,

               'reference_id' => $payment->id,

               'entry_date' => $payment->payment_date,

               'status' => 'posted',

               'created_by' => $user->id,

               'posted_by' => $user->id,

               'posted_at' => now(),

           ]);

           JournalEntryLine::create(['journal_entry_id' => $journalEntry->id, 'account_id' => $apAccount->id, 'debit_amount' => $payment->amount]);

           JournalEntryLine::create(['journal_entry_id' => $journalEntry->id, 'account_id' => $bankAccount->id, 'credit_amount' => $payment->amount]);



           $payment->update(['journal_entry_id' => $journalEntry->id]);

           $invoice->update(['status' => 'paid', 'paid_amount' => $invoice->grand_total]);

       }

   }

}

```

---



## PurchaseReturnWithOriginalSeeder.php – Return goods to supplier with original GRN reference

```

<?php

namespace Modules\Purchase\Database\Seeders;



use Illuminate\Database\Seeder;

use Modules\Purchase\Infrastructure\Persistence\Eloquent\GrnHeader;

use Modules\Purchase\Infrastructure\Persistence\Eloquent\PurchaseReturn;

use Modules\Purchase\Infrastructure\Persistence\Eloquent\PurchaseReturnLine;

use Modules\Inventory\Infrastructure\Persistence\Eloquent\StockMovement;

use Modules\Inventory\Infrastructure\Persistence\Eloquent\StockLevel;

use Modules\Inventory\Infrastructure\Persistence\Eloquent\InventoryCostLayer;

use Modules\Finance\Infrastructure\Persistence\Eloquent\JournalEntry;

use Modules\Finance\Infrastructure\Persistence\Eloquent\JournalEntryLine;

use Modules\Finance\Infrastructure\Persistence\Eloquent\Account;

use Modules\Finance\Infrastructure\Persistence\Eloquent\FiscalPeriod;

use Modules\Finance\Infrastructure\Persistence\Eloquent\CreditMemo;

use Modules\Warehouse\Infrastructure\Persistence\Eloquent\WarehouseLocation;

use Modules\User\Infrastructure\Persistence\Eloquent\User;

use Modules\Core\Infrastructure\Persistence\Eloquent\Currency;

use Illuminate\Support\Facades\DB;



class PurchaseReturnWithOriginalSeeder extends Seeder

{

   public function run(): void

   {

       $grns = GrnHeader::where('status', 'posted')->take(1)->get(); // Only one example

       $usd = Currency::where('code', 'USD')->first();

       $apAccount = Account::where('code', '2000')->first();

       $inventoryAccount = Account::where('code', '1300')->first();

       $fiscalPeriod = FiscalPeriod::where('status', 'open')->first();



       foreach ($grns as $grn) {

           DB::transaction(function () use ($grn, $usd, $apAccount, $inventoryAccount, $fiscalPeriod) {

               $user = User::where('tenant_id', $grn->tenant_id)->first();

               $returnLocation = WarehouseLocation::where('warehouse_id', $grn->warehouse_id)->where('type', 'bin')->first();



               $purchaseReturn = PurchaseReturn::create([

                   'tenant_id' => $grn->tenant_id,

                   'supplier_id' => $grn->supplier_id,

                   'original_grn_id' => $grn->id,

                   'return_number' => 'PR-' . date('Ymd') . '-001',

                   'status' => 'approved',

                   'return_date' => now(),

                   'return_reason' => 'Damaged items',

                   'currency_id' => $usd->id,

               ]);



               $totalReturnCost = 0;

               foreach ($grn->lines->take(1) as $line) { // Return part of first line

                   $returnQty = ceil($line->received_qty \* 0.2); // 20% return

                   $lineCost = $returnQty \* $line->unit_cost;

                   $totalReturnCost += $lineCost;



                   $returnLine = PurchaseReturnLine::create([

                       'purchase_return_id' => $purchaseReturn->id,

                       'original_grn_line_id' => $line->id,

                       'product_id' => $line->product_id,

                       'from_location_id' => $line->location_id,

                       'uom_id' => $line->uom_id,

                       'return_qty' => $returnQty,

                       'unit_cost' => $line->unit_cost,

                       'condition' => 'damaged',

                       'disposition' => 'return_to_vendor',

                   ]);



                   // Stock Movement (return_out)

                   StockMovement::create([

                       'tenant_id' => $grn->tenant_id,

                       'product_id' => $line->product_id,

                       'from_location_id' => $line->location_id,

                       'movement_type' => 'return_out',

                       'reference_type' => PurchaseReturn::class,

                       'reference_id' => $purchaseReturn->id,

                       'uom_id' => $line->uom_id,

                       'quantity' => $returnQty,

                       'unit_cost' => $line->unit_cost,

                       'performed_by' => $user->id,

                   ]);



                   // Reduce stock level

                   StockLevel::where([

                       'tenant_id' => $grn->tenant_id,

                       'product_id' => $line->product_id,

                       'location_id' => $line->location_id,

                   ])->decrement('quantity_on_hand', $returnQty);



                   // Adjust cost layer (remove from original layer)

                   $layer = InventoryCostLayer::where('reference_id', $grn->id)

                       ->where('product_id', $line->product_id)

                       ->where('quantity_remaining', '>', 0)

                       ->first();

                   if ($layer) {

                       $layer->decrement('quantity_remaining', $returnQty);

                   }

               }



               $purchaseReturn->update(['subtotal' => $totalReturnCost, 'grand_total' => $totalReturnCost]);



               // Journal Entry: Dr AP, Cr Inventory

               $journalEntry = JournalEntry::create([

                   'tenant_id' => $grn->tenant_id,

                   'fiscal_period_id' => $fiscalPeriod->id,

                   'entry_type' => 'auto',

                   'reference_type' => PurchaseReturn::class,

                   'reference_id' => $purchaseReturn->id,

                   'entry_date' => $purchaseReturn->return_date,

                   'status' => 'posted',

                   'created_by' => $user->id,

                   'posted_by' => $user->id,

                   'posted_at' => now(),

               ]);

               JournalEntryLine::create(['journal_entry_id' => $journalEntry->id, 'account_id' => $apAccount->id, 'debit_amount' => $totalReturnCost]);

               JournalEntryLine::create(['journal_entry_id' => $journalEntry->id, 'account_id' => $inventoryAccount->id, 'credit_amount' => $totalReturnCost]);



               $purchaseReturn->update(['journal_entry_id' => $journalEntry->id]);



               // Debit Note / Credit Memo

               CreditMemo::create([

                   'tenant_id' => $grn->tenant_id,

                   'party_type' => 'supplier',

                   'party_id' => $grn->supplier_id,

                   'return_order_type' => PurchaseReturn::class,

                   'return_order_id' => $purchaseReturn->id,

                   'credit_memo_number' => 'DN-' . $purchaseReturn->return_number,

                   'amount' => $totalReturnCost,

                   'status' => 'issued',

                   'issued_date' => now(),

                   'journal_entry_id' => $journalEntry->id,

               ]);

           });

       }

   }

}

```

---



## DirectGrnSeeder.php – GRN without PO (SMB scenario)

```

<?php

namespace Modules\Purchase\Database\Seeders;



use Illuminate\Database\Seeder;

use Modules\Purchase\Infrastructure\Persistence\Eloquent\GrnHeader;

use Modules\Purchase\Infrastructure\Persistence\Eloquent\GrnLine;

use Modules\Inventory\Infrastructure\Persistence\Eloquent\StockMovement;

use Modules\Inventory\Infrastructure\Persistence\Eloquent\StockLevel;

use Modules\Inventory\Infrastructure\Persistence\Eloquent\InventoryCostLayer;

use Modules\Finance\Infrastructure\Persistence\Eloquent\JournalEntry;

use Modules\Finance\Infrastructure\Persistence\Eloquent\JournalEntryLine;

use Modules\Finance\Infrastructure\Persistence\Eloquent\Account;

use Modules\Finance\Infrastructure\Persistence\Eloquent\FiscalPeriod;

use Modules\Supplier\Infrastructure\Persistence\Eloquent\Supplier;

use Modules\Product\Infrastructure\Persistence\Eloquent\Product;

use Modules\Warehouse\Infrastructure\Persistence\Eloquent\Warehouse;

use Modules\Warehouse\Infrastructure\Persistence\Eloquent\WarehouseLocation;

use Modules\User\Infrastructure\Persistence\Eloquent\User;

use Modules\Core\Infrastructure\Persistence\Eloquent\Tenant;

use Modules\Core\Infrastructure\Persistence\Eloquent\Currency;

use Illuminate\Support\Facades\DB;



class DirectGrnSeeder extends Seeder

{

   public function run(): void

   {

       $tenants = Tenant::all();

       $usd = Currency::where('code', 'USD')->first();

       $inventoryAccount = Account::where('code', '1300')->first();

       $apAccount = Account::where('code', '2000')->first();

       $fiscalPeriod = FiscalPeriod::where('status', 'open')->first();



       foreach ($tenants as $tenant) {

           DB::transaction(function () use ($tenant, $usd, $inventoryAccount, $apAccount, $fiscalPeriod) {

               $supplier = Supplier::where('tenant_id', $tenant->id)->first();

               $warehouse = Warehouse::where('tenant_id', $tenant->id)->where('is_default', true)->first();

               $user = User::where('tenant_id', $tenant->id)->first();

               $product = Product::where('tenant_id', $tenant->id)->where('type', 'physical')->first();

               $location = WarehouseLocation::where('warehouse_id', $warehouse->id)->where('type', 'bin')->first();

               if (!$supplier || !$warehouse || !$user || !$product || !$location) return;



               // Direct GRN (no PO)

               $grn = GrnHeader::create([

                   'tenant_id' => $tenant->id,

                   'supplier_id' => $supplier->id,

                   'warehouse_id' => $warehouse->id,

                   'purchase_order_id' => null,

                   'grn_number' => 'GRN-DIRECT-' . date('Ymd'),

                   'status' => 'complete',

                   'received_date' => now()->subDays(2),

                   'currency_id' => $usd->id,

                   'created_by' => $user->id,

               ]);



               $qty = 15;

               $unitCost = 8.75;

               GrnLine::create([

                   'grn_header_id' => $grn->id,

                   'product_id' => $product->id,

                   'location_id' => $location->id,

                   'uom_id' => $product->base_uom_id,

                   'received_qty' => $qty,

                   'unit_cost' => $unitCost,

               ]);



               // Stock Movement \& Level

               StockMovement::create([...]); // similar to above

               StockLevel::updateOrCreate([...])->increment('quantity_on_hand', $qty);

               InventoryCostLayer::create([...]);



               // Journal Entry: Dr Inventory, Cr AP (direct to AP since no GR/IR)

               $total = $qty \* $unitCost;

               $journalEntry = JournalEntry::create([...]);

               JournalEntryLine::create(['journal_entry_id' => $journalEntry->id, 'account_id' => $inventoryAccount->id, 'debit_amount' => $total]);

               JournalEntryLine::create(['journal_entry_id' => $journalEntry->id, 'account_id' => $apAccount->id, 'credit_amount' => $total]);

           });

       }

   }

}

```

---



## Sales Module Seeders (Complete Scenarios)

```

<?php

namespace Modules\Sales\Database\Seeders;



use Illuminate\Database\Seeder;

use Modules\Sales\Infrastructure\Persistence\Eloquent\SalesOrder;

use Modules\Sales\Infrastructure\Persistence\Eloquent\SalesOrderLine;

use Modules\Customer\Infrastructure\Persistence\Eloquent\Customer;

use Modules\Product\Infrastructure\Persistence\Eloquent\Product;

use Modules\Warehouse\Infrastructure\Persistence\Eloquent\Warehouse;

use Modules\User\Infrastructure\Persistence\Eloquent\User;

use Modules\Core\Infrastructure\Persistence\Eloquent\Tenant;

use Modules\Core\Infrastructure\Persistence\Eloquent\Currency;



class SalesOrderSeeder extends Seeder

{

   public function run(): void

   {

       $tenants = Tenant::all();

       $usd = Currency::where('code', 'USD')->first();



       foreach ($tenants as $tenant) {

           $customer = Customer::where('tenant_id', $tenant->id)->first();

           $warehouse = Warehouse::where('tenant_id', $tenant->id)->where('is_default', true)->first();

           $user = User::where('tenant_id', $tenant->id)->first();

           if (!$customer || !$warehouse || !$user) continue;



           $so = SalesOrder::create([

               'tenant_id' => $tenant->id,

               'customer_id' => $customer->id,

               'org_unit_id' => $warehouse->org_unit_id,

               'warehouse_id' => $warehouse->id,

               'so_number' => 'SO-' . date('Ymd') . '-001',

               'status' => 'confirmed',

               'currency_id' => $usd->id,

               'order_date' => now()->subDays(5),

               'created_by' => $user->id,

               'approved_by' => $user->id,

           ]);



           $products = Product::where('tenant_id', $tenant->id)->where('type', 'physical')->take(2)->get();

           $subtotal = 0;

           foreach ($products as $product) {

               $qty = rand(2, 10);

               $price = 29.99;

               $lineTotal = $qty \* $price;

               $subtotal += $lineTotal;

               SalesOrderLine::create([

                   'sales_order_id' => $so->id,

                   'product_id' => $product->id,

                   'uom_id' => $product->base_uom_id,

                   'ordered_qty' => $qty,

                   'unit_price' => $price,

                   'line_total' => $lineTotal,

                   'reserved_qty' => $qty,

               ]);

           }

           $so->update(['subtotal' => $subtotal, 'grand_total' => $subtotal]);

       }

   }

}

```

---



## ShipmentFromSoSeeder.php – Ship against SO (stock issue \& COGS)

```

<?php

namespace Modules\Sales\Database\Seeders;



use Illuminate\Database\Seeder;

use Modules\Sales\Infrastructure\Persistence\Eloquent\SalesOrder;

use Modules\Sales\Infrastructure\Persistence\Eloquent\Shipment;

use Modules\Sales\Infrastructure\Persistence\Eloquent\ShipmentLine;

use Modules\Inventory\Infrastructure\Persistence\Eloquent\StockMovement;

use Modules\Inventory\Infrastructure\Persistence\Eloquent\StockLevel;

use Modules\Inventory\Infrastructure\Persistence\Eloquent\InventoryCostLayer;

use Modules\Finance\Infrastructure\Persistence\Eloquent\JournalEntry;

use Modules\Finance\Infrastructure\Persistence\Eloquent\JournalEntryLine;

use Modules\Finance\Infrastructure\Persistence\Eloquent\Account;

use Modules\Finance\Infrastructure\Persistence\Eloquent\FiscalPeriod;

use Modules\Warehouse\Infrastructure\Persistence\Eloquent\WarehouseLocation;

use Modules\User\Infrastructure\Persistence\Eloquent\User;

use Modules\Core\Infrastructure\Persistence\Eloquent\Currency;

use Illuminate\Support\Facades\DB;



class ShipmentFromSoSeeder extends Seeder

{

   public function run(): void

   {

       $orders = SalesOrder::where('status', 'confirmed')->get();

       $usd = Currency::where('code', 'USD')->first();

       $cogsAccount = Account::where('code', '5000')->first();

       $inventoryAccount = Account::where('code', '1300')->first();

       $fiscalPeriod = FiscalPeriod::where('status', 'open')->first();



       foreach ($orders as $so) {

           DB::transaction(function () use ($so, $usd, $cogsAccount, $inventoryAccount, $fiscalPeriod) {

               $user = User::where('tenant_id', $so->tenant_id)->first();

               $location = WarehouseLocation::where('warehouse_id', $so->warehouse_id)->where('type', 'bin')->first();

               if (!$user || !$location) return;



               $shipment = Shipment::create([

                   'tenant_id' => $so->tenant_id,

                   'customer_id' => $so->customer_id,

                   'sales_order_id' => $so->id,

                   'warehouse_id' => $so->warehouse_id,

                   'shipment_number' => 'SHIP-' . $so->so_number,

                   'status' => 'shipped',

                   'shipped_date' => now()->subDays(1),

                   'currency_id' => $usd->id,

               ]);



               $totalCogs = 0;

               foreach ($so->lines as $line) {

                   $shipmentLine = ShipmentLine::create([

                       'shipment_id' => $shipment->id,

                       'sales_order_line_id' => $line->id,

                       'product_id' => $line->product_id,

                       'from_location_id' => $location->id,

                       'uom_id' => $line->uom_id,

                       'shipped_qty' => $line->ordered_qty,

                   ]);



                   // Allocate cost layer (FIFO)

                   $layer = InventoryCostLayer::where('product_id', $line->product_id)

                       ->where('location_id', $location->id)

                       ->where('quantity_remaining', '>', 0)

                       ->orderBy('layer_date')

                       ->first();

                   $unitCost = $layer ? $layer->unit_cost : 10.00;

                   $cogs = $line->ordered_qty \* $unitCost;

                   $totalCogs += $cogs;



                   // Stock Movement

                   StockMovement::create([

                       'tenant_id' => $so->tenant_id,

                       'product_id' => $line->product_id,

                       'from_location_id' => $location->id,

                       'movement_type' => 'shipment',

                       'reference_type' => Shipment::class,

                       'reference_id' => $shipment->id,

                       'uom_id' => $line->uom_id,

                       'quantity' => $line->ordered_qty,

                       'unit_cost' => $unitCost,

                       'performed_by' => $user->id,

                   ]);



                   // Reduce stock

                   StockLevel::where(['product_id' => $line->product_id, 'location_id' => $location->id])

                       ->decrement('quantity_on_hand', $line->ordered_qty);



                   // Consume cost layer

                   if ($layer) {

                       $layer->decrement('quantity_remaining', $line->ordered_qty);

                   }



                   $line->update(['shipped_qty' => $line->ordered_qty]);

               }



               // COGS Journal Entry

               $journalEntry = JournalEntry::create([...]);

               JournalEntryLine::create(['journal_entry_id' => $journalEntry->id, 'account_id' => $cogsAccount->id, 'debit_amount' => $totalCogs]);

               JournalEntryLine::create(['journal_entry_id' => $journalEntry->id, 'account_id' => $inventoryAccount->id, 'credit_amount' => $totalCogs]);



               $so->update(['status' => 'shipped']);

           });

       }

   }

}

```

---



## SalesInvoiceSeeder.php, SalesPaymentSeeder.php – Similar to purchase but for AR/revenue.



// SalesInvoiceSeeder creates invoice, posts Dr AR, Cr Revenue.

// SalesPaymentSeeder receives payment, posts Dr Bank, Cr AR.



---



## DirectSaleSeeder.php – Shipment without SO (SMB)



// Create shipment with sales_order_id = null, directly issue stock, create invoice optionally.



---



## SalesReturnWithOriginalSeeder.php – Return with original SO reference

```

<?php

namespace Modules\Sales\Database\Seeders;



use Illuminate\Database\Seeder;

use Modules\Sales\Infrastructure\Persistence\Eloquent\SalesOrder;

use Modules\Sales\Infrastructure\Persistence\Eloquent\SalesReturn;

use Modules\Sales\Infrastructure\Persistence\Eloquent\SalesReturnLine;

use Modules\Inventory\Infrastructure\Persistence\Eloquent\StockMovement;

use Modules\Inventory\Infrastructure\Persistence\Eloquent\StockLevel;

use Modules\Inventory\Infrastructure\Persistence\Eloquent\InventoryCostLayer;

use Modules\Finance\Infrastructure\Persistence\Eloquent\JournalEntry;

use Modules\Finance\Infrastructure\Persistence\Eloquent\JournalEntryLine;

use Modules\Finance\Infrastructure\Persistence\Eloquent\Account;

use Modules\Finance\Infrastructure\Persistence\Eloquent\FiscalPeriod;

use Modules\Finance\Infrastructure\Persistence\Eloquent\CreditMemo;

use Modules\Warehouse\Infrastructure\Persistence\Eloquent\WarehouseLocation;

use Modules\User\Infrastructure\Persistence\Eloquent\User;

use Modules\Core\Infrastructure\Persistence\Eloquent\Currency;

use Illuminate\Support\Facades\DB;



class SalesReturnWithOriginalSeeder extends Seeder

{

   public function run(): void

   {

       $orders = SalesOrder::where('status', 'shipped')->take(1)->get();

       $usd = Currency::where('code', 'USD')->first();

       $salesReturnsAccount = Account::firstOrCreate(['code' => '4100', 'name' => 'Sales Returns', 'type' => 'revenue', 'normal_balance' => 'debit']);

       $arAccount = Account::where('code', '1200')->first();

       $inventoryAccount = Account::where('code', '1300')->first();

       $cogsAccount = Account::where('code', '5000')->first();

       $fiscalPeriod = FiscalPeriod::where('status', 'open')->first();



       foreach ($orders as $so) {

           DB::transaction(function () use ($so, $usd, $salesReturnsAccount, $arAccount, $inventoryAccount, $cogsAccount, $fiscalPeriod) {

               $user = User::where('tenant_id', $so->tenant_id)->first();

               $restockLocation = WarehouseLocation::where('warehouse_id', $so->warehouse_id)->where('type', 'bin')->first();



               $salesReturn = SalesReturn::create([

                   'tenant_id' => $so->tenant_id,

                   'customer_id' => $so->customer_id,

                   'original_sales_order_id' => $so->id,

                   'return_number' => 'SR-' . date('Ymd') . '-001',

                   'status' => 'approved',

                   'return_date' => now(),

                   'return_reason' => 'Wrong size',

                   'currency_id' => $usd->id,

               ]);



               $totalRevenueDebit = 0;

               $totalInventoryCredit = 0;



               foreach ($so->lines->take(1) as $line) {

                   $returnQty = ceil($line->ordered_qty \* 0.3); // 30% return

                   $revenueDebit = $returnQty \* $line->unit_price;

                   $totalRevenueDebit += $revenueDebit;



                   // Original cost from shipment movement

                   $shipMovement = StockMovement::where('reference_type', Shipment::class)

                       ->where('product_id', $line->product_id)

                       ->orderBy('performed_at', 'desc')

                       ->first();

                   $originalCost = $shipMovement ? $shipMovement->unit_cost : 10.00;

                   $inventoryCredit = $returnQty \* $originalCost;

                   $totalInventoryCredit += $inventoryCredit;



                   $returnLine = SalesReturnLine::create([

                       'sales_return_id' => $salesReturn->id,

                       'original_sales_order_line_id' => $line->id,

                       'product_id' => $line->product_id,

                       'to_location_id' => $restockLocation->id,

                       'uom_id' => $line->uom_id,

                       'return_qty' => $returnQty,

                       'unit_price' => $line->unit_price,

                       'condition' => 'good',

                       'disposition' => 'restock',

                   ]);



                   // Stock Movement (return_in)

                   StockMovement::create([

                       'tenant_id' => $so->tenant_id,

                       'product_id' => $line->product_id,

                       'to_location_id' => $restockLocation->id,

                       'movement_type' => 'return_in',

                       'reference_type' => SalesReturn::class,

                       'reference_id' => $salesReturn->id,

                       'uom_id' => $line->uom_id,

                       'quantity' => $returnQty,

                       'unit_cost' => $originalCost,

                       'performed_by' => $user->id,

                   ]);



                   // Increase stock

                   StockLevel::updateOrCreate([

                       'tenant_id' => $so->tenant_id,

                       'product_id' => $line->product_id,

                       'location_id' => $restockLocation->id,

                   ], ['uom_id' => $line->uom_id])->increment('quantity_on_hand', $returnQty);



                   // Re-insert cost layer

                   InventoryCostLayer::create([

                       'tenant_id' => $so->tenant_id,

                       'product_id' => $line->product_id,

                       'location_id' => $restockLocation->id,

                       'valuation_method' => $line->product->valuation_method,

                       'layer_date' => now(),

                       'quantity_in' => $returnQty,

                       'quantity_remaining' => $returnQty,

                       'unit_cost' => $originalCost,

                       'reference_type' => SalesReturn::class,

                       'reference_id' => $salesReturn->id,

                   ]);

               }



               $salesReturn->update(['subtotal' => $totalRevenueDebit, 'grand_total' => $totalRevenueDebit]);



               // Journal Entry

               $journalEntry = JournalEntry::create([...]);

               // Dr Sales Returns, Cr AR

               JournalEntryLine::create(['journal_entry_id' => $journalEntry->id, 'account_id' => $salesReturnsAccount->id, 'debit_amount' => $totalRevenueDebit]);

               JournalEntryLine::create(['journal_entry_id' => $journalEntry->id, 'account_id' => $arAccount->id, 'credit_amount' => $totalRevenueDebit]);

               // Dr Inventory, Cr COGS (reversal)

               JournalEntryLine::create(['journal_entry_id' => $journalEntry->id, 'account_id' => $inventoryAccount->id, 'debit_amount' => $totalInventoryCredit]);

               JournalEntryLine::create(['journal_entry_id' => $journalEntry->id, 'account_id' => $cogsAccount->id, 'credit_amount' => $totalInventoryCredit]);



               $salesReturn->update(['journal_entry_id' => $journalEntry->id]);



               // Credit Memo

               CreditMemo::create([

                   'tenant_id' => $so->tenant_id,

                   'party_type' => 'customer',

                   'party_id' => $so->customer_id,

                   'return_order_type' => SalesReturn::class,

                   'return_order_id' => $salesReturn->id,

                   'credit_memo_number' => 'CM-' . $salesReturn->return_number,

                   'amount' => $totalRevenueDebit,

                   'status' => 'issued',

                   'issued_date' => now(),

                   'journal_entry_id' => $journalEntry->id,

               ]);

           });

       }

   }

}

```

---



## SalesReturnRestockingFeeSeeder.php – Return with restocking fee



// Similar to above but adds restocking_fee to line and separate revenue account.

// Journal entry includes restocking fee as credit to Restocking Fee Revenue.



---



## SalesReturnRefundSeeder.php – Refund credit memo as cash



// Creates a payment (outbound) linked to a credit memo, posts Dr AR/CreditMemo Liability, Cr Bank.





---



## SalesReturnWithoutOriginalSeeder.php – Return without original reference



// No original_sales_order_id; uses current average cost for inventory restock.



---



## Purchase Order Lifecycle Seeder

```

<?php

namespace Modules\Purchase\Database\Seeders;



use Illuminate\Database\Seeder;

use Modules\Purchase\Infrastructure\Persistence\Eloquent\PurchaseOrder;

use Modules\Purchase\Infrastructure\Persistence\Eloquent\PurchaseOrderLine;

use Modules\Supplier\Infrastructure\Persistence\Eloquent\Supplier;

use Modules\Product\Infrastructure\Persistence\Eloquent\Product;

use Modules\Warehouse\Infrastructure\Persistence\Eloquent\Warehouse;

use Modules\User\Infrastructure\Persistence\Eloquent\User;

use Modules\Core\Infrastructure\Persistence\Eloquent\Tenant;

use Modules\Core\Infrastructure\Persistence\Eloquent\Currency;



class PurchaseOrderLifecycleSeeder extends Seeder

{

   public function run(): void

   {

       $tenant = Tenant::first();

       $supplier = Supplier::where('tenant_id', $tenant->id)->first();

       $warehouse = Warehouse::where('tenant_id', $tenant->id)->first();

       $user = User::where('tenant_id', $tenant->id)->first();

       $product = Product::where('tenant_id', $tenant->id)->first();

       $usd = Currency::where('code', 'USD')->first();



       if (!$supplier || !$warehouse || !$user || !$product) return;



       // 1. CREATE Draft PO

       $draftPo = PurchaseOrder::create([

           'tenant_id' => $tenant->id,

           'supplier_id' => $supplier->id,

           'warehouse_id' => $warehouse->id,

           'po_number' => 'PO-DRAFT-001',

           'status' => 'draft',

           'currency_id' => $usd->id,

           'order_date' => now(),

           'created_by' => $user->id,

       ]);

       PurchaseOrderLine::create([

           'purchase_order_id' => $draftPo->id,

           'product_id' => $product->id,

           'uom_id' => $product->base_uom_id,

           'ordered_qty' => 10,

           'unit_price' => 15.00,

       ]);



       // 2. UPDATE to Cancelled (soft delete demonstration)

       $draftPo->update(['status' => 'cancelled']);

       $draftPo->delete(); // soft delete



       // 3. CREATE another PO that goes through full flow

       $activePo = PurchaseOrder::create([

           'tenant_id' => $tenant->id,

           'supplier_id' => $supplier->id,

           'warehouse_id' => $warehouse->id,

           'po_number' => 'PO-ACTIVE-001',

           'status' => 'draft',

           'currency_id' => $usd->id,

           'order_date' => now()->subDays(2),

           'created_by' => $user->id,

       ]);

       PurchaseOrderLine::create([

           'purchase_order_id' => $activePo->id,

           'product_id' => $product->id,

           'uom_id' => $product->base_uom_id,

           'ordered_qty' => 20,

           'unit_price' => 15.00,

       ]);



       // UPDATE to confirmed

       $activePo->update([

           'status' => 'confirmed',

           'approved_by' => $user->id,

       ]);

   }

}

```

---



## Sales Order Lifecycle Seeder

```

<?php

namespace Modules\Sales\Database\Seeders;



use Illuminate\Database\Seeder;

use Modules\Sales\Infrastructure\Persistence\Eloquent\SalesOrder;

use Modules\Sales\Infrastructure\Persistence\Eloquent\SalesOrderLine;

use Modules\Customer\Infrastructure\Persistence\Eloquent\Customer;

use Modules\Product\Infrastructure\Persistence\Eloquent\Product;

use Modules\Warehouse\Infrastructure\Persistence\Eloquent\Warehouse;

use Modules\User\Infrastructure\Persistence\Eloquent\User;

use Modules\Core\Infrastructure\Persistence\Eloquent\Tenant;

use Modules\Core\Infrastructure\Persistence\Eloquent\Currency;



class SalesOrderLifecycleSeeder extends Seeder

{

   public function run(): void

   {

       $tenant = Tenant::first();

       $customer = Customer::where('tenant_id', $tenant->id)->first();

       $warehouse = Warehouse::where('tenant_id', $tenant->id)->first();

       $user = User::where('tenant_id', $tenant->id)->first();

       $product = Product::where('tenant_id', $tenant->id)->first();

       $usd = Currency::where('code', 'USD')->first();



       if (!$customer || !$warehouse || !$user || !$product) return;



       // CREATE draft SO

       $draftSo = SalesOrder::create([

           'tenant_id' => $tenant->id,

           'customer_id' => $customer->id,

           'warehouse_id' => $warehouse->id,

           'so_number' => 'SO-DRAFT-001',

           'status' => 'draft',

           'currency_id' => $usd->id,

           'order_date' => now(),

           'created_by' => $user->id,

       ]);

       SalesOrderLine::create([

           'sales_order_id' => $draftSo->id,

           'product_id' => $product->id,

           'uom_id' => $product->base_uom_id,

           'ordered_qty' => 5,

           'unit_price' => 49.99,

       ]);



       // UPDATE to Cancelled \& soft delete

       $draftSo->update(['status' => 'cancelled']);

       $draftSo->delete();



       // CREATE another SO for full flow

       $activeSo = SalesOrder::create([

           'tenant_id' => $tenant->id,

           'customer_id' => $customer->id,

           'warehouse_id' => $warehouse->id,

           'so_number' => 'SO-ACTIVE-001',

           'status' => 'draft',

           'currency_id' => $usd->id,

           'order_date' => now()->subDays(3),

           'created_by' => $user->id,

       ]);

       SalesOrderLine::create([

           'sales_order_id' => $activeSo->id,

           'product_id' => $product->id,

           'uom_id' => $product->base_uom_id,

           'ordered_qty' => 8,

           'unit_price' => 49.99,

       ]);



       // UPDATE to confirmed

       $activeSo->update([

           'status' => 'confirmed',

           'approved_by' => $user->id,

       ]);

   }

}

```

---



## Invoice Lifecycle Seeder (Purchase \& Sales)

```

<?php

namespace Modules\Purchase\Database\Seeders;



use Illuminate\Database\Seeder;

use Modules\Purchase\Infrastructure\Persistence\Eloquent\PurchaseInvoice;

use Modules\Purchase\Infrastructure\Persistence\Eloquent\PurchaseInvoiceLine;

use Modules\Purchase\Infrastructure\Persistence\Eloquent\GrnHeader;

use Modules\Finance\Infrastructure\Persistence\Eloquent\JournalEntry;

use Modules\Finance\Infrastructure\Persistence\Eloquent\JournalEntryLine;

use Modules\Finance\Infrastructure\Persistence\Eloquent\Account;

use Modules\Finance\Infrastructure\Persistence\Eloquent\FiscalPeriod;

use Modules\User\Infrastructure\Persistence\Eloquent\User;



class PurchaseInvoiceLifecycleSeeder extends Seeder

{

   public function run(): void

   {

       $grn = GrnHeader::where('status', 'posted')->first();

       if (!$grn) return;



       $user = User::where('tenant_id', $grn->tenant_id)->first();

       $apAccount = Account::where('code', '2000')->first();

       $grIrAccount = Account::where('code', '1500')->first();

       $fiscalPeriod = FiscalPeriod::where('status', 'open')->first();



       // CREATE draft invoice

       $invoice = PurchaseInvoice::create([

           'tenant_id' => $grn->tenant_id,

           'supplier_id' => $grn->supplier_id,

           'grn_header_id' => $grn->id,

           'invoice_number' => 'INV-DRAFT-001',

           'status' => 'draft',

           'invoice_date' => now(),

           'due_date' => now()->addDays(30),

           'currency_id' => $grn->currency_id,

           'subtotal' => 1000.00,

           'grand_total' => 1000.00,

       ]);



       // Add line

       $line = $grn->lines->first();

       PurchaseInvoiceLine::create([

           'purchase_invoice_id' => $invoice->id,

           'grn_line_id' => $line->id,

           'product_id' => $line->product_id,

           'uom_id' => $line->uom_id,

           'quantity' => $line->received_qty,

           'unit_price' => $line->unit_cost,

           'line_total' => $line->line_cost,

       ]);



       // UPDATE to approved (with journal entry)

       $invoice->update(['status' => 'approved']);

       $journalEntry = JournalEntry::create([

           'tenant_id' => $grn->tenant_id,

           'fiscal_period_id' => $fiscalPeriod->id,

           'entry_type' => 'auto',

           'reference_type' => PurchaseInvoice::class,

           'reference_id' => $invoice->id,

           'entry_date' => $invoice->invoice_date,

           'status' => 'posted',

           'created_by' => $user->id,

           'posted_by' => $user->id,

           'posted_at' => now(),

       ]);

       JournalEntryLine::create(['journal_entry_id' => $journalEntry->id, 'account_id' => $grIrAccount->id, 'debit_amount' => 1000.00]);

       JournalEntryLine::create(['journal_entry_id' => $journalEntry->id, 'account_id' => $apAccount->id, 'credit_amount' => 1000.00]);

       $invoice->update(['journal_entry_id' => $journalEntry->id]);



       // CREATE another invoice that will be voided (soft delete)

       $voidInvoice = PurchaseInvoice::create([

           'tenant_id' => $grn->tenant_id,

           'supplier_id' => $grn->supplier_id,

           'invoice_number' => 'INV-VOID-001',

           'status' => 'cancelled',

           'invoice_date' => now(),

           'due_date' => now()->addDays(30),

           'currency_id' => $grn->currency_id,

           'subtotal' => 500.00,

           'grand_total' => 500.00,

       ]);

       $voidInvoice->delete(); // soft delete

   }

}

```

---



## PurchaseCreateSeeder.php – Create PO, confirm, receive (GRN), invoice

```

<?php

namespace Database\Seeders\Purchase;



use Illuminate\Database\Seeder;

use Modules\Purchase\Models\PurchaseOrder;

use Modules\Purchase\Models\PurchaseOrderLine;

use Modules\Purchase\Models\GrnHeader;

use Modules\Purchase\Models\GrnLine;

use Modules\Purchase\Models\PurchaseInvoice;

use Modules\Purchase\Models\PurchaseInvoiceLine;

use Modules\Supplier\Models\Supplier;

use Modules\Product\Models\Product;

use Modules\Warehouse\Models\Warehouse;

use Modules\Warehouse\Models\WarehouseLocation;

use Modules\User\Models\User;

use Modules\Core\Models\Tenant;

use Modules\Finance\Models\Account;

use Modules\Finance\Models\FiscalPeriod;

use Modules\Finance\Models\JournalEntry;

use Modules\Finance\Models\JournalEntryLine;

use Modules\Inventory\Models\StockMovement;

use Modules\Inventory\Models\StockLevel;

use Modules\Inventory\Models\InventoryCostLayer;

use Illuminate\Support\Facades\DB;



class PurchaseCreateSeeder extends Seeder

{

   public function run(): void

   {

       $tenant = Tenant::first();

       $supplier = Supplier::where('tenant_id', $tenant->id)->first();

       $warehouse = Warehouse::where('tenant_id', $tenant->id)->first();

       $user = User::where('tenant_id', $tenant->id)->first();

       $product = Product::where('tenant_id', $tenant->id)->first();

       $location = WarehouseLocation::where('warehouse_id', $warehouse->id)->first();

       $usd = \Modules\Core\Models\Currency::where('code', 'USD')->first();

       $inventoryAccount = Account::where('code', '1300')->first();

       $apAccount = Account::where('code', '2000')->first();

       $grIrAccount = Account::where('code', '1500')->first();

       $fiscalPeriod = FiscalPeriod::where('status', 'open')->first();



       DB::transaction(function () use ($tenant, $supplier, $warehouse, $user, $product, $location, $usd, $inventoryAccount, $apAccount, $grIrAccount, $fiscalPeriod) {

           // CREATE Purchase Order

           $po = PurchaseOrder::create([

               'tenant_id' => $tenant->id,

               'supplier_id' => $supplier->id,

               'warehouse_id' => $warehouse->id,

               'po_number' => 'PO-CREATE-001',

               'status' => 'draft',

               'currency_id' => $usd->id,

               'order_date' => now(),

               'expected_date' => now()->addDays(7),

               'created_by' => $user->id,

           ]);



           PurchaseOrderLine::create([

               'purchase_order_id' => $po->id,

               'product_id' => $product->id,

               'uom_id' => $product->base_uom_id,

               'ordered_qty' => 25,

               'unit_price' => 12.50,

               'line_total' => 312.50,

           ]);



           // UPDATE: Confirm PO

           $po->update(['status' => 'confirmed', 'approved_by' => $user->id]);



           // CREATE Goods Receipt (GRN)

           $grn = GrnHeader::create([

               'tenant_id' => $tenant->id,

               'supplier_id' => $supplier->id,

               'warehouse_id' => $warehouse->id,

               'purchase_order_id' => $po->id,

               'grn_number' => 'GRN-CREATE-001',

               'status' => 'complete',

               'received_date' => now(),

               'currency_id' => $usd->id,

               'created_by' => $user->id,

           ]);



           $line = $po->lines->first();

           GrnLine::create([

               'grn_header_id' => $grn->id,

               'purchase_order_line_id' => $line->id,

               'product_id' => $line->product_id,

               'location_id' => $location->id,

               'uom_id' => $line->uom_id,

               'expected_qty' => $line->ordered_qty,

               'received_qty' => $line->ordered_qty,

               'unit_cost' => $line->unit_price,

               'line_cost' => $line->ordered_qty \* $line->unit_price,

           ]);



           // Stock Movement (receipt)

           StockMovement::create([

               'tenant_id' => $tenant->id,

               'product_id' => $product->id,

               'to_location_id' => $location->id,

               'movement_type' => 'receipt',

               'reference_type' => GrnHeader::class,

               'reference_id' => $grn->id,

               'uom_id' => $product->base_uom_id,

               'quantity' => 25,

               'unit_cost' => 12.50,

               'performed_by' => $user->id,

           ]);



           // Update Stock Level

           StockLevel::updateOrCreate([

               'tenant_id' => $tenant->id,

               'product_id' => $product->id,

               'location_id' => $location->id,

           ], ['uom_id' => $product->base_uom_id])

               ->increment('quantity_on_hand', 25);



           // Cost Layer

           InventoryCostLayer::create([

               'tenant_id' => $tenant->id,

               'product_id' => $product->id,

               'location_id' => $location->id,

               'valuation_method' => 'fifo',

               'layer_date' => now(),

               'quantity_in' => 25,

               'quantity_remaining' => 25,

               'unit_cost' => 12.50,

               'reference_type' => GrnHeader::class,

               'reference_id' => $grn->id,

           ]);



           // Journal Entry: Dr Inventory, Cr GR/IR

           $je = JournalEntry::create([

               'tenant_id' => $tenant->id,

               'fiscal_period_id' => $fiscalPeriod->id,

               'entry_type' => 'auto',

               'reference_type' => GrnHeader::class,

               'reference_id' => $grn->id,

               'entry_date' => now(),

               'status' => 'posted',

               'created_by' => $user->id,

               'posted_by' => $user->id,

               'posted_at' => now(),

           ]);

           JournalEntryLine::create(['journal_entry_id' => $je->id, 'account_id' => $inventoryAccount->id, 'debit_amount' => 312.50]);

           JournalEntryLine::create(['journal_entry_id' => $je->id, 'account_id' => $grIrAccount->id, 'credit_amount' => 312.50]);



           // CREATE Purchase Invoice

           $invoice = PurchaseInvoice::create([

               'tenant_id' => $tenant->id,

               'supplier_id' => $supplier->id,

               'grn_header_id' => $grn->id,

               'invoice_number' => 'INV-CREATE-001',

               'status' => 'approved',

               'invoice_date' => now(),

               'due_date' => now()->addDays(30),

               'currency_id' => $usd->id,

               'subtotal' => 312.50,

               'grand_total' => 312.50,

               'ap_account_id' => $apAccount->id,

           ]);

           PurchaseInvoiceLine::create([

               'purchase_invoice_id' => $invoice->id,

               'grn_line_id' => $grn->lines->first()->id,

               'product_id' => $product->id,

               'uom_id' => $product->base_uom_id,

               'quantity' => 25,

               'unit_price' => 12.50,

               'line_total' => 312.50,

           ]);



           // Journal Entry: Dr GR/IR, Cr AP

           $je2 = JournalEntry::create([...]);

           JournalEntryLine::create(['journal_entry_id' => $je2->id, 'account_id' => $grIrAccount->id, 'debit_amount' => 312.50]);

           JournalEntryLine::create(['journal_entry_id' => $je2->id, 'account_id' => $apAccount->id, 'credit_amount' => 312.50]);

           $invoice->update(['journal_entry_id' => $je2->id]);

       });

   }

}

```

---



## PurchaseUpdateSeeder.php – Demonstrate updates: PO quantity change, invoice status change, payment

```

<?php

// Updates an existing PO line quantity, then updates invoice to paid.

public function run(): void

{

   $po = PurchaseOrder::where('po_number', 'PO-CREATE-001')->first();

   if ($po) {

       // UPDATE line quantity

       $line = $po->lines->first();

       $line->update(['ordered_qty' => 30, 'line_total' => 30 \* $line->unit_price]);

       $po->update(['subtotal' => 30 \* $line->unit_price, 'grand_total' => 30 \* $line->unit_price]);



       // UPDATE invoice to paid (via payment)

       $invoice = PurchaseInvoice::where('invoice_number', 'INV-CREATE-001')->first();

       // Create payment and allocate...

       $invoice->update(['status' => 'paid']);

   }

}

```

---



## SalesReturnCreateSeeder.php – Create and approve a sales return with original reference, restock, credit memo

```

<?php

namespace Database\Seeders\Returns;



use Illuminate\Database\Seeder;

use Modules\Sales\Models\SalesOrder;

use Modules\Sales\Models\SalesReturn;

use Modules\Sales\Models\SalesReturnLine;

use Modules\Inventory\Models\StockMovement;

use Modules\Inventory\Models\StockLevel;

use Modules\Inventory\Models\InventoryCostLayer;

use Modules\Finance\Models\JournalEntry;

use Modules\Finance\Models\JournalEntryLine;

use Modules\Finance\Models\CreditMemo;

use Modules\Finance\Models\Account;

use Modules\Warehouse\Models\WarehouseLocation;

use Modules\User\Models\User;

use Illuminate\Support\Facades\DB;



class SalesReturnCreateSeeder extends Seeder

{

   public function run(): void

   {

       $so = SalesOrder::where('status', 'shipped')->first();

       if (!$so) return;



       DB::transaction(function () use ($so) {

           $user = User::where('tenant_id', $so->tenant_id)->first();

           $restockLocation = WarehouseLocation::where('warehouse_id', $so->warehouse_id)->first();

           $salesReturnsAccount = Account::where('code', '4100')->first();

           $arAccount = Account::where('code', '1200')->first();

           $inventoryAccount = Account::where('code', '1300')->first();

           $cogsAccount = Account::where('code', '5000')->first();



           $salesReturn = SalesReturn::create([

               'tenant_id' => $so->tenant_id,

               'customer_id' => $so->customer_id,

               'original_sales_order_id' => $so->id,

               'return_number' => 'SR-CREATE-001',

               'status' => 'draft',

               'return_date' => now(),

               'return_reason' => 'Defective',

               'currency_id' => $so->currency_id,

           ]);



           $line = $so->lines->first();

           $returnQty = 2;

           $revenueDebit = $returnQty \* $line->unit_price;



           $returnLine = SalesReturnLine::create([

               'sales_return_id' => $salesReturn->id,

               'original_sales_order_line_id' => $line->id,

               'product_id' => $line->product_id,

               'to_location_id' => $restockLocation->id,

               'uom_id' => $line->uom_id,

               'return_qty' => $returnQty,

               'unit_price' => $line->unit_price,

               'condition' => 'defective',

               'disposition' => 'restock',

               'restocking_fee' => 5.00,

           ]);



           // Approve return

           $salesReturn->update(['status' => 'approved']);



           // Stock Movement (return_in)

           StockMovement::create([... 'movement_type' => 'return_in', 'quantity' => $returnQty, 'unit_cost' => 10.00]);



           // Update stock level

           StockLevel::updateOrCreate([...])->increment('quantity_on_hand', $returnQty);



           // Re-insert cost layer

           InventoryCostLayer::create([... 'quantity_in' => $returnQty, 'unit_cost' => 10.00]);



           // Journal Entry

           $je = JournalEntry::create([...]);

           JournalEntryLine::create(['journal_entry_id' => $je->id, 'account_id' => $salesReturnsAccount->id, 'debit_amount' => $revenueDebit]);

           JournalEntryLine::create(['journal_entry_id' => $je->id, 'account_id' => $arAccount->id, 'credit_amount' => $revenueDebit + 5.00]);

           JournalEntryLine::create(['journal_entry_id' => $je->id, 'account_id' => $inventoryAccount->id, 'debit_amount' => $returnQty \* 10.00]);

           JournalEntryLine::create(['journal_entry_id' => $je->id, 'account_id' => $cogsAccount->id, 'credit_amount' => $returnQty \* 10.00]);



           // Credit Memo

           CreditMemo::create([... 'amount' => $revenueDebit + 5.00, 'status' => 'issued']);



           $salesReturn->update(['status' => 'closed']);

       });

   }

}

```

---



## Laravel Migrations (Full Production Code)

```

// 1. Create parties table

Schema::create('parties', function (Blueprint $table) {

   $table->id();

   $table->enum('party_type', ['supplier', 'customer', 'both']);

   $table->string('name');

   $table->string('tax_id')->nullable();

   $table->string('email')->nullable();

   $table->string('phone')->nullable();

   $table->string('website')->nullable();

   $table->boolean('is_active')->default(true);

   $table->timestamps();

   $table->softDeletes();

});



// 2. Party addresses

Schema::create('party_addresses', function (Blueprint $table) {

   $table->id();

   $table->foreignId('party_id')->constrained('parties')->restrictOnDelete();

   $table->enum('address_type', ['billing', 'shipping', 'legal']);

   $table->string('line1');

   $table->string('line2')->nullable();

   $table->string('city');

   $table->string('state')->nullable();

   $table->string('postal_code')->nullable();

   $table->string('country');

   $table->boolean('is_default')->default(false);

   $table->timestamps();

});



// 3. UOMs

Schema::create('uoms', function (Blueprint $table) {

   $table->id();

   $table->string('code', 10)->unique();

   $table->string('name', 50);

   $table->string('category', 50);

   $table->timestamps();

});



// 4. Product categories

Schema::create('product_categories', function (Blueprint $table) {

   $table->id();

   $table->foreignId('parent_id')->nullable()->constrained('product_categories')->nullOnDelete();

   $table->string('name');

   $table->string('slug')->unique();

   $table->timestamps();

});



// 5. Products

Schema::create('products', function (Blueprint $table) {

   $table->id();

   $table->string('sku')->unique();

   $table->string('name');

   $table->text('description')->nullable();

   $table->enum('product_type', ['simple', 'variant_parent', 'bundle', 'digital', 'service']);

   $table->boolean('is_stockable')->default(true);

   $table->boolean('is_tracked_batch')->default(false);

   $table->boolean('is_tracked_serial')->default(false);

   $table->decimal('weight', 12, 4)->nullable();

   $table->foreignId('weight_uom_id')->nullable()->constrained('uoms')->nullOnDelete();

   $table->foreignId('category_id')->nullable()->constrained('product_categories')->nullOnDelete();

   $table->timestamps();

   $table->softDeletes();

});



// 6. Product variants

Schema::create('product_variants', function (Blueprint $table) {

   $table->id();

   $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();

   $table->string('sku')->unique();

   $table->json('attributes'); // e.g. {"color":"red"}

   $table->string('barcode', 100)->nullable()->unique();

   $table->boolean('is_active')->default(true);

   $table->timestamps();

});



// 7. Product UOM conversions

Schema::create('product_uom_conversions', function (Blueprint $table) {

   $table->id();

   $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();

   $table->foreignId('from_uom_id')->constrained('uoms')->restrictOnDelete();

   $table->foreignId('to_uom_id')->constrained('uoms')->restrictOnDelete();

   $table->decimal('factor', 20, 10);

   $table->unique(['product_id', 'from_uom_id', 'to_uom_id']);

   $table->timestamps();

});



// 8. Warehouses \& storage locations

Schema::create('warehouses', function (Blueprint $table) {

   $table->id();

   $table->string('code', 50)->unique();

   $table->string('name');

   $table->text('address')->nullable();

   $table->boolean('is_active')->default(true);

   $table->timestamps();

});



Schema::create('storage_locations', function (Blueprint $table) {

   $table->id();

   $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete();

   $table->string('code', 100);

   $table->string('barcode', 100)->nullable();

   $table->timestamps();

   $table->unique(['warehouse_id', 'code']);

});



// 9. Batches \& serial numbers

Schema::create('batches', function (Blueprint $table) {

   $table->id();

   $table->foreignId('product_id')->constrained('products')->restrictOnDelete();

   $table->string('batch_number', 100);

   $table->string('manufacturer_batch', 100)->nullable();

   $table->date('expiry_date')->nullable();

   $table->date('manufacture_date')->nullable();

   $table->string('barcode', 100)->nullable();

   $table->boolean('is_active')->default(true);

   $table->timestamps();

   $table->unique(['product_id', 'batch_number']);

});



Schema::create('serial_numbers', function (Blueprint $table) {

   $table->id();

   $table->foreignId('product_id')->constrained('products')->restrictOnDelete();

   $table->foreignId('variant_id')->nullable()->constrained('product_variants')->nullOnDelete();

   $table->foreignId('batch_id')->nullable()->constrained('batches')->nullOnDelete();

   $table->string('serial_number', 100)->unique();

   $table->enum('status', ['in_stock', 'sold', 'returned', 'scrapped'])->default('in_stock');

   $table->foreignId('current_location_id')->nullable()->constrained('storage_locations')->nullOnDelete();

   $table->timestamps();

});



// 10. Purchase side

Schema::create('purchase_orders', function (Blueprint $table) {

   $table->id();

   $table->string('po_number', 50)->unique();

   $table->foreignId('supplier_id')->constrained('parties')->restrictOnDelete();

   $table->date('order_date');

   $table->date('expected_date')->nullable();

   $table->enum('status', ['draft', 'confirmed', 'partially_received', 'received', 'cancelled'])->default('draft');

   $table->decimal('total_amount', 15, 2);

   $table->char('currency', 3)->default('USD');

   $table->text('notes')->nullable();

   $table->timestamps();

});



Schema::create('purchase_order_lines', function (Blueprint $table) {

   $table->id();

   $table->foreignId('purchase_order_id')->constrained('purchase_orders')->cascadeOnDelete();

   $table->foreignId('product_id')->constrained('products')->restrictOnDelete();

   $table->foreignId('variant_id')->nullable()->constrained('product_variants')->nullOnDelete();

   $table->foreignId('uom_id')->constrained('uoms')->restrictOnDelete();

   $table->decimal('quantity', 15, 5);

   $table->decimal('unit_price', 15, 5);

   $table->decimal('discount_percent', 8, 2)->default(0);

   $table->decimal('tax_rate', 8, 4)->default(0);

   $table->decimal('total_line', 15, 2);

   $table->timestamps();

});



Schema::create('purchase_receipts', function (Blueprint $table) {

   $table->id();

   $table->string('receipt_number', 50)->unique();

   $table->foreignId('purchase_order_id')->nullable()->constrained('purchase_orders')->nullOnDelete();

   $table->foreignId('supplier_id')->constrained('parties')->restrictOnDelete();

   $table->dateTime('receipt_date');

   $table->foreignId('warehouse_id')->constrained('warehouses')->restrictOnDelete();

   $table->enum('status', ['draft', 'completed', 'cancelled'])->default('draft');

   $table->text('notes')->nullable();

   $table->timestamps();

});



Schema::create('purchase_receipt_lines', function (Blueprint $table) {

   $table->id();

   $table->foreignId('receipt_id')->constrained('purchase_receipts')->cascadeOnDelete();

   $table->foreignId('po_line_id')->nullable()->constrained('purchase_order_lines')->nullOnDelete();

   $table->foreignId('product_id')->constrained('products')->restrictOnDelete();

   $table->foreignId('variant_id')->nullable()->constrained('product_variants')->nullOnDelete();

   $table->foreignId('uom_id')->constrained('uoms')->restrictOnDelete();

   $table->decimal('quantity', 15, 5);

   $table->foreignId('batch_id')->nullable()->constrained('batches')->nullOnDelete();

   $table->text('serial_numbers')->nullable(); // comma separated

   $table->foreignId('storage_location_id')->nullable()->constrained('storage_locations')->nullOnDelete();

   $table->timestamps();

});



// 11. Sales side (mirror structure)

Schema::create('sales_orders', function (Blueprint $table) {

   $table->id();

   $table->string('so_number', 50)->unique();

   $table->foreignId('customer_id')->constrained('parties')->restrictOnDelete();

   $table->date('order_date');

   $table->date('requested_date')->nullable();

   $table->enum('status', ['draft', 'confirmed', 'partially_delivered', 'delivered', 'cancelled'])->default('draft');

   $table->decimal('total_amount', 15, 2);

   $table->char('currency', 3)->default('USD');

   $table->timestamps();

});



Schema::create('sales_order_lines', function (Blueprint $table) {

   $table->id();

   $table->foreignId('sales_order_id')->constrained('sales_orders')->cascadeOnDelete();

   $table->foreignId('product_id')->constrained('products');

   $table->foreignId('variant_id')->nullable()->constrained('product_variants');

   $table->foreignId('uom_id')->constrained('uoms');

   $table->decimal('quantity', 15, 5);

   $table->decimal('unit_price', 15, 5);

   $table->decimal('discount_percent', 8, 2)->default(0);

   $table->decimal('tax_rate', 8, 4)->default(0);

   $table->decimal('total_line', 15, 2);

   $table->timestamps();

});



Schema::create('sales_deliveries', function (Blueprint $table) {

   $table->id();

   $table->string('delivery_number', 50)->unique();

   $table->foreignId('sales_order_id')->nullable()->constrained('sales_orders')->nullOnDelete();

   $table->foreignId('customer_id')->constrained('parties');

   $table->dateTime('delivery_date');

   $table->foreignId('warehouse_id')->constrained('warehouses');

   $table->enum('status', ['draft', 'shipped', 'delivered', 'cancelled'])->default('draft');

   $table->timestamps();

});



Schema::create('sales_delivery_lines', function (Blueprint $table) {

   $table->id();

   $table->foreignId('delivery_id')->constrained('sales_deliveries')->cascadeOnDelete();

   $table->foreignId('so_line_id')->nullable()->constrained('sales_order_lines')->nullOnDelete();

   $table->foreignId('product_id')->constrained('products');

   $table->foreignId('variant_id')->nullable()->constrained('product_variants');

   $table->foreignId('uom_id')->constrained('uoms');

   $table->decimal('quantity', 15, 5);

   $table->foreignId('batch_id')->nullable()->constrained('batches');

   $table->text('serial_numbers')->nullable();

   $table->foreignId('storage_location_id')->nullable()->constrained('storage_locations');

   $table->timestamps();

});



// 12. Returns (purchase \& sales) – similar pattern, omitted for brevity.



// 13. Stock movements (core inventory)

Schema::create('stock_movements', function (Blueprint $table) {

   $table->id();

   $table->enum('movement_type', ['purchase_receipt', 'sales_delivery', 'purchase_return', 'sales_return', 'adjustment', 'transfer']);

   $table->string('reference_type', 50); // polymorphic

   $table->unsignedBigInteger('reference_id');

   $table->foreignId('product_id')->constrained('products');

   $table->foreignId('variant_id')->nullable()->constrained('product_variants');

   $table->foreignId('from_location_id')->nullable()->constrained('storage_locations');

   $table->foreignId('to_location_id')->nullable()->constrained('storage_locations');

   $table->foreignId('batch_id')->nullable()->constrained('batches');

   $table->foreignId('serial_id')->nullable()->constrained('serial_numbers');

   $table->decimal('quantity', 15, 5);

   $table->foreignId('uom_id')->constrained('uoms');

   $table->dateTime('movement_date');

   $table->foreignId('created_by')->nullable()->constrained('users');

   $table->timestamps();



   $table->index(['reference_type', 'reference_id']);

});



// 14. Accounting

Schema::create('accounts', function (Blueprint $table) {

   $table->id();

   $table->string('code', 20)->unique();

   $table->string('name');

   $table->enum('account_type', ['asset', 'liability', 'equity', 'revenue', 'expense']);

   $table->foreignId('parent_id')->nullable()->constrained('accounts')->nullOnDelete();

   $table->boolean('is_control')->default(false);

   $table->boolean('is_active')->default(true);

   $table->timestamps();

});



Schema::create('journal_entries', function (Blueprint $table) {

   $table->id();

   $table->string('entry_number', 50)->unique();

   $table->date('entry_date');

   $table->string('reference_type', 50); // polymorphic

   $table->unsignedBigInteger('reference_id')->nullable();

   $table->text('description')->nullable();

   $table->boolean('is_posted')->default(false);

   $table->timestamp('posted_at')->nullable();

   $table->timestamps();



   $table->index(['reference_type', 'reference_id']);

});



Schema::create('journal_entry_lines', function (Blueprint $table) {

   $table->id();

   $table->foreignId('journal_entry_id')->constrained('journal_entries')->cascadeOnDelete();

   $table->foreignId('account_id')->constrained('accounts')->restrictOnDelete();

   $table->decimal('debit', 15, 2)->default(0);

   $table->decimal('credit', 15, 2)->default(0);

   $table->text('memo')->nullable();

   $table->timestamps();



   $table->check('debit >= 0 and credit >= 0');

});



Schema::create('payments', function (Blueprint $table) {

   $table->id();

   $table->string('payment_number', 50)->unique();

   $table->foreignId('party_id')->constrained('parties');

   $table->enum('payment_type', ['supplier_payment', 'customer_receipt']);

   $table->decimal('amount', 15, 2);

   $table->date('payment_date');

   $table->string('reference', 255)->nullable();

   $table->enum('status', ['pending', 'completed', 'failed'])->default('pending');

   $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();

   $table->timestamps();

});



// 15. Current stock balances (for performance)

Schema::create('current_stock_balances', function (Blueprint $table) {

   $table->id();

   $table->foreignId('product_id')->constrained('products');

   $table->foreignId('variant_id')->nullable()->constrained('product_variants');

   $table->foreignId('warehouse_id')->constrained('warehouses');

   $table->foreignId('storage_location_id')->nullable()->constrained('storage_locations');

   $table->foreignId('batch_id')->nullable()->constrained('batches');

   $table->decimal('quantity_on_hand', 15, 5)->default(0);

   $table->decimal('quantity_reserved', 15, 5)->default(0);

   $table->timestamp('last_updated')->useCurrent();

   $table->unique(['product_id', 'variant_id', 'warehouse_id', 'storage_location_id', 'batch_id'], 'stock_balance_unique');

});

```

---



## PROCUREMENT FLOW



Supplier → PURCHASE → Document Line → Inventory Ledger (IN)

        → Journal Entry (Dr Inventory / Cr Payable)



## SALES FLOW



Customer → SALE → Document Line → Inventory Ledger (OUT)

        → Journal Entry (Dr Receivable / Cr Revenue)



---



## Purchase



Document(type=purchase)

→ Line

→ Inventory IN

→ Accounting:

   DR Inventory

   CR Accounts Payable



## Sale



Document(type=sale)

→ Line

→ Inventory OUT

→ Accounting:

   DR COGS

   CR Inventory

   DR Accounts Receivable

   CR Revenue



---


```


<?php

// app/Enums/TenantStatus.php

enum TenantStatus: string

{

   case ACTIVE = 'active';

   case SUSPENDED = 'suspended';

   case TRIAL = 'trial';



   public static function values(): array

   {

       return array_column(self::cases(), 'value');

   }

}



<?php

// app/Enums/PartyType.php

enum PartyType: string

{

   case INDIVIDUAL = 'individual';

   case ORGANIZATION = 'organization';



   public static function values(): array

   {

       return array_column(self::cases(), 'value');

   }

}



<?php

// app/Enums/PartyStatus.php

enum PartyStatus: string

{

   case ACTIVE = 'active';

   case INACTIVE = 'inactive';

   case BLOCKED = 'blocked';



   public static function values(): array

   {

       return array_column(self::cases(), 'value');

   }

}



<?php

// app/Enums/ContactType.php

enum ContactType: string

{

   case EMAIL = 'email';

   case PHONE = 'phone';

   case MOBILE = 'mobile';

   case FAX = 'fax';

   case WEBSITE = 'website';



   public static function values(): array

   {

       return array_column(self::cases(), 'value');

   }

}



<?php

// app/Enums/AddressType.php

enum AddressType: string

{

   case BILLING = 'billing';

   case SHIPPING = 'shipping';

   case POSTAL = 'postal';

   case PHYSICAL = 'physical';

   case REGISTERED = 'registered';



   public static function values(): array

   {

       return array_column(self::cases(), 'value');

   }

}



<?php

// app/Enums/OrganizationUnitType.php

enum OrganizationUnitType: string

{

   case COMPANY = 'company';

   case DIVISION = 'division';

   case DEPARTMENT = 'department';

   case WAREHOUSE = 'warehouse';

   case STORE = 'store';

   case COST_CENTER = 'cost_center';

   case PROFIT_CENTER = 'profit_center';



   public static function values(): array

   {

       return array_column(self::cases(), 'value');

   }

}



<?php

// app/Enums/ProductType.php

enum ProductType: string

{

   case PHYSICAL = 'physical';

   case SERVICE = 'service';

   case DIGITAL = 'digital';

   case COMBO = 'combo';

   case VARIABLE = 'variable';



   public static function values(): array

   {

       return array_column(self::cases(), 'value');

   }

}



<?php

// app/Enums/ValuationMethod.php

enum ValuationMethod: string

{

   case FIFO = 'FIFO';

   case LIFO = 'LIFO';

   case AVERAGE = 'AVERAGE';

   case STANDARD = 'STANDARD';



   public static function values(): array

   {

       return array_column(self::cases(), 'value');

   }

}



<?php

// app/Enums/UomType.php

enum UomType: string

{

   case WEIGHT = 'weight';

   case VOLUME = 'volume';

   case LENGTH = 'length';

   case AREA = 'area';

   case COUNT = 'count';

   case TIME = 'time';



   public static function values(): array

   {

       return array_column(self::cases(), 'value');

   }

}



<?php

// app/Enums/InventoryItemStatus.php

enum InventoryItemStatus: string

{

   case AVAILABLE = 'available';

   case RESERVED = 'reserved';

   case QUARANTINE = 'quarantine';

   case DAMAGED = 'damaged';

   case IN_TRANSIT = 'in_transit';



   public static function values(): array

   {

       return array_column(self::cases(), 'value');

   }

}



<?php

// app/Enums/InventoryMovementType.php

enum InventoryMovementType: string

{

   case RECEIPT = 'receipt';

   case ISSUE = 'issue';

   case TRANSFER_IN = 'transfer_in';

   case TRANSFER_OUT = 'transfer_out';

   case ADJUSTMENT = 'adjustment';

   case RETURN = 'return';

   case RESERVE = 'reserve';

   case UNRESERVE = 'unreserve';

   case CYCLE_COUNT = 'cycle_count';



   public static function values(): array

   {

       return array_column(self::cases(), 'value');

   }

}



<?php

// app/Enums/LocationType.php

enum LocationType: string

{

   case RECEIVING = 'receiving';

   case STORAGE = 'storage';

   case PICKING = 'picking';

   case SHIPPING = 'shipping';

   case QUALITY = 'quality';

   case QUARANTINE = 'quarantine';

   case RETURNS = 'returns';



   public static function values(): array

   {

       return array_column(self::cases(), 'value');

   }

}



<?php

// app/Enums/DocumentDirection.php

enum DocumentDirection: string

{

   case INBOUND = 'inbound';

   case OUTBOUND = 'outbound';

   case INTERNAL = 'internal';



   public static function values(): array

   {

       return array_column(self::cases(), 'value');

   }

}



<?php

// app/Enums/DocumentStatus.php

enum DocumentStatus: string

{

   case DRAFT = 'draft';

   case PENDING = 'pending';

   case APPROVED = 'approved';

   case POSTED = 'posted';

   case PARTIALLY_RECEIVED = 'partially_received';

   case RECEIVED = 'received';

   case PARTIALLY_INVOICED = 'partially_invoiced';

   case INVOICED = 'invoiced';

   case PAID = 'paid';

   case CANCELLED = 'cancelled';



   public static function values(): array

   {

       return array_column(self::cases(), 'value');

   }

}



<?php

// app/Enums/AccountType.php

enum AccountType: string

{

   case ASSET = 'asset';

   case LIABILITY = 'liability';

   case EQUITY = 'equity';

   case REVENUE = 'revenue';

   case EXPENSE = 'expense';

   case CONTRA_ASSET = 'contra_asset';

   case CONTRA_LIABILITY = 'contra_liability';

   case OTHER_INCOME = 'other_income';

   case OTHER_EXPENSE = 'other_expense';



   public static function values(): array

   {

       return array_column(self::cases(), 'value');

   }

}



<?php

// app/Enums/NormalBalance.php

enum NormalBalance: string

{

   case DEBIT = 'debit';

   case CREDIT = 'credit';



   public static function values(): array

   {

       return array_column(self::cases(), 'value');

   }

}



<?php

// app/Enums/AidcTagType.php

enum AidcTagType: string

{

   case BARCODE_1D = 'barcode_1d';

   case BARCODE_2D = 'barcode_2d';

   case QR = 'qr';

   case RFID_HF = 'rfid_hf';

   case RFID_UHF = 'rfid_uhf';

   case NFC = 'nfc';

   case GS1_EPC = 'gs1_epc';



   public static function values(): array

   {

       return array_column(self::cases(), 'value');

   }

}



<?php

// app/Enums/AidcEntityType.php

enum AidcEntityType: string

{

   case PRODUCT_VARIANT = 'product_variant';

   case INVENTORY_ITEM = 'inventory_item';

   case LOCATION = 'location';

   case DOCUMENT = 'document';

   case PARTY = 'party';

   case ASSET = 'asset';



   public static function values(): array

   {

       return array_column(self::cases(), 'value');

   }

}



use App\Enums\TenantStatus;



// Inside up()

$table->enum('status', TenantStatus::values())->default(TenantStatus::ACTIVE->value);



use App\Enums\PartyType;

use App\Enums\PartyStatus;



$table->enum('party_type', PartyType::values());

$table->enum('status', PartyStatus::values())->default(PartyStatus::ACTIVE->value);


```






