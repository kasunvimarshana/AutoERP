## Low Stock Query


```
Product::whereHas('stocks', function ($q) {

   $q->selectRaw('SUM(quantity) as total')

     ->havingRaw('total <= reorder\_level');

});
```



---





## BatchQueryService


```
class BatchQueryService

{

   public function getAvailableBatches(int $productId, int $warehouseId)

   {

       return \\DB::table('stocks')

           ->join('batches', 'stocks.batch\_id', '=', 'batches.id')

           ->where('stocks.product\_id', $productId)

           ->where('stocks.warehouse\_id', $warehouseId)

           ->where('stocks.quantity', '>', 0)

           ->whereDate('batches.expires\_at', '>', now())

           ->orderBy('batches.expires\_at', 'asc') // FEFO

           ->select(

               'stocks.id as stock\_id',

               'stocks.batch\_id',

               'stocks.quantity',

               'batches.expires\_at'

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

       $allocations = \[];

       $remaining = $requiredQty;



       foreach ($batches as $batch) {

           if ($remaining <= 0) break;



           $allocQty = min($batch->quantity, $remaining);



           if ($allocQty > 0) {

               $allocations\[] = new AllocationItem(

                   batchId: $batch->batch\_id,

                   allocatedQty: $allocQty

               );



               $remaining -= $allocQty;

           }

       }



       if ($remaining > 0) {

           throw new \\DomainException("Insufficient stock for allocation");

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



           $stock = \\DB::table('stocks')

               ->where('product\_id', $productId)

               ->where('batch\_id', $allocation->batchId)

               ->where('warehouse\_id', $warehouseId)

               ->lockForUpdate()

               ->first();



           if (!$stock || $stock->quantity < $allocation->allocatedQty) {

               throw new \\RuntimeException("Stock inconsistency detected");

           }



           // Update stock

           \\DB::table('stocks')

               ->where('id', $stock->id)

               ->update(\[

                   'quantity' => $stock->quantity - $allocation->allocatedQty,

                   'updated\_at' => now()

               ]);



           // Insert movement

           \\DB::table('stock\_movements')->insert(\[

               'product\_id'   => $productId,

               'batch\_id'     => $allocation->batchId,

               'warehouse\_id' => $warehouseId,

               'type'         => 'sale',

               'quantity'     => -$allocation->allocatedQty,

               'reference'    => $reference,

               'moved\_at'     => now(),

               'created\_at'   => now(),

               'updated\_at'   => now(),

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

   public function \_\_construct(

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

       return \\DB::transaction(function () use (

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
namespace App\\Http\\Controllers\\Api;



use App\\Services\\PurchaseOrderService;

use Illuminate\\Http\\Request;



class PurchaseOrderController extends BaseController

{

   public function \_\_construct(private PurchaseOrderService $service) {}



   public function store(Request $request)

   {

       $data = $request->validate(\[

           'supplier\_id' => 'required|exists:suppliers,id',

           'items' => 'required|array',

           'items.\*.product\_id' => 'required',

           'items.\*.quantity' => 'required|integer',

           'items.\*.batch\_number' => 'required',

           'items.\*.expires\_at' => 'required|date',

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
namespace App\\Http\\Controllers\\Api;



use App\\Services\\SaleService;

use Illuminate\\Http\\Request;



class SaleController extends BaseController

{

   public function \_\_construct(private SaleService $service) {}



   public function store(Request $request)

   {

       $data = $request->validate(\[

           'warehouse\_id' => 'required|exists:warehouses,id',

           'items' => 'required|array',

           'items.\*.product\_id' => 'required|exists:products,id',

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
namespace App\\Services;



use Illuminate\\Support\\Facades\\DB;



class BatchQueryService

{

   public function getAvailableBatches(int $productId, int $warehouseId)

   {

       return DB::table('stocks')

           ->join('batches', 'stocks.batch\_id', '=', 'batches.id')

           ->where('stocks.product\_id', $productId)

           ->where('stocks.warehouse\_id', $warehouseId)

           ->where('stocks.quantity', '>', 0)

           ->whereDate('batches.expires\_at', '>', now())

           ->orderBy('batches.expires\_at', 'asc')

           ->select('stocks.\*', 'batches.expires\_at')

           ->lockForUpdate()

           ->get();

   }

}
```


---



## StockAllocatorService (FEFO Strategy)


```
namespace App\\Services;



use App\\Services\\Contracts\\StockAllocationStrategy;

use DomainException;



class StockAllocatorService implements StockAllocationStrategy

{

   public function allocate($batches, int $requiredQty): array

   {

       $allocations = \[];

       $remaining = $requiredQty;



       foreach ($batches as $batch) {

           if ($remaining <= 0) break;



           $allocQty = min($batch->quantity, $remaining);



           if ($allocQty > 0) {

               $allocations\[] = \[

                   'batch\_id' => $batch->batch\_id,

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
namespace App\\Services;



use Illuminate\\Support\\Facades\\DB;



class StockService

{

   public function increase(int $productId, int $batchId, int $warehouseId, int $qty, string $ref)

   {

       $stock = DB::table('stocks')

           ->where(compact('productId','batchId','warehouseId'))

           ->lockForUpdate()

           ->first();



       if ($stock) {

           DB::table('stocks')->where('id', $stock->id)->update(\[

               'quantity' => $stock->quantity + $qty,

               'updated\_at' => now()

           ]);

       } else {

           DB::table('stocks')->insert(\[

               'product\_id' => $productId,

               'batch\_id' => $batchId,

               'warehouse\_id' => $warehouseId,

               'quantity' => $qty,

               'created\_at' => now(),

               'updated\_at' => now()

           ]);

       }



       $this->movement($productId, $batchId, $warehouseId, $qty, 'purchase', $ref);

   }



   public function decrease(int $productId, int $warehouseId, array $allocations, string $ref)

   {

       foreach ($allocations as $a) {



           $stock = DB::table('stocks')

               ->where(\[

                   'product\_id' => $productId,

                   'batch\_id' => $a\['batch\_id'],

                   'warehouse\_id' => $warehouseId

               ])

               ->lockForUpdate()

               ->first();



           if (!$stock || $stock->quantity < $a\['qty']) {

               throw new \\RuntimeException('Stock inconsistency');

           }



           DB::table('stocks')->where('id', $stock->id)->update(\[

               'quantity' => $stock->quantity - $a\['qty'],

               'updated\_at' => now()

           ]);



           $this->movement($productId, $a\['batch\_id'], $warehouseId, -$a\['qty'], 'sale', $ref);

       }

   }



   private function movement($productId, $batchId, $warehouseId, $qty, $type, $ref)

   {

       DB::table('stock\_movements')->insert(\[

           'product\_id' => $productId,

           'batch\_id' => $batchId,

           'warehouse\_id' => $warehouseId,

           'type' => $type,

           'quantity' => $qty,

           'reference' => $ref,

           'moved\_at' => now(),

           'created\_at' => now(),

           'updated\_at' => now(),

       ]);

   }

}

```

---



## StockQueryService (Read Optimized)

```

namespace App\\Services;



use Illuminate\\Support\\Facades\\DB;



class StockQueryService

{

   public function getCurrentStock()

   {

       return DB::table('stocks')

           ->join('products', 'stocks.product\_id', '=', 'products.id')

           ->select('products.name', DB::raw('SUM(stocks.quantity) as qty'))

           ->groupBy('products.name')

           ->get();

   }



   public function getLowStock()

   {

       return DB::table('products')

           ->join('stocks', 'products.id', '=', 'stocks.product\_id')

           ->groupBy('products.id')

           ->havingRaw('SUM(stocks.quantity) <= products.reorder\_level')

           ->select('products.name', DB::raw('SUM(stocks.quantity) as qty'))

           ->get();

   }



   public function getExpiringSoon(int $days = 30)

   {

       return DB::table('batches')

           ->whereDate('expires\_at', '<=', now()->addDays($days))

           ->get();

   }

}

```

---



## PurchaseOrderService


```
namespace App\\Services;



use Illuminate\\Support\\Facades\\DB;



class PurchaseOrderService

{

   public function \_\_construct(

       private StockService $stockService

   ) {}



   public function create(array $data)

   {

       return DB::transaction(function () use ($data) {



           $poId = DB::table('purchase\_orders')->insertGetId(\[

               'supplier\_id' => $data\['supplier\_id'],

               'po\_number' => uniqid('PO-'),

               'order\_date' => now(),

               'created\_at' => now(),

               'updated\_at' => now(),

           ]);



           foreach ($data\['items'] as $item) {



               $batchId = DB::table('batches')->insertGetId(\[

                   'product\_id' => $item\['product\_id'],

                   'batch\_number' => $item\['batch\_number'],

                   'expires\_at' => $item\['expires\_at'],

                   'purchase\_price' => $item\['purchase\_price'] ?? 0,

                   'selling\_price' => $item\['selling\_price'] ?? 0,

                   'created\_at' => now(),

                   'updated\_at' => now(),

               ]);



               DB::table('purchase\_order\_items')->insert(\[

                   'purchase\_order\_id' => $poId,

                   'product\_id' => $item\['product\_id'],

                   'batch\_id' => $batchId,

                   'quantity' => $item\['quantity'],

                   'unit\_price' => $item\['purchase\_price'] ?? 0,

                   'created\_at' => now(),

                   'updated\_at' => now(),

               ]);



               $this->stockService->increase(

                   $item\['product\_id'],

                   $batchId,

                   $data\['warehouse\_id'] ?? 1,

                   $item\['quantity'],

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

namespace App\\Services;



use Illuminate\\Support\\Facades\\DB;



class SaleService

{

   public function \_\_construct(

       private BatchQueryService $batchQuery,

       private StockAllocatorService $allocator,

       private StockService $stockService

   ) {}



   public function processSaleTransaction(array $data)

   {

       return DB::transaction(function () use ($data) {



           $saleId = DB::table('sales')->insertGetId(\[

               'invoice\_number' => uniqid('INV-'),

               'sale\_date' => now(),

               'total\_amount' => 0,

               'created\_at' => now(),

               'updated\_at' => now(),

           ]);



           foreach ($data\['items'] as $item) {



               $batches = $this->batchQuery->getAvailableBatches(

                   $item\['product\_id'],

                   $data\['warehouse\_id']

               );



               $allocations = $this->allocator->allocate(

                   $batches,

                   $item\['quantity']

               );



               $this->stockService->decrease(

                   $item\['product\_id'],

                   $data\['warehouse\_id'],

                   $allocations,

                   "SALE:$saleId"

               );



               foreach ($allocations as $a) {

                   DB::table('sale\_items')->insert(\[

                       'sale\_id' => $saleId,

                       'product\_id' => $item\['product\_id'],

                       'batch\_id' => $a\['batch\_id'],

                       'quantity' => $a\['qty'],

                       'unit\_price' => 0,

                       'created\_at' => now(),

                       'updated\_at' => now(),

                   ]);

               }

           }



           return \['sale\_id' => $saleId];

       });

   }

}

```

---



## BarcodeTraceService


```
namespace App\\Services;



use Illuminate\\Support\\Facades\\DB;



class BarcodeTraceService

{

   public function trace(string $code): array

   {

       $barcode = DB::table('barcodes')

           ->where('code', $code)

           ->first();



       if (!$barcode) {

           throw new \\DomainException('Barcode not found');

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

           ->where('id', $barcode->barcodeable\_id)

           ->first();

   }



   private function traceBatch($barcode)

   {

       return \[

           'batch' => DB::table('batches')->find($barcode->barcodeable\_id),



           'movements' => DB::table('stock\_movements')

               ->where('batch\_id', $barcode->barcodeable\_id)

               ->orderBy('moved\_at', 'desc')

               ->get(),



           'stock' => DB::table('stocks')

               ->where('batch\_id', $barcode->barcodeable\_id)

               ->get(),

       ];

   }



   private function traceUnit($barcode)

   {

       return DB::table('serial\_numbers')

           ->where('id', $barcode->barcodeable\_id)

           ->first();

   }



   private function traceTransaction($barcode)

   {

       return DB::table('transactions')

           ->where('id', $barcode->barcodeable\_id)

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

           ->sortBy('created\_at')

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

           ->sortBy('expires\_at')

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

           ->sortByDesc('created\_at')

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

           $a\['qty'] \* $a\['purchase\_price']

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

           $a\['qty'] \* $a\['purchase\_price']

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

           $a\['qty'] \* $a\['standard\_cost']

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

           default => throw new \\Exception('Invalid rotation')

       };

   }



   public function valuation(string $type): InventoryValuationStrategy

   {

       return match ($type) {

           'fifo' => new FIFOValuationStrategy(),

           'weighted\_average' => new WeightedAverageStrategy(),

           'standard\_cost' => new StandardCostStrategy(),

           default => throw new \\Exception('Invalid valuation')

       };

   }

}

```

---



## Integration with SaleService (Dynamic Allocation + Costing)


```
$settings = $this->settingsService->resolve($productId, $warehouseId);



$rotation = $this->factory->rotation($settings->rotation\_strategy);

$valuation = $this->factory->valuation($settings->valuation\_method);



// Allocate stock

$allocations = $rotation->allocate($batches, $qty);



// Calculate cost

$cost = $valuation->calculateCost($allocations);

```

---



## Auditable Trait


```
namespace App\\Models\\Traits;



use Illuminate\\Support\\Facades\\Auth;



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

       \\DB::table('audit\_logs')->insert(\[

           'entity\_type' => get\_class($model),

           'entity\_id'   => $model->id,

           'action'      => $action,

           'old\_values'  => json\_encode($model->getOriginal()),

           'new\_values'  => json\_encode($model->getAttributes()),

           'user\_id'     => Auth::id(),

           'created\_at'  => now(),

           'updated\_at'  => now(),

       ]);

   }

}


```


---



## StockMovement


```
class StockMovement extends Model

{

   protected $fillable = \[

       'product\_id','product\_variant\_id','batch\_id',

       'warehouse\_id','type','quantity',

       'reference\_type','reference\_id','meta','moved\_at'

   ];



   protected $casts = \[

       'meta' => 'array',

       'moved\_at' => 'datetime'

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

       return $this->morphTo(\_\_FUNCTION\_\_, 'reference\_type', 'reference\_id');

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

           'weighted\_average' => new WeightedAverage(),

           'standard\_cost' => new StandardCost(),

       };

   }



   public function allocation(string $type): AllocationStrategy

   {

       return match ($type) {

           'default' => new DefaultAllocator(),

           'strict\_batch' => new StrictBatchAllocator(),

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

           ->join('batches', 'stocks.batch\_id', '=', 'batches.id')

           ->where('stocks.product\_id', $productId)

           ->where('stocks.product\_variant\_id', $variantId)

           ->where('stocks.warehouse\_id', $warehouseId)

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

       return Stock::selectRaw('product\_id, SUM(quantity) as qty')

           ->groupBy('product\_id')

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

       $stock = Stock::lockForUpdate()->firstOrCreate(\[

           'product\_id' => $data\['product\_id'],

           'product\_variant\_id' => $data\['variant\_id'],

           'batch\_id' => $data\['batch\_id'],

           'warehouse\_id' => $data\['warehouse\_id'],

       ]);



       $stock->increment('quantity', $data\['qty']);



       $this->movement($data, 'purchase');

   }



   public function decrease($data, array $allocations)

   {

       foreach ($allocations as $a) {



           $stock = Stock::lockForUpdate()->where(\[

               'product\_id' => $data\['product\_id'],

               'product\_variant\_id' => $data\['variant\_id'],

               'batch\_id' => $a\['batch\_id'],

               'warehouse\_id' => $data\['warehouse\_id'],

           ])->first();



           if (!$stock || $stock->quantity < $a\['qty']) {

               throw new \\RuntimeException('Stock error');

           }



           $stock->decrement('quantity', $a\['qty']);



           $this->movement(\[

               ...$data,

               'batch\_id' => $a\['batch\_id'],

               'qty' => -$a\['qty']

           ], 'sale');

       }

   }



   private function movement($data, $type)

   {

       StockMovement::create(\[

           ...$data,

           'type' => $type,

           'moved\_at' => now(),

       ]);

   }

}

```

---



## AllocationService (Strategy Engine)

```

class AllocationService

{

   public function \_\_construct(

       private InventoryStrategyFactory $factory

   ) {}



   public function allocate($settings, $batches, $qty)

   {

       $rotation = $this->factory->rotation($settings->rotation\_strategy);

       $allocator = $this->factory->allocation($settings->allocation\_algorithm);



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

       return ProductComponent::where('parent\_product\_id', $productId)

           ->get()

           ->map(fn($c) => \[

               'product\_id' => $c->component\_product\_id,

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

   public function \_\_construct(

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



           $transaction = Transaction::create(\[

               'type' => $data\['type'],

               'warehouse\_id' => $data\['warehouse\_id'],

               'reference\_no' => uniqid(),

               'transaction\_date' => now(),

           ]);



           foreach ($data\['items'] as $item) {



               $product = Product::findOrFail($item\['product\_id']);



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

       $item\['product\_id'],

       $transaction->warehouse\_id

   );



   $batches = $this->batchQuery->getAvailable(

       $item\['product\_id'],

       $item\['variant\_id'] ?? null,

       $transaction->warehouse\_id

   );



   $allocations = $this->allocation->allocate(

       $settings,

       $batches,

       $item\['quantity']

   );



   $valuation = $this->factory->valuation($settings->valuation\_method);

   $cost = $valuation->calculate($allocations);



   $this->stock->decrease(\[

       'product\_id' => $item\['product\_id'],

       'variant\_id' => $item\['variant\_id'] ?? null,

       'warehouse\_id' => $transaction->warehouse\_id

   ], $allocations);



   foreach ($allocations as $a) {

       TransactionItem::create(\[

           'transaction\_id' => $transaction->id,

           'product\_id' => $item\['product\_id'],

           'product\_variant\_id' => $item\['variant\_id'] ?? null,

           'batch\_id' => $a\['batch\_id'],

           'quantity' => $a\['qty'],

           'unit\_price' => $cost,

       ]);

   }

}

```

---



## Handle Digital

```

private function handleDigital($transaction, $item)

{

   $asset = $this->digital->assign($item\['product\_id']);



   TransactionItem::create(\[

       'transaction\_id' => $transaction->id,

       'product\_id' => $item\['product\_id'],

       'quantity' => 1,

       'meta' => \['license' => $asset->license\_key]

   ]);

}

```

---



## Handle Combo

```

private function handleCombo($transaction, $item)

{

   $components = $this->combo->explode(

       $item\['product\_id'],

       $item\['quantity']

   );



   foreach ($components as $c) {

       $this->handleStock($transaction, \[

           'product\_id' => $c\['product\_id'],

           'quantity' => $c\['qty']

       ]);

   }

}

```

---



## Handle Service

```

private function handleService($transaction, $item)

{

   TransactionItem::create(\[

       'transaction\_id' => $transaction->id,

       'product\_id' => $item\['product\_id'],

       'quantity' => $item\['quantity'],

       'unit\_price' => $item\['price']

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

   config(\["database.connections.tenant" => \[

       'driver'   => $tenant->db\_driver,

       'host'     => $tenant->db\_host,

       'database' => $tenant->db\_name,

       'username' => $tenant->db\_user,

       'password' => decrypt($tenant->db\_password),

       // ...

   ]]);

   DB::purge('tenant');

   DB::reconnect('tenant');



   // Set mail, cache, queue, etc.

   config(\["mail.mailers.smtp" => array\_merge(

       config("mail.mailers.smtp"),

       $tenant->mail\_config

   )]);

   config(\["cache.default" => $tenant->cache\_driver]);

   config(\["queue.default" => $tenant->queue\_driver]);



   // Feature flags

   Feature::define('advanced-reports', fn() => $tenant->feature\_flags\['advanced-reports'] ?? false);



   // Bind tenant to service container

   app()->instance('current\_tenant', $tenant);



   return $next($request);

}

```

---



```

class FiscalYearSeeder extends Seeder

{

   public function run(): void

   {

       FiscalYear::create(\[

           'tenant\_id' => 1,

           'name' => 'FY 2025',

           'start\_date' => '2025-01-01',

           'end\_date' => '2025-12-31',

           'is\_closed' => false,

       ]);

   }

}



class AccountingPeriodSeeder extends Seeder

{

   public function run(): void

   {

       $months = \['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

       foreach ($months as $i => $month) {

           AccountingPeriod::create(\[

               'tenant\_id' => 1,

               'fiscal\_year\_id' => 1,

               'name' => $month . ' 2025',

               'period\_number' => $i + 1,

               'start\_date' => "2025-" . str\_pad($i+1, 2, '0', STR\_PAD\_LEFT) . "-01",

               'end\_date' => date("Y-m-t", strtotime("2025-" . str\_pad($i+1, 2, '0', STR\_PAD\_LEFT) . "-01")),

               'status' => 'open',

           ]);

       }

   }

}



class ChartOfAccountSeeder extends Seeder

{

   public function run(): void

   {

       $accounts = \[

           \['code' => '1000', 'name' => 'Cash on Hand', 'type' => 'asset', 'normal\_balance' => 'debit', 'level' => 1, 'path' => '/1000', 'is\_leaf' => true],

           \['code' => '1100', 'name' => 'Main Checking', 'type' => 'asset', 'normal\_balance' => 'debit', 'is\_bank' => true, 'level' => 1, 'path' => '/1100', 'is\_leaf' => true],

           \['code' => '1200', 'name' => 'Accounts Receivable', 'type' => 'asset', 'normal\_balance' => 'debit', 'level' => 1, 'path' => '/1200', 'is\_leaf' => true],

           \['code' => '1300', 'name' => 'Inventory', 'type' => 'asset', 'normal\_balance' => 'debit', 'level' => 1, 'path' => '/1300', 'is\_leaf' => true],

           \['code' => '2000', 'name' => 'Accounts Payable', 'type' => 'liability', 'normal\_balance' => 'credit', 'level' => 1, 'path' => '/2000', 'is\_leaf' => true],

           \['code' => '3000', 'name' => 'Retained Earnings', 'type' => 'equity', 'normal\_balance' => 'credit', 'level' => 1, 'path' => '/3000', 'is\_leaf' => true],

           \['code' => '4000', 'name' => 'Sales Revenue', 'type' => 'income', 'normal\_balance' => 'credit', 'level' => 1, 'path' => '/4000', 'is\_leaf' => true],

           \['code' => '5000', 'name' => 'Cost of Goods Sold', 'type' => 'expense', 'normal\_balance' => 'debit', 'level' => 1, 'path' => '/5000', 'is\_leaf' => true],

           \['code' => '6000', 'name' => 'Operating Expenses', 'type' => 'expense', 'normal\_balance' => 'debit', 'level' => 1, 'path' => '/6000', 'is\_leaf' => true],

           \['code' => '7000', 'name' => 'Tax Expense', 'type' => 'expense', 'normal\_balance' => 'debit', 'level' => 1, 'path' => '/7000', 'is\_leaf' => true],

       ];

       foreach ($accounts as $acc) {

           ChartOfAccount::create(array\_merge($acc, \['tenant\_id' => 1, 'is\_active' => true]));

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



   public function \_\_construct(

       StockMovementRepositoryInterface $movementRepo,

       StockBalanceRepositoryInterface $balanceRepo

   ) {

       $this->movementRepo = $movementRepo;

       $this->balanceRepo = $balanceRepo;

   }



   public function recordReceipt(array $data)

   {

       return DB::transaction(function () use ($data) {

           $movement = $this->movementRepo->create(array\_merge($data, \[

               'movement\_type' => 'receipt',

               'movement\_number' => $this->generateMovementNumber()

           ]));

           $this->updateStockBalances(

               $data\['product\_id'],

               $data\['to\_location\_id'],

               $data\['batch\_id'] ?? null,

               $data\['quantity']

           );

           Event::dispatch(new StockMovementCreated($movement->toArray()));

           return $movement;

       });

   }



   public function recordIssue(array $data)

   {

       return DB::transaction(function () use ($data) {

           $movement = $this->movementRepo->create(array\_merge($data, \[

               'movement\_type' => 'issue',

               'movement\_number' => $this->generateMovementNumber()

           ]));

           $this->updateStockBalances(

               $data\['product\_id'],

               $data\['from\_location\_id'],

               $data\['batch\_id'] ?? null,

               -$data\['quantity']

           );

           Event::dispatch(new StockMovementCreated($movement->toArray()));

           return $movement;

       });

   }



   public function recordTransfer(array $data)

   {

       return DB::transaction(function () use ($data) {

           // Out movement

           $outMovement = $this->movementRepo->create(array\_merge($data, \[

               'movement\_type' => 'transfer',

               'from\_location\_id' => $data\['from\_location\_id'],

               'to\_location\_id' => null,

               'movement\_number' => $this->generateMovementNumber()

           ]));

           $this->updateStockBalances(

               $data\['product\_id'],

               $data\['from\_location\_id'],

               $data\['batch\_id'] ?? null,

               -$data\['quantity']

           );



           // In movement

           $inMovement = $this->movementRepo->create(array\_merge($data, \[

               'movement\_type' => 'transfer',

               'from\_location\_id' => null,

               'to\_location\_id' => $data\['to\_location\_id'],

               'movement\_number' => $this->generateMovementNumber()

           ]));

           $this->updateStockBalances(

               $data\['product\_id'],

               $data\['to\_location\_id'],

               $data\['batch\_id'] ?? null,

               $data\['quantity']

           );



           return \['out' => $outMovement, 'in' => $inMovement];

       });

   }



   public function recordAdjustment(array $data)

   {

       return DB::transaction(function () use ($data) {

           $movement = $this->movementRepo->create(array\_merge($data, \[

               'movement\_type' => 'adjustment',

               'movement\_number' => $this->generateMovementNumber()

           ]));

           $this->updateStockBalances(

               $data\['product\_id'],

               $data\['to\_location\_id'] ?? $data\['from\_location\_id'],

               $data\['batch\_id'] ?? null,

               $data\['quantity']

           );

           Event::dispatch(new StockMovementCreated($movement->toArray()));

           return $movement;

       });

   }



   public function updateStockBalances(int $productId, int $locationId, ?int $batchId, float $quantityChange)

   {

       $balance = $this->balanceRepo->findByProductLocationBatch($productId, $locationId, $batchId);

       if ($balance) {

           $newQty = $balance->qty\_on\_hand + $quantityChange;

           $this->balanceRepo->update($balance->id, \[

               'qty\_on\_hand' => $newQty,

               'qty\_available' => $newQty - $balance->qty\_reserved,

               'updated\_at' => now()

           ]);

       } else if ($quantityChange > 0) {

           $this->balanceRepo->create(\[

               'tenant\_id' => auth()->user()->tenant\_id,

               'product\_id' => $productId,

               'location\_id' => $locationId,

               'batch\_id' => $batchId,

               'uom\_id' => 1, // default

               'qty\_on\_hand' => $quantityChange,

               'qty\_available' => $quantityChange,

               'avg\_cost' => 0

           ]);

       }

       // Negative balance not allowed – would throw exception in real validation

   }



   protected function generateMovementNumber(): string

   {

       return 'MOV-' . str\_pad(rand(1, 99999), 5, '0', STR\_PAD\_LEFT);

   }

}



class JournalEntryService implements JournalEntryServiceInterface

{

   protected JournalEntryRepositoryInterface $repository;



   public function \_\_construct(JournalEntryRepositoryInterface $repository)

   {

       $this->repository = $repository;

   }



   public function createFromTransaction(string $sourceType, int $sourceId, array $entries)

   {

       return DB::transaction(function () use ($sourceType, $sourceId, $entries) {

           $totalDebit = array\_sum(array\_column($entries, 'debit'));

           $totalCredit = array\_sum(array\_column($entries, 'credit'));

           if ($totalDebit !== $totalCredit) {

               throw new \\Exception('Journal entry must balance: Debits must equal Credits');

           }



           $period = AccountingPeriod::where('start\_date', '<=', now())

               ->where('end\_date', '>=', now())

               ->where('status', 'open')

               ->first();

           if (!$period) {

               throw new \\Exception('No open accounting period found');

           }



           $journalEntry = $this->repository->create(\[

               'tenant\_id' => auth()->user()->tenant\_id,

               'period\_id' => $period->id,

               'entry\_number' => $this->generateEntryNumber(),

               'entry\_date' => now(),

               'post\_date' => now(),

               'source\_type' => $sourceType,

               'source\_id' => $sourceId,

               'description' => "Auto-generated from {$sourceType} #{$sourceId}",

               'currency\_id' => 1,

               'exchange\_rate' => 1,

               'status' => 'draft',

               'created\_by' => auth()->id()

           ]);



           foreach ($entries as $line) {

               $journalEntry->lines()->create(\[

                   'account\_id' => $line\['account\_id'],

                   'debit' => $line\['debit'],

                   'credit' => $line\['credit'],

                   'line\_number' => $line\['line\_number'] ?? 0,

                   'party\_id' => $line\['party\_id'] ?? null,

                   'cost\_center\_id' => $line\['cost\_center\_id'] ?? null,

                   'description' => $line\['description'] ?? null

               ]);

           }



           return $journalEntry;

       });

   }



   public function postJournalEntry(int $journalEntryId)

   {

       $entry = $this->repository->find($journalEntryId);

       if ($entry->status !== 'draft') {

           throw new \\Exception('Only draft entries can be posted');

       }

       return $this->repository->update($journalEntryId, \[

           'status' => 'posted',

           'posted\_by' => auth()->id(),

           'posted\_at' => now()

       ]);

   }



   public function reverseJournalEntry(int $journalEntryId, string $reason)

   {

       $original = $this->repository->find($journalEntryId);

       if ($original->status !== 'posted') {

           throw new \\Exception('Only posted entries can be reversed');

       }



       return DB::transaction(function () use ($original, $reason) {

           // Create reversing entry with opposite signs

           $reversalLines = \[];

           foreach ($original->lines as $line) {

               $reversalLines\[] = \[

                   'account\_id' => $line->account\_id,

                   'debit' => $line->credit,

                   'credit' => $line->debit,

                   'line\_number' => $line->line\_number,

                   'party\_id' => $line->party\_id,

                   'cost\_center\_id' => $line->cost\_center\_id,

                   'description' => "Reversal: {$reason}"

               ];

           }



           $reversal = $this->createFromTransaction(

               'reversal',

               $original->id,

               $reversalLines

           );

           $this->postJournalEntry($reversal->id);



           $this->repository->update($original->id, \[

               'status' => 'reversed',

               'reversed\_by' => auth()->id()

           ]);



           return $reversal;

       });

   }



   protected function generateEntryNumber(): string

   {

       return 'JE-' . date('Ymd') . '-' . str\_pad(rand(1, 9999), 4, '0', STR\_PAD\_LEFT);

   }

}



class PurchaseOrderService implements PurchaseOrderServiceInterface

{

   protected PurchaseOrderRepositoryInterface $poRepo;

   protected GoodsReceiptRepositoryInterface $grRepo;

   protected StockMovementServiceInterface $stockMovementService;

   protected JournalEntryServiceInterface $journalEntryService;



   public function \_\_construct(

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

               throw new \\Exception('Goods receipt can only be received from draft status');

           }



           foreach ($lines as $line) {

               // Record stock movement

               $movement = $this->stockMovementService->recordReceipt(\[

                   'tenant\_id' => $gr->tenant\_id,

                   'product\_id' => $line\['product\_id'],

                   'variant\_id' => $line\['variant\_id'] ?? null,

                   'batch\_id' => $line\['batch\_id'] ?? null,

                   'serial\_id' => $line\['serial\_id'] ?? null,

                   'to\_location\_id' => $line\['location\_id'],

                   'uom\_id' => $line\['uom\_id'],

                   'quantity' => $line\['received\_qty'],

                   'unit\_cost' => $line\['unit\_cost'],

                   'source\_type' => 'goods\_receipt',

                   'source\_id' => $goodsReceiptId,

                   'created\_by' => auth()->id()

               ]);



               // Create goods receipt line

               $gr->lines()->create(\[

                   'po\_line\_id' => $line\['po\_line\_id'] ?? null,

                   'product\_id' => $line\['product\_id'],

                   'variant\_id' => $line\['variant\_id'] ?? null,

                   'batch\_id' => $line\['batch\_id'] ?? null,

                   'serial\_id' => $line\['serial\_id'] ?? null,

                   'location\_id' => $line\['location\_id'],

                   'uom\_id' => $line\['uom\_id'],

                   'received\_qty' => $line\['received\_qty'],

                   'unit\_cost' => $line\['unit\_cost'],

                   'total\_cost' => $line\['received\_qty'] \* $line\['unit\_cost'],

                   'stock\_movement\_id' => $movement->id

               ]);



               // Update purchase order line received quantity

               if ($line\['po\_line\_id']) {

                   $poLine = $gr->purchaseOrder->lines()->find($line\['po\_line\_id']);

                   $newReceived = $poLine->received\_qty + $line\['received\_qty'];

                   $poLine->update(\['received\_qty' => $newReceived]);

               }

           }



           $gr->update(\['status' => 'received']);



           // If entire PO is received, update PO status

           $po = $gr->purchaseOrder;

           $totalOrdered = $po->lines->sum('ordered\_qty');

           $totalReceived = $po->lines->sum('received\_qty');

           if ($totalReceived >= $totalOrdered) {

               $po->update(\['status' => 'received']);

           } elseif ($totalReceived > 0) {

               $po->update(\['status' => 'partially\_received']);

           }



           return $gr;

       });

   }



   public function approvePurchaseOrder(int $purchaseOrderId)

   {

       return $this->poRepo->update($purchaseOrderId, \['status' => 'approved']);

   }

}



class SalesOrderService implements SalesOrderServiceInterface

{

   protected SalesOrderRepositoryInterface $soRepo;

   protected DeliveryOrderRepositoryInterface $doRepo;

   protected StockMovementServiceInterface $stockMovementService;

   protected JournalEntryServiceInterface $journalEntryService;



   public function \_\_construct(

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

               throw new \\Exception('Delivery order can only be shipped from draft status');

           }



           foreach ($lines as $line) {

               // Record stock issue

               $movement = $this->stockMovementService->recordIssue(\[

                   'tenant\_id' => $do->tenant\_id,

                   'product\_id' => $line\['product\_id'],

                   'variant\_id' => $line\['variant\_id'] ?? null,

                   'batch\_id' => $line\['batch\_id'] ?? null,

                   'serial\_id' => $line\['serial\_id'] ?? null,

                   'from\_location\_id' => $line\['from\_location\_id'],

                   'uom\_id' => $line\['uom\_id'],

                   'quantity' => $line\['delivered\_qty'],

                   'unit\_cost' => $line\['unit\_cost'] ?? 0,

                   'source\_type' => 'delivery\_order',

                   'source\_id' => $deliveryOrderId,

                   'created\_by' => auth()->id()

               ]);



               $do->lines()->create(\[

                   'so\_line\_id' => $line\['so\_line\_id'],

                   'product\_id' => $line\['product\_id'],

                   'variant\_id' => $line\['variant\_id'] ?? null,

                   'batch\_id' => $line\['batch\_id'] ?? null,

                   'serial\_id' => $line\['serial\_id'] ?? null,

                   'from\_location\_id' => $line\['from\_location\_id'],

                   'uom\_id' => $line\['uom\_id'],

                   'delivered\_qty' => $line\['delivered\_qty'],

                   'stock\_movement\_id' => $movement->id

               ]);



               // Update sales order line shipped quantity

               $soLine = $do->salesOrder->lines()->find($line\['so\_line\_id']);

               $newShipped = $soLine->shipped\_qty + $line\['delivered\_qty'];

               $soLine->update(\['shipped\_qty' => $newShipped]);

           }



           $do->update(\['status' => 'shipped']);



           $so = $do->salesOrder;

           $totalOrdered = $so->lines->sum('ordered\_qty');

           $totalShipped = $so->lines->sum('shipped\_qty');

           if ($totalShipped >= $totalOrdered) {

               $so->update(\['status' => 'shipped']);

           }



           return $do;

       });

   }



   public function invoiceOrder(int $customerInvoiceId)

   {

       return DB::transaction(function () use ($customerInvoiceId) {

           $invoice = CustomerInvoice::findOrFail($customerInvoiceId);

           if ($invoice->status !== 'draft') {

               throw new \\Exception('Invoice can only be generated from draft status');

           }



           // Create journal entry for revenue recognition

           $journalLines = \[

               \[

                   'account\_id' => 3, // Accounts Receivable

                   'debit' => $invoice->total,

                   'credit' => 0,

                   'line\_number' => 1,

                   'party\_id' => $invoice->customer\_id

               ],

               \[

                   'account\_id' => 7, // Sales Revenue

                   'debit' => 0,

                   'credit' => $invoice->total,

                   'line\_number' => 2,

                   'party\_id' => $invoice->customer\_id

               ]

           ];



           $journalEntry = $this->journalEntryService->createFromTransaction(

               'customer\_invoice',

               $customerInvoiceId,

               $journalLines

           );

           $this->journalEntryService->postJournalEntry($journalEntry->id);



           $invoice->update(\[

               'status' => 'sent',

               'journal\_entry\_id' => $journalEntry->id

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

 id, tenant\_id, period\_id, document\_type ENUM('PO','SO','GRN','SUP\_INV','CUST\_INV','RETURN','CREDIT\_NOTE','PAYMENT'),

 document\_number, party\_id, warehouse\_id, currency\_id, exchange\_rate,

 document\_date, accounting\_date, due\_date, status, total\_amount, paid\_amount,

 created\_by, approved\_by, journal\_entry\_id, ...

)



document\_lines (

 id, document\_id, line\_number, product\_id, variant\_id, uom\_id, quantity,

 unit\_price, discount\_pct, tax\_code\_id, subtotal, tax\_amount, total,

 batch\_id, serial\_id, location\_id, stock\_movement\_id, ...

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

           $supplier = Supplier::where('tenant\_id', $tenant->id)->first();

           $warehouse = Warehouse::where('tenant\_id', $tenant->id)->first();

           $user = User::where('tenant\_id', $tenant->id)->first();

           $products = Product::where('tenant\_id', $tenant->id)->where('type', 'physical')->take(2)->get();



           if (!$supplier || !$warehouse || !$user || $products->isEmpty()) continue;



           $po = PurchaseOrder::create(\[

               'tenant\_id' => $tenant->id,

               'supplier\_id' => $supplier->id,

               'org\_unit\_id' => $warehouse->org\_unit\_id,

               'warehouse\_id' => $warehouse->id,

               'po\_number' => 'PO-' . date('Ymd') . '-001',

               'status' => 'confirmed',

               'currency\_id' => $usd->id,

               'exchange\_rate' => 1,

               'order\_date' => now()->subDays(5),

               'expected\_date' => now()->addDays(7),

               'subtotal' => 0,

               'grand\_total' => 0,

               'created\_by' => $user->id,

           ]);



           $subtotal = 0;

           foreach ($products as $product) {

               $qty = rand(10, 50);

               $price = 12.50;

               $lineTotal = $qty \* $price;

               $subtotal += $lineTotal;



               PurchaseOrderLine::create(\[

                   'purchase\_order\_id' => $po->id,

                   'product\_id' => $product->id,

                   'uom\_id' => $product->base\_uom\_id,

                   'ordered\_qty' => $qty,

                   'received\_qty' => 0,

                   'unit\_price' => $price,

                   'line\_total' => $lineTotal,

               ]);

           }



           $po->update(\['subtotal' => $subtotal, 'grand\_total' => $subtotal]);

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

           $user = User::where('tenant\_id', $po->tenant\_id)->first();

           $location = WarehouseLocation::where('warehouse\_id', $po->warehouse\_id)->where('type', 'bin')->first();



           $grn = GrnHeader::create(\[

               'tenant\_id' => $po->tenant\_id,

               'supplier\_id' => $po->supplier\_id,

               'warehouse\_id' => $po->warehouse\_id,

               'purchase\_order\_id' => $po->id,

               'grn\_number' => 'GRN-' . date('Ymd') . '-' . $po->id,

               'status' => 'complete',

               'received\_date' => now()->subDays(2),

               'currency\_id' => $usd->id,

               'created\_by' => $user->id,

           ]);



           foreach ($po->lines as $line) {

               $grnLine = GrnLine::create(\[

                   'grn\_header\_id' => $grn->id,

                   'purchase\_order\_line\_id' => $line->id,

                   'product\_id' => $line->product\_id,

                   'variant\_id' => $line->variant\_id,

                   'location\_id' => $location?->id,

                   'uom\_id' => $line->uom\_id,

                   'expected\_qty' => $line->ordered\_qty,

                   'received\_qty' => $line->ordered\_qty,

                   'unit\_cost' => $line->unit\_price,

               ]);



               // Record stock movement

               StockMovement::create(\[

                   'tenant\_id' => $po->tenant\_id,

                   'product\_id' => $line->product\_id,

                   'variant\_id' => $line->variant\_id,

                   'to\_location\_id' => $location?->id,

                   'movement\_type' => 'receipt',

                   'reference\_type' => GrnHeader::class,

                   'reference\_id' => $grn->id,

                   'uom\_id' => $line->uom\_id,

                   'quantity' => $line->ordered\_qty,

                   'unit\_cost' => $line->unit\_price,

                   'performed\_by' => $user->id,

               ]);



               // Update stock level

               StockLevel::updateOrCreate(

                   \[

                       'tenant\_id' => $po->tenant\_id,

                       'product\_id' => $line->product\_id,

                       'variant\_id' => $line->variant\_id,

                       'location\_id' => $location?->id,

                   ],

                   \[

                       'uom\_id' => $line->uom\_id,

                       'unit\_cost' => $line->unit\_price,

                   ]

               )->increment('quantity\_on\_hand', $line->ordered\_qty);



               $line->update(\['received\_qty' => $line->ordered\_qty]);

           }



           $po->update(\['status' => 'received']);

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

           $bankAccount = Account::where('tenant\_id', $tenant->id)->where('is\_bank\_account', true)->first();

           $cashAccount = Account::where('tenant\_id', $tenant->id)->where('code', '1000')->first();

           $apAccount = Account::where('tenant\_id', $tenant->id)->where('code', '2000')->first();



           $bankMethod = PaymentMethod::firstOrCreate(

               \['tenant\_id' => $tenant->id, 'name' => 'Bank Transfer'],

               \['type' => 'bank\_transfer', 'account\_id' => $bankAccount?->id, 'is\_active' => true]

           );



           // Pay a purchase invoice

           $purchaseInvoice = PurchaseInvoice::where('tenant\_id', $tenant->id)->where('status', 'approved')->first();

           if ($purchaseInvoice) {

               Payment::create(\[

                   'tenant\_id' => $tenant->id,

                   'payment\_number' => 'PAY-OUT-' . date('Ymd') . '-001',

                   'direction' => 'outbound',

                   'party\_type' => 'supplier',

                   'party\_id' => $purchaseInvoice->supplier\_id,

                   'payment\_method\_id' => $bankMethod->id,

                   'account\_id' => $bankAccount?->id ?? $cashAccount->id,

                   'amount' => $purchaseInvoice->grand\_total,

                   'currency\_id' => $usd->id,

                   'exchange\_rate' => 1,

                   'base\_amount' => $purchaseInvoice->grand\_total,

                   'payment\_date' => now(),

                   'status' => 'posted',

               ]);

               $purchaseInvoice->update(\['status' => 'paid']);

           }



           // Receive payment for a sales invoice

           $salesInvoice = SalesInvoice::where('tenant\_id', $tenant->id)->where('status', 'sent')->first();

           if ($salesInvoice) {

               Payment::create(\[

                   'tenant\_id' => $tenant->id,

                   'payment\_number' => 'PAY-IN-' . date('Ymd') . '-001',

                   'direction' => 'inbound',

                   'party\_type' => 'customer',

                   'party\_id' => $salesInvoice->customer\_id,

                   'payment\_method\_id' => $bankMethod->id,

                   'account\_id' => $bankAccount?->id ?? $cashAccount->id,

                   'amount' => $salesInvoice->grand\_total,

                   'currency\_id' => $usd->id,

                   'exchange\_rate' => 1,

                   'base\_amount' => $salesInvoice->grand\_total,

                   'payment\_date' => now(),

                   'status' => 'posted',

               ]);

               $salesInvoice->update(\['status' => 'paid']);

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

                                               Purchase Return (optional) → Stock Movement (return\_out) → Journal Entry (Dr AP, Cr Inventory)

```

class GoodsReceivedListener

{

   public function handle(GoodsReceived $event): void

   {

       DB::transaction(function () use ($event) {

           $grn = $event->grn;

           foreach ($grn->lines as $line) {

               // 1. Stock Movement (receipt)

               $movement = StockMovement::create(\[

                   'tenant\_id' => $grn->tenant\_id,

                   'product\_id' => $line->product\_id,

                   'variant\_id' => $line->variant\_id,

                   'batch\_id' => $line->batch\_id,

                   'serial\_id' => $line->serial\_id,

                   'to\_location\_id' => $line->location\_id,

                   'movement\_type' => 'receipt',

                   'reference\_type' => GrnHeader::class,

                   'reference\_id' => $grn->id,

                   'uom\_id' => $line->uom\_id,

                   'quantity' => $line->received\_qty,

                   'unit\_cost' => $line->unit\_cost,

                   'performed\_by' => $grn->created\_by,

               ]);



               // 2. Update Stock Level

               StockLevel::updateOrCreate(\[...])->increment('quantity\_on\_hand', $line->received\_qty);



               // 3. Create Cost Layer (FIFO/LIFO/FEFO)

               InventoryCostLayer::create(\[

                   'tenant\_id' => $grn->tenant\_id,

                   'product\_id' => $line->product\_id,

                   'variant\_id' => $line->variant\_id,

                   'batch\_id' => $line->batch\_id,

                   'location\_id' => $line->location\_id,

                   'valuation\_method' => $line->product->valuation\_method,

                   'layer\_date' => $grn->received\_date,

                   'quantity\_in' => $line->received\_qty,

                   'quantity\_remaining' => $line->received\_qty,

                   'unit\_cost' => $line->unit\_cost,

                   'reference\_type' => StockMovement::class,

                   'reference\_id' => $movement->id,

               ]);

           }



           // 4. Post Journal Entry (if configured to do so at GRN)

           $this->postReceiptJournalEntry($grn);

       });

   }



   protected function postReceiptJournalEntry(GrnHeader $grn): void

   {

       $journalEntry = JournalEntry::create(\[

           'tenant\_id' => $grn->tenant\_id,

           'fiscal\_period\_id' => FiscalPeriod::current()->id,

           'entry\_type' => 'auto',

           'reference\_type' => GrnHeader::class,

           'reference\_id' => $grn->id,

           'entry\_date' => $grn->received\_date,

           'status' => 'posted',

           'created\_by' => $grn->created\_by,

       ]);



       $inventoryAccount = Account::where('code', '1300')->first(); // Inventory

       $apAccount = Account::where('code', '2000')->first();       // Accounts Payable

       $totalCost = $grn->lines->sum('line\_cost');



       // Debit Inventory

       JournalEntryLine::create(\[

           'journal\_entry\_id' => $journalEntry->id,

           'account\_id' => $inventoryAccount->id,

           'debit\_amount' => $totalCost,

       ]);

       // Credit AP

       JournalEntryLine::create(\[

           'journal\_entry\_id' => $journalEntry->id,

           'account\_id' => $apAccount->id,

           'credit\_amount' => $totalCost,

       ]);



       $grn->update(\['status' => 'posted']);

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

                                                             Sales Return (optional) → Stock Movement (return\_in) → Journal Entry (Dr Revenue/Inventory, Cr AR)

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

               $layers = $this->allocateLayers($line->product\_id, $line->shipped\_qty, $line->batch\_id, $line->from\_location\_id);

               $unitCost = $layers->avg('unit\_cost');

               $totalLineCost = $line->shipped\_qty \* $unitCost;

               $totalCogs += $totalLineCost;



               // 2. Create Stock Movement (issue)

               $movement = StockMovement::create(\[

                   'tenant\_id' => $shipment->tenant\_id,

                   'product\_id' => $line->product\_id,

                   'variant\_id' => $line->variant\_id,

                   'batch\_id' => $line->batch\_id,

                   'serial\_id' => $line->serial\_id,

                   'from\_location\_id' => $line->from\_location\_id,

                   'movement\_type' => 'shipment',

                   'reference\_type' => Shipment::class,

                   'reference\_id' => $shipment->id,

                   'uom\_id' => $line->uom\_id,

                   'quantity' => $line->shipped\_qty,

                   'unit\_cost' => $unitCost,

                   'performed\_by' => $shipment->created\_by,

               ]);



               // 3. Update Stock Level

               StockLevel::where(\[...])->decrement('quantity\_on\_hand', $line->shipped\_qty);



               // 4. Release reservations

               StockReservation::where('reserved\_for\_type', SalesOrderLine::class)

                   ->where('reserved\_for\_id', $line->sales\_order\_line\_id)

                   ->delete();

           }



           // 5. Post COGS Journal Entry

           $journalEntry = JournalEntry::create(\[...]);

           JournalEntryLine::create(\['journal\_entry\_id' => $journalEntry->id, 'account\_id' => $cogsAccount->id, 'debit\_amount' => $totalCogs]);

           JournalEntryLine::create(\['journal\_entry\_id' => $journalEntry->id, 'account\_id' => $inventoryAccount->id, 'credit\_amount' => $totalCogs]);

       });

   }



   protected function allocateLayers($productId, $qty, $batchId, $locationId): Collection

   {

       $remaining = $qty;

       $layers = InventoryCostLayer::where('product\_id', $productId)

           ->where('location\_id', $locationId)

           ->when($batchId, fn($q) => $q->where('batch\_id', $batchId))

           ->where('quantity\_remaining', '>', 0)

           ->orderBy('layer\_date') // FIFO; for FEFO order by expiry\_date via batch

           ->get();



       foreach ($layers as $layer) {

           $consume = min($remaining, $layer->quantity\_remaining);

           $layer->decrement('quantity\_remaining', $consume);

           $layer->update(\['is\_closed' => $layer->quantity\_remaining == 0]);

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

               $originalCost = $line->original\_sales\_order\_line\_id

                   ? $this->getOriginalCost($line->original\_sales\_order\_line\_id)

                   : $this->getCurrentCost($line->product\_id);



               if ($disposition === 'restock') {

                   // 1. Stock Movement (return\_in)

                   $movement = StockMovement::create(\[

                       'tenant\_id' => $return->tenant\_id,

                       'product\_id' => $line->product\_id,

                       'variant\_id' => $line->variant\_id,

                       'batch\_id' => $line->batch\_id,

                       'serial\_id' => $line->serial\_id,

                       'to\_location\_id' => $line->to\_location\_id,

                       'movement\_type' => 'return\_in',

                       'reference\_type' => SalesReturn::class,

                       'reference\_id' => $return->id,

                       'uom\_id' => $line->uom\_id,

                       'quantity' => $line->return\_qty,

                       'unit\_cost' => $originalCost,

                       'performed\_by' => $return->created\_by,

                   ]);



                   // 2. Update Stock Level

                   StockLevel::updateOrCreate(\[...])->increment('quantity\_on\_hand', $line->return\_qty);



                   // 3. Re-insert Cost Layer (at original cost)

                   InventoryCostLayer::create(\[

                       'tenant\_id' => $return->tenant\_id,

                       'product\_id' => $line->product\_id,

                       'variant\_id' => $line->variant\_id,

                       'batch\_id' => $line->batch\_id,

                       'location\_id' => $line->to\_location\_id,

                       'valuation\_method' => $line->product->valuation\_method,

                       'layer\_date' => $return->return\_date,

                       'quantity\_in' => $line->return\_qty,

                       'quantity\_remaining' => $line->return\_qty,

                       'unit\_cost' => $originalCost,

                       'reference\_type' => StockMovement::class,

                       'reference\_id' => $movement->id,

                   ]);



                   $totalInventoryCredit += $line->return\_qty \* $originalCost;

               }



               $totalRevenueDebit += $line->line\_total;

               $totalRestockingFee += $line->restocking\_fee;

           }



           // 4. Post Journal Entry

           $journalEntry = JournalEntry::create(\[...]);

           // Debit Sales Returns (Revenue contra)

           JournalEntryLine::create(\['account\_id' => $salesReturnsAccount->id, 'debit\_amount' => $totalRevenueDebit]);

           // Credit Accounts Receivable

           JournalEntryLine::create(\['account\_id' => $arAccount->id, 'credit\_amount' => $totalRevenueDebit + $totalRestockingFee]);

           // If restocked, Credit Inventory (reversal of COGS) and Debit COGS reversal

           if ($totalInventoryCredit > 0) {

               JournalEntryLine::create(\['account\_id' => $inventoryAccount->id, 'debit\_amount' => $totalInventoryCredit]);

               JournalEntryLine::create(\['account\_id' => $cogsAccount->id, 'credit\_amount' => $totalInventoryCredit]);

           }

           // Restocking fee as revenue

           if ($totalRestockingFee > 0) {

               JournalEntryLine::create(\['account\_id' => $restockingFeeRevenueAccount->id, 'credit\_amount' => $totalRestockingFee]);

           }



           // 5. Create Credit Memo

           CreditMemo::create(\[

               'tenant\_id' => $return->tenant\_id,

               'party\_type' => 'customer',

               'party\_id' => $return->customer\_id,

               'return\_order\_type' => SalesReturn::class,

               'return\_order\_id' => $return->id,

               'credit\_memo\_number' => 'CM-' . $return->return\_number,

               'amount' => $totalRevenueDebit + $totalRestockingFee,

               'status' => 'issued',

               'issued\_date' => now(),

               'journal\_entry\_id' => $journalEntry->id,

           ]);



           $return->update(\['status' => 'closed']);

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

       $journalEntry = JournalEntry::create(\[...]);

       $bankAccount = $payment->account; // Bank/Cash account

       $partyAccount = $payment->direction === 'inbound'

           ? Account::where('code', '1200')->first() // AR

           : Account::where('code', '2000')->first(); // AP



       if ($payment->direction === 'inbound') {

           // Debit Bank, Credit AR

           JournalEntryLine::create(\['account\_id' => $bankAccount->id, 'debit\_amount' => $payment->amount]);

           JournalEntryLine::create(\['account\_id' => $partyAccount->id, 'credit\_amount' => $payment->amount]);

       } else {

           // Debit AP, Credit Bank

           JournalEntryLine::create(\['account\_id' => $partyAccount->id, 'debit\_amount' => $payment->amount]);

           JournalEntryLine::create(\['account\_id' => $bankAccount->id, 'credit\_amount' => $payment->amount]);

       }

       return $journalEntry;

   }



   protected function applyToInvoices(Payment $payment, string $invoiceClass): void

   {

       $remaining = $payment->amount;

       $invoices = $invoiceClass::where('party\_id', $payment->party\_id)

           ->where('status', '!=', 'paid')

           ->orderBy('due\_date')

           ->get();



       foreach ($invoices as $invoice) {

           $allocate = min($remaining, $invoice->grand\_total - $invoice->paid\_amount);

           PaymentAllocation::create(\[

               'payment\_id' => $payment->id,

               'invoice\_type' => $invoiceClass,

               'invoice\_id' => $invoice->id,

               'allocated\_amount' => $allocate,

           ]);

           $invoice->paid\_amount += $allocate;

           $invoice->status = ($invoice->paid\_amount >= $invoice->grand\_total) ? 'paid' : 'partial\_paid';

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

namespace Modules\\Purchase\\Database\\Seeders;



use Illuminate\\Database\\Seeder;

use Modules\\Purchase\\Infrastructure\\Persistence\\Eloquent\\PurchaseOrder;

use Modules\\Purchase\\Infrastructure\\Persistence\\Eloquent\\PurchaseOrderLine;

use Modules\\Supplier\\Infrastructure\\Persistence\\Eloquent\\Supplier;

use Modules\\Product\\Infrastructure\\Persistence\\Eloquent\\Product;

use Modules\\Warehouse\\Infrastructure\\Persistence\\Eloquent\\Warehouse;

use Modules\\User\\Infrastructure\\Persistence\\Eloquent\\User;

use Modules\\Core\\Infrastructure\\Persistence\\Eloquent\\Tenant;

use Modules\\Core\\Infrastructure\\Persistence\\Eloquent\\Currency;



class PurchaseOrderSeeder extends Seeder

{

   public function run(): void

   {

       $tenants = Tenant::all();

       $usd = Currency::where('code', 'USD')->first();



       foreach ($tenants as $tenant) {

           $supplier = Supplier::where('tenant\_id', $tenant->id)->first();

           $warehouse = Warehouse::where('tenant\_id', $tenant->id)->where('is\_default', true)->first();

           $user = User::where('tenant\_id', $tenant->id)->whereHas('roles', fn($q) => $q->where('name', 'Manager'))->first();

           if (!$supplier || !$warehouse || !$user) continue;



           // Create 2 POs

           for ($i = 1; $i <= 2; $i++) {

               $po = PurchaseOrder::create(\[

                   'tenant\_id' => $tenant->id,

                   'supplier\_id' => $supplier->id,

                   'org\_unit\_id' => $warehouse->org\_unit\_id,

                   'warehouse\_id' => $warehouse->id,

                   'po\_number' => 'PO-' . date('Ymd') . '-' . str\_pad($i, 3, '0', STR\_PAD\_LEFT),

                   'status' => 'confirmed',

                   'currency\_id' => $usd->id,

                   'exchange\_rate' => 1,

                   'order\_date' => now()->subDays(10 + $i),

                   'expected\_date' => now()->addDays(5),

                   'created\_by' => $user->id,

                   'approved\_by' => $user->id,

               ]);



               $products = Product::where('tenant\_id', $tenant->id)->where('type', 'physical')->take(2)->get();

               $subtotal = 0;

               foreach ($products as $product) {

                   $qty = rand(5, 20) \* ($i == 1 ? 1 : 2);

                   $price = 12.50;

                   $lineTotal = $qty \* $price;

                   $subtotal += $lineTotal;

                   PurchaseOrderLine::create(\[

                       'purchase\_order\_id' => $po->id,

                       'product\_id' => $product->id,

                       'uom\_id' => $product->base\_uom\_id,

                       'ordered\_qty' => $qty,

                       'unit\_price' => $price,

                       'line\_total' => $lineTotal,

                   ]);

               }

               $po->update(\['subtotal' => $subtotal, 'grand\_total' => $subtotal]);

           }

       }

   }

}

```

---



## GrnFromPoSeeder.php – Receive goods against PO (creates stock \& journal entry)

```

<?php

namespace Modules\\Purchase\\Database\\Seeders;



use Illuminate\\Database\\Seeder;

use Modules\\Purchase\\Infrastructure\\Persistence\\Eloquent\\PurchaseOrder;

use Modules\\Purchase\\Infrastructure\\Persistence\\Eloquent\\GrnHeader;

use Modules\\Purchase\\Infrastructure\\Persistence\\Eloquent\\GrnLine;

use Modules\\Inventory\\Infrastructure\\Persistence\\Eloquent\\StockMovement;

use Modules\\Inventory\\Infrastructure\\Persistence\\Eloquent\\StockLevel;

use Modules\\Inventory\\Infrastructure\\Persistence\\Eloquent\\InventoryCostLayer;

use Modules\\Finance\\Infrastructure\\Persistence\\Eloquent\\JournalEntry;

use Modules\\Finance\\Infrastructure\\Persistence\\Eloquent\\JournalEntryLine;

use Modules\\Finance\\Infrastructure\\Persistence\\Eloquent\\Account;

use Modules\\Finance\\Infrastructure\\Persistence\\Eloquent\\FiscalPeriod;

use Modules\\Warehouse\\Infrastructure\\Persistence\\Eloquent\\WarehouseLocation;

use Modules\\User\\Infrastructure\\Persistence\\Eloquent\\User;

use Modules\\Core\\Infrastructure\\Persistence\\Eloquent\\Currency;

use Illuminate\\Support\\Facades\\DB;



class GrnFromPoSeeder extends Seeder

{

   public function run(): void

   {

       $pos = PurchaseOrder::where('status', 'confirmed')->get();

       $usd = Currency::where('code', 'USD')->first();

       $inventoryAccount = Account::where('code', '1300')->first();

       $grIrAccount = Account::firstOrCreate(\['code' => '1500', 'name' => 'GR/IR', 'type' => 'liability', 'normal\_balance' => 'credit']);

       $fiscalPeriod = FiscalPeriod::where('status', 'open')->first();



       foreach ($pos as $po) {

           DB::transaction(function () use ($po, $usd, $inventoryAccount, $grIrAccount, $fiscalPeriod) {

               $user = User::where('tenant\_id', $po->tenant\_id)->first();

               $location = WarehouseLocation::where('warehouse\_id', $po->warehouse\_id)->where('type', 'bin')->first();

               if (!$user || !$location) return;



               // Create GRN

               $grn = GrnHeader::create(\[

                   'tenant\_id' => $po->tenant\_id,

                   'supplier\_id' => $po->supplier\_id,

                   'warehouse\_id' => $po->warehouse\_id,

                   'purchase\_order\_id' => $po->id,

                   'grn\_number' => 'GRN-PO-' . $po->id,

                   'status' => 'complete',

                   'received\_date' => now()->subDays(3),

                   'currency\_id' => $usd->id,

                   'created\_by' => $user->id,

               ]);



               $totalCost = 0;

               foreach ($po->lines as $line) {

                   $grnLine = GrnLine::create(\[

                       'grn\_header\_id' => $grn->id,

                       'purchase\_order\_line\_id' => $line->id,

                       'product\_id' => $line->product\_id,

                       'variant\_id' => $line->variant\_id,

                       'location\_id' => $location->id,

                       'uom\_id' => $line->uom\_id,

                       'expected\_qty' => $line->ordered\_qty,

                       'received\_qty' => $line->ordered\_qty,

                       'unit\_cost' => $line->unit\_price,

                   ]);



                   // Stock Movement

                   StockMovement::create(\[

                       'tenant\_id' => $po->tenant\_id,

                       'product\_id' => $line->product\_id,

                       'to\_location\_id' => $location->id,

                       'movement\_type' => 'receipt',

                       'reference\_type' => GrnHeader::class,

                       'reference\_id' => $grn->id,

                       'uom\_id' => $line->uom\_id,

                       'quantity' => $line->ordered\_qty,

                       'unit\_cost' => $line->unit\_price,

                       'performed\_by' => $user->id,

                   ]);



                   // Update Stock Level

                   StockLevel::updateOrCreate(\[

                       'tenant\_id' => $po->tenant\_id,

                       'product\_id' => $line->product\_id,

                       'variant\_id' => $line->variant\_id,

                       'location\_id' => $location->id,

                   ], \[

                       'uom\_id' => $line->uom\_id,

                       'unit\_cost' => $line->unit\_price,

                   ])->increment('quantity\_on\_hand', $line->ordered\_qty);



                   // Cost Layer

                   InventoryCostLayer::create(\[

                       'tenant\_id' => $po->tenant\_id,

                       'product\_id' => $line->product\_id,

                       'location\_id' => $location->id,

                       'valuation\_method' => $line->product->valuation\_method,

                       'layer\_date' => $grn->received\_date,

                       'quantity\_in' => $line->ordered\_qty,

                       'quantity\_remaining' => $line->ordered\_qty,

                       'unit\_cost' => $line->unit\_price,

                       'reference\_type' => StockMovement::class,

                       'reference\_id' => $grn->id,

                   ]);



                   $totalCost += $line->ordered\_qty \* $line->unit\_price;

               }



               // Journal Entry (Dr Inventory, Cr GR/IR)

               $journalEntry = JournalEntry::create(\[

                   'tenant\_id' => $po->tenant\_id,

                   'fiscal\_period\_id' => $fiscalPeriod->id,

                   'entry\_type' => 'auto',

                   'reference\_type' => GrnHeader::class,

                   'reference\_id' => $grn->id,

                   'entry\_date' => $grn->received\_date,

                   'status' => 'posted',

                   'created\_by' => $user->id,

                   'posted\_by' => $user->id,

                   'posted\_at' => now(),

               ]);

               JournalEntryLine::create(\['journal\_entry\_id' => $journalEntry->id, 'account\_id' => $inventoryAccount->id, 'debit\_amount' => $totalCost]);

               JournalEntryLine::create(\['journal\_entry\_id' => $journalEntry->id, 'account\_id' => $grIrAccount->id, 'credit\_amount' => $totalCost]);



               $po->update(\['status' => 'received']);

               $grn->update(\['status' => 'posted']);

           });

       }

   }

}

```

---



## PurchaseInvoiceSeeder.php – Create invoices and clear GR/IR


```


<?php

namespace Modules\\Purchase\\Database\\Seeders;



use Illuminate\\Database\\Seeder;

use Modules\\Purchase\\Infrastructure\\Persistence\\Eloquent\\GrnHeader;

use Modules\\Purchase\\Infrastructure\\Persistence\\Eloquent\\PurchaseInvoice;

use Modules\\Purchase\\Infrastructure\\Persistence\\Eloquent\\PurchaseInvoiceLine;

use Modules\\Finance\\Infrastructure\\Persistence\\Eloquent\\JournalEntry;

use Modules\\Finance\\Infrastructure\\Persistence\\Eloquent\\JournalEntryLine;

use Modules\\Finance\\Infrastructure\\Persistence\\Eloquent\\Account;

use Modules\\Finance\\Infrastructure\\Persistence\\Eloquent\\FiscalPeriod;

use Modules\\Core\\Infrastructure\\Persistence\\Eloquent\\Currency;

use Illuminate\\Support\\Facades\\DB;



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

               $total = $grn->lines->sum('line\_cost');



               $invoice = PurchaseInvoice::create(\[

                   'tenant\_id' => $grn->tenant\_id,

                   'supplier\_id' => $grn->supplier\_id,

                   'grn\_header\_id' => $grn->id,

                   'purchase\_order\_id' => $grn->purchase\_order\_id,

                   'invoice\_number' => 'INV-' . $grn->grn\_number,

                   'supplier\_invoice\_number' => 'SUPP-INV-' . rand(1000,9999),

                   'status' => 'approved',

                   'invoice\_date' => now()->subDays(1),

                   'due\_date' => now()->addDays(30),

                   'currency\_id' => $usd->id,

                   'subtotal' => $total,

                   'grand\_total' => $total,

                   'ap\_account\_id' => $apAccount->id,

               ]);



               foreach ($grn->lines as $line) {

                   PurchaseInvoiceLine::create(\[

                       'purchase\_invoice\_id' => $invoice->id,

                       'grn\_line\_id' => $line->id,

                       'product\_id' => $line->product\_id,

                       'uom\_id' => $line->uom\_id,

                       'quantity' => $line->received\_qty,

                       'unit\_price' => $line->unit\_cost,

                       'line\_total' => $line->line\_cost,

                   ]);

               }



               // Journal Entry: Dr GR/IR, Cr AP

               $journalEntry = JournalEntry::create(\[

                   'tenant\_id' => $grn->tenant\_id,

                   'fiscal\_period\_id' => $fiscalPeriod->id,

                   'entry\_type' => 'auto',

                   'reference\_type' => PurchaseInvoice::class,

                   'reference\_id' => $invoice->id,

                   'entry\_date' => $invoice->invoice\_date,

                   'status' => 'posted',

                   'created\_by' => $grn->created\_by,

                   'posted\_by' => $grn->created\_by,

                   'posted\_at' => now(),

               ]);

               JournalEntryLine::create(\['journal\_entry\_id' => $journalEntry->id, 'account\_id' => $grIrAccount->id, 'debit\_amount' => $total]);

               JournalEntryLine::create(\['journal\_entry\_id' => $journalEntry->id, 'account\_id' => $apAccount->id, 'credit\_amount' => $total]);



               $invoice->update(\['journal\_entry\_id' => $journalEntry->id]);

           });

       }

   }

}

```

---



## PurchasePaymentSeeder.php – Pay supplier invoices

```

<?php

namespace Modules\\Purchase\\Database\\Seeders;



use Illuminate\\Database\\Seeder;

use Modules\\Purchase\\Infrastructure\\Persistence\\Eloquent\\PurchaseInvoice;

use Modules\\Finance\\Infrastructure\\Persistence\\Eloquent\\Payment;

use Modules\\Finance\\Infrastructure\\Persistence\\Eloquent\\PaymentMethod;

use Modules\\Finance\\Infrastructure\\Persistence\\Eloquent\\PaymentAllocation;

use Modules\\Finance\\Infrastructure\\Persistence\\Eloquent\\JournalEntry;

use Modules\\Finance\\Infrastructure\\Persistence\\Eloquent\\JournalEntryLine;

use Modules\\Finance\\Infrastructure\\Persistence\\Eloquent\\Account;

use Modules\\Finance\\Infrastructure\\Persistence\\Eloquent\\FiscalPeriod;

use Modules\\Core\\Infrastructure\\Persistence\\Eloquent\\Currency;

use Modules\\User\\Infrastructure\\Persistence\\Eloquent\\User;



class PurchasePaymentSeeder extends Seeder

{

   public function run(): void

   {

       $invoices = PurchaseInvoice::where('status', 'approved')->get();

       $usd = Currency::where('code', 'USD')->first();

       $bankAccount = Account::where('is\_bank\_account', true)->first();

       $apAccount = Account::where('code', '2000')->first();

       $fiscalPeriod = FiscalPeriod::where('status', 'open')->first();



       foreach ($invoices as $invoice) {

           $user = User::where('tenant\_id', $invoice->tenant\_id)->first();

           $method = PaymentMethod::firstOrCreate(\['tenant\_id' => $invoice->tenant\_id, 'name' => 'Bank Transfer'], \['type' => 'bank\_transfer', 'account\_id' => $bankAccount->id, 'is\_active' => true]);



           $payment = Payment::create(\[

               'tenant\_id' => $invoice->tenant\_id,

               'payment\_number' => 'PAY-OUT-' . date('Ymd') . '-' . $invoice->id,

               'direction' => 'outbound',

               'party\_type' => 'supplier',

               'party\_id' => $invoice->supplier\_id,

               'payment\_method\_id' => $method->id,

               'account\_id' => $bankAccount->id,

               'amount' => $invoice->grand\_total,

               'currency\_id' => $usd->id,

               'exchange\_rate' => 1,

               'base\_amount' => $invoice->grand\_total,

               'payment\_date' => now(),

               'status' => 'posted',

           ]);



           PaymentAllocation::create(\[

               'payment\_id' => $payment->id,

               'invoice\_type' => PurchaseInvoice::class,

               'invoice\_id' => $invoice->id,

               'allocated\_amount' => $invoice->grand\_total,

           ]);



           // Journal Entry: Dr AP, Cr Bank

           $journalEntry = JournalEntry::create(\[

               'tenant\_id' => $invoice->tenant\_id,

               'fiscal\_period\_id' => $fiscalPeriod->id,

               'entry\_type' => 'auto',

               'reference\_type' => Payment::class,

               'reference\_id' => $payment->id,

               'entry\_date' => $payment->payment\_date,

               'status' => 'posted',

               'created\_by' => $user->id,

               'posted\_by' => $user->id,

               'posted\_at' => now(),

           ]);

           JournalEntryLine::create(\['journal\_entry\_id' => $journalEntry->id, 'account\_id' => $apAccount->id, 'debit\_amount' => $payment->amount]);

           JournalEntryLine::create(\['journal\_entry\_id' => $journalEntry->id, 'account\_id' => $bankAccount->id, 'credit\_amount' => $payment->amount]);



           $payment->update(\['journal\_entry\_id' => $journalEntry->id]);

           $invoice->update(\['status' => 'paid', 'paid\_amount' => $invoice->grand\_total]);

       }

   }

}

```

---



## PurchaseReturnWithOriginalSeeder.php – Return goods to supplier with original GRN reference

```

<?php

namespace Modules\\Purchase\\Database\\Seeders;



use Illuminate\\Database\\Seeder;

use Modules\\Purchase\\Infrastructure\\Persistence\\Eloquent\\GrnHeader;

use Modules\\Purchase\\Infrastructure\\Persistence\\Eloquent\\PurchaseReturn;

use Modules\\Purchase\\Infrastructure\\Persistence\\Eloquent\\PurchaseReturnLine;

use Modules\\Inventory\\Infrastructure\\Persistence\\Eloquent\\StockMovement;

use Modules\\Inventory\\Infrastructure\\Persistence\\Eloquent\\StockLevel;

use Modules\\Inventory\\Infrastructure\\Persistence\\Eloquent\\InventoryCostLayer;

use Modules\\Finance\\Infrastructure\\Persistence\\Eloquent\\JournalEntry;

use Modules\\Finance\\Infrastructure\\Persistence\\Eloquent\\JournalEntryLine;

use Modules\\Finance\\Infrastructure\\Persistence\\Eloquent\\Account;

use Modules\\Finance\\Infrastructure\\Persistence\\Eloquent\\FiscalPeriod;

use Modules\\Finance\\Infrastructure\\Persistence\\Eloquent\\CreditMemo;

use Modules\\Warehouse\\Infrastructure\\Persistence\\Eloquent\\WarehouseLocation;

use Modules\\User\\Infrastructure\\Persistence\\Eloquent\\User;

use Modules\\Core\\Infrastructure\\Persistence\\Eloquent\\Currency;

use Illuminate\\Support\\Facades\\DB;



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

               $user = User::where('tenant\_id', $grn->tenant\_id)->first();

               $returnLocation = WarehouseLocation::where('warehouse\_id', $grn->warehouse\_id)->where('type', 'bin')->first();



               $purchaseReturn = PurchaseReturn::create(\[

                   'tenant\_id' => $grn->tenant\_id,

                   'supplier\_id' => $grn->supplier\_id,

                   'original\_grn\_id' => $grn->id,

                   'return\_number' => 'PR-' . date('Ymd') . '-001',

                   'status' => 'approved',

                   'return\_date' => now(),

                   'return\_reason' => 'Damaged items',

                   'currency\_id' => $usd->id,

               ]);



               $totalReturnCost = 0;

               foreach ($grn->lines->take(1) as $line) { // Return part of first line

                   $returnQty = ceil($line->received\_qty \* 0.2); // 20% return

                   $lineCost = $returnQty \* $line->unit\_cost;

                   $totalReturnCost += $lineCost;



                   $returnLine = PurchaseReturnLine::create(\[

                       'purchase\_return\_id' => $purchaseReturn->id,

                       'original\_grn\_line\_id' => $line->id,

                       'product\_id' => $line->product\_id,

                       'from\_location\_id' => $line->location\_id,

                       'uom\_id' => $line->uom\_id,

                       'return\_qty' => $returnQty,

                       'unit\_cost' => $line->unit\_cost,

                       'condition' => 'damaged',

                       'disposition' => 'return\_to\_vendor',

                   ]);



                   // Stock Movement (return\_out)

                   StockMovement::create(\[

                       'tenant\_id' => $grn->tenant\_id,

                       'product\_id' => $line->product\_id,

                       'from\_location\_id' => $line->location\_id,

                       'movement\_type' => 'return\_out',

                       'reference\_type' => PurchaseReturn::class,

                       'reference\_id' => $purchaseReturn->id,

                       'uom\_id' => $line->uom\_id,

                       'quantity' => $returnQty,

                       'unit\_cost' => $line->unit\_cost,

                       'performed\_by' => $user->id,

                   ]);



                   // Reduce stock level

                   StockLevel::where(\[

                       'tenant\_id' => $grn->tenant\_id,

                       'product\_id' => $line->product\_id,

                       'location\_id' => $line->location\_id,

                   ])->decrement('quantity\_on\_hand', $returnQty);



                   // Adjust cost layer (remove from original layer)

                   $layer = InventoryCostLayer::where('reference\_id', $grn->id)

                       ->where('product\_id', $line->product\_id)

                       ->where('quantity\_remaining', '>', 0)

                       ->first();

                   if ($layer) {

                       $layer->decrement('quantity\_remaining', $returnQty);

                   }

               }



               $purchaseReturn->update(\['subtotal' => $totalReturnCost, 'grand\_total' => $totalReturnCost]);



               // Journal Entry: Dr AP, Cr Inventory

               $journalEntry = JournalEntry::create(\[

                   'tenant\_id' => $grn->tenant\_id,

                   'fiscal\_period\_id' => $fiscalPeriod->id,

                   'entry\_type' => 'auto',

                   'reference\_type' => PurchaseReturn::class,

                   'reference\_id' => $purchaseReturn->id,

                   'entry\_date' => $purchaseReturn->return\_date,

                   'status' => 'posted',

                   'created\_by' => $user->id,

                   'posted\_by' => $user->id,

                   'posted\_at' => now(),

               ]);

               JournalEntryLine::create(\['journal\_entry\_id' => $journalEntry->id, 'account\_id' => $apAccount->id, 'debit\_amount' => $totalReturnCost]);

               JournalEntryLine::create(\['journal\_entry\_id' => $journalEntry->id, 'account\_id' => $inventoryAccount->id, 'credit\_amount' => $totalReturnCost]);



               $purchaseReturn->update(\['journal\_entry\_id' => $journalEntry->id]);



               // Debit Note / Credit Memo

               CreditMemo::create(\[

                   'tenant\_id' => $grn->tenant\_id,

                   'party\_type' => 'supplier',

                   'party\_id' => $grn->supplier\_id,

                   'return\_order\_type' => PurchaseReturn::class,

                   'return\_order\_id' => $purchaseReturn->id,

                   'credit\_memo\_number' => 'DN-' . $purchaseReturn->return\_number,

                   'amount' => $totalReturnCost,

                   'status' => 'issued',

                   'issued\_date' => now(),

                   'journal\_entry\_id' => $journalEntry->id,

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

namespace Modules\\Purchase\\Database\\Seeders;



use Illuminate\\Database\\Seeder;

use Modules\\Purchase\\Infrastructure\\Persistence\\Eloquent\\GrnHeader;

use Modules\\Purchase\\Infrastructure\\Persistence\\Eloquent\\GrnLine;

use Modules\\Inventory\\Infrastructure\\Persistence\\Eloquent\\StockMovement;

use Modules\\Inventory\\Infrastructure\\Persistence\\Eloquent\\StockLevel;

use Modules\\Inventory\\Infrastructure\\Persistence\\Eloquent\\InventoryCostLayer;

use Modules\\Finance\\Infrastructure\\Persistence\\Eloquent\\JournalEntry;

use Modules\\Finance\\Infrastructure\\Persistence\\Eloquent\\JournalEntryLine;

use Modules\\Finance\\Infrastructure\\Persistence\\Eloquent\\Account;

use Modules\\Finance\\Infrastructure\\Persistence\\Eloquent\\FiscalPeriod;

use Modules\\Supplier\\Infrastructure\\Persistence\\Eloquent\\Supplier;

use Modules\\Product\\Infrastructure\\Persistence\\Eloquent\\Product;

use Modules\\Warehouse\\Infrastructure\\Persistence\\Eloquent\\Warehouse;

use Modules\\Warehouse\\Infrastructure\\Persistence\\Eloquent\\WarehouseLocation;

use Modules\\User\\Infrastructure\\Persistence\\Eloquent\\User;

use Modules\\Core\\Infrastructure\\Persistence\\Eloquent\\Tenant;

use Modules\\Core\\Infrastructure\\Persistence\\Eloquent\\Currency;

use Illuminate\\Support\\Facades\\DB;



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

               $supplier = Supplier::where('tenant\_id', $tenant->id)->first();

               $warehouse = Warehouse::where('tenant\_id', $tenant->id)->where('is\_default', true)->first();

               $user = User::where('tenant\_id', $tenant->id)->first();

               $product = Product::where('tenant\_id', $tenant->id)->where('type', 'physical')->first();

               $location = WarehouseLocation::where('warehouse\_id', $warehouse->id)->where('type', 'bin')->first();

               if (!$supplier || !$warehouse || !$user || !$product || !$location) return;



               // Direct GRN (no PO)

               $grn = GrnHeader::create(\[

                   'tenant\_id' => $tenant->id,

                   'supplier\_id' => $supplier->id,

                   'warehouse\_id' => $warehouse->id,

                   'purchase\_order\_id' => null,

                   'grn\_number' => 'GRN-DIRECT-' . date('Ymd'),

                   'status' => 'complete',

                   'received\_date' => now()->subDays(2),

                   'currency\_id' => $usd->id,

                   'created\_by' => $user->id,

               ]);



               $qty = 15;

               $unitCost = 8.75;

               GrnLine::create(\[

                   'grn\_header\_id' => $grn->id,

                   'product\_id' => $product->id,

                   'location\_id' => $location->id,

                   'uom\_id' => $product->base\_uom\_id,

                   'received\_qty' => $qty,

                   'unit\_cost' => $unitCost,

               ]);



               // Stock Movement \& Level

               StockMovement::create(\[...]); // similar to above

               StockLevel::updateOrCreate(\[...])->increment('quantity\_on\_hand', $qty);

               InventoryCostLayer::create(\[...]);



               // Journal Entry: Dr Inventory, Cr AP (direct to AP since no GR/IR)

               $total = $qty \* $unitCost;

               $journalEntry = JournalEntry::create(\[...]);

               JournalEntryLine::create(\['journal\_entry\_id' => $journalEntry->id, 'account\_id' => $inventoryAccount->id, 'debit\_amount' => $total]);

               JournalEntryLine::create(\['journal\_entry\_id' => $journalEntry->id, 'account\_id' => $apAccount->id, 'credit\_amount' => $total]);

           });

       }

   }

}

```

---



## Sales Module Seeders (Complete Scenarios)

```

<?php

namespace Modules\\Sales\\Database\\Seeders;



use Illuminate\\Database\\Seeder;

use Modules\\Sales\\Infrastructure\\Persistence\\Eloquent\\SalesOrder;

use Modules\\Sales\\Infrastructure\\Persistence\\Eloquent\\SalesOrderLine;

use Modules\\Customer\\Infrastructure\\Persistence\\Eloquent\\Customer;

use Modules\\Product\\Infrastructure\\Persistence\\Eloquent\\Product;

use Modules\\Warehouse\\Infrastructure\\Persistence\\Eloquent\\Warehouse;

use Modules\\User\\Infrastructure\\Persistence\\Eloquent\\User;

use Modules\\Core\\Infrastructure\\Persistence\\Eloquent\\Tenant;

use Modules\\Core\\Infrastructure\\Persistence\\Eloquent\\Currency;



class SalesOrderSeeder extends Seeder

{

   public function run(): void

   {

       $tenants = Tenant::all();

       $usd = Currency::where('code', 'USD')->first();



       foreach ($tenants as $tenant) {

           $customer = Customer::where('tenant\_id', $tenant->id)->first();

           $warehouse = Warehouse::where('tenant\_id', $tenant->id)->where('is\_default', true)->first();

           $user = User::where('tenant\_id', $tenant->id)->first();

           if (!$customer || !$warehouse || !$user) continue;



           $so = SalesOrder::create(\[

               'tenant\_id' => $tenant->id,

               'customer\_id' => $customer->id,

               'org\_unit\_id' => $warehouse->org\_unit\_id,

               'warehouse\_id' => $warehouse->id,

               'so\_number' => 'SO-' . date('Ymd') . '-001',

               'status' => 'confirmed',

               'currency\_id' => $usd->id,

               'order\_date' => now()->subDays(5),

               'created\_by' => $user->id,

               'approved\_by' => $user->id,

           ]);



           $products = Product::where('tenant\_id', $tenant->id)->where('type', 'physical')->take(2)->get();

           $subtotal = 0;

           foreach ($products as $product) {

               $qty = rand(2, 10);

               $price = 29.99;

               $lineTotal = $qty \* $price;

               $subtotal += $lineTotal;

               SalesOrderLine::create(\[

                   'sales\_order\_id' => $so->id,

                   'product\_id' => $product->id,

                   'uom\_id' => $product->base\_uom\_id,

                   'ordered\_qty' => $qty,

                   'unit\_price' => $price,

                   'line\_total' => $lineTotal,

                   'reserved\_qty' => $qty,

               ]);

           }

           $so->update(\['subtotal' => $subtotal, 'grand\_total' => $subtotal]);

       }

   }

}

```

---



## ShipmentFromSoSeeder.php – Ship against SO (stock issue \& COGS)

```

<?php

namespace Modules\\Sales\\Database\\Seeders;



use Illuminate\\Database\\Seeder;

use Modules\\Sales\\Infrastructure\\Persistence\\Eloquent\\SalesOrder;

use Modules\\Sales\\Infrastructure\\Persistence\\Eloquent\\Shipment;

use Modules\\Sales\\Infrastructure\\Persistence\\Eloquent\\ShipmentLine;

use Modules\\Inventory\\Infrastructure\\Persistence\\Eloquent\\StockMovement;

use Modules\\Inventory\\Infrastructure\\Persistence\\Eloquent\\StockLevel;

use Modules\\Inventory\\Infrastructure\\Persistence\\Eloquent\\InventoryCostLayer;

use Modules\\Finance\\Infrastructure\\Persistence\\Eloquent\\JournalEntry;

use Modules\\Finance\\Infrastructure\\Persistence\\Eloquent\\JournalEntryLine;

use Modules\\Finance\\Infrastructure\\Persistence\\Eloquent\\Account;

use Modules\\Finance\\Infrastructure\\Persistence\\Eloquent\\FiscalPeriod;

use Modules\\Warehouse\\Infrastructure\\Persistence\\Eloquent\\WarehouseLocation;

use Modules\\User\\Infrastructure\\Persistence\\Eloquent\\User;

use Modules\\Core\\Infrastructure\\Persistence\\Eloquent\\Currency;

use Illuminate\\Support\\Facades\\DB;



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

               $user = User::where('tenant\_id', $so->tenant\_id)->first();

               $location = WarehouseLocation::where('warehouse\_id', $so->warehouse\_id)->where('type', 'bin')->first();

               if (!$user || !$location) return;



               $shipment = Shipment::create(\[

                   'tenant\_id' => $so->tenant\_id,

                   'customer\_id' => $so->customer\_id,

                   'sales\_order\_id' => $so->id,

                   'warehouse\_id' => $so->warehouse\_id,

                   'shipment\_number' => 'SHIP-' . $so->so\_number,

                   'status' => 'shipped',

                   'shipped\_date' => now()->subDays(1),

                   'currency\_id' => $usd->id,

               ]);



               $totalCogs = 0;

               foreach ($so->lines as $line) {

                   $shipmentLine = ShipmentLine::create(\[

                       'shipment\_id' => $shipment->id,

                       'sales\_order\_line\_id' => $line->id,

                       'product\_id' => $line->product\_id,

                       'from\_location\_id' => $location->id,

                       'uom\_id' => $line->uom\_id,

                       'shipped\_qty' => $line->ordered\_qty,

                   ]);



                   // Allocate cost layer (FIFO)

                   $layer = InventoryCostLayer::where('product\_id', $line->product\_id)

                       ->where('location\_id', $location->id)

                       ->where('quantity\_remaining', '>', 0)

                       ->orderBy('layer\_date')

                       ->first();

                   $unitCost = $layer ? $layer->unit\_cost : 10.00;

                   $cogs = $line->ordered\_qty \* $unitCost;

                   $totalCogs += $cogs;



                   // Stock Movement

                   StockMovement::create(\[

                       'tenant\_id' => $so->tenant\_id,

                       'product\_id' => $line->product\_id,

                       'from\_location\_id' => $location->id,

                       'movement\_type' => 'shipment',

                       'reference\_type' => Shipment::class,

                       'reference\_id' => $shipment->id,

                       'uom\_id' => $line->uom\_id,

                       'quantity' => $line->ordered\_qty,

                       'unit\_cost' => $unitCost,

                       'performed\_by' => $user->id,

                   ]);



                   // Reduce stock

                   StockLevel::where(\['product\_id' => $line->product\_id, 'location\_id' => $location->id])

                       ->decrement('quantity\_on\_hand', $line->ordered\_qty);



                   // Consume cost layer

                   if ($layer) {

                       $layer->decrement('quantity\_remaining', $line->ordered\_qty);

                   }



                   $line->update(\['shipped\_qty' => $line->ordered\_qty]);

               }



               // COGS Journal Entry

               $journalEntry = JournalEntry::create(\[...]);

               JournalEntryLine::create(\['journal\_entry\_id' => $journalEntry->id, 'account\_id' => $cogsAccount->id, 'debit\_amount' => $totalCogs]);

               JournalEntryLine::create(\['journal\_entry\_id' => $journalEntry->id, 'account\_id' => $inventoryAccount->id, 'credit\_amount' => $totalCogs]);



               $so->update(\['status' => 'shipped']);

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



// Create shipment with sales\_order\_id = null, directly issue stock, create invoice optionally.



---



## SalesReturnWithOriginalSeeder.php – Return with original SO reference

```

<?php

namespace Modules\\Sales\\Database\\Seeders;



use Illuminate\\Database\\Seeder;

use Modules\\Sales\\Infrastructure\\Persistence\\Eloquent\\SalesOrder;

use Modules\\Sales\\Infrastructure\\Persistence\\Eloquent\\SalesReturn;

use Modules\\Sales\\Infrastructure\\Persistence\\Eloquent\\SalesReturnLine;

use Modules\\Inventory\\Infrastructure\\Persistence\\Eloquent\\StockMovement;

use Modules\\Inventory\\Infrastructure\\Persistence\\Eloquent\\StockLevel;

use Modules\\Inventory\\Infrastructure\\Persistence\\Eloquent\\InventoryCostLayer;

use Modules\\Finance\\Infrastructure\\Persistence\\Eloquent\\JournalEntry;

use Modules\\Finance\\Infrastructure\\Persistence\\Eloquent\\JournalEntryLine;

use Modules\\Finance\\Infrastructure\\Persistence\\Eloquent\\Account;

use Modules\\Finance\\Infrastructure\\Persistence\\Eloquent\\FiscalPeriod;

use Modules\\Finance\\Infrastructure\\Persistence\\Eloquent\\CreditMemo;

use Modules\\Warehouse\\Infrastructure\\Persistence\\Eloquent\\WarehouseLocation;

use Modules\\User\\Infrastructure\\Persistence\\Eloquent\\User;

use Modules\\Core\\Infrastructure\\Persistence\\Eloquent\\Currency;

use Illuminate\\Support\\Facades\\DB;



class SalesReturnWithOriginalSeeder extends Seeder

{

   public function run(): void

   {

       $orders = SalesOrder::where('status', 'shipped')->take(1)->get();

       $usd = Currency::where('code', 'USD')->first();

       $salesReturnsAccount = Account::firstOrCreate(\['code' => '4100', 'name' => 'Sales Returns', 'type' => 'revenue', 'normal\_balance' => 'debit']);

       $arAccount = Account::where('code', '1200')->first();

       $inventoryAccount = Account::where('code', '1300')->first();

       $cogsAccount = Account::where('code', '5000')->first();

       $fiscalPeriod = FiscalPeriod::where('status', 'open')->first();



       foreach ($orders as $so) {

           DB::transaction(function () use ($so, $usd, $salesReturnsAccount, $arAccount, $inventoryAccount, $cogsAccount, $fiscalPeriod) {

               $user = User::where('tenant\_id', $so->tenant\_id)->first();

               $restockLocation = WarehouseLocation::where('warehouse\_id', $so->warehouse\_id)->where('type', 'bin')->first();



               $salesReturn = SalesReturn::create(\[

                   'tenant\_id' => $so->tenant\_id,

                   'customer\_id' => $so->customer\_id,

                   'original\_sales\_order\_id' => $so->id,

                   'return\_number' => 'SR-' . date('Ymd') . '-001',

                   'status' => 'approved',

                   'return\_date' => now(),

                   'return\_reason' => 'Wrong size',

                   'currency\_id' => $usd->id,

               ]);



               $totalRevenueDebit = 0;

               $totalInventoryCredit = 0;



               foreach ($so->lines->take(1) as $line) {

                   $returnQty = ceil($line->ordered\_qty \* 0.3); // 30% return

                   $revenueDebit = $returnQty \* $line->unit\_price;

                   $totalRevenueDebit += $revenueDebit;



                   // Original cost from shipment movement

                   $shipMovement = StockMovement::where('reference\_type', Shipment::class)

                       ->where('product\_id', $line->product\_id)

                       ->orderBy('performed\_at', 'desc')

                       ->first();

                   $originalCost = $shipMovement ? $shipMovement->unit\_cost : 10.00;

                   $inventoryCredit = $returnQty \* $originalCost;

                   $totalInventoryCredit += $inventoryCredit;



                   $returnLine = SalesReturnLine::create(\[

                       'sales\_return\_id' => $salesReturn->id,

                       'original\_sales\_order\_line\_id' => $line->id,

                       'product\_id' => $line->product\_id,

                       'to\_location\_id' => $restockLocation->id,

                       'uom\_id' => $line->uom\_id,

                       'return\_qty' => $returnQty,

                       'unit\_price' => $line->unit\_price,

                       'condition' => 'good',

                       'disposition' => 'restock',

                   ]);



                   // Stock Movement (return\_in)

                   StockMovement::create(\[

                       'tenant\_id' => $so->tenant\_id,

                       'product\_id' => $line->product\_id,

                       'to\_location\_id' => $restockLocation->id,

                       'movement\_type' => 'return\_in',

                       'reference\_type' => SalesReturn::class,

                       'reference\_id' => $salesReturn->id,

                       'uom\_id' => $line->uom\_id,

                       'quantity' => $returnQty,

                       'unit\_cost' => $originalCost,

                       'performed\_by' => $user->id,

                   ]);



                   // Increase stock

                   StockLevel::updateOrCreate(\[

                       'tenant\_id' => $so->tenant\_id,

                       'product\_id' => $line->product\_id,

                       'location\_id' => $restockLocation->id,

                   ], \['uom\_id' => $line->uom\_id])->increment('quantity\_on\_hand', $returnQty);



                   // Re-insert cost layer

                   InventoryCostLayer::create(\[

                       'tenant\_id' => $so->tenant\_id,

                       'product\_id' => $line->product\_id,

                       'location\_id' => $restockLocation->id,

                       'valuation\_method' => $line->product->valuation\_method,

                       'layer\_date' => now(),

                       'quantity\_in' => $returnQty,

                       'quantity\_remaining' => $returnQty,

                       'unit\_cost' => $originalCost,

                       'reference\_type' => SalesReturn::class,

                       'reference\_id' => $salesReturn->id,

                   ]);

               }



               $salesReturn->update(\['subtotal' => $totalRevenueDebit, 'grand\_total' => $totalRevenueDebit]);



               // Journal Entry

               $journalEntry = JournalEntry::create(\[...]);

               // Dr Sales Returns, Cr AR

               JournalEntryLine::create(\['journal\_entry\_id' => $journalEntry->id, 'account\_id' => $salesReturnsAccount->id, 'debit\_amount' => $totalRevenueDebit]);

               JournalEntryLine::create(\['journal\_entry\_id' => $journalEntry->id, 'account\_id' => $arAccount->id, 'credit\_amount' => $totalRevenueDebit]);

               // Dr Inventory, Cr COGS (reversal)

               JournalEntryLine::create(\['journal\_entry\_id' => $journalEntry->id, 'account\_id' => $inventoryAccount->id, 'debit\_amount' => $totalInventoryCredit]);

               JournalEntryLine::create(\['journal\_entry\_id' => $journalEntry->id, 'account\_id' => $cogsAccount->id, 'credit\_amount' => $totalInventoryCredit]);



               $salesReturn->update(\['journal\_entry\_id' => $journalEntry->id]);



               // Credit Memo

               CreditMemo::create(\[

                   'tenant\_id' => $so->tenant\_id,

                   'party\_type' => 'customer',

                   'party\_id' => $so->customer\_id,

                   'return\_order\_type' => SalesReturn::class,

                   'return\_order\_id' => $salesReturn->id,

                   'credit\_memo\_number' => 'CM-' . $salesReturn->return\_number,

                   'amount' => $totalRevenueDebit,

                   'status' => 'issued',

                   'issued\_date' => now(),

                   'journal\_entry\_id' => $journalEntry->id,

               ]);

           });

       }

   }

}

```

---



## SalesReturnRestockingFeeSeeder.php – Return with restocking fee



// Similar to above but adds restocking\_fee to line and separate revenue account.

// Journal entry includes restocking fee as credit to Restocking Fee Revenue.



---



## SalesReturnRefundSeeder.php – Refund credit memo as cash



// Creates a payment (outbound) linked to a credit memo, posts Dr AR/CreditMemo Liability, Cr Bank.





---



## SalesReturnWithoutOriginalSeeder.php – Return without original reference



// No original\_sales\_order\_id; uses current average cost for inventory restock.



---



## Purchase Order Lifecycle Seeder

```

<?php

namespace Modules\\Purchase\\Database\\Seeders;



use Illuminate\\Database\\Seeder;

use Modules\\Purchase\\Infrastructure\\Persistence\\Eloquent\\PurchaseOrder;

use Modules\\Purchase\\Infrastructure\\Persistence\\Eloquent\\PurchaseOrderLine;

use Modules\\Supplier\\Infrastructure\\Persistence\\Eloquent\\Supplier;

use Modules\\Product\\Infrastructure\\Persistence\\Eloquent\\Product;

use Modules\\Warehouse\\Infrastructure\\Persistence\\Eloquent\\Warehouse;

use Modules\\User\\Infrastructure\\Persistence\\Eloquent\\User;

use Modules\\Core\\Infrastructure\\Persistence\\Eloquent\\Tenant;

use Modules\\Core\\Infrastructure\\Persistence\\Eloquent\\Currency;



class PurchaseOrderLifecycleSeeder extends Seeder

{

   public function run(): void

   {

       $tenant = Tenant::first();

       $supplier = Supplier::where('tenant\_id', $tenant->id)->first();

       $warehouse = Warehouse::where('tenant\_id', $tenant->id)->first();

       $user = User::where('tenant\_id', $tenant->id)->first();

       $product = Product::where('tenant\_id', $tenant->id)->first();

       $usd = Currency::where('code', 'USD')->first();



       if (!$supplier || !$warehouse || !$user || !$product) return;



       // 1. CREATE Draft PO

       $draftPo = PurchaseOrder::create(\[

           'tenant\_id' => $tenant->id,

           'supplier\_id' => $supplier->id,

           'warehouse\_id' => $warehouse->id,

           'po\_number' => 'PO-DRAFT-001',

           'status' => 'draft',

           'currency\_id' => $usd->id,

           'order\_date' => now(),

           'created\_by' => $user->id,

       ]);

       PurchaseOrderLine::create(\[

           'purchase\_order\_id' => $draftPo->id,

           'product\_id' => $product->id,

           'uom\_id' => $product->base\_uom\_id,

           'ordered\_qty' => 10,

           'unit\_price' => 15.00,

       ]);



       // 2. UPDATE to Cancelled (soft delete demonstration)

       $draftPo->update(\['status' => 'cancelled']);

       $draftPo->delete(); // soft delete



       // 3. CREATE another PO that goes through full flow

       $activePo = PurchaseOrder::create(\[

           'tenant\_id' => $tenant->id,

           'supplier\_id' => $supplier->id,

           'warehouse\_id' => $warehouse->id,

           'po\_number' => 'PO-ACTIVE-001',

           'status' => 'draft',

           'currency\_id' => $usd->id,

           'order\_date' => now()->subDays(2),

           'created\_by' => $user->id,

       ]);

       PurchaseOrderLine::create(\[

           'purchase\_order\_id' => $activePo->id,

           'product\_id' => $product->id,

           'uom\_id' => $product->base\_uom\_id,

           'ordered\_qty' => 20,

           'unit\_price' => 15.00,

       ]);



       // UPDATE to confirmed

       $activePo->update(\[

           'status' => 'confirmed',

           'approved\_by' => $user->id,

       ]);

   }

}

```

---



## Sales Order Lifecycle Seeder

```

<?php

namespace Modules\\Sales\\Database\\Seeders;



use Illuminate\\Database\\Seeder;

use Modules\\Sales\\Infrastructure\\Persistence\\Eloquent\\SalesOrder;

use Modules\\Sales\\Infrastructure\\Persistence\\Eloquent\\SalesOrderLine;

use Modules\\Customer\\Infrastructure\\Persistence\\Eloquent\\Customer;

use Modules\\Product\\Infrastructure\\Persistence\\Eloquent\\Product;

use Modules\\Warehouse\\Infrastructure\\Persistence\\Eloquent\\Warehouse;

use Modules\\User\\Infrastructure\\Persistence\\Eloquent\\User;

use Modules\\Core\\Infrastructure\\Persistence\\Eloquent\\Tenant;

use Modules\\Core\\Infrastructure\\Persistence\\Eloquent\\Currency;



class SalesOrderLifecycleSeeder extends Seeder

{

   public function run(): void

   {

       $tenant = Tenant::first();

       $customer = Customer::where('tenant\_id', $tenant->id)->first();

       $warehouse = Warehouse::where('tenant\_id', $tenant->id)->first();

       $user = User::where('tenant\_id', $tenant->id)->first();

       $product = Product::where('tenant\_id', $tenant->id)->first();

       $usd = Currency::where('code', 'USD')->first();



       if (!$customer || !$warehouse || !$user || !$product) return;



       // CREATE draft SO

       $draftSo = SalesOrder::create(\[

           'tenant\_id' => $tenant->id,

           'customer\_id' => $customer->id,

           'warehouse\_id' => $warehouse->id,

           'so\_number' => 'SO-DRAFT-001',

           'status' => 'draft',

           'currency\_id' => $usd->id,

           'order\_date' => now(),

           'created\_by' => $user->id,

       ]);

       SalesOrderLine::create(\[

           'sales\_order\_id' => $draftSo->id,

           'product\_id' => $product->id,

           'uom\_id' => $product->base\_uom\_id,

           'ordered\_qty' => 5,

           'unit\_price' => 49.99,

       ]);



       // UPDATE to Cancelled \& soft delete

       $draftSo->update(\['status' => 'cancelled']);

       $draftSo->delete();



       // CREATE another SO for full flow

       $activeSo = SalesOrder::create(\[

           'tenant\_id' => $tenant->id,

           'customer\_id' => $customer->id,

           'warehouse\_id' => $warehouse->id,

           'so\_number' => 'SO-ACTIVE-001',

           'status' => 'draft',

           'currency\_id' => $usd->id,

           'order\_date' => now()->subDays(3),

           'created\_by' => $user->id,

       ]);

       SalesOrderLine::create(\[

           'sales\_order\_id' => $activeSo->id,

           'product\_id' => $product->id,

           'uom\_id' => $product->base\_uom\_id,

           'ordered\_qty' => 8,

           'unit\_price' => 49.99,

       ]);



       // UPDATE to confirmed

       $activeSo->update(\[

           'status' => 'confirmed',

           'approved\_by' => $user->id,

       ]);

   }

}

```

---



## Invoice Lifecycle Seeder (Purchase \& Sales)

```

<?php

namespace Modules\\Purchase\\Database\\Seeders;



use Illuminate\\Database\\Seeder;

use Modules\\Purchase\\Infrastructure\\Persistence\\Eloquent\\PurchaseInvoice;

use Modules\\Purchase\\Infrastructure\\Persistence\\Eloquent\\PurchaseInvoiceLine;

use Modules\\Purchase\\Infrastructure\\Persistence\\Eloquent\\GrnHeader;

use Modules\\Finance\\Infrastructure\\Persistence\\Eloquent\\JournalEntry;

use Modules\\Finance\\Infrastructure\\Persistence\\Eloquent\\JournalEntryLine;

use Modules\\Finance\\Infrastructure\\Persistence\\Eloquent\\Account;

use Modules\\Finance\\Infrastructure\\Persistence\\Eloquent\\FiscalPeriod;

use Modules\\User\\Infrastructure\\Persistence\\Eloquent\\User;



class PurchaseInvoiceLifecycleSeeder extends Seeder

{

   public function run(): void

   {

       $grn = GrnHeader::where('status', 'posted')->first();

       if (!$grn) return;



       $user = User::where('tenant\_id', $grn->tenant\_id)->first();

       $apAccount = Account::where('code', '2000')->first();

       $grIrAccount = Account::where('code', '1500')->first();

       $fiscalPeriod = FiscalPeriod::where('status', 'open')->first();



       // CREATE draft invoice

       $invoice = PurchaseInvoice::create(\[

           'tenant\_id' => $grn->tenant\_id,

           'supplier\_id' => $grn->supplier\_id,

           'grn\_header\_id' => $grn->id,

           'invoice\_number' => 'INV-DRAFT-001',

           'status' => 'draft',

           'invoice\_date' => now(),

           'due\_date' => now()->addDays(30),

           'currency\_id' => $grn->currency\_id,

           'subtotal' => 1000.00,

           'grand\_total' => 1000.00,

       ]);



       // Add line

       $line = $grn->lines->first();

       PurchaseInvoiceLine::create(\[

           'purchase\_invoice\_id' => $invoice->id,

           'grn\_line\_id' => $line->id,

           'product\_id' => $line->product\_id,

           'uom\_id' => $line->uom\_id,

           'quantity' => $line->received\_qty,

           'unit\_price' => $line->unit\_cost,

           'line\_total' => $line->line\_cost,

       ]);



       // UPDATE to approved (with journal entry)

       $invoice->update(\['status' => 'approved']);

       $journalEntry = JournalEntry::create(\[

           'tenant\_id' => $grn->tenant\_id,

           'fiscal\_period\_id' => $fiscalPeriod->id,

           'entry\_type' => 'auto',

           'reference\_type' => PurchaseInvoice::class,

           'reference\_id' => $invoice->id,

           'entry\_date' => $invoice->invoice\_date,

           'status' => 'posted',

           'created\_by' => $user->id,

           'posted\_by' => $user->id,

           'posted\_at' => now(),

       ]);

       JournalEntryLine::create(\['journal\_entry\_id' => $journalEntry->id, 'account\_id' => $grIrAccount->id, 'debit\_amount' => 1000.00]);

       JournalEntryLine::create(\['journal\_entry\_id' => $journalEntry->id, 'account\_id' => $apAccount->id, 'credit\_amount' => 1000.00]);

       $invoice->update(\['journal\_entry\_id' => $journalEntry->id]);



       // CREATE another invoice that will be voided (soft delete)

       $voidInvoice = PurchaseInvoice::create(\[

           'tenant\_id' => $grn->tenant\_id,

           'supplier\_id' => $grn->supplier\_id,

           'invoice\_number' => 'INV-VOID-001',

           'status' => 'cancelled',

           'invoice\_date' => now(),

           'due\_date' => now()->addDays(30),

           'currency\_id' => $grn->currency\_id,

           'subtotal' => 500.00,

           'grand\_total' => 500.00,

       ]);

       $voidInvoice->delete(); // soft delete

   }

}

```

---



## PurchaseCreateSeeder.php – Create PO, confirm, receive (GRN), invoice

```

<?php

namespace Database\\Seeders\\Purchase;



use Illuminate\\Database\\Seeder;

use Modules\\Purchase\\Models\\PurchaseOrder;

use Modules\\Purchase\\Models\\PurchaseOrderLine;

use Modules\\Purchase\\Models\\GrnHeader;

use Modules\\Purchase\\Models\\GrnLine;

use Modules\\Purchase\\Models\\PurchaseInvoice;

use Modules\\Purchase\\Models\\PurchaseInvoiceLine;

use Modules\\Supplier\\Models\\Supplier;

use Modules\\Product\\Models\\Product;

use Modules\\Warehouse\\Models\\Warehouse;

use Modules\\Warehouse\\Models\\WarehouseLocation;

use Modules\\User\\Models\\User;

use Modules\\Core\\Models\\Tenant;

use Modules\\Finance\\Models\\Account;

use Modules\\Finance\\Models\\FiscalPeriod;

use Modules\\Finance\\Models\\JournalEntry;

use Modules\\Finance\\Models\\JournalEntryLine;

use Modules\\Inventory\\Models\\StockMovement;

use Modules\\Inventory\\Models\\StockLevel;

use Modules\\Inventory\\Models\\InventoryCostLayer;

use Illuminate\\Support\\Facades\\DB;



class PurchaseCreateSeeder extends Seeder

{

   public function run(): void

   {

       $tenant = Tenant::first();

       $supplier = Supplier::where('tenant\_id', $tenant->id)->first();

       $warehouse = Warehouse::where('tenant\_id', $tenant->id)->first();

       $user = User::where('tenant\_id', $tenant->id)->first();

       $product = Product::where('tenant\_id', $tenant->id)->first();

       $location = WarehouseLocation::where('warehouse\_id', $warehouse->id)->first();

       $usd = \\Modules\\Core\\Models\\Currency::where('code', 'USD')->first();

       $inventoryAccount = Account::where('code', '1300')->first();

       $apAccount = Account::where('code', '2000')->first();

       $grIrAccount = Account::where('code', '1500')->first();

       $fiscalPeriod = FiscalPeriod::where('status', 'open')->first();



       DB::transaction(function () use ($tenant, $supplier, $warehouse, $user, $product, $location, $usd, $inventoryAccount, $apAccount, $grIrAccount, $fiscalPeriod) {

           // CREATE Purchase Order

           $po = PurchaseOrder::create(\[

               'tenant\_id' => $tenant->id,

               'supplier\_id' => $supplier->id,

               'warehouse\_id' => $warehouse->id,

               'po\_number' => 'PO-CREATE-001',

               'status' => 'draft',

               'currency\_id' => $usd->id,

               'order\_date' => now(),

               'expected\_date' => now()->addDays(7),

               'created\_by' => $user->id,

           ]);



           PurchaseOrderLine::create(\[

               'purchase\_order\_id' => $po->id,

               'product\_id' => $product->id,

               'uom\_id' => $product->base\_uom\_id,

               'ordered\_qty' => 25,

               'unit\_price' => 12.50,

               'line\_total' => 312.50,

           ]);



           // UPDATE: Confirm PO

           $po->update(\['status' => 'confirmed', 'approved\_by' => $user->id]);



           // CREATE Goods Receipt (GRN)

           $grn = GrnHeader::create(\[

               'tenant\_id' => $tenant->id,

               'supplier\_id' => $supplier->id,

               'warehouse\_id' => $warehouse->id,

               'purchase\_order\_id' => $po->id,

               'grn\_number' => 'GRN-CREATE-001',

               'status' => 'complete',

               'received\_date' => now(),

               'currency\_id' => $usd->id,

               'created\_by' => $user->id,

           ]);



           $line = $po->lines->first();

           GrnLine::create(\[

               'grn\_header\_id' => $grn->id,

               'purchase\_order\_line\_id' => $line->id,

               'product\_id' => $line->product\_id,

               'location\_id' => $location->id,

               'uom\_id' => $line->uom\_id,

               'expected\_qty' => $line->ordered\_qty,

               'received\_qty' => $line->ordered\_qty,

               'unit\_cost' => $line->unit\_price,

               'line\_cost' => $line->ordered\_qty \* $line->unit\_price,

           ]);



           // Stock Movement (receipt)

           StockMovement::create(\[

               'tenant\_id' => $tenant->id,

               'product\_id' => $product->id,

               'to\_location\_id' => $location->id,

               'movement\_type' => 'receipt',

               'reference\_type' => GrnHeader::class,

               'reference\_id' => $grn->id,

               'uom\_id' => $product->base\_uom\_id,

               'quantity' => 25,

               'unit\_cost' => 12.50,

               'performed\_by' => $user->id,

           ]);



           // Update Stock Level

           StockLevel::updateOrCreate(\[

               'tenant\_id' => $tenant->id,

               'product\_id' => $product->id,

               'location\_id' => $location->id,

           ], \['uom\_id' => $product->base\_uom\_id])

               ->increment('quantity\_on\_hand', 25);



           // Cost Layer

           InventoryCostLayer::create(\[

               'tenant\_id' => $tenant->id,

               'product\_id' => $product->id,

               'location\_id' => $location->id,

               'valuation\_method' => 'fifo',

               'layer\_date' => now(),

               'quantity\_in' => 25,

               'quantity\_remaining' => 25,

               'unit\_cost' => 12.50,

               'reference\_type' => GrnHeader::class,

               'reference\_id' => $grn->id,

           ]);



           // Journal Entry: Dr Inventory, Cr GR/IR

           $je = JournalEntry::create(\[

               'tenant\_id' => $tenant->id,

               'fiscal\_period\_id' => $fiscalPeriod->id,

               'entry\_type' => 'auto',

               'reference\_type' => GrnHeader::class,

               'reference\_id' => $grn->id,

               'entry\_date' => now(),

               'status' => 'posted',

               'created\_by' => $user->id,

               'posted\_by' => $user->id,

               'posted\_at' => now(),

           ]);

           JournalEntryLine::create(\['journal\_entry\_id' => $je->id, 'account\_id' => $inventoryAccount->id, 'debit\_amount' => 312.50]);

           JournalEntryLine::create(\['journal\_entry\_id' => $je->id, 'account\_id' => $grIrAccount->id, 'credit\_amount' => 312.50]);



           // CREATE Purchase Invoice

           $invoice = PurchaseInvoice::create(\[

               'tenant\_id' => $tenant->id,

               'supplier\_id' => $supplier->id,

               'grn\_header\_id' => $grn->id,

               'invoice\_number' => 'INV-CREATE-001',

               'status' => 'approved',

               'invoice\_date' => now(),

               'due\_date' => now()->addDays(30),

               'currency\_id' => $usd->id,

               'subtotal' => 312.50,

               'grand\_total' => 312.50,

               'ap\_account\_id' => $apAccount->id,

           ]);

           PurchaseInvoiceLine::create(\[

               'purchase\_invoice\_id' => $invoice->id,

               'grn\_line\_id' => $grn->lines->first()->id,

               'product\_id' => $product->id,

               'uom\_id' => $product->base\_uom\_id,

               'quantity' => 25,

               'unit\_price' => 12.50,

               'line\_total' => 312.50,

           ]);



           // Journal Entry: Dr GR/IR, Cr AP

           $je2 = JournalEntry::create(\[...]);

           JournalEntryLine::create(\['journal\_entry\_id' => $je2->id, 'account\_id' => $grIrAccount->id, 'debit\_amount' => 312.50]);

           JournalEntryLine::create(\['journal\_entry\_id' => $je2->id, 'account\_id' => $apAccount->id, 'credit\_amount' => 312.50]);

           $invoice->update(\['journal\_entry\_id' => $je2->id]);

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

   $po = PurchaseOrder::where('po\_number', 'PO-CREATE-001')->first();

   if ($po) {

       // UPDATE line quantity

       $line = $po->lines->first();

       $line->update(\['ordered\_qty' => 30, 'line\_total' => 30 \* $line->unit\_price]);

       $po->update(\['subtotal' => 30 \* $line->unit\_price, 'grand\_total' => 30 \* $line->unit\_price]);



       // UPDATE invoice to paid (via payment)

       $invoice = PurchaseInvoice::where('invoice\_number', 'INV-CREATE-001')->first();

       // Create payment and allocate...

       $invoice->update(\['status' => 'paid']);

   }

}

```

---



## SalesReturnCreateSeeder.php – Create and approve a sales return with original reference, restock, credit memo

```

<?php

namespace Database\\Seeders\\Returns;



use Illuminate\\Database\\Seeder;

use Modules\\Sales\\Models\\SalesOrder;

use Modules\\Sales\\Models\\SalesReturn;

use Modules\\Sales\\Models\\SalesReturnLine;

use Modules\\Inventory\\Models\\StockMovement;

use Modules\\Inventory\\Models\\StockLevel;

use Modules\\Inventory\\Models\\InventoryCostLayer;

use Modules\\Finance\\Models\\JournalEntry;

use Modules\\Finance\\Models\\JournalEntryLine;

use Modules\\Finance\\Models\\CreditMemo;

use Modules\\Finance\\Models\\Account;

use Modules\\Warehouse\\Models\\WarehouseLocation;

use Modules\\User\\Models\\User;

use Illuminate\\Support\\Facades\\DB;



class SalesReturnCreateSeeder extends Seeder

{

   public function run(): void

   {

       $so = SalesOrder::where('status', 'shipped')->first();

       if (!$so) return;



       DB::transaction(function () use ($so) {

           $user = User::where('tenant\_id', $so->tenant\_id)->first();

           $restockLocation = WarehouseLocation::where('warehouse\_id', $so->warehouse\_id)->first();

           $salesReturnsAccount = Account::where('code', '4100')->first();

           $arAccount = Account::where('code', '1200')->first();

           $inventoryAccount = Account::where('code', '1300')->first();

           $cogsAccount = Account::where('code', '5000')->first();



           $salesReturn = SalesReturn::create(\[

               'tenant\_id' => $so->tenant\_id,

               'customer\_id' => $so->customer\_id,

               'original\_sales\_order\_id' => $so->id,

               'return\_number' => 'SR-CREATE-001',

               'status' => 'draft',

               'return\_date' => now(),

               'return\_reason' => 'Defective',

               'currency\_id' => $so->currency\_id,

           ]);



           $line = $so->lines->first();

           $returnQty = 2;

           $revenueDebit = $returnQty \* $line->unit\_price;



           $returnLine = SalesReturnLine::create(\[

               'sales\_return\_id' => $salesReturn->id,

               'original\_sales\_order\_line\_id' => $line->id,

               'product\_id' => $line->product\_id,

               'to\_location\_id' => $restockLocation->id,

               'uom\_id' => $line->uom\_id,

               'return\_qty' => $returnQty,

               'unit\_price' => $line->unit\_price,

               'condition' => 'defective',

               'disposition' => 'restock',

               'restocking\_fee' => 5.00,

           ]);



           // Approve return

           $salesReturn->update(\['status' => 'approved']);



           // Stock Movement (return\_in)

           StockMovement::create(\[... 'movement\_type' => 'return\_in', 'quantity' => $returnQty, 'unit\_cost' => 10.00]);



           // Update stock level

           StockLevel::updateOrCreate(\[...])->increment('quantity\_on\_hand', $returnQty);



           // Re-insert cost layer

           InventoryCostLayer::create(\[... 'quantity\_in' => $returnQty, 'unit\_cost' => 10.00]);



           // Journal Entry

           $je = JournalEntry::create(\[...]);

           JournalEntryLine::create(\['journal\_entry\_id' => $je->id, 'account\_id' => $salesReturnsAccount->id, 'debit\_amount' => $revenueDebit]);

           JournalEntryLine::create(\['journal\_entry\_id' => $je->id, 'account\_id' => $arAccount->id, 'credit\_amount' => $revenueDebit + 5.00]);

           JournalEntryLine::create(\['journal\_entry\_id' => $je->id, 'account\_id' => $inventoryAccount->id, 'debit\_amount' => $returnQty \* 10.00]);

           JournalEntryLine::create(\['journal\_entry\_id' => $je->id, 'account\_id' => $cogsAccount->id, 'credit\_amount' => $returnQty \* 10.00]);



           // Credit Memo

           CreditMemo::create(\[... 'amount' => $revenueDebit + 5.00, 'status' => 'issued']);



           $salesReturn->update(\['status' => 'closed']);

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

   $table->enum('party\_type', \['supplier', 'customer', 'both']);

   $table->string('name');

   $table->string('tax\_id')->nullable();

   $table->string('email')->nullable();

   $table->string('phone')->nullable();

   $table->string('website')->nullable();

   $table->boolean('is\_active')->default(true);

   $table->timestamps();

   $table->softDeletes();

});



// 2. Party addresses

Schema::create('party\_addresses', function (Blueprint $table) {

   $table->id();

   $table->foreignId('party\_id')->constrained('parties')->restrictOnDelete();

   $table->enum('address\_type', \['billing', 'shipping', 'legal']);

   $table->string('line1');

   $table->string('line2')->nullable();

   $table->string('city');

   $table->string('state')->nullable();

   $table->string('postal\_code')->nullable();

   $table->string('country');

   $table->boolean('is\_default')->default(false);

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

Schema::create('product\_categories', function (Blueprint $table) {

   $table->id();

   $table->foreignId('parent\_id')->nullable()->constrained('product\_categories')->nullOnDelete();

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

   $table->enum('product\_type', \['simple', 'variant\_parent', 'bundle', 'digital', 'service']);

   $table->boolean('is\_stockable')->default(true);

   $table->boolean('is\_tracked\_batch')->default(false);

   $table->boolean('is\_tracked\_serial')->default(false);

   $table->decimal('weight', 12, 4)->nullable();

   $table->foreignId('weight\_uom\_id')->nullable()->constrained('uoms')->nullOnDelete();

   $table->foreignId('category\_id')->nullable()->constrained('product\_categories')->nullOnDelete();

   $table->timestamps();

   $table->softDeletes();

});



// 6. Product variants

Schema::create('product\_variants', function (Blueprint $table) {

   $table->id();

   $table->foreignId('product\_id')->constrained('products')->cascadeOnDelete();

   $table->string('sku')->unique();

   $table->json('attributes'); // e.g. {"color":"red"}

   $table->string('barcode', 100)->nullable()->unique();

   $table->boolean('is\_active')->default(true);

   $table->timestamps();

});



// 7. Product UOM conversions

Schema::create('product\_uom\_conversions', function (Blueprint $table) {

   $table->id();

   $table->foreignId('product\_id')->constrained('products')->cascadeOnDelete();

   $table->foreignId('from\_uom\_id')->constrained('uoms')->restrictOnDelete();

   $table->foreignId('to\_uom\_id')->constrained('uoms')->restrictOnDelete();

   $table->decimal('factor', 20, 10);

   $table->unique(\['product\_id', 'from\_uom\_id', 'to\_uom\_id']);

   $table->timestamps();

});



// 8. Warehouses \& storage locations

Schema::create('warehouses', function (Blueprint $table) {

   $table->id();

   $table->string('code', 50)->unique();

   $table->string('name');

   $table->text('address')->nullable();

   $table->boolean('is\_active')->default(true);

   $table->timestamps();

});



Schema::create('storage\_locations', function (Blueprint $table) {

   $table->id();

   $table->foreignId('warehouse\_id')->constrained('warehouses')->cascadeOnDelete();

   $table->string('code', 100);

   $table->string('barcode', 100)->nullable();

   $table->timestamps();

   $table->unique(\['warehouse\_id', 'code']);

});



// 9. Batches \& serial numbers

Schema::create('batches', function (Blueprint $table) {

   $table->id();

   $table->foreignId('product\_id')->constrained('products')->restrictOnDelete();

   $table->string('batch\_number', 100);

   $table->string('manufacturer\_batch', 100)->nullable();

   $table->date('expiry\_date')->nullable();

   $table->date('manufacture\_date')->nullable();

   $table->string('barcode', 100)->nullable();

   $table->boolean('is\_active')->default(true);

   $table->timestamps();

   $table->unique(\['product\_id', 'batch\_number']);

});



Schema::create('serial\_numbers', function (Blueprint $table) {

   $table->id();

   $table->foreignId('product\_id')->constrained('products')->restrictOnDelete();

   $table->foreignId('variant\_id')->nullable()->constrained('product\_variants')->nullOnDelete();

   $table->foreignId('batch\_id')->nullable()->constrained('batches')->nullOnDelete();

   $table->string('serial\_number', 100)->unique();

   $table->enum('status', \['in\_stock', 'sold', 'returned', 'scrapped'])->default('in\_stock');

   $table->foreignId('current\_location\_id')->nullable()->constrained('storage\_locations')->nullOnDelete();

   $table->timestamps();

});



// 10. Purchase side

Schema::create('purchase\_orders', function (Blueprint $table) {

   $table->id();

   $table->string('po\_number', 50)->unique();

   $table->foreignId('supplier\_id')->constrained('parties')->restrictOnDelete();

   $table->date('order\_date');

   $table->date('expected\_date')->nullable();

   $table->enum('status', \['draft', 'confirmed', 'partially\_received', 'received', 'cancelled'])->default('draft');

   $table->decimal('total\_amount', 15, 2);

   $table->char('currency', 3)->default('USD');

   $table->text('notes')->nullable();

   $table->timestamps();

});



Schema::create('purchase\_order\_lines', function (Blueprint $table) {

   $table->id();

   $table->foreignId('purchase\_order\_id')->constrained('purchase\_orders')->cascadeOnDelete();

   $table->foreignId('product\_id')->constrained('products')->restrictOnDelete();

   $table->foreignId('variant\_id')->nullable()->constrained('product\_variants')->nullOnDelete();

   $table->foreignId('uom\_id')->constrained('uoms')->restrictOnDelete();

   $table->decimal('quantity', 15, 5);

   $table->decimal('unit\_price', 15, 5);

   $table->decimal('discount\_percent', 8, 2)->default(0);

   $table->decimal('tax\_rate', 8, 4)->default(0);

   $table->decimal('total\_line', 15, 2);

   $table->timestamps();

});



Schema::create('purchase\_receipts', function (Blueprint $table) {

   $table->id();

   $table->string('receipt\_number', 50)->unique();

   $table->foreignId('purchase\_order\_id')->nullable()->constrained('purchase\_orders')->nullOnDelete();

   $table->foreignId('supplier\_id')->constrained('parties')->restrictOnDelete();

   $table->dateTime('receipt\_date');

   $table->foreignId('warehouse\_id')->constrained('warehouses')->restrictOnDelete();

   $table->enum('status', \['draft', 'completed', 'cancelled'])->default('draft');

   $table->text('notes')->nullable();

   $table->timestamps();

});



Schema::create('purchase\_receipt\_lines', function (Blueprint $table) {

   $table->id();

   $table->foreignId('receipt\_id')->constrained('purchase\_receipts')->cascadeOnDelete();

   $table->foreignId('po\_line\_id')->nullable()->constrained('purchase\_order\_lines')->nullOnDelete();

   $table->foreignId('product\_id')->constrained('products')->restrictOnDelete();

   $table->foreignId('variant\_id')->nullable()->constrained('product\_variants')->nullOnDelete();

   $table->foreignId('uom\_id')->constrained('uoms')->restrictOnDelete();

   $table->decimal('quantity', 15, 5);

   $table->foreignId('batch\_id')->nullable()->constrained('batches')->nullOnDelete();

   $table->text('serial\_numbers')->nullable(); // comma separated

   $table->foreignId('storage\_location\_id')->nullable()->constrained('storage\_locations')->nullOnDelete();

   $table->timestamps();

});



// 11. Sales side (mirror structure)

Schema::create('sales\_orders', function (Blueprint $table) {

   $table->id();

   $table->string('so\_number', 50)->unique();

   $table->foreignId('customer\_id')->constrained('parties')->restrictOnDelete();

   $table->date('order\_date');

   $table->date('requested\_date')->nullable();

   $table->enum('status', \['draft', 'confirmed', 'partially\_delivered', 'delivered', 'cancelled'])->default('draft');

   $table->decimal('total\_amount', 15, 2);

   $table->char('currency', 3)->default('USD');

   $table->timestamps();

});



Schema::create('sales\_order\_lines', function (Blueprint $table) {

   $table->id();

   $table->foreignId('sales\_order\_id')->constrained('sales\_orders')->cascadeOnDelete();

   $table->foreignId('product\_id')->constrained('products');

   $table->foreignId('variant\_id')->nullable()->constrained('product\_variants');

   $table->foreignId('uom\_id')->constrained('uoms');

   $table->decimal('quantity', 15, 5);

   $table->decimal('unit\_price', 15, 5);

   $table->decimal('discount\_percent', 8, 2)->default(0);

   $table->decimal('tax\_rate', 8, 4)->default(0);

   $table->decimal('total\_line', 15, 2);

   $table->timestamps();

});



Schema::create('sales\_deliveries', function (Blueprint $table) {

   $table->id();

   $table->string('delivery\_number', 50)->unique();

   $table->foreignId('sales\_order\_id')->nullable()->constrained('sales\_orders')->nullOnDelete();

   $table->foreignId('customer\_id')->constrained('parties');

   $table->dateTime('delivery\_date');

   $table->foreignId('warehouse\_id')->constrained('warehouses');

   $table->enum('status', \['draft', 'shipped', 'delivered', 'cancelled'])->default('draft');

   $table->timestamps();

});



Schema::create('sales\_delivery\_lines', function (Blueprint $table) {

   $table->id();

   $table->foreignId('delivery\_id')->constrained('sales\_deliveries')->cascadeOnDelete();

   $table->foreignId('so\_line\_id')->nullable()->constrained('sales\_order\_lines')->nullOnDelete();

   $table->foreignId('product\_id')->constrained('products');

   $table->foreignId('variant\_id')->nullable()->constrained('product\_variants');

   $table->foreignId('uom\_id')->constrained('uoms');

   $table->decimal('quantity', 15, 5);

   $table->foreignId('batch\_id')->nullable()->constrained('batches');

   $table->text('serial\_numbers')->nullable();

   $table->foreignId('storage\_location\_id')->nullable()->constrained('storage\_locations');

   $table->timestamps();

});



// 12. Returns (purchase \& sales) – similar pattern, omitted for brevity.



// 13. Stock movements (core inventory)

Schema::create('stock\_movements', function (Blueprint $table) {

   $table->id();

   $table->enum('movement\_type', \['purchase\_receipt', 'sales\_delivery', 'purchase\_return', 'sales\_return', 'adjustment', 'transfer']);

   $table->string('reference\_type', 50); // polymorphic

   $table->unsignedBigInteger('reference\_id');

   $table->foreignId('product\_id')->constrained('products');

   $table->foreignId('variant\_id')->nullable()->constrained('product\_variants');

   $table->foreignId('from\_location\_id')->nullable()->constrained('storage\_locations');

   $table->foreignId('to\_location\_id')->nullable()->constrained('storage\_locations');

   $table->foreignId('batch\_id')->nullable()->constrained('batches');

   $table->foreignId('serial\_id')->nullable()->constrained('serial\_numbers');

   $table->decimal('quantity', 15, 5);

   $table->foreignId('uom\_id')->constrained('uoms');

   $table->dateTime('movement\_date');

   $table->foreignId('created\_by')->nullable()->constrained('users');

   $table->timestamps();



   $table->index(\['reference\_type', 'reference\_id']);

});



// 14. Accounting

Schema::create('accounts', function (Blueprint $table) {

   $table->id();

   $table->string('code', 20)->unique();

   $table->string('name');

   $table->enum('account\_type', \['asset', 'liability', 'equity', 'revenue', 'expense']);

   $table->foreignId('parent\_id')->nullable()->constrained('accounts')->nullOnDelete();

   $table->boolean('is\_control')->default(false);

   $table->boolean('is\_active')->default(true);

   $table->timestamps();

});



Schema::create('journal\_entries', function (Blueprint $table) {

   $table->id();

   $table->string('entry\_number', 50)->unique();

   $table->date('entry\_date');

   $table->string('reference\_type', 50); // polymorphic

   $table->unsignedBigInteger('reference\_id')->nullable();

   $table->text('description')->nullable();

   $table->boolean('is\_posted')->default(false);

   $table->timestamp('posted\_at')->nullable();

   $table->timestamps();



   $table->index(\['reference\_type', 'reference\_id']);

});



Schema::create('journal\_entry\_lines', function (Blueprint $table) {

   $table->id();

   $table->foreignId('journal\_entry\_id')->constrained('journal\_entries')->cascadeOnDelete();

   $table->foreignId('account\_id')->constrained('accounts')->restrictOnDelete();

   $table->decimal('debit', 15, 2)->default(0);

   $table->decimal('credit', 15, 2)->default(0);

   $table->text('memo')->nullable();

   $table->timestamps();



   $table->check('debit >= 0 and credit >= 0');

});



Schema::create('payments', function (Blueprint $table) {

   $table->id();

   $table->string('payment\_number', 50)->unique();

   $table->foreignId('party\_id')->constrained('parties');

   $table->enum('payment\_type', \['supplier\_payment', 'customer\_receipt']);

   $table->decimal('amount', 15, 2);

   $table->date('payment\_date');

   $table->string('reference', 255)->nullable();

   $table->enum('status', \['pending', 'completed', 'failed'])->default('pending');

   $table->foreignId('journal\_entry\_id')->nullable()->constrained('journal\_entries')->nullOnDelete();

   $table->timestamps();

});



// 15. Current stock balances (for performance)

Schema::create('current\_stock\_balances', function (Blueprint $table) {

   $table->id();

   $table->foreignId('product\_id')->constrained('products');

   $table->foreignId('variant\_id')->nullable()->constrained('product\_variants');

   $table->foreignId('warehouse\_id')->constrained('warehouses');

   $table->foreignId('storage\_location\_id')->nullable()->constrained('storage\_locations');

   $table->foreignId('batch\_id')->nullable()->constrained('batches');

   $table->decimal('quantity\_on\_hand', 15, 5)->default(0);

   $table->decimal('quantity\_reserved', 15, 5)->default(0);

   $table->timestamp('last\_updated')->useCurrent();

   $table->unique(\['product\_id', 'variant\_id', 'warehouse\_id', 'storage\_location\_id', 'batch\_id'], 'stock\_balance\_unique');

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

       return array\_column(self::cases(), 'value');

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

       return array\_column(self::cases(), 'value');

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

       return array\_column(self::cases(), 'value');

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

       return array\_column(self::cases(), 'value');

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

       return array\_column(self::cases(), 'value');

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

   case COST\_CENTER = 'cost\_center';

   case PROFIT\_CENTER = 'profit\_center';



   public static function values(): array

   {

       return array\_column(self::cases(), 'value');

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

       return array\_column(self::cases(), 'value');

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

       return array\_column(self::cases(), 'value');

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

       return array\_column(self::cases(), 'value');

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

   case IN\_TRANSIT = 'in\_transit';



   public static function values(): array

   {

       return array\_column(self::cases(), 'value');

   }

}



<?php

// app/Enums/InventoryMovementType.php

enum InventoryMovementType: string

{

   case RECEIPT = 'receipt';

   case ISSUE = 'issue';

   case TRANSFER\_IN = 'transfer\_in';

   case TRANSFER\_OUT = 'transfer\_out';

   case ADJUSTMENT = 'adjustment';

   case RETURN = 'return';

   case RESERVE = 'reserve';

   case UNRESERVE = 'unreserve';

   case CYCLE\_COUNT = 'cycle\_count';



   public static function values(): array

   {

       return array\_column(self::cases(), 'value');

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

       return array\_column(self::cases(), 'value');

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

       return array\_column(self::cases(), 'value');

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

   case PARTIALLY\_RECEIVED = 'partially\_received';

   case RECEIVED = 'received';

   case PARTIALLY\_INVOICED = 'partially\_invoiced';

   case INVOICED = 'invoiced';

   case PAID = 'paid';

   case CANCELLED = 'cancelled';



   public static function values(): array

   {

       return array\_column(self::cases(), 'value');

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

   case CONTRA\_ASSET = 'contra\_asset';

   case CONTRA\_LIABILITY = 'contra\_liability';

   case OTHER\_INCOME = 'other\_income';

   case OTHER\_EXPENSE = 'other\_expense';



   public static function values(): array

   {

       return array\_column(self::cases(), 'value');

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

       return array\_column(self::cases(), 'value');

   }

}



<?php

// app/Enums/AidcTagType.php

enum AidcTagType: string

{

   case BARCODE\_1D = 'barcode\_1d';

   case BARCODE\_2D = 'barcode\_2d';

   case QR = 'qr';

   case RFID\_HF = 'rfid\_hf';

   case RFID\_UHF = 'rfid\_uhf';

   case NFC = 'nfc';

   case GS1\_EPC = 'gs1\_epc';



   public static function values(): array

   {

       return array\_column(self::cases(), 'value');

   }

}



<?php

// app/Enums/AidcEntityType.php

enum AidcEntityType: string

{

   case PRODUCT\_VARIANT = 'product\_variant';

   case INVENTORY\_ITEM = 'inventory\_item';

   case LOCATION = 'location';

   case DOCUMENT = 'document';

   case PARTY = 'party';

   case ASSET = 'asset';



   public static function values(): array

   {

       return array\_column(self::cases(), 'value');

   }

}



use App\\Enums\\TenantStatus;



// Inside up()

$table->enum('status', TenantStatus::values())->default(TenantStatus::ACTIVE->value);



use App\\Enums\\PartyType;

use App\\Enums\\PartyStatus;



$table->enum('party\_type', PartyType::values());

$table->enum('status', PartyStatus::values())->default(PartyStatus::ACTIVE->value);


```






