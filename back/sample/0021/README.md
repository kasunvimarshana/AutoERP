\## Low Stock Query



Product::whereHas('stocks', function ($q) {

&#x20;   $q->selectRaw('SUM(quantity) as total')

&#x20;     ->havingRaw('total <= reorder\_level');

});



\---





\## BatchQueryService



class BatchQueryService

{

&#x20;   public function getAvailableBatches(int $productId, int $warehouseId)

&#x20;   {

&#x20;       return \\DB::table('stocks')

&#x20;           ->join('batches', 'stocks.batch\_id', '=', 'batches.id')

&#x20;           ->where('stocks.product\_id', $productId)

&#x20;           ->where('stocks.warehouse\_id', $warehouseId)

&#x20;           ->where('stocks.quantity', '>', 0)

&#x20;           ->whereDate('batches.expires\_at', '>', now())

&#x20;           ->orderBy('batches.expires\_at', 'asc') // FEFO

&#x20;           ->select(

&#x20;               'stocks.id as stock\_id',

&#x20;               'stocks.batch\_id',

&#x20;               'stocks.quantity',

&#x20;               'batches.expires\_at'

&#x20;           )

&#x20;           ->lockForUpdate() // 🔒 prevents race conditions

&#x20;           ->get();

&#x20;   }

}



\---



\## StockAllocatorService





class StockAllocatorService

{

&#x20;   public function allocate($batches, int $requiredQty): array

&#x20;   {

&#x20;       $allocations = \[];

&#x20;       $remaining = $requiredQty;



&#x20;       foreach ($batches as $batch) {

&#x20;           if ($remaining <= 0) break;



&#x20;           $allocQty = min($batch->quantity, $remaining);



&#x20;           if ($allocQty > 0) {

&#x20;               $allocations\[] = new AllocationItem(

&#x20;                   batchId: $batch->batch\_id,

&#x20;                   allocatedQty: $allocQty

&#x20;               );



&#x20;               $remaining -= $allocQty;

&#x20;           }

&#x20;       }



&#x20;       if ($remaining > 0) {

&#x20;           throw new \\DomainException("Insufficient stock for allocation");

&#x20;       }



&#x20;       return $allocations;

&#x20;   }

}



\---



\## StockService (Update + Movements)



class StockService

{

&#x20;   public function deductStock(

&#x20;       int $productId,

&#x20;       int $warehouseId,

&#x20;       array $allocations,

&#x20;       string $reference

&#x20;   ): void {

&#x20;       foreach ($allocations as $allocation) {



&#x20;           $stock = \\DB::table('stocks')

&#x20;               ->where('product\_id', $productId)

&#x20;               ->where('batch\_id', $allocation->batchId)

&#x20;               ->where('warehouse\_id', $warehouseId)

&#x20;               ->lockForUpdate()

&#x20;               ->first();



&#x20;           if (!$stock || $stock->quantity < $allocation->allocatedQty) {

&#x20;               throw new \\RuntimeException("Stock inconsistency detected");

&#x20;           }



&#x20;           // Update stock

&#x20;           \\DB::table('stocks')

&#x20;               ->where('id', $stock->id)

&#x20;               ->update(\[

&#x20;                   'quantity' => $stock->quantity - $allocation->allocatedQty,

&#x20;                   'updated\_at' => now()

&#x20;               ]);



&#x20;           // Insert movement

&#x20;           \\DB::table('stock\_movements')->insert(\[

&#x20;               'product\_id'   => $productId,

&#x20;               'batch\_id'     => $allocation->batchId,

&#x20;               'warehouse\_id' => $warehouseId,

&#x20;               'type'         => 'sale',

&#x20;               'quantity'     => -$allocation->allocatedQty,

&#x20;               'reference'    => $reference,

&#x20;               'moved\_at'     => now(),

&#x20;               'created\_at'   => now(),

&#x20;               'updated\_at'   => now(),

&#x20;           ]);

&#x20;       }

&#x20;   }

}



\---



\## SaleService (Orchestration Layer)



class SaleService

{

&#x20;   public function \_\_construct(

&#x20;       private BatchQueryService $batchQuery,

&#x20;       private StockAllocatorService $allocator,

&#x20;       private StockService $stockService

&#x20;   ) {}



&#x20;   public function processSale(

&#x20;       int $productId,

&#x20;       int $warehouseId,

&#x20;       int $quantity,

&#x20;       string $reference

&#x20;   ): array {

&#x20;       return \\DB::transaction(function () use (

&#x20;           $productId,

&#x20;           $warehouseId,

&#x20;           $quantity,

&#x20;           $reference

&#x20;       ) {

&#x20;           // Step 1: Fetch batches

&#x20;           $batches = $this->batchQuery

&#x20;               ->getAvailableBatches($productId, $warehouseId);



&#x20;           // Step 2: Allocate

&#x20;           $allocations = $this->allocator

&#x20;               ->allocate($batches, $quantity);



&#x20;           // Step 3: Deduct stock

&#x20;           $this->stockService->deductStock(

&#x20;               $productId,

&#x20;               $warehouseId,

&#x20;               $allocations,

&#x20;               $reference

&#x20;           );



&#x20;           return $allocations;

&#x20;       });

&#x20;   }

}



\---



\## PurchaseOrderController



namespace App\\Http\\Controllers\\Api;



use App\\Services\\PurchaseOrderService;

use Illuminate\\Http\\Request;



class PurchaseOrderController extends BaseController

{

&#x20;   public function \_\_construct(private PurchaseOrderService $service) {}



&#x20;   public function store(Request $request)

&#x20;   {

&#x20;       $data = $request->validate(\[

&#x20;           'supplier\_id' => 'required|exists:suppliers,id',

&#x20;           'items' => 'required|array',

&#x20;           'items.\*.product\_id' => 'required',

&#x20;           'items.\*.quantity' => 'required|integer',

&#x20;           'items.\*.batch\_number' => 'required',

&#x20;           'items.\*.expires\_at' => 'required|date',

&#x20;       ]);



&#x20;       return $this->success(

&#x20;           $this->service->create($data),

&#x20;           'Purchase Order created'

&#x20;       );

&#x20;   }

}





\---



\## SaleController (FEFO Integrated)



namespace App\\Http\\Controllers\\Api;



use App\\Services\\SaleService;

use Illuminate\\Http\\Request;



class SaleController extends BaseController

{

&#x20;   public function \_\_construct(private SaleService $service) {}



&#x20;   public function store(Request $request)

&#x20;   {

&#x20;       $data = $request->validate(\[

&#x20;           'warehouse\_id' => 'required|exists:warehouses,id',

&#x20;           'items' => 'required|array',

&#x20;           'items.\*.product\_id' => 'required|exists:products,id',

&#x20;           'items.\*.quantity' => 'required|integer|min:1',

&#x20;       ]);



&#x20;       $result = $this->service->processSaleTransaction($data);



&#x20;       return $this->success($result, 'Sale completed (FEFO applied)');

&#x20;   }

}



\---



\## BatchQueryService (FEFO Source)



namespace App\\Services;



use Illuminate\\Support\\Facades\\DB;



class BatchQueryService

{

&#x20;   public function getAvailableBatches(int $productId, int $warehouseId)

&#x20;   {

&#x20;       return DB::table('stocks')

&#x20;           ->join('batches', 'stocks.batch\_id', '=', 'batches.id')

&#x20;           ->where('stocks.product\_id', $productId)

&#x20;           ->where('stocks.warehouse\_id', $warehouseId)

&#x20;           ->where('stocks.quantity', '>', 0)

&#x20;           ->whereDate('batches.expires\_at', '>', now())

&#x20;           ->orderBy('batches.expires\_at', 'asc')

&#x20;           ->select('stocks.\*', 'batches.expires\_at')

&#x20;           ->lockForUpdate()

&#x20;           ->get();

&#x20;   }

}



\---



\## StockAllocatorService (FEFO Strategy)



namespace App\\Services;



use App\\Services\\Contracts\\StockAllocationStrategy;

use DomainException;



class StockAllocatorService implements StockAllocationStrategy

{

&#x20;   public function allocate($batches, int $requiredQty): array

&#x20;   {

&#x20;       $allocations = \[];

&#x20;       $remaining = $requiredQty;



&#x20;       foreach ($batches as $batch) {

&#x20;           if ($remaining <= 0) break;



&#x20;           $allocQty = min($batch->quantity, $remaining);



&#x20;           if ($allocQty > 0) {

&#x20;               $allocations\[] = \[

&#x20;                   'batch\_id' => $batch->batch\_id,

&#x20;                   'qty' => $allocQty

&#x20;               ];

&#x20;               $remaining -= $allocQty;

&#x20;           }

&#x20;       }



&#x20;       if ($remaining > 0) {

&#x20;           throw new DomainException('Insufficient stock');

&#x20;       }



&#x20;       return $allocations;

&#x20;   }

}



\---



\## StockService (Write Operations)



namespace App\\Services;



use Illuminate\\Support\\Facades\\DB;



class StockService

{

&#x20;   public function increase(int $productId, int $batchId, int $warehouseId, int $qty, string $ref)

&#x20;   {

&#x20;       $stock = DB::table('stocks')

&#x20;           ->where(compact('productId','batchId','warehouseId'))

&#x20;           ->lockForUpdate()

&#x20;           ->first();



&#x20;       if ($stock) {

&#x20;           DB::table('stocks')->where('id', $stock->id)->update(\[

&#x20;               'quantity' => $stock->quantity + $qty,

&#x20;               'updated\_at' => now()

&#x20;           ]);

&#x20;       } else {

&#x20;           DB::table('stocks')->insert(\[

&#x20;               'product\_id' => $productId,

&#x20;               'batch\_id' => $batchId,

&#x20;               'warehouse\_id' => $warehouseId,

&#x20;               'quantity' => $qty,

&#x20;               'created\_at' => now(),

&#x20;               'updated\_at' => now()

&#x20;           ]);

&#x20;       }



&#x20;       $this->movement($productId, $batchId, $warehouseId, $qty, 'purchase', $ref);

&#x20;   }



&#x20;   public function decrease(int $productId, int $warehouseId, array $allocations, string $ref)

&#x20;   {

&#x20;       foreach ($allocations as $a) {



&#x20;           $stock = DB::table('stocks')

&#x20;               ->where(\[

&#x20;                   'product\_id' => $productId,

&#x20;                   'batch\_id' => $a\['batch\_id'],

&#x20;                   'warehouse\_id' => $warehouseId

&#x20;               ])

&#x20;               ->lockForUpdate()

&#x20;               ->first();



&#x20;           if (!$stock || $stock->quantity < $a\['qty']) {

&#x20;               throw new \\RuntimeException('Stock inconsistency');

&#x20;           }



&#x20;           DB::table('stocks')->where('id', $stock->id)->update(\[

&#x20;               'quantity' => $stock->quantity - $a\['qty'],

&#x20;               'updated\_at' => now()

&#x20;           ]);



&#x20;           $this->movement($productId, $a\['batch\_id'], $warehouseId, -$a\['qty'], 'sale', $ref);

&#x20;       }

&#x20;   }



&#x20;   private function movement($productId, $batchId, $warehouseId, $qty, $type, $ref)

&#x20;   {

&#x20;       DB::table('stock\_movements')->insert(\[

&#x20;           'product\_id' => $productId,

&#x20;           'batch\_id' => $batchId,

&#x20;           'warehouse\_id' => $warehouseId,

&#x20;           'type' => $type,

&#x20;           'quantity' => $qty,

&#x20;           'reference' => $ref,

&#x20;           'moved\_at' => now(),

&#x20;           'created\_at' => now(),

&#x20;           'updated\_at' => now(),

&#x20;       ]);

&#x20;   }

}



\---



\## StockQueryService (Read Optimized)



namespace App\\Services;



use Illuminate\\Support\\Facades\\DB;



class StockQueryService

{

&#x20;   public function getCurrentStock()

&#x20;   {

&#x20;       return DB::table('stocks')

&#x20;           ->join('products', 'stocks.product\_id', '=', 'products.id')

&#x20;           ->select('products.name', DB::raw('SUM(stocks.quantity) as qty'))

&#x20;           ->groupBy('products.name')

&#x20;           ->get();

&#x20;   }



&#x20;   public function getLowStock()

&#x20;   {

&#x20;       return DB::table('products')

&#x20;           ->join('stocks', 'products.id', '=', 'stocks.product\_id')

&#x20;           ->groupBy('products.id')

&#x20;           ->havingRaw('SUM(stocks.quantity) <= products.reorder\_level')

&#x20;           ->select('products.name', DB::raw('SUM(stocks.quantity) as qty'))

&#x20;           ->get();

&#x20;   }



&#x20;   public function getExpiringSoon(int $days = 30)

&#x20;   {

&#x20;       return DB::table('batches')

&#x20;           ->whereDate('expires\_at', '<=', now()->addDays($days))

&#x20;           ->get();

&#x20;   }

}



\---



\## PurchaseOrderService



namespace App\\Services;



use Illuminate\\Support\\Facades\\DB;



class PurchaseOrderService

{

&#x20;   public function \_\_construct(

&#x20;       private StockService $stockService

&#x20;   ) {}



&#x20;   public function create(array $data)

&#x20;   {

&#x20;       return DB::transaction(function () use ($data) {



&#x20;           $poId = DB::table('purchase\_orders')->insertGetId(\[

&#x20;               'supplier\_id' => $data\['supplier\_id'],

&#x20;               'po\_number' => uniqid('PO-'),

&#x20;               'order\_date' => now(),

&#x20;               'created\_at' => now(),

&#x20;               'updated\_at' => now(),

&#x20;           ]);



&#x20;           foreach ($data\['items'] as $item) {



&#x20;               $batchId = DB::table('batches')->insertGetId(\[

&#x20;                   'product\_id' => $item\['product\_id'],

&#x20;                   'batch\_number' => $item\['batch\_number'],

&#x20;                   'expires\_at' => $item\['expires\_at'],

&#x20;                   'purchase\_price' => $item\['purchase\_price'] ?? 0,

&#x20;                   'selling\_price' => $item\['selling\_price'] ?? 0,

&#x20;                   'created\_at' => now(),

&#x20;                   'updated\_at' => now(),

&#x20;               ]);



&#x20;               DB::table('purchase\_order\_items')->insert(\[

&#x20;                   'purchase\_order\_id' => $poId,

&#x20;                   'product\_id' => $item\['product\_id'],

&#x20;                   'batch\_id' => $batchId,

&#x20;                   'quantity' => $item\['quantity'],

&#x20;                   'unit\_price' => $item\['purchase\_price'] ?? 0,

&#x20;                   'created\_at' => now(),

&#x20;                   'updated\_at' => now(),

&#x20;               ]);



&#x20;               $this->stockService->increase(

&#x20;                   $item\['product\_id'],

&#x20;                   $batchId,

&#x20;                   $data\['warehouse\_id'] ?? 1,

&#x20;                   $item\['quantity'],

&#x20;                   "PO:$poId"

&#x20;               );

&#x20;           }



&#x20;           return $poId;

&#x20;       });

&#x20;   }

}



\---



\## SaleService (FEFO Orchestrator)



namespace App\\Services;



use Illuminate\\Support\\Facades\\DB;



class SaleService

{

&#x20;   public function \_\_construct(

&#x20;       private BatchQueryService $batchQuery,

&#x20;       private StockAllocatorService $allocator,

&#x20;       private StockService $stockService

&#x20;   ) {}



&#x20;   public function processSaleTransaction(array $data)

&#x20;   {

&#x20;       return DB::transaction(function () use ($data) {



&#x20;           $saleId = DB::table('sales')->insertGetId(\[

&#x20;               'invoice\_number' => uniqid('INV-'),

&#x20;               'sale\_date' => now(),

&#x20;               'total\_amount' => 0,

&#x20;               'created\_at' => now(),

&#x20;               'updated\_at' => now(),

&#x20;           ]);



&#x20;           foreach ($data\['items'] as $item) {



&#x20;               $batches = $this->batchQuery->getAvailableBatches(

&#x20;                   $item\['product\_id'],

&#x20;                   $data\['warehouse\_id']

&#x20;               );



&#x20;               $allocations = $this->allocator->allocate(

&#x20;                   $batches,

&#x20;                   $item\['quantity']

&#x20;               );



&#x20;               $this->stockService->decrease(

&#x20;                   $item\['product\_id'],

&#x20;                   $data\['warehouse\_id'],

&#x20;                   $allocations,

&#x20;                   "SALE:$saleId"

&#x20;               );



&#x20;               foreach ($allocations as $a) {

&#x20;                   DB::table('sale\_items')->insert(\[

&#x20;                       'sale\_id' => $saleId,

&#x20;                       'product\_id' => $item\['product\_id'],

&#x20;                       'batch\_id' => $a\['batch\_id'],

&#x20;                       'quantity' => $a\['qty'],

&#x20;                       'unit\_price' => 0,

&#x20;                       'created\_at' => now(),

&#x20;                       'updated\_at' => now(),

&#x20;                   ]);

&#x20;               }

&#x20;           }



&#x20;           return \['sale\_id' => $saleId];

&#x20;       });

&#x20;   }

}



\---



\## BarcodeTraceService



namespace App\\Services;



use Illuminate\\Support\\Facades\\DB;



class BarcodeTraceService

{

&#x20;   public function trace(string $code): array

&#x20;   {

&#x20;       $barcode = DB::table('barcodes')

&#x20;           ->where('code', $code)

&#x20;           ->first();



&#x20;       if (!$barcode) {

&#x20;           throw new \\DomainException('Barcode not found');

&#x20;       }



&#x20;       return match ($barcode->type) {

&#x20;           'product'     => $this->traceProduct($barcode),

&#x20;           'batch'       => $this->traceBatch($barcode),

&#x20;           'unit'        => $this->traceUnit($barcode),

&#x20;           'transaction' => $this->traceTransaction($barcode),

&#x20;       };

&#x20;   }



&#x20;   private function traceProduct($barcode)

&#x20;   {

&#x20;       return DB::table('products')

&#x20;           ->where('id', $barcode->barcodeable\_id)

&#x20;           ->first();

&#x20;   }



&#x20;   private function traceBatch($barcode)

&#x20;   {

&#x20;       return \[

&#x20;           'batch' => DB::table('batches')->find($barcode->barcodeable\_id),



&#x20;           'movements' => DB::table('stock\_movements')

&#x20;               ->where('batch\_id', $barcode->barcodeable\_id)

&#x20;               ->orderBy('moved\_at', 'desc')

&#x20;               ->get(),



&#x20;           'stock' => DB::table('stocks')

&#x20;               ->where('batch\_id', $barcode->barcodeable\_id)

&#x20;               ->get(),

&#x20;       ];

&#x20;   }



&#x20;   private function traceUnit($barcode)

&#x20;   {

&#x20;       return DB::table('serial\_numbers')

&#x20;           ->where('id', $barcode->barcodeable\_id)

&#x20;           ->first();

&#x20;   }



&#x20;   private function traceTransaction($barcode)

&#x20;   {

&#x20;       return DB::table('transactions')

&#x20;           ->where('id', $barcode->barcodeable\_id)

&#x20;           ->first();

&#x20;   }

}



\---



\## Rotation Strategies (FIFO)



class FIFOAllocationStrategy implements StockRotationStrategy

{

&#x20;   public function allocate($batches, int $qty): array

&#x20;   {

&#x20;       return collect($batches)

&#x20;           ->sortBy('created\_at')

&#x20;           ->values()

&#x20;           ->pipe(fn($b) => app(BaseAllocator::class)->allocate($b, $qty));

&#x20;   }

}



\## Rotation Strategies (FEFO)



class FEFOAllocationStrategy implements StockRotationStrategy

{

&#x20;   public function allocate($batches, int $qty): array

&#x20;   {

&#x20;       return collect($batches)

&#x20;           ->sortBy('expires\_at')

&#x20;           ->values()

&#x20;           ->pipe(fn($b) => app(BaseAllocator::class)->allocate($b, $qty));

&#x20;   }

}



\## Rotation Strategies (LIFO)



class LIFOAllocationStrategy implements StockRotationStrategy

{

&#x20;   public function allocate($batches, int $qty): array

&#x20;   {

&#x20;       return collect($batches)

&#x20;           ->sortByDesc('created\_at')

&#x20;           ->values()

&#x20;           ->pipe(fn($b) => app(BaseAllocator::class)->allocate($b, $qty));

&#x20;   }

}



\---



\## Valuation Strategies (FIFO Cost)



class FIFOValuationStrategy implements InventoryValuationStrategy

{

&#x20;   public function calculateCost(array $allocations): float

&#x20;   {

&#x20;       return collect($allocations)->sum(fn($a) =>

&#x20;           $a\['qty'] \* $a\['purchase\_price']

&#x20;       );

&#x20;   }

}





\## Valuation Strategies (Weighted Average)



class WeightedAverageStrategy implements InventoryValuationStrategy

{

&#x20;   public function calculateCost(array $allocations): float

&#x20;   {

&#x20;       $totalQty = collect($allocations)->sum('qty');

&#x20;       $totalCost = collect($allocations)->sum(fn($a) =>

&#x20;           $a\['qty'] \* $a\['purchase\_price']

&#x20;       );



&#x20;       return $totalQty ? $totalCost / $totalQty : 0;

&#x20;   }

}



\## Valuation Strategies (Standard Cost)



class StandardCostStrategy implements InventoryValuationStrategy

{

&#x20;   public function calculateCost(array $allocations): float

&#x20;   {

&#x20;       return collect($allocations)->sum(fn($a) =>

&#x20;           $a\['qty'] \* $a\['standard\_cost']

&#x20;       );

&#x20;   }

}



\---



\## Strategy Resolver (Dynamic Runtime Selection)



class InventoryStrategyFactory

{

&#x20;   public function rotation(string $type): StockRotationStrategy

&#x20;   {

&#x20;       return match ($type) {

&#x20;           'fifo' => new FIFOAllocationStrategy(),

&#x20;           'lifo' => new LIFOAllocationStrategy(),

&#x20;           'fefo' => new FEFOAllocationStrategy(),

&#x20;           default => throw new \\Exception('Invalid rotation')

&#x20;       };

&#x20;   }



&#x20;   public function valuation(string $type): InventoryValuationStrategy

&#x20;   {

&#x20;       return match ($type) {

&#x20;           'fifo' => new FIFOValuationStrategy(),

&#x20;           'weighted\_average' => new WeightedAverageStrategy(),

&#x20;           'standard\_cost' => new StandardCostStrategy(),

&#x20;           default => throw new \\Exception('Invalid valuation')

&#x20;       };

&#x20;   }

}



\---



\## Integration with SaleService (Dynamic Allocation + Costing)



$settings = $this->settingsService->resolve($productId, $warehouseId);



$rotation = $this->factory->rotation($settings->rotation\_strategy);

$valuation = $this->factory->valuation($settings->valuation\_method);



// Allocate stock

$allocations = $rotation->allocate($batches, $qty);



// Calculate cost

$cost = $valuation->calculateCost($allocations);



\---



\## Auditable Trait



namespace App\\Models\\Traits;



use Illuminate\\Support\\Facades\\Auth;



trait Auditable

{

&#x20;   protected static function bootAuditable()

&#x20;   {

&#x20;       static::created(fn($model) => self::log('created', $model));

&#x20;       static::updated(fn($model) => self::log('updated', $model));

&#x20;       static::deleted(fn($model) => self::log('deleted', $model));

&#x20;   }



&#x20;   protected static function log($action, $model)

&#x20;   {

&#x20;       \\DB::table('audit\_logs')->insert(\[

&#x20;           'entity\_type' => get\_class($model),

&#x20;           'entity\_id'   => $model->id,

&#x20;           'action'      => $action,

&#x20;           'old\_values'  => json\_encode($model->getOriginal()),

&#x20;           'new\_values'  => json\_encode($model->getAttributes()),

&#x20;           'user\_id'     => Auth::id(),

&#x20;           'created\_at'  => now(),

&#x20;           'updated\_at'  => now(),

&#x20;       ]);

&#x20;   }

}





\---



\## StockMovement



class StockMovement extends Model

{

&#x20;   protected $fillable = \[

&#x20;       'product\_id','product\_variant\_id','batch\_id',

&#x20;       'warehouse\_id','type','quantity',

&#x20;       'reference\_type','reference\_id','meta','moved\_at'

&#x20;   ];



&#x20;   protected $casts = \[

&#x20;       'meta' => 'array',

&#x20;       'moved\_at' => 'datetime'

&#x20;   ];



&#x20;   public function product()

&#x20;   {

&#x20;       return $this->belongsTo(Product::class);

&#x20;   }



&#x20;   public function batch()

&#x20;   {

&#x20;       return $this->belongsTo(Batch::class);

&#x20;   }



&#x20;   public function warehouse()

&#x20;   {

&#x20;       return $this->belongsTo(Warehouse::class);

&#x20;   }



&#x20;   public function reference()

&#x20;   {

&#x20;       return $this->morphTo(\_\_FUNCTION\_\_, 'reference\_type', 'reference\_id');

&#x20;   }

}



\---



\## InventoryStrategyFactory



class InventoryStrategyFactory

{

&#x20;   public function rotation(string $type): RotationStrategy

&#x20;   {

&#x20;       return match ($type) {

&#x20;           'fifo' => new FIFO(),

&#x20;           'lifo' => new LIFO(),

&#x20;           'fefo' => new FEFO(),

&#x20;       };

&#x20;   }



&#x20;   public function valuation(string $type): ValuationStrategy

&#x20;   {

&#x20;       return match ($type) {

&#x20;           'fifo' => new FIFOValuation(),

&#x20;           'weighted\_average' => new WeightedAverage(),

&#x20;           'standard\_cost' => new StandardCost(),

&#x20;       };

&#x20;   }



&#x20;   public function allocation(string $type): AllocationStrategy

&#x20;   {

&#x20;       return match ($type) {

&#x20;           'default' => new DefaultAllocator(),

&#x20;           'strict\_batch' => new StrictBatchAllocator(),

&#x20;       };

&#x20;   }

}



\---





\## BatchQueryService





class BatchQueryService

{

&#x20;   public function getAvailable($productId, $variantId, $warehouseId)

&#x20;   {

&#x20;       return DB::table('stocks')

&#x20;           ->join('batches', 'stocks.batch\_id', '=', 'batches.id')

&#x20;           ->where('stocks.product\_id', $productId)

&#x20;           ->where('stocks.product\_variant\_id', $variantId)

&#x20;           ->where('stocks.warehouse\_id', $warehouseId)

&#x20;           ->where('stocks.quantity', '>', 0)

&#x20;           ->lockForUpdate()

&#x20;           ->get();

&#x20;   }

}



\---





\## StockQueryService





class StockQueryService

{

&#x20;   public function currentStock()

&#x20;   {

&#x20;       return Stock::selectRaw('product\_id, SUM(quantity) as qty')

&#x20;           ->groupBy('product\_id')

&#x20;           ->get();

&#x20;   }

}



\---



\## StockService (Ledger Safe)



class StockService

{

&#x20;   public function increase($data)

&#x20;   {

&#x20;       $stock = Stock::lockForUpdate()->firstOrCreate(\[

&#x20;           'product\_id' => $data\['product\_id'],

&#x20;           'product\_variant\_id' => $data\['variant\_id'],

&#x20;           'batch\_id' => $data\['batch\_id'],

&#x20;           'warehouse\_id' => $data\['warehouse\_id'],

&#x20;       ]);



&#x20;       $stock->increment('quantity', $data\['qty']);



&#x20;       $this->movement($data, 'purchase');

&#x20;   }



&#x20;   public function decrease($data, array $allocations)

&#x20;   {

&#x20;       foreach ($allocations as $a) {



&#x20;           $stock = Stock::lockForUpdate()->where(\[

&#x20;               'product\_id' => $data\['product\_id'],

&#x20;               'product\_variant\_id' => $data\['variant\_id'],

&#x20;               'batch\_id' => $a\['batch\_id'],

&#x20;               'warehouse\_id' => $data\['warehouse\_id'],

&#x20;           ])->first();



&#x20;           if (!$stock || $stock->quantity < $a\['qty']) {

&#x20;               throw new \\RuntimeException('Stock error');

&#x20;           }



&#x20;           $stock->decrement('quantity', $a\['qty']);



&#x20;           $this->movement(\[

&#x20;               ...$data,

&#x20;               'batch\_id' => $a\['batch\_id'],

&#x20;               'qty' => -$a\['qty']

&#x20;           ], 'sale');

&#x20;       }

&#x20;   }



&#x20;   private function movement($data, $type)

&#x20;   {

&#x20;       StockMovement::create(\[

&#x20;           ...$data,

&#x20;           'type' => $type,

&#x20;           'moved\_at' => now(),

&#x20;       ]);

&#x20;   }

}



\---



\## AllocationService (Strategy Engine)



class AllocationService

{

&#x20;   public function \_\_construct(

&#x20;       private InventoryStrategyFactory $factory

&#x20;   ) {}



&#x20;   public function allocate($settings, $batches, $qty)

&#x20;   {

&#x20;       $rotation = $this->factory->rotation($settings->rotation\_strategy);

&#x20;       $allocator = $this->factory->allocation($settings->allocation\_algorithm);



&#x20;       $sorted = $rotation->sort($batches);



&#x20;       return $allocator->allocate($sorted, $qty);

&#x20;   }

}



\---



\## ComboService (BOM Explosion)



class ComboService

{

&#x20;   public function explode($productId, $qty)

&#x20;   {

&#x20;       return ProductComponent::where('parent\_product\_id', $productId)

&#x20;           ->get()

&#x20;           ->map(fn($c) => \[

&#x20;               'product\_id' => $c->component\_product\_id,

&#x20;               'qty' => $c->quantity \* $qty

&#x20;           ]);

&#x20;   }

}





\---



\## FULL ORCHESTRATION



class TransactionService

{

&#x20;   public function \_\_construct(

&#x20;       private BatchQueryService $batchQuery,

&#x20;       private AllocationService $allocation,

&#x20;       private StockService $stock,

&#x20;       private DigitalAssetService $digital,

&#x20;       private ComboService $combo,

&#x20;       private SettingsResolver $settings,

&#x20;       private InventoryStrategyFactory $factory

&#x20;   ) {}



&#x20;   public function process(array $data)

&#x20;   {

&#x20;       return DB::transaction(function () use ($data) {



&#x20;           $transaction = Transaction::create(\[

&#x20;               'type' => $data\['type'],

&#x20;               'warehouse\_id' => $data\['warehouse\_id'],

&#x20;               'reference\_no' => uniqid(),

&#x20;               'transaction\_date' => now(),

&#x20;           ]);



&#x20;           foreach ($data\['items'] as $item) {



&#x20;               $product = Product::findOrFail($item\['product\_id']);



&#x20;               match ($product->type) {



&#x20;                   'service' => $this->handleService($transaction, $item),



&#x20;                   'digital' => $this->handleDigital($transaction, $item),



&#x20;                   'combo' => $this->handleCombo($transaction, $item),



&#x20;                   default => $this->handleStock($transaction, $item)

&#x20;               };

&#x20;           }



&#x20;           return $transaction;

&#x20;       });

&#x20;   }



\---





\## Handle Physical / Variable





private function handleStock($transaction, $item)

{

&#x20;   $settings = $this->settings->resolve(

&#x20;       $item\['product\_id'],

&#x20;       $transaction->warehouse\_id

&#x20;   );



&#x20;   $batches = $this->batchQuery->getAvailable(

&#x20;       $item\['product\_id'],

&#x20;       $item\['variant\_id'] ?? null,

&#x20;       $transaction->warehouse\_id

&#x20;   );



&#x20;   $allocations = $this->allocation->allocate(

&#x20;       $settings,

&#x20;       $batches,

&#x20;       $item\['quantity']

&#x20;   );



&#x20;   $valuation = $this->factory->valuation($settings->valuation\_method);

&#x20;   $cost = $valuation->calculate($allocations);



&#x20;   $this->stock->decrease(\[

&#x20;       'product\_id' => $item\['product\_id'],

&#x20;       'variant\_id' => $item\['variant\_id'] ?? null,

&#x20;       'warehouse\_id' => $transaction->warehouse\_id

&#x20;   ], $allocations);



&#x20;   foreach ($allocations as $a) {

&#x20;       TransactionItem::create(\[

&#x20;           'transaction\_id' => $transaction->id,

&#x20;           'product\_id' => $item\['product\_id'],

&#x20;           'product\_variant\_id' => $item\['variant\_id'] ?? null,

&#x20;           'batch\_id' => $a\['batch\_id'],

&#x20;           'quantity' => $a\['qty'],

&#x20;           'unit\_price' => $cost,

&#x20;       ]);

&#x20;   }

}



\---



\## Handle Digital



private function handleDigital($transaction, $item)

{

&#x20;   $asset = $this->digital->assign($item\['product\_id']);



&#x20;   TransactionItem::create(\[

&#x20;       'transaction\_id' => $transaction->id,

&#x20;       'product\_id' => $item\['product\_id'],

&#x20;       'quantity' => 1,

&#x20;       'meta' => \['license' => $asset->license\_key]

&#x20;   ]);

}



\---



\## Handle Combo



private function handleCombo($transaction, $item)

{

&#x20;   $components = $this->combo->explode(

&#x20;       $item\['product\_id'],

&#x20;       $item\['quantity']

&#x20;   );



&#x20;   foreach ($components as $c) {

&#x20;       $this->handleStock($transaction, \[

&#x20;           'product\_id' => $c\['product\_id'],

&#x20;           'quantity' => $c\['qty']

&#x20;       ]);

&#x20;   }

}



\---



\## Handle Service



private function handleService($transaction, $item)

{

&#x20;   TransactionItem::create(\[

&#x20;       'transaction\_id' => $transaction->id,

&#x20;       'product\_id' => $item\['product\_id'],

&#x20;       'quantity' => $item\['quantity'],

&#x20;       'unit\_price' => $item\['price']

&#x20;   ]);

}





\---



\## app/Http/Middleware/ResolveTenant.php



public function handle($request, Closure $next)

{

&#x20;   $tenantId = $request->header('X-Tenant-ID') ?? $request->route('tenant');

&#x20;   $tenant = TenantConfig::find($tenantId); // from central store



&#x20;   if (!$tenant) {

&#x20;       abort(404, 'Tenant not found');

&#x20;   }



&#x20;   // Dynamically set database connection

&#x20;   config(\["database.connections.tenant" => \[

&#x20;       'driver'   => $tenant->db\_driver,

&#x20;       'host'     => $tenant->db\_host,

&#x20;       'database' => $tenant->db\_name,

&#x20;       'username' => $tenant->db\_user,

&#x20;       'password' => decrypt($tenant->db\_password),

&#x20;       // ...

&#x20;   ]]);

&#x20;   DB::purge('tenant');

&#x20;   DB::reconnect('tenant');



&#x20;   // Set mail, cache, queue, etc.

&#x20;   config(\["mail.mailers.smtp" => array\_merge(

&#x20;       config("mail.mailers.smtp"),

&#x20;       $tenant->mail\_config

&#x20;   )]);

&#x20;   config(\["cache.default" => $tenant->cache\_driver]);

&#x20;   config(\["queue.default" => $tenant->queue\_driver]);



&#x20;   // Feature flags

&#x20;   Feature::define('advanced-reports', fn() => $tenant->feature\_flags\['advanced-reports'] ?? false);



&#x20;   // Bind tenant to service container

&#x20;   app()->instance('current\_tenant', $tenant);



&#x20;   return $next($request);

}



\---





class FiscalYearSeeder extends Seeder

{

&#x20;   public function run(): void

&#x20;   {

&#x20;       FiscalYear::create(\[

&#x20;           'tenant\_id' => 1,

&#x20;           'name' => 'FY 2025',

&#x20;           'start\_date' => '2025-01-01',

&#x20;           'end\_date' => '2025-12-31',

&#x20;           'is\_closed' => false,

&#x20;       ]);

&#x20;   }

}



class AccountingPeriodSeeder extends Seeder

{

&#x20;   public function run(): void

&#x20;   {

&#x20;       $months = \['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

&#x20;       foreach ($months as $i => $month) {

&#x20;           AccountingPeriod::create(\[

&#x20;               'tenant\_id' => 1,

&#x20;               'fiscal\_year\_id' => 1,

&#x20;               'name' => $month . ' 2025',

&#x20;               'period\_number' => $i + 1,

&#x20;               'start\_date' => "2025-" . str\_pad($i+1, 2, '0', STR\_PAD\_LEFT) . "-01",

&#x20;               'end\_date' => date("Y-m-t", strtotime("2025-" . str\_pad($i+1, 2, '0', STR\_PAD\_LEFT) . "-01")),

&#x20;               'status' => 'open',

&#x20;           ]);

&#x20;       }

&#x20;   }

}



class ChartOfAccountSeeder extends Seeder

{

&#x20;   public function run(): void

&#x20;   {

&#x20;       $accounts = \[

&#x20;           \['code' => '1000', 'name' => 'Cash on Hand', 'type' => 'asset', 'normal\_balance' => 'debit', 'level' => 1, 'path' => '/1000', 'is\_leaf' => true],

&#x20;           \['code' => '1100', 'name' => 'Main Checking', 'type' => 'asset', 'normal\_balance' => 'debit', 'is\_bank' => true, 'level' => 1, 'path' => '/1100', 'is\_leaf' => true],

&#x20;           \['code' => '1200', 'name' => 'Accounts Receivable', 'type' => 'asset', 'normal\_balance' => 'debit', 'level' => 1, 'path' => '/1200', 'is\_leaf' => true],

&#x20;           \['code' => '1300', 'name' => 'Inventory', 'type' => 'asset', 'normal\_balance' => 'debit', 'level' => 1, 'path' => '/1300', 'is\_leaf' => true],

&#x20;           \['code' => '2000', 'name' => 'Accounts Payable', 'type' => 'liability', 'normal\_balance' => 'credit', 'level' => 1, 'path' => '/2000', 'is\_leaf' => true],

&#x20;           \['code' => '3000', 'name' => 'Retained Earnings', 'type' => 'equity', 'normal\_balance' => 'credit', 'level' => 1, 'path' => '/3000', 'is\_leaf' => true],

&#x20;           \['code' => '4000', 'name' => 'Sales Revenue', 'type' => 'income', 'normal\_balance' => 'credit', 'level' => 1, 'path' => '/4000', 'is\_leaf' => true],

&#x20;           \['code' => '5000', 'name' => 'Cost of Goods Sold', 'type' => 'expense', 'normal\_balance' => 'debit', 'level' => 1, 'path' => '/5000', 'is\_leaf' => true],

&#x20;           \['code' => '6000', 'name' => 'Operating Expenses', 'type' => 'expense', 'normal\_balance' => 'debit', 'level' => 1, 'path' => '/6000', 'is\_leaf' => true],

&#x20;           \['code' => '7000', 'name' => 'Tax Expense', 'type' => 'expense', 'normal\_balance' => 'debit', 'level' => 1, 'path' => '/7000', 'is\_leaf' => true],

&#x20;       ];

&#x20;       foreach ($accounts as $acc) {

&#x20;           ChartOfAccount::create(array\_merge($acc, \['tenant\_id' => 1, 'is\_active' => true]));

&#x20;       }

&#x20;   }

}



\---



class StockMovementService implements StockMovementServiceInterface

{

&#x20;   protected StockMovementRepositoryInterface $movementRepo;

&#x20;   protected StockBalanceRepositoryInterface $balanceRepo;



&#x20;   public function \_\_construct(

&#x20;       StockMovementRepositoryInterface $movementRepo,

&#x20;       StockBalanceRepositoryInterface $balanceRepo

&#x20;   ) {

&#x20;       $this->movementRepo = $movementRepo;

&#x20;       $this->balanceRepo = $balanceRepo;

&#x20;   }



&#x20;   public function recordReceipt(array $data)

&#x20;   {

&#x20;       return DB::transaction(function () use ($data) {

&#x20;           $movement = $this->movementRepo->create(array\_merge($data, \[

&#x20;               'movement\_type' => 'receipt',

&#x20;               'movement\_number' => $this->generateMovementNumber()

&#x20;           ]));

&#x20;           $this->updateStockBalances(

&#x20;               $data\['product\_id'],

&#x20;               $data\['to\_location\_id'],

&#x20;               $data\['batch\_id'] ?? null,

&#x20;               $data\['quantity']

&#x20;           );

&#x20;           Event::dispatch(new StockMovementCreated($movement->toArray()));

&#x20;           return $movement;

&#x20;       });

&#x20;   }



&#x20;   public function recordIssue(array $data)

&#x20;   {

&#x20;       return DB::transaction(function () use ($data) {

&#x20;           $movement = $this->movementRepo->create(array\_merge($data, \[

&#x20;               'movement\_type' => 'issue',

&#x20;               'movement\_number' => $this->generateMovementNumber()

&#x20;           ]));

&#x20;           $this->updateStockBalances(

&#x20;               $data\['product\_id'],

&#x20;               $data\['from\_location\_id'],

&#x20;               $data\['batch\_id'] ?? null,

&#x20;               -$data\['quantity']

&#x20;           );

&#x20;           Event::dispatch(new StockMovementCreated($movement->toArray()));

&#x20;           return $movement;

&#x20;       });

&#x20;   }



&#x20;   public function recordTransfer(array $data)

&#x20;   {

&#x20;       return DB::transaction(function () use ($data) {

&#x20;           // Out movement

&#x20;           $outMovement = $this->movementRepo->create(array\_merge($data, \[

&#x20;               'movement\_type' => 'transfer',

&#x20;               'from\_location\_id' => $data\['from\_location\_id'],

&#x20;               'to\_location\_id' => null,

&#x20;               'movement\_number' => $this->generateMovementNumber()

&#x20;           ]));

&#x20;           $this->updateStockBalances(

&#x20;               $data\['product\_id'],

&#x20;               $data\['from\_location\_id'],

&#x20;               $data\['batch\_id'] ?? null,

&#x20;               -$data\['quantity']

&#x20;           );



&#x20;           // In movement

&#x20;           $inMovement = $this->movementRepo->create(array\_merge($data, \[

&#x20;               'movement\_type' => 'transfer',

&#x20;               'from\_location\_id' => null,

&#x20;               'to\_location\_id' => $data\['to\_location\_id'],

&#x20;               'movement\_number' => $this->generateMovementNumber()

&#x20;           ]));

&#x20;           $this->updateStockBalances(

&#x20;               $data\['product\_id'],

&#x20;               $data\['to\_location\_id'],

&#x20;               $data\['batch\_id'] ?? null,

&#x20;               $data\['quantity']

&#x20;           );



&#x20;           return \['out' => $outMovement, 'in' => $inMovement];

&#x20;       });

&#x20;   }



&#x20;   public function recordAdjustment(array $data)

&#x20;   {

&#x20;       return DB::transaction(function () use ($data) {

&#x20;           $movement = $this->movementRepo->create(array\_merge($data, \[

&#x20;               'movement\_type' => 'adjustment',

&#x20;               'movement\_number' => $this->generateMovementNumber()

&#x20;           ]));

&#x20;           $this->updateStockBalances(

&#x20;               $data\['product\_id'],

&#x20;               $data\['to\_location\_id'] ?? $data\['from\_location\_id'],

&#x20;               $data\['batch\_id'] ?? null,

&#x20;               $data\['quantity']

&#x20;           );

&#x20;           Event::dispatch(new StockMovementCreated($movement->toArray()));

&#x20;           return $movement;

&#x20;       });

&#x20;   }



&#x20;   public function updateStockBalances(int $productId, int $locationId, ?int $batchId, float $quantityChange)

&#x20;   {

&#x20;       $balance = $this->balanceRepo->findByProductLocationBatch($productId, $locationId, $batchId);

&#x20;       if ($balance) {

&#x20;           $newQty = $balance->qty\_on\_hand + $quantityChange;

&#x20;           $this->balanceRepo->update($balance->id, \[

&#x20;               'qty\_on\_hand' => $newQty,

&#x20;               'qty\_available' => $newQty - $balance->qty\_reserved,

&#x20;               'updated\_at' => now()

&#x20;           ]);

&#x20;       } else if ($quantityChange > 0) {

&#x20;           $this->balanceRepo->create(\[

&#x20;               'tenant\_id' => auth()->user()->tenant\_id,

&#x20;               'product\_id' => $productId,

&#x20;               'location\_id' => $locationId,

&#x20;               'batch\_id' => $batchId,

&#x20;               'uom\_id' => 1, // default

&#x20;               'qty\_on\_hand' => $quantityChange,

&#x20;               'qty\_available' => $quantityChange,

&#x20;               'avg\_cost' => 0

&#x20;           ]);

&#x20;       }

&#x20;       // Negative balance not allowed – would throw exception in real validation

&#x20;   }



&#x20;   protected function generateMovementNumber(): string

&#x20;   {

&#x20;       return 'MOV-' . str\_pad(rand(1, 99999), 5, '0', STR\_PAD\_LEFT);

&#x20;   }

}



class JournalEntryService implements JournalEntryServiceInterface

{

&#x20;   protected JournalEntryRepositoryInterface $repository;



&#x20;   public function \_\_construct(JournalEntryRepositoryInterface $repository)

&#x20;   {

&#x20;       $this->repository = $repository;

&#x20;   }



&#x20;   public function createFromTransaction(string $sourceType, int $sourceId, array $entries)

&#x20;   {

&#x20;       return DB::transaction(function () use ($sourceType, $sourceId, $entries) {

&#x20;           $totalDebit = array\_sum(array\_column($entries, 'debit'));

&#x20;           $totalCredit = array\_sum(array\_column($entries, 'credit'));

&#x20;           if ($totalDebit !== $totalCredit) {

&#x20;               throw new \\Exception('Journal entry must balance: Debits must equal Credits');

&#x20;           }



&#x20;           $period = AccountingPeriod::where('start\_date', '<=', now())

&#x20;               ->where('end\_date', '>=', now())

&#x20;               ->where('status', 'open')

&#x20;               ->first();

&#x20;           if (!$period) {

&#x20;               throw new \\Exception('No open accounting period found');

&#x20;           }



&#x20;           $journalEntry = $this->repository->create(\[

&#x20;               'tenant\_id' => auth()->user()->tenant\_id,

&#x20;               'period\_id' => $period->id,

&#x20;               'entry\_number' => $this->generateEntryNumber(),

&#x20;               'entry\_date' => now(),

&#x20;               'post\_date' => now(),

&#x20;               'source\_type' => $sourceType,

&#x20;               'source\_id' => $sourceId,

&#x20;               'description' => "Auto-generated from {$sourceType} #{$sourceId}",

&#x20;               'currency\_id' => 1,

&#x20;               'exchange\_rate' => 1,

&#x20;               'status' => 'draft',

&#x20;               'created\_by' => auth()->id()

&#x20;           ]);



&#x20;           foreach ($entries as $line) {

&#x20;               $journalEntry->lines()->create(\[

&#x20;                   'account\_id' => $line\['account\_id'],

&#x20;                   'debit' => $line\['debit'],

&#x20;                   'credit' => $line\['credit'],

&#x20;                   'line\_number' => $line\['line\_number'] ?? 0,

&#x20;                   'party\_id' => $line\['party\_id'] ?? null,

&#x20;                   'cost\_center\_id' => $line\['cost\_center\_id'] ?? null,

&#x20;                   'description' => $line\['description'] ?? null

&#x20;               ]);

&#x20;           }



&#x20;           return $journalEntry;

&#x20;       });

&#x20;   }



&#x20;   public function postJournalEntry(int $journalEntryId)

&#x20;   {

&#x20;       $entry = $this->repository->find($journalEntryId);

&#x20;       if ($entry->status !== 'draft') {

&#x20;           throw new \\Exception('Only draft entries can be posted');

&#x20;       }

&#x20;       return $this->repository->update($journalEntryId, \[

&#x20;           'status' => 'posted',

&#x20;           'posted\_by' => auth()->id(),

&#x20;           'posted\_at' => now()

&#x20;       ]);

&#x20;   }



&#x20;   public function reverseJournalEntry(int $journalEntryId, string $reason)

&#x20;   {

&#x20;       $original = $this->repository->find($journalEntryId);

&#x20;       if ($original->status !== 'posted') {

&#x20;           throw new \\Exception('Only posted entries can be reversed');

&#x20;       }



&#x20;       return DB::transaction(function () use ($original, $reason) {

&#x20;           // Create reversing entry with opposite signs

&#x20;           $reversalLines = \[];

&#x20;           foreach ($original->lines as $line) {

&#x20;               $reversalLines\[] = \[

&#x20;                   'account\_id' => $line->account\_id,

&#x20;                   'debit' => $line->credit,

&#x20;                   'credit' => $line->debit,

&#x20;                   'line\_number' => $line->line\_number,

&#x20;                   'party\_id' => $line->party\_id,

&#x20;                   'cost\_center\_id' => $line->cost\_center\_id,

&#x20;                   'description' => "Reversal: {$reason}"

&#x20;               ];

&#x20;           }



&#x20;           $reversal = $this->createFromTransaction(

&#x20;               'reversal',

&#x20;               $original->id,

&#x20;               $reversalLines

&#x20;           );

&#x20;           $this->postJournalEntry($reversal->id);



&#x20;           $this->repository->update($original->id, \[

&#x20;               'status' => 'reversed',

&#x20;               'reversed\_by' => auth()->id()

&#x20;           ]);



&#x20;           return $reversal;

&#x20;       });

&#x20;   }



&#x20;   protected function generateEntryNumber(): string

&#x20;   {

&#x20;       return 'JE-' . date('Ymd') . '-' . str\_pad(rand(1, 9999), 4, '0', STR\_PAD\_LEFT);

&#x20;   }

}



class PurchaseOrderService implements PurchaseOrderServiceInterface

{

&#x20;   protected PurchaseOrderRepositoryInterface $poRepo;

&#x20;   protected GoodsReceiptRepositoryInterface $grRepo;

&#x20;   protected StockMovementServiceInterface $stockMovementService;

&#x20;   protected JournalEntryServiceInterface $journalEntryService;



&#x20;   public function \_\_construct(

&#x20;       PurchaseOrderRepositoryInterface $poRepo,

&#x20;       GoodsReceiptRepositoryInterface $grRepo,

&#x20;       StockMovementServiceInterface $stockMovementService,

&#x20;       JournalEntryServiceInterface $journalEntryService

&#x20;   ) {

&#x20;       $this->poRepo = $poRepo;

&#x20;       $this->grRepo = $grRepo;

&#x20;       $this->stockMovementService = $stockMovementService;

&#x20;       $this->journalEntryService = $journalEntryService;

&#x20;   }



&#x20;   public function createPurchaseOrder(array $data)

&#x20;   {

&#x20;       return $this->poRepo->create($data);

&#x20;   }



&#x20;   public function receiveGoods(int $goodsReceiptId, array $lines)

&#x20;   {

&#x20;       return DB::transaction(function () use ($goodsReceiptId, $lines) {

&#x20;           $gr = $this->grRepo->find($goodsReceiptId);

&#x20;           if ($gr->status !== 'draft') {

&#x20;               throw new \\Exception('Goods receipt can only be received from draft status');

&#x20;           }



&#x20;           foreach ($lines as $line) {

&#x20;               // Record stock movement

&#x20;               $movement = $this->stockMovementService->recordReceipt(\[

&#x20;                   'tenant\_id' => $gr->tenant\_id,

&#x20;                   'product\_id' => $line\['product\_id'],

&#x20;                   'variant\_id' => $line\['variant\_id'] ?? null,

&#x20;                   'batch\_id' => $line\['batch\_id'] ?? null,

&#x20;                   'serial\_id' => $line\['serial\_id'] ?? null,

&#x20;                   'to\_location\_id' => $line\['location\_id'],

&#x20;                   'uom\_id' => $line\['uom\_id'],

&#x20;                   'quantity' => $line\['received\_qty'],

&#x20;                   'unit\_cost' => $line\['unit\_cost'],

&#x20;                   'source\_type' => 'goods\_receipt',

&#x20;                   'source\_id' => $goodsReceiptId,

&#x20;                   'created\_by' => auth()->id()

&#x20;               ]);



&#x20;               // Create goods receipt line

&#x20;               $gr->lines()->create(\[

&#x20;                   'po\_line\_id' => $line\['po\_line\_id'] ?? null,

&#x20;                   'product\_id' => $line\['product\_id'],

&#x20;                   'variant\_id' => $line\['variant\_id'] ?? null,

&#x20;                   'batch\_id' => $line\['batch\_id'] ?? null,

&#x20;                   'serial\_id' => $line\['serial\_id'] ?? null,

&#x20;                   'location\_id' => $line\['location\_id'],

&#x20;                   'uom\_id' => $line\['uom\_id'],

&#x20;                   'received\_qty' => $line\['received\_qty'],

&#x20;                   'unit\_cost' => $line\['unit\_cost'],

&#x20;                   'total\_cost' => $line\['received\_qty'] \* $line\['unit\_cost'],

&#x20;                   'stock\_movement\_id' => $movement->id

&#x20;               ]);



&#x20;               // Update purchase order line received quantity

&#x20;               if ($line\['po\_line\_id']) {

&#x20;                   $poLine = $gr->purchaseOrder->lines()->find($line\['po\_line\_id']);

&#x20;                   $newReceived = $poLine->received\_qty + $line\['received\_qty'];

&#x20;                   $poLine->update(\['received\_qty' => $newReceived]);

&#x20;               }

&#x20;           }



&#x20;           $gr->update(\['status' => 'received']);



&#x20;           // If entire PO is received, update PO status

&#x20;           $po = $gr->purchaseOrder;

&#x20;           $totalOrdered = $po->lines->sum('ordered\_qty');

&#x20;           $totalReceived = $po->lines->sum('received\_qty');

&#x20;           if ($totalReceived >= $totalOrdered) {

&#x20;               $po->update(\['status' => 'received']);

&#x20;           } elseif ($totalReceived > 0) {

&#x20;               $po->update(\['status' => 'partially\_received']);

&#x20;           }



&#x20;           return $gr;

&#x20;       });

&#x20;   }



&#x20;   public function approvePurchaseOrder(int $purchaseOrderId)

&#x20;   {

&#x20;       return $this->poRepo->update($purchaseOrderId, \['status' => 'approved']);

&#x20;   }

}



class SalesOrderService implements SalesOrderServiceInterface

{

&#x20;   protected SalesOrderRepositoryInterface $soRepo;

&#x20;   protected DeliveryOrderRepositoryInterface $doRepo;

&#x20;   protected StockMovementServiceInterface $stockMovementService;

&#x20;   protected JournalEntryServiceInterface $journalEntryService;



&#x20;   public function \_\_construct(

&#x20;       SalesOrderRepositoryInterface $soRepo,

&#x20;       DeliveryOrderRepositoryInterface $doRepo,

&#x20;       StockMovementServiceInterface $stockMovementService,

&#x20;       JournalEntryServiceInterface $journalEntryService

&#x20;   ) {

&#x20;       $this->soRepo = $soRepo;

&#x20;       $this->doRepo = $doRepo;

&#x20;       $this->stockMovementService = $stockMovementService;

&#x20;       $this->journalEntryService = $journalEntryService;

&#x20;   }



&#x20;   public function createSalesOrder(array $data)

&#x20;   {

&#x20;       return $this->soRepo->create($data);

&#x20;   }



&#x20;   public function shipOrder(int $deliveryOrderId, array $lines)

&#x20;   {

&#x20;       return DB::transaction(function () use ($deliveryOrderId, $lines) {

&#x20;           $do = $this->doRepo->find($deliveryOrderId);

&#x20;           if ($do->status !== 'draft') {

&#x20;               throw new \\Exception('Delivery order can only be shipped from draft status');

&#x20;           }



&#x20;           foreach ($lines as $line) {

&#x20;               // Record stock issue

&#x20;               $movement = $this->stockMovementService->recordIssue(\[

&#x20;                   'tenant\_id' => $do->tenant\_id,

&#x20;                   'product\_id' => $line\['product\_id'],

&#x20;                   'variant\_id' => $line\['variant\_id'] ?? null,

&#x20;                   'batch\_id' => $line\['batch\_id'] ?? null,

&#x20;                   'serial\_id' => $line\['serial\_id'] ?? null,

&#x20;                   'from\_location\_id' => $line\['from\_location\_id'],

&#x20;                   'uom\_id' => $line\['uom\_id'],

&#x20;                   'quantity' => $line\['delivered\_qty'],

&#x20;                   'unit\_cost' => $line\['unit\_cost'] ?? 0,

&#x20;                   'source\_type' => 'delivery\_order',

&#x20;                   'source\_id' => $deliveryOrderId,

&#x20;                   'created\_by' => auth()->id()

&#x20;               ]);



&#x20;               $do->lines()->create(\[

&#x20;                   'so\_line\_id' => $line\['so\_line\_id'],

&#x20;                   'product\_id' => $line\['product\_id'],

&#x20;                   'variant\_id' => $line\['variant\_id'] ?? null,

&#x20;                   'batch\_id' => $line\['batch\_id'] ?? null,

&#x20;                   'serial\_id' => $line\['serial\_id'] ?? null,

&#x20;                   'from\_location\_id' => $line\['from\_location\_id'],

&#x20;                   'uom\_id' => $line\['uom\_id'],

&#x20;                   'delivered\_qty' => $line\['delivered\_qty'],

&#x20;                   'stock\_movement\_id' => $movement->id

&#x20;               ]);



&#x20;               // Update sales order line shipped quantity

&#x20;               $soLine = $do->salesOrder->lines()->find($line\['so\_line\_id']);

&#x20;               $newShipped = $soLine->shipped\_qty + $line\['delivered\_qty'];

&#x20;               $soLine->update(\['shipped\_qty' => $newShipped]);

&#x20;           }



&#x20;           $do->update(\['status' => 'shipped']);



&#x20;           $so = $do->salesOrder;

&#x20;           $totalOrdered = $so->lines->sum('ordered\_qty');

&#x20;           $totalShipped = $so->lines->sum('shipped\_qty');

&#x20;           if ($totalShipped >= $totalOrdered) {

&#x20;               $so->update(\['status' => 'shipped']);

&#x20;           }



&#x20;           return $do;

&#x20;       });

&#x20;   }



&#x20;   public function invoiceOrder(int $customerInvoiceId)

&#x20;   {

&#x20;       return DB::transaction(function () use ($customerInvoiceId) {

&#x20;           $invoice = CustomerInvoice::findOrFail($customerInvoiceId);

&#x20;           if ($invoice->status !== 'draft') {

&#x20;               throw new \\Exception('Invoice can only be generated from draft status');

&#x20;           }



&#x20;           // Create journal entry for revenue recognition

&#x20;           $journalLines = \[

&#x20;               \[

&#x20;                   'account\_id' => 3, // Accounts Receivable

&#x20;                   'debit' => $invoice->total,

&#x20;                   'credit' => 0,

&#x20;                   'line\_number' => 1,

&#x20;                   'party\_id' => $invoice->customer\_id

&#x20;               ],

&#x20;               \[

&#x20;                   'account\_id' => 7, // Sales Revenue

&#x20;                   'debit' => 0,

&#x20;                   'credit' => $invoice->total,

&#x20;                   'line\_number' => 2,

&#x20;                   'party\_id' => $invoice->customer\_id

&#x20;               ]

&#x20;           ];



&#x20;           $journalEntry = $this->journalEntryService->createFromTransaction(

&#x20;               'customer\_invoice',

&#x20;               $customerInvoiceId,

&#x20;               $journalLines

&#x20;           );

&#x20;           $this->journalEntryService->postJournalEntry($journalEntry->id);



&#x20;           $invoice->update(\[

&#x20;               'status' => 'sent',

&#x20;               'journal\_entry\_id' => $journalEntry->id

&#x20;           ]);



&#x20;           // Also create COGS journal entry if needed (in a real system, from inventory layers)

&#x20;           // ...



&#x20;           return $invoice;

&#x20;       });

&#x20;   }

}



\---



\## Unify Transactional Documents



documents (

&#x20; id, tenant\_id, period\_id, document\_type ENUM('PO','SO','GRN','SUP\_INV','CUST\_INV','RETURN','CREDIT\_NOTE','PAYMENT'),

&#x20; document\_number, party\_id, warehouse\_id, currency\_id, exchange\_rate,

&#x20; document\_date, accounting\_date, due\_date, status, total\_amount, paid\_amount,

&#x20; created\_by, approved\_by, journal\_entry\_id, ...

)



document\_lines (

&#x20; id, document\_id, line\_number, product\_id, variant\_id, uom\_id, quantity,

&#x20; unit\_price, discount\_pct, tax\_code\_id, subtotal, tax\_amount, total,

&#x20; batch\_id, serial\_id, location\_id, stock\_movement\_id, ...

)



\---





\## Purchase (Transactions)



class PurchaseOrderSeeder extends Seeder

{

&#x20;   public function run(): void

&#x20;   {

&#x20;       $tenants = Tenant::all();

&#x20;       $usd = Currency::where('code', 'USD')->first();



&#x20;       foreach ($tenants as $tenant) {

&#x20;           $supplier = Supplier::where('tenant\_id', $tenant->id)->first();

&#x20;           $warehouse = Warehouse::where('tenant\_id', $tenant->id)->first();

&#x20;           $user = User::where('tenant\_id', $tenant->id)->first();

&#x20;           $products = Product::where('tenant\_id', $tenant->id)->where('type', 'physical')->take(2)->get();



&#x20;           if (!$supplier || !$warehouse || !$user || $products->isEmpty()) continue;



&#x20;           $po = PurchaseOrder::create(\[

&#x20;               'tenant\_id' => $tenant->id,

&#x20;               'supplier\_id' => $supplier->id,

&#x20;               'org\_unit\_id' => $warehouse->org\_unit\_id,

&#x20;               'warehouse\_id' => $warehouse->id,

&#x20;               'po\_number' => 'PO-' . date('Ymd') . '-001',

&#x20;               'status' => 'confirmed',

&#x20;               'currency\_id' => $usd->id,

&#x20;               'exchange\_rate' => 1,

&#x20;               'order\_date' => now()->subDays(5),

&#x20;               'expected\_date' => now()->addDays(7),

&#x20;               'subtotal' => 0,

&#x20;               'grand\_total' => 0,

&#x20;               'created\_by' => $user->id,

&#x20;           ]);



&#x20;           $subtotal = 0;

&#x20;           foreach ($products as $product) {

&#x20;               $qty = rand(10, 50);

&#x20;               $price = 12.50;

&#x20;               $lineTotal = $qty \* $price;

&#x20;               $subtotal += $lineTotal;



&#x20;               PurchaseOrderLine::create(\[

&#x20;                   'purchase\_order\_id' => $po->id,

&#x20;                   'product\_id' => $product->id,

&#x20;                   'uom\_id' => $product->base\_uom\_id,

&#x20;                   'ordered\_qty' => $qty,

&#x20;                   'received\_qty' => 0,

&#x20;                   'unit\_price' => $price,

&#x20;                   'line\_total' => $lineTotal,

&#x20;               ]);

&#x20;           }



&#x20;           $po->update(\['subtotal' => $subtotal, 'grand\_total' => $subtotal]);

&#x20;       }

&#x20;   }

}



class GrnSeeder extends Seeder

{

&#x20;   public function run(): void

&#x20;   {

&#x20;       $pos = PurchaseOrder::where('status', 'confirmed')->get();

&#x20;       $usd = Currency::where('code', 'USD')->first();



&#x20;       foreach ($pos as $po) {

&#x20;           $user = User::where('tenant\_id', $po->tenant\_id)->first();

&#x20;           $location = WarehouseLocation::where('warehouse\_id', $po->warehouse\_id)->where('type', 'bin')->first();



&#x20;           $grn = GrnHeader::create(\[

&#x20;               'tenant\_id' => $po->tenant\_id,

&#x20;               'supplier\_id' => $po->supplier\_id,

&#x20;               'warehouse\_id' => $po->warehouse\_id,

&#x20;               'purchase\_order\_id' => $po->id,

&#x20;               'grn\_number' => 'GRN-' . date('Ymd') . '-' . $po->id,

&#x20;               'status' => 'complete',

&#x20;               'received\_date' => now()->subDays(2),

&#x20;               'currency\_id' => $usd->id,

&#x20;               'created\_by' => $user->id,

&#x20;           ]);



&#x20;           foreach ($po->lines as $line) {

&#x20;               $grnLine = GrnLine::create(\[

&#x20;                   'grn\_header\_id' => $grn->id,

&#x20;                   'purchase\_order\_line\_id' => $line->id,

&#x20;                   'product\_id' => $line->product\_id,

&#x20;                   'variant\_id' => $line->variant\_id,

&#x20;                   'location\_id' => $location?->id,

&#x20;                   'uom\_id' => $line->uom\_id,

&#x20;                   'expected\_qty' => $line->ordered\_qty,

&#x20;                   'received\_qty' => $line->ordered\_qty,

&#x20;                   'unit\_cost' => $line->unit\_price,

&#x20;               ]);



&#x20;               // Record stock movement

&#x20;               StockMovement::create(\[

&#x20;                   'tenant\_id' => $po->tenant\_id,

&#x20;                   'product\_id' => $line->product\_id,

&#x20;                   'variant\_id' => $line->variant\_id,

&#x20;                   'to\_location\_id' => $location?->id,

&#x20;                   'movement\_type' => 'receipt',

&#x20;                   'reference\_type' => GrnHeader::class,

&#x20;                   'reference\_id' => $grn->id,

&#x20;                   'uom\_id' => $line->uom\_id,

&#x20;                   'quantity' => $line->ordered\_qty,

&#x20;                   'unit\_cost' => $line->unit\_price,

&#x20;                   'performed\_by' => $user->id,

&#x20;               ]);



&#x20;               // Update stock level

&#x20;               StockLevel::updateOrCreate(

&#x20;                   \[

&#x20;                       'tenant\_id' => $po->tenant\_id,

&#x20;                       'product\_id' => $line->product\_id,

&#x20;                       'variant\_id' => $line->variant\_id,

&#x20;                       'location\_id' => $location?->id,

&#x20;                   ],

&#x20;                   \[

&#x20;                       'uom\_id' => $line->uom\_id,

&#x20;                       'unit\_cost' => $line->unit\_price,

&#x20;                   ]

&#x20;               )->increment('quantity\_on\_hand', $line->ordered\_qty);



&#x20;               $line->update(\['received\_qty' => $line->ordered\_qty]);

&#x20;           }



&#x20;           $po->update(\['status' => 'received']);

&#x20;       }

&#x20;   }

}



\---



\## Finance (Transactions)



class PaymentSeeder extends Seeder

{

&#x20;   public function run(): void

&#x20;   {

&#x20;       $tenants = Tenant::all();

&#x20;       $usd = Currency::where('code', 'USD')->first();



&#x20;       foreach ($tenants as $tenant) {

&#x20;           $bankAccount = Account::where('tenant\_id', $tenant->id)->where('is\_bank\_account', true)->first();

&#x20;           $cashAccount = Account::where('tenant\_id', $tenant->id)->where('code', '1000')->first();

&#x20;           $apAccount = Account::where('tenant\_id', $tenant->id)->where('code', '2000')->first();



&#x20;           $bankMethod = PaymentMethod::firstOrCreate(

&#x20;               \['tenant\_id' => $tenant->id, 'name' => 'Bank Transfer'],

&#x20;               \['type' => 'bank\_transfer', 'account\_id' => $bankAccount?->id, 'is\_active' => true]

&#x20;           );



&#x20;           // Pay a purchase invoice

&#x20;           $purchaseInvoice = PurchaseInvoice::where('tenant\_id', $tenant->id)->where('status', 'approved')->first();

&#x20;           if ($purchaseInvoice) {

&#x20;               Payment::create(\[

&#x20;                   'tenant\_id' => $tenant->id,

&#x20;                   'payment\_number' => 'PAY-OUT-' . date('Ymd') . '-001',

&#x20;                   'direction' => 'outbound',

&#x20;                   'party\_type' => 'supplier',

&#x20;                   'party\_id' => $purchaseInvoice->supplier\_id,

&#x20;                   'payment\_method\_id' => $bankMethod->id,

&#x20;                   'account\_id' => $bankAccount?->id ?? $cashAccount->id,

&#x20;                   'amount' => $purchaseInvoice->grand\_total,

&#x20;                   'currency\_id' => $usd->id,

&#x20;                   'exchange\_rate' => 1,

&#x20;                   'base\_amount' => $purchaseInvoice->grand\_total,

&#x20;                   'payment\_date' => now(),

&#x20;                   'status' => 'posted',

&#x20;               ]);

&#x20;               $purchaseInvoice->update(\['status' => 'paid']);

&#x20;           }



&#x20;           // Receive payment for a sales invoice

&#x20;           $salesInvoice = SalesInvoice::where('tenant\_id', $tenant->id)->where('status', 'sent')->first();

&#x20;           if ($salesInvoice) {

&#x20;               Payment::create(\[

&#x20;                   'tenant\_id' => $tenant->id,

&#x20;                   'payment\_number' => 'PAY-IN-' . date('Ymd') . '-001',

&#x20;                   'direction' => 'inbound',

&#x20;                   'party\_type' => 'customer',

&#x20;                   'party\_id' => $salesInvoice->customer\_id,

&#x20;                   'payment\_method\_id' => $bankMethod->id,

&#x20;                   'account\_id' => $bankAccount?->id ?? $cashAccount->id,

&#x20;                   'amount' => $salesInvoice->grand\_total,

&#x20;                   'currency\_id' => $usd->id,

&#x20;                   'exchange\_rate' => 1,

&#x20;                   'base\_amount' => $salesInvoice->grand\_total,

&#x20;                   'payment\_date' => now(),

&#x20;                   'status' => 'posted',

&#x20;               ]);

&#x20;               $salesInvoice->update(\['status' => 'paid']);

&#x20;           }

&#x20;       }

&#x20;   }

}



\---



\## GoodsReceivedListener



Create PO → Confirm PO → Goods Receipt (GRN) → Stock Movement (receipt) → Journal Entry (Dr Inventory, Cr AP)

&#x20;                                                        ↓

&#x20;                                               Purchase Invoice → Journal Entry (optional adjustment)

&#x20;                                                        ↓

&#x20;                                                  Payment (outbound) → Journal Entry (Dr AP, Cr Bank)

&#x20;                                                        ↓

&#x20;                                               Purchase Return (optional) → Stock Movement (return\_out) → Journal Entry (Dr AP, Cr Inventory)



class GoodsReceivedListener

{

&#x20;   public function handle(GoodsReceived $event): void

&#x20;   {

&#x20;       DB::transaction(function () use ($event) {

&#x20;           $grn = $event->grn;

&#x20;           foreach ($grn->lines as $line) {

&#x20;               // 1. Stock Movement (receipt)

&#x20;               $movement = StockMovement::create(\[

&#x20;                   'tenant\_id' => $grn->tenant\_id,

&#x20;                   'product\_id' => $line->product\_id,

&#x20;                   'variant\_id' => $line->variant\_id,

&#x20;                   'batch\_id' => $line->batch\_id,

&#x20;                   'serial\_id' => $line->serial\_id,

&#x20;                   'to\_location\_id' => $line->location\_id,

&#x20;                   'movement\_type' => 'receipt',

&#x20;                   'reference\_type' => GrnHeader::class,

&#x20;                   'reference\_id' => $grn->id,

&#x20;                   'uom\_id' => $line->uom\_id,

&#x20;                   'quantity' => $line->received\_qty,

&#x20;                   'unit\_cost' => $line->unit\_cost,

&#x20;                   'performed\_by' => $grn->created\_by,

&#x20;               ]);



&#x20;               // 2. Update Stock Level

&#x20;               StockLevel::updateOrCreate(\[...])->increment('quantity\_on\_hand', $line->received\_qty);



&#x20;               // 3. Create Cost Layer (FIFO/LIFO/FEFO)

&#x20;               InventoryCostLayer::create(\[

&#x20;                   'tenant\_id' => $grn->tenant\_id,

&#x20;                   'product\_id' => $line->product\_id,

&#x20;                   'variant\_id' => $line->variant\_id,

&#x20;                   'batch\_id' => $line->batch\_id,

&#x20;                   'location\_id' => $line->location\_id,

&#x20;                   'valuation\_method' => $line->product->valuation\_method,

&#x20;                   'layer\_date' => $grn->received\_date,

&#x20;                   'quantity\_in' => $line->received\_qty,

&#x20;                   'quantity\_remaining' => $line->received\_qty,

&#x20;                   'unit\_cost' => $line->unit\_cost,

&#x20;                   'reference\_type' => StockMovement::class,

&#x20;                   'reference\_id' => $movement->id,

&#x20;               ]);

&#x20;           }



&#x20;           // 4. Post Journal Entry (if configured to do so at GRN)

&#x20;           $this->postReceiptJournalEntry($grn);

&#x20;       });

&#x20;   }



&#x20;   protected function postReceiptJournalEntry(GrnHeader $grn): void

&#x20;   {

&#x20;       $journalEntry = JournalEntry::create(\[

&#x20;           'tenant\_id' => $grn->tenant\_id,

&#x20;           'fiscal\_period\_id' => FiscalPeriod::current()->id,

&#x20;           'entry\_type' => 'auto',

&#x20;           'reference\_type' => GrnHeader::class,

&#x20;           'reference\_id' => $grn->id,

&#x20;           'entry\_date' => $grn->received\_date,

&#x20;           'status' => 'posted',

&#x20;           'created\_by' => $grn->created\_by,

&#x20;       ]);



&#x20;       $inventoryAccount = Account::where('code', '1300')->first(); // Inventory

&#x20;       $apAccount = Account::where('code', '2000')->first();       // Accounts Payable

&#x20;       $totalCost = $grn->lines->sum('line\_cost');



&#x20;       // Debit Inventory

&#x20;       JournalEntryLine::create(\[

&#x20;           'journal\_entry\_id' => $journalEntry->id,

&#x20;           'account\_id' => $inventoryAccount->id,

&#x20;           'debit\_amount' => $totalCost,

&#x20;       ]);

&#x20;       // Credit AP

&#x20;       JournalEntryLine::create(\[

&#x20;           'journal\_entry\_id' => $journalEntry->id,

&#x20;           'account\_id' => $apAccount->id,

&#x20;           'credit\_amount' => $totalCost,

&#x20;       ]);



&#x20;       $grn->update(\['status' => 'posted']);

&#x20;   }

}



\---



\## ShipmentShippedListener



Create SO → Confirm SO → Reserve Stock → Pick/Pack → Shipment → Stock Movement (issue) → Journal Entry (Dr COGS, Cr Inventory)

&#x20;                                                                    ↓

&#x20;                                                             Sales Invoice → Journal Entry (Dr AR, Cr Revenue)

&#x20;                                                                    ↓

&#x20;                                                               Payment (inbound) → Journal Entry (Dr Bank, Cr AR)

&#x20;                                                                    ↓

&#x20;                                                             Sales Return (optional) → Stock Movement (return\_in) → Journal Entry (Dr Revenue/Inventory, Cr AR)



class ShipmentShippedListener

{

&#x20;   public function handle(ShipmentShipped $event): void

&#x20;   {

&#x20;       DB::transaction(function () use ($event) {

&#x20;           $shipment = $event->shipment;

&#x20;           $cogsAccount = Account::where('code', '5000')->first(); // COGS

&#x20;           $inventoryAccount = Account::where('code', '1300')->first();

&#x20;           $totalCogs = 0;



&#x20;           foreach ($shipment->lines as $line) {

&#x20;               // 1. Allocate stock via FIFO/FEFO cost layers

&#x20;               $layers = $this->allocateLayers($line->product\_id, $line->shipped\_qty, $line->batch\_id, $line->from\_location\_id);

&#x20;               $unitCost = $layers->avg('unit\_cost');

&#x20;               $totalLineCost = $line->shipped\_qty \* $unitCost;

&#x20;               $totalCogs += $totalLineCost;



&#x20;               // 2. Create Stock Movement (issue)

&#x20;               $movement = StockMovement::create(\[

&#x20;                   'tenant\_id' => $shipment->tenant\_id,

&#x20;                   'product\_id' => $line->product\_id,

&#x20;                   'variant\_id' => $line->variant\_id,

&#x20;                   'batch\_id' => $line->batch\_id,

&#x20;                   'serial\_id' => $line->serial\_id,

&#x20;                   'from\_location\_id' => $line->from\_location\_id,

&#x20;                   'movement\_type' => 'shipment',

&#x20;                   'reference\_type' => Shipment::class,

&#x20;                   'reference\_id' => $shipment->id,

&#x20;                   'uom\_id' => $line->uom\_id,

&#x20;                   'quantity' => $line->shipped\_qty,

&#x20;                   'unit\_cost' => $unitCost,

&#x20;                   'performed\_by' => $shipment->created\_by,

&#x20;               ]);



&#x20;               // 3. Update Stock Level

&#x20;               StockLevel::where(\[...])->decrement('quantity\_on\_hand', $line->shipped\_qty);



&#x20;               // 4. Release reservations

&#x20;               StockReservation::where('reserved\_for\_type', SalesOrderLine::class)

&#x20;                   ->where('reserved\_for\_id', $line->sales\_order\_line\_id)

&#x20;                   ->delete();

&#x20;           }



&#x20;           // 5. Post COGS Journal Entry

&#x20;           $journalEntry = JournalEntry::create(\[...]);

&#x20;           JournalEntryLine::create(\['journal\_entry\_id' => $journalEntry->id, 'account\_id' => $cogsAccount->id, 'debit\_amount' => $totalCogs]);

&#x20;           JournalEntryLine::create(\['journal\_entry\_id' => $journalEntry->id, 'account\_id' => $inventoryAccount->id, 'credit\_amount' => $totalCogs]);

&#x20;       });

&#x20;   }



&#x20;   protected function allocateLayers($productId, $qty, $batchId, $locationId): Collection

&#x20;   {

&#x20;       $remaining = $qty;

&#x20;       $layers = InventoryCostLayer::where('product\_id', $productId)

&#x20;           ->where('location\_id', $locationId)

&#x20;           ->when($batchId, fn($q) => $q->where('batch\_id', $batchId))

&#x20;           ->where('quantity\_remaining', '>', 0)

&#x20;           ->orderBy('layer\_date') // FIFO; for FEFO order by expiry\_date via batch

&#x20;           ->get();



&#x20;       foreach ($layers as $layer) {

&#x20;           $consume = min($remaining, $layer->quantity\_remaining);

&#x20;           $layer->decrement('quantity\_remaining', $consume);

&#x20;           $layer->update(\['is\_closed' => $layer->quantity\_remaining == 0]);

&#x20;           $remaining -= $consume;

&#x20;           if ($remaining <= 0) break;

&#x20;       }

&#x20;       if ($remaining > 0) throw new InsufficientStockException();

&#x20;       return $layers;

&#x20;   }

}



\---



\## SalesReturnApprovedListener



class SalesReturnApprovedListener

{

&#x20;   public function handle(SalesReturnApproved $event): void

&#x20;   {

&#x20;       DB::transaction(function () use ($event) {

&#x20;           $return = $event->salesReturn;

&#x20;           $totalInventoryCredit = 0;

&#x20;           $totalRevenueDebit = 0;

&#x20;           $totalRestockingFee = 0;



&#x20;           foreach ($return->lines as $line) {

&#x20;               $disposition = $line->disposition;

&#x20;               $originalCost = $line->original\_sales\_order\_line\_id

&#x20;                   ? $this->getOriginalCost($line->original\_sales\_order\_line\_id)

&#x20;                   : $this->getCurrentCost($line->product\_id);



&#x20;               if ($disposition === 'restock') {

&#x20;                   // 1. Stock Movement (return\_in)

&#x20;                   $movement = StockMovement::create(\[

&#x20;                       'tenant\_id' => $return->tenant\_id,

&#x20;                       'product\_id' => $line->product\_id,

&#x20;                       'variant\_id' => $line->variant\_id,

&#x20;                       'batch\_id' => $line->batch\_id,

&#x20;                       'serial\_id' => $line->serial\_id,

&#x20;                       'to\_location\_id' => $line->to\_location\_id,

&#x20;                       'movement\_type' => 'return\_in',

&#x20;                       'reference\_type' => SalesReturn::class,

&#x20;                       'reference\_id' => $return->id,

&#x20;                       'uom\_id' => $line->uom\_id,

&#x20;                       'quantity' => $line->return\_qty,

&#x20;                       'unit\_cost' => $originalCost,

&#x20;                       'performed\_by' => $return->created\_by,

&#x20;                   ]);



&#x20;                   // 2. Update Stock Level

&#x20;                   StockLevel::updateOrCreate(\[...])->increment('quantity\_on\_hand', $line->return\_qty);



&#x20;                   // 3. Re-insert Cost Layer (at original cost)

&#x20;                   InventoryCostLayer::create(\[

&#x20;                       'tenant\_id' => $return->tenant\_id,

&#x20;                       'product\_id' => $line->product\_id,

&#x20;                       'variant\_id' => $line->variant\_id,

&#x20;                       'batch\_id' => $line->batch\_id,

&#x20;                       'location\_id' => $line->to\_location\_id,

&#x20;                       'valuation\_method' => $line->product->valuation\_method,

&#x20;                       'layer\_date' => $return->return\_date,

&#x20;                       'quantity\_in' => $line->return\_qty,

&#x20;                       'quantity\_remaining' => $line->return\_qty,

&#x20;                       'unit\_cost' => $originalCost,

&#x20;                       'reference\_type' => StockMovement::class,

&#x20;                       'reference\_id' => $movement->id,

&#x20;                   ]);



&#x20;                   $totalInventoryCredit += $line->return\_qty \* $originalCost;

&#x20;               }



&#x20;               $totalRevenueDebit += $line->line\_total;

&#x20;               $totalRestockingFee += $line->restocking\_fee;

&#x20;           }



&#x20;           // 4. Post Journal Entry

&#x20;           $journalEntry = JournalEntry::create(\[...]);

&#x20;           // Debit Sales Returns (Revenue contra)

&#x20;           JournalEntryLine::create(\['account\_id' => $salesReturnsAccount->id, 'debit\_amount' => $totalRevenueDebit]);

&#x20;           // Credit Accounts Receivable

&#x20;           JournalEntryLine::create(\['account\_id' => $arAccount->id, 'credit\_amount' => $totalRevenueDebit + $totalRestockingFee]);

&#x20;           // If restocked, Credit Inventory (reversal of COGS) and Debit COGS reversal

&#x20;           if ($totalInventoryCredit > 0) {

&#x20;               JournalEntryLine::create(\['account\_id' => $inventoryAccount->id, 'debit\_amount' => $totalInventoryCredit]);

&#x20;               JournalEntryLine::create(\['account\_id' => $cogsAccount->id, 'credit\_amount' => $totalInventoryCredit]);

&#x20;           }

&#x20;           // Restocking fee as revenue

&#x20;           if ($totalRestockingFee > 0) {

&#x20;               JournalEntryLine::create(\['account\_id' => $restockingFeeRevenueAccount->id, 'credit\_amount' => $totalRestockingFee]);

&#x20;           }



&#x20;           // 5. Create Credit Memo

&#x20;           CreditMemo::create(\[

&#x20;               'tenant\_id' => $return->tenant\_id,

&#x20;               'party\_type' => 'customer',

&#x20;               'party\_id' => $return->customer\_id,

&#x20;               'return\_order\_type' => SalesReturn::class,

&#x20;               'return\_order\_id' => $return->id,

&#x20;               'credit\_memo\_number' => 'CM-' . $return->return\_number,

&#x20;               'amount' => $totalRevenueDebit + $totalRestockingFee,

&#x20;               'status' => 'issued',

&#x20;               'issued\_date' => now(),

&#x20;               'journal\_entry\_id' => $journalEntry->id,

&#x20;           ]);



&#x20;           $return->update(\['status' => 'closed']);

&#x20;       });

&#x20;   }

}



\---



\## PAYMENTS AND REFUNDS (Payment Processing)



class PaymentService

{

&#x20;   public function process(Payment $payment): void

&#x20;   {

&#x20;       DB::transaction(function () use ($payment) {

&#x20;           // 1. Update payment status

&#x20;           $payment->status = 'posted';

&#x20;           $payment->save();



&#x20;           // 2. Post Journal Entry

&#x20;           $journalEntry = $this->createPaymentJournalEntry($payment);



&#x20;           // 3. Update Party Balance (AR/AP)

&#x20;           if ($payment->direction === 'inbound') {

&#x20;               $this->applyToInvoices($payment, SalesInvoice::class);

&#x20;           } else {

&#x20;               $this->applyToInvoices($payment, PurchaseInvoice::class);

&#x20;           }



&#x20;           // 4. Reconcile bank transaction if matched

&#x20;           $this->reconcileBankTransaction($payment);

&#x20;       });

&#x20;   }



&#x20;   protected function createPaymentJournalEntry(Payment $payment): JournalEntry

&#x20;   {

&#x20;       $journalEntry = JournalEntry::create(\[...]);

&#x20;       $bankAccount = $payment->account; // Bank/Cash account

&#x20;       $partyAccount = $payment->direction === 'inbound'

&#x20;           ? Account::where('code', '1200')->first() // AR

&#x20;           : Account::where('code', '2000')->first(); // AP



&#x20;       if ($payment->direction === 'inbound') {

&#x20;           // Debit Bank, Credit AR

&#x20;           JournalEntryLine::create(\['account\_id' => $bankAccount->id, 'debit\_amount' => $payment->amount]);

&#x20;           JournalEntryLine::create(\['account\_id' => $partyAccount->id, 'credit\_amount' => $payment->amount]);

&#x20;       } else {

&#x20;           // Debit AP, Credit Bank

&#x20;           JournalEntryLine::create(\['account\_id' => $partyAccount->id, 'debit\_amount' => $payment->amount]);

&#x20;           JournalEntryLine::create(\['account\_id' => $bankAccount->id, 'credit\_amount' => $payment->amount]);

&#x20;       }

&#x20;       return $journalEntry;

&#x20;   }



&#x20;   protected function applyToInvoices(Payment $payment, string $invoiceClass): void

&#x20;   {

&#x20;       $remaining = $payment->amount;

&#x20;       $invoices = $invoiceClass::where('party\_id', $payment->party\_id)

&#x20;           ->where('status', '!=', 'paid')

&#x20;           ->orderBy('due\_date')

&#x20;           ->get();



&#x20;       foreach ($invoices as $invoice) {

&#x20;           $allocate = min($remaining, $invoice->grand\_total - $invoice->paid\_amount);

&#x20;           PaymentAllocation::create(\[

&#x20;               'payment\_id' => $payment->id,

&#x20;               'invoice\_type' => $invoiceClass,

&#x20;               'invoice\_id' => $invoice->id,

&#x20;               'allocated\_amount' => $allocate,

&#x20;           ]);

&#x20;           $invoice->paid\_amount += $allocate;

&#x20;           $invoice->status = ($invoice->paid\_amount >= $invoice->grand\_total) ? 'paid' : 'partial\_paid';

&#x20;           $invoice->save();

&#x20;           $remaining -= $allocate;

&#x20;           if ($remaining <= 0) break;

&#x20;       }

&#x20;   }

}



\---



\## Purchase Module Seeders (Complete Scenarios)



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

&#x20;   public function run(): void

&#x20;   {

&#x20;       $tenants = Tenant::all();

&#x20;       $usd = Currency::where('code', 'USD')->first();



&#x20;       foreach ($tenants as $tenant) {

&#x20;           $supplier = Supplier::where('tenant\_id', $tenant->id)->first();

&#x20;           $warehouse = Warehouse::where('tenant\_id', $tenant->id)->where('is\_default', true)->first();

&#x20;           $user = User::where('tenant\_id', $tenant->id)->whereHas('roles', fn($q) => $q->where('name', 'Manager'))->first();

&#x20;           if (!$supplier || !$warehouse || !$user) continue;



&#x20;           // Create 2 POs

&#x20;           for ($i = 1; $i <= 2; $i++) {

&#x20;               $po = PurchaseOrder::create(\[

&#x20;                   'tenant\_id' => $tenant->id,

&#x20;                   'supplier\_id' => $supplier->id,

&#x20;                   'org\_unit\_id' => $warehouse->org\_unit\_id,

&#x20;                   'warehouse\_id' => $warehouse->id,

&#x20;                   'po\_number' => 'PO-' . date('Ymd') . '-' . str\_pad($i, 3, '0', STR\_PAD\_LEFT),

&#x20;                   'status' => 'confirmed',

&#x20;                   'currency\_id' => $usd->id,

&#x20;                   'exchange\_rate' => 1,

&#x20;                   'order\_date' => now()->subDays(10 + $i),

&#x20;                   'expected\_date' => now()->addDays(5),

&#x20;                   'created\_by' => $user->id,

&#x20;                   'approved\_by' => $user->id,

&#x20;               ]);



&#x20;               $products = Product::where('tenant\_id', $tenant->id)->where('type', 'physical')->take(2)->get();

&#x20;               $subtotal = 0;

&#x20;               foreach ($products as $product) {

&#x20;                   $qty = rand(5, 20) \* ($i == 1 ? 1 : 2);

&#x20;                   $price = 12.50;

&#x20;                   $lineTotal = $qty \* $price;

&#x20;                   $subtotal += $lineTotal;

&#x20;                   PurchaseOrderLine::create(\[

&#x20;                       'purchase\_order\_id' => $po->id,

&#x20;                       'product\_id' => $product->id,

&#x20;                       'uom\_id' => $product->base\_uom\_id,

&#x20;                       'ordered\_qty' => $qty,

&#x20;                       'unit\_price' => $price,

&#x20;                       'line\_total' => $lineTotal,

&#x20;                   ]);

&#x20;               }

&#x20;               $po->update(\['subtotal' => $subtotal, 'grand\_total' => $subtotal]);

&#x20;           }

&#x20;       }

&#x20;   }

}



\---



\## GrnFromPoSeeder.php – Receive goods against PO (creates stock \& journal entry)



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

&#x20;   public function run(): void

&#x20;   {

&#x20;       $pos = PurchaseOrder::where('status', 'confirmed')->get();

&#x20;       $usd = Currency::where('code', 'USD')->first();

&#x20;       $inventoryAccount = Account::where('code', '1300')->first();

&#x20;       $grIrAccount = Account::firstOrCreate(\['code' => '1500', 'name' => 'GR/IR', 'type' => 'liability', 'normal\_balance' => 'credit']);

&#x20;       $fiscalPeriod = FiscalPeriod::where('status', 'open')->first();



&#x20;       foreach ($pos as $po) {

&#x20;           DB::transaction(function () use ($po, $usd, $inventoryAccount, $grIrAccount, $fiscalPeriod) {

&#x20;               $user = User::where('tenant\_id', $po->tenant\_id)->first();

&#x20;               $location = WarehouseLocation::where('warehouse\_id', $po->warehouse\_id)->where('type', 'bin')->first();

&#x20;               if (!$user || !$location) return;



&#x20;               // Create GRN

&#x20;               $grn = GrnHeader::create(\[

&#x20;                   'tenant\_id' => $po->tenant\_id,

&#x20;                   'supplier\_id' => $po->supplier\_id,

&#x20;                   'warehouse\_id' => $po->warehouse\_id,

&#x20;                   'purchase\_order\_id' => $po->id,

&#x20;                   'grn\_number' => 'GRN-PO-' . $po->id,

&#x20;                   'status' => 'complete',

&#x20;                   'received\_date' => now()->subDays(3),

&#x20;                   'currency\_id' => $usd->id,

&#x20;                   'created\_by' => $user->id,

&#x20;               ]);



&#x20;               $totalCost = 0;

&#x20;               foreach ($po->lines as $line) {

&#x20;                   $grnLine = GrnLine::create(\[

&#x20;                       'grn\_header\_id' => $grn->id,

&#x20;                       'purchase\_order\_line\_id' => $line->id,

&#x20;                       'product\_id' => $line->product\_id,

&#x20;                       'variant\_id' => $line->variant\_id,

&#x20;                       'location\_id' => $location->id,

&#x20;                       'uom\_id' => $line->uom\_id,

&#x20;                       'expected\_qty' => $line->ordered\_qty,

&#x20;                       'received\_qty' => $line->ordered\_qty,

&#x20;                       'unit\_cost' => $line->unit\_price,

&#x20;                   ]);



&#x20;                   // Stock Movement

&#x20;                   StockMovement::create(\[

&#x20;                       'tenant\_id' => $po->tenant\_id,

&#x20;                       'product\_id' => $line->product\_id,

&#x20;                       'to\_location\_id' => $location->id,

&#x20;                       'movement\_type' => 'receipt',

&#x20;                       'reference\_type' => GrnHeader::class,

&#x20;                       'reference\_id' => $grn->id,

&#x20;                       'uom\_id' => $line->uom\_id,

&#x20;                       'quantity' => $line->ordered\_qty,

&#x20;                       'unit\_cost' => $line->unit\_price,

&#x20;                       'performed\_by' => $user->id,

&#x20;                   ]);



&#x20;                   // Update Stock Level

&#x20;                   StockLevel::updateOrCreate(\[

&#x20;                       'tenant\_id' => $po->tenant\_id,

&#x20;                       'product\_id' => $line->product\_id,

&#x20;                       'variant\_id' => $line->variant\_id,

&#x20;                       'location\_id' => $location->id,

&#x20;                   ], \[

&#x20;                       'uom\_id' => $line->uom\_id,

&#x20;                       'unit\_cost' => $line->unit\_price,

&#x20;                   ])->increment('quantity\_on\_hand', $line->ordered\_qty);



&#x20;                   // Cost Layer

&#x20;                   InventoryCostLayer::create(\[

&#x20;                       'tenant\_id' => $po->tenant\_id,

&#x20;                       'product\_id' => $line->product\_id,

&#x20;                       'location\_id' => $location->id,

&#x20;                       'valuation\_method' => $line->product->valuation\_method,

&#x20;                       'layer\_date' => $grn->received\_date,

&#x20;                       'quantity\_in' => $line->ordered\_qty,

&#x20;                       'quantity\_remaining' => $line->ordered\_qty,

&#x20;                       'unit\_cost' => $line->unit\_price,

&#x20;                       'reference\_type' => StockMovement::class,

&#x20;                       'reference\_id' => $grn->id,

&#x20;                   ]);



&#x20;                   $totalCost += $line->ordered\_qty \* $line->unit\_price;

&#x20;               }



&#x20;               // Journal Entry (Dr Inventory, Cr GR/IR)

&#x20;               $journalEntry = JournalEntry::create(\[

&#x20;                   'tenant\_id' => $po->tenant\_id,

&#x20;                   'fiscal\_period\_id' => $fiscalPeriod->id,

&#x20;                   'entry\_type' => 'auto',

&#x20;                   'reference\_type' => GrnHeader::class,

&#x20;                   'reference\_id' => $grn->id,

&#x20;                   'entry\_date' => $grn->received\_date,

&#x20;                   'status' => 'posted',

&#x20;                   'created\_by' => $user->id,

&#x20;                   'posted\_by' => $user->id,

&#x20;                   'posted\_at' => now(),

&#x20;               ]);

&#x20;               JournalEntryLine::create(\['journal\_entry\_id' => $journalEntry->id, 'account\_id' => $inventoryAccount->id, 'debit\_amount' => $totalCost]);

&#x20;               JournalEntryLine::create(\['journal\_entry\_id' => $journalEntry->id, 'account\_id' => $grIrAccount->id, 'credit\_amount' => $totalCost]);



&#x20;               $po->update(\['status' => 'received']);

&#x20;               $grn->update(\['status' => 'posted']);

&#x20;           });

&#x20;       }

&#x20;   }

}



\---



\## PurchaseInvoiceSeeder.php – Create invoices and clear GR/IR





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

&#x20;   public function run(): void

&#x20;   {

&#x20;       $grns = GrnHeader::where('status', 'posted')->get();

&#x20;       $usd = Currency::where('code', 'USD')->first();

&#x20;       $apAccount = Account::where('code', '2000')->first();

&#x20;       $grIrAccount = Account::where('code', '1500')->first();

&#x20;       $fiscalPeriod = FiscalPeriod::where('status', 'open')->first();



&#x20;       foreach ($grns as $grn) {

&#x20;           DB::transaction(function () use ($grn, $usd, $apAccount, $grIrAccount, $fiscalPeriod) {

&#x20;               $total = $grn->lines->sum('line\_cost');



&#x20;               $invoice = PurchaseInvoice::create(\[

&#x20;                   'tenant\_id' => $grn->tenant\_id,

&#x20;                   'supplier\_id' => $grn->supplier\_id,

&#x20;                   'grn\_header\_id' => $grn->id,

&#x20;                   'purchase\_order\_id' => $grn->purchase\_order\_id,

&#x20;                   'invoice\_number' => 'INV-' . $grn->grn\_number,

&#x20;                   'supplier\_invoice\_number' => 'SUPP-INV-' . rand(1000,9999),

&#x20;                   'status' => 'approved',

&#x20;                   'invoice\_date' => now()->subDays(1),

&#x20;                   'due\_date' => now()->addDays(30),

&#x20;                   'currency\_id' => $usd->id,

&#x20;                   'subtotal' => $total,

&#x20;                   'grand\_total' => $total,

&#x20;                   'ap\_account\_id' => $apAccount->id,

&#x20;               ]);



&#x20;               foreach ($grn->lines as $line) {

&#x20;                   PurchaseInvoiceLine::create(\[

&#x20;                       'purchase\_invoice\_id' => $invoice->id,

&#x20;                       'grn\_line\_id' => $line->id,

&#x20;                       'product\_id' => $line->product\_id,

&#x20;                       'uom\_id' => $line->uom\_id,

&#x20;                       'quantity' => $line->received\_qty,

&#x20;                       'unit\_price' => $line->unit\_cost,

&#x20;                       'line\_total' => $line->line\_cost,

&#x20;                   ]);

&#x20;               }



&#x20;               // Journal Entry: Dr GR/IR, Cr AP

&#x20;               $journalEntry = JournalEntry::create(\[

&#x20;                   'tenant\_id' => $grn->tenant\_id,

&#x20;                   'fiscal\_period\_id' => $fiscalPeriod->id,

&#x20;                   'entry\_type' => 'auto',

&#x20;                   'reference\_type' => PurchaseInvoice::class,

&#x20;                   'reference\_id' => $invoice->id,

&#x20;                   'entry\_date' => $invoice->invoice\_date,

&#x20;                   'status' => 'posted',

&#x20;                   'created\_by' => $grn->created\_by,

&#x20;                   'posted\_by' => $grn->created\_by,

&#x20;                   'posted\_at' => now(),

&#x20;               ]);

&#x20;               JournalEntryLine::create(\['journal\_entry\_id' => $journalEntry->id, 'account\_id' => $grIrAccount->id, 'debit\_amount' => $total]);

&#x20;               JournalEntryLine::create(\['journal\_entry\_id' => $journalEntry->id, 'account\_id' => $apAccount->id, 'credit\_amount' => $total]);



&#x20;               $invoice->update(\['journal\_entry\_id' => $journalEntry->id]);

&#x20;           });

&#x20;       }

&#x20;   }

}



\---



\## PurchasePaymentSeeder.php – Pay supplier invoices



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

&#x20;   public function run(): void

&#x20;   {

&#x20;       $invoices = PurchaseInvoice::where('status', 'approved')->get();

&#x20;       $usd = Currency::where('code', 'USD')->first();

&#x20;       $bankAccount = Account::where('is\_bank\_account', true)->first();

&#x20;       $apAccount = Account::where('code', '2000')->first();

&#x20;       $fiscalPeriod = FiscalPeriod::where('status', 'open')->first();



&#x20;       foreach ($invoices as $invoice) {

&#x20;           $user = User::where('tenant\_id', $invoice->tenant\_id)->first();

&#x20;           $method = PaymentMethod::firstOrCreate(\['tenant\_id' => $invoice->tenant\_id, 'name' => 'Bank Transfer'], \['type' => 'bank\_transfer', 'account\_id' => $bankAccount->id, 'is\_active' => true]);



&#x20;           $payment = Payment::create(\[

&#x20;               'tenant\_id' => $invoice->tenant\_id,

&#x20;               'payment\_number' => 'PAY-OUT-' . date('Ymd') . '-' . $invoice->id,

&#x20;               'direction' => 'outbound',

&#x20;               'party\_type' => 'supplier',

&#x20;               'party\_id' => $invoice->supplier\_id,

&#x20;               'payment\_method\_id' => $method->id,

&#x20;               'account\_id' => $bankAccount->id,

&#x20;               'amount' => $invoice->grand\_total,

&#x20;               'currency\_id' => $usd->id,

&#x20;               'exchange\_rate' => 1,

&#x20;               'base\_amount' => $invoice->grand\_total,

&#x20;               'payment\_date' => now(),

&#x20;               'status' => 'posted',

&#x20;           ]);



&#x20;           PaymentAllocation::create(\[

&#x20;               'payment\_id' => $payment->id,

&#x20;               'invoice\_type' => PurchaseInvoice::class,

&#x20;               'invoice\_id' => $invoice->id,

&#x20;               'allocated\_amount' => $invoice->grand\_total,

&#x20;           ]);



&#x20;           // Journal Entry: Dr AP, Cr Bank

&#x20;           $journalEntry = JournalEntry::create(\[

&#x20;               'tenant\_id' => $invoice->tenant\_id,

&#x20;               'fiscal\_period\_id' => $fiscalPeriod->id,

&#x20;               'entry\_type' => 'auto',

&#x20;               'reference\_type' => Payment::class,

&#x20;               'reference\_id' => $payment->id,

&#x20;               'entry\_date' => $payment->payment\_date,

&#x20;               'status' => 'posted',

&#x20;               'created\_by' => $user->id,

&#x20;               'posted\_by' => $user->id,

&#x20;               'posted\_at' => now(),

&#x20;           ]);

&#x20;           JournalEntryLine::create(\['journal\_entry\_id' => $journalEntry->id, 'account\_id' => $apAccount->id, 'debit\_amount' => $payment->amount]);

&#x20;           JournalEntryLine::create(\['journal\_entry\_id' => $journalEntry->id, 'account\_id' => $bankAccount->id, 'credit\_amount' => $payment->amount]);



&#x20;           $payment->update(\['journal\_entry\_id' => $journalEntry->id]);

&#x20;           $invoice->update(\['status' => 'paid', 'paid\_amount' => $invoice->grand\_total]);

&#x20;       }

&#x20;   }

}



\---



\## PurchaseReturnWithOriginalSeeder.php – Return goods to supplier with original GRN reference



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

&#x20;   public function run(): void

&#x20;   {

&#x20;       $grns = GrnHeader::where('status', 'posted')->take(1)->get(); // Only one example

&#x20;       $usd = Currency::where('code', 'USD')->first();

&#x20;       $apAccount = Account::where('code', '2000')->first();

&#x20;       $inventoryAccount = Account::where('code', '1300')->first();

&#x20;       $fiscalPeriod = FiscalPeriod::where('status', 'open')->first();



&#x20;       foreach ($grns as $grn) {

&#x20;           DB::transaction(function () use ($grn, $usd, $apAccount, $inventoryAccount, $fiscalPeriod) {

&#x20;               $user = User::where('tenant\_id', $grn->tenant\_id)->first();

&#x20;               $returnLocation = WarehouseLocation::where('warehouse\_id', $grn->warehouse\_id)->where('type', 'bin')->first();



&#x20;               $purchaseReturn = PurchaseReturn::create(\[

&#x20;                   'tenant\_id' => $grn->tenant\_id,

&#x20;                   'supplier\_id' => $grn->supplier\_id,

&#x20;                   'original\_grn\_id' => $grn->id,

&#x20;                   'return\_number' => 'PR-' . date('Ymd') . '-001',

&#x20;                   'status' => 'approved',

&#x20;                   'return\_date' => now(),

&#x20;                   'return\_reason' => 'Damaged items',

&#x20;                   'currency\_id' => $usd->id,

&#x20;               ]);



&#x20;               $totalReturnCost = 0;

&#x20;               foreach ($grn->lines->take(1) as $line) { // Return part of first line

&#x20;                   $returnQty = ceil($line->received\_qty \* 0.2); // 20% return

&#x20;                   $lineCost = $returnQty \* $line->unit\_cost;

&#x20;                   $totalReturnCost += $lineCost;



&#x20;                   $returnLine = PurchaseReturnLine::create(\[

&#x20;                       'purchase\_return\_id' => $purchaseReturn->id,

&#x20;                       'original\_grn\_line\_id' => $line->id,

&#x20;                       'product\_id' => $line->product\_id,

&#x20;                       'from\_location\_id' => $line->location\_id,

&#x20;                       'uom\_id' => $line->uom\_id,

&#x20;                       'return\_qty' => $returnQty,

&#x20;                       'unit\_cost' => $line->unit\_cost,

&#x20;                       'condition' => 'damaged',

&#x20;                       'disposition' => 'return\_to\_vendor',

&#x20;                   ]);



&#x20;                   // Stock Movement (return\_out)

&#x20;                   StockMovement::create(\[

&#x20;                       'tenant\_id' => $grn->tenant\_id,

&#x20;                       'product\_id' => $line->product\_id,

&#x20;                       'from\_location\_id' => $line->location\_id,

&#x20;                       'movement\_type' => 'return\_out',

&#x20;                       'reference\_type' => PurchaseReturn::class,

&#x20;                       'reference\_id' => $purchaseReturn->id,

&#x20;                       'uom\_id' => $line->uom\_id,

&#x20;                       'quantity' => $returnQty,

&#x20;                       'unit\_cost' => $line->unit\_cost,

&#x20;                       'performed\_by' => $user->id,

&#x20;                   ]);



&#x20;                   // Reduce stock level

&#x20;                   StockLevel::where(\[

&#x20;                       'tenant\_id' => $grn->tenant\_id,

&#x20;                       'product\_id' => $line->product\_id,

&#x20;                       'location\_id' => $line->location\_id,

&#x20;                   ])->decrement('quantity\_on\_hand', $returnQty);



&#x20;                   // Adjust cost layer (remove from original layer)

&#x20;                   $layer = InventoryCostLayer::where('reference\_id', $grn->id)

&#x20;                       ->where('product\_id', $line->product\_id)

&#x20;                       ->where('quantity\_remaining', '>', 0)

&#x20;                       ->first();

&#x20;                   if ($layer) {

&#x20;                       $layer->decrement('quantity\_remaining', $returnQty);

&#x20;                   }

&#x20;               }



&#x20;               $purchaseReturn->update(\['subtotal' => $totalReturnCost, 'grand\_total' => $totalReturnCost]);



&#x20;               // Journal Entry: Dr AP, Cr Inventory

&#x20;               $journalEntry = JournalEntry::create(\[

&#x20;                   'tenant\_id' => $grn->tenant\_id,

&#x20;                   'fiscal\_period\_id' => $fiscalPeriod->id,

&#x20;                   'entry\_type' => 'auto',

&#x20;                   'reference\_type' => PurchaseReturn::class,

&#x20;                   'reference\_id' => $purchaseReturn->id,

&#x20;                   'entry\_date' => $purchaseReturn->return\_date,

&#x20;                   'status' => 'posted',

&#x20;                   'created\_by' => $user->id,

&#x20;                   'posted\_by' => $user->id,

&#x20;                   'posted\_at' => now(),

&#x20;               ]);

&#x20;               JournalEntryLine::create(\['journal\_entry\_id' => $journalEntry->id, 'account\_id' => $apAccount->id, 'debit\_amount' => $totalReturnCost]);

&#x20;               JournalEntryLine::create(\['journal\_entry\_id' => $journalEntry->id, 'account\_id' => $inventoryAccount->id, 'credit\_amount' => $totalReturnCost]);



&#x20;               $purchaseReturn->update(\['journal\_entry\_id' => $journalEntry->id]);



&#x20;               // Debit Note / Credit Memo

&#x20;               CreditMemo::create(\[

&#x20;                   'tenant\_id' => $grn->tenant\_id,

&#x20;                   'party\_type' => 'supplier',

&#x20;                   'party\_id' => $grn->supplier\_id,

&#x20;                   'return\_order\_type' => PurchaseReturn::class,

&#x20;                   'return\_order\_id' => $purchaseReturn->id,

&#x20;                   'credit\_memo\_number' => 'DN-' . $purchaseReturn->return\_number,

&#x20;                   'amount' => $totalReturnCost,

&#x20;                   'status' => 'issued',

&#x20;                   'issued\_date' => now(),

&#x20;                   'journal\_entry\_id' => $journalEntry->id,

&#x20;               ]);

&#x20;           });

&#x20;       }

&#x20;   }

}



\---



\## DirectGrnSeeder.php – GRN without PO (SMB scenario)



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

&#x20;   public function run(): void

&#x20;   {

&#x20;       $tenants = Tenant::all();

&#x20;       $usd = Currency::where('code', 'USD')->first();

&#x20;       $inventoryAccount = Account::where('code', '1300')->first();

&#x20;       $apAccount = Account::where('code', '2000')->first();

&#x20;       $fiscalPeriod = FiscalPeriod::where('status', 'open')->first();



&#x20;       foreach ($tenants as $tenant) {

&#x20;           DB::transaction(function () use ($tenant, $usd, $inventoryAccount, $apAccount, $fiscalPeriod) {

&#x20;               $supplier = Supplier::where('tenant\_id', $tenant->id)->first();

&#x20;               $warehouse = Warehouse::where('tenant\_id', $tenant->id)->where('is\_default', true)->first();

&#x20;               $user = User::where('tenant\_id', $tenant->id)->first();

&#x20;               $product = Product::where('tenant\_id', $tenant->id)->where('type', 'physical')->first();

&#x20;               $location = WarehouseLocation::where('warehouse\_id', $warehouse->id)->where('type', 'bin')->first();

&#x20;               if (!$supplier || !$warehouse || !$user || !$product || !$location) return;



&#x20;               // Direct GRN (no PO)

&#x20;               $grn = GrnHeader::create(\[

&#x20;                   'tenant\_id' => $tenant->id,

&#x20;                   'supplier\_id' => $supplier->id,

&#x20;                   'warehouse\_id' => $warehouse->id,

&#x20;                   'purchase\_order\_id' => null,

&#x20;                   'grn\_number' => 'GRN-DIRECT-' . date('Ymd'),

&#x20;                   'status' => 'complete',

&#x20;                   'received\_date' => now()->subDays(2),

&#x20;                   'currency\_id' => $usd->id,

&#x20;                   'created\_by' => $user->id,

&#x20;               ]);



&#x20;               $qty = 15;

&#x20;               $unitCost = 8.75;

&#x20;               GrnLine::create(\[

&#x20;                   'grn\_header\_id' => $grn->id,

&#x20;                   'product\_id' => $product->id,

&#x20;                   'location\_id' => $location->id,

&#x20;                   'uom\_id' => $product->base\_uom\_id,

&#x20;                   'received\_qty' => $qty,

&#x20;                   'unit\_cost' => $unitCost,

&#x20;               ]);



&#x20;               // Stock Movement \& Level

&#x20;               StockMovement::create(\[...]); // similar to above

&#x20;               StockLevel::updateOrCreate(\[...])->increment('quantity\_on\_hand', $qty);

&#x20;               InventoryCostLayer::create(\[...]);



&#x20;               // Journal Entry: Dr Inventory, Cr AP (direct to AP since no GR/IR)

&#x20;               $total = $qty \* $unitCost;

&#x20;               $journalEntry = JournalEntry::create(\[...]);

&#x20;               JournalEntryLine::create(\['journal\_entry\_id' => $journalEntry->id, 'account\_id' => $inventoryAccount->id, 'debit\_amount' => $total]);

&#x20;               JournalEntryLine::create(\['journal\_entry\_id' => $journalEntry->id, 'account\_id' => $apAccount->id, 'credit\_amount' => $total]);

&#x20;           });

&#x20;       }

&#x20;   }

}



\---



\## Sales Module Seeders (Complete Scenarios)



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

&#x20;   public function run(): void

&#x20;   {

&#x20;       $tenants = Tenant::all();

&#x20;       $usd = Currency::where('code', 'USD')->first();



&#x20;       foreach ($tenants as $tenant) {

&#x20;           $customer = Customer::where('tenant\_id', $tenant->id)->first();

&#x20;           $warehouse = Warehouse::where('tenant\_id', $tenant->id)->where('is\_default', true)->first();

&#x20;           $user = User::where('tenant\_id', $tenant->id)->first();

&#x20;           if (!$customer || !$warehouse || !$user) continue;



&#x20;           $so = SalesOrder::create(\[

&#x20;               'tenant\_id' => $tenant->id,

&#x20;               'customer\_id' => $customer->id,

&#x20;               'org\_unit\_id' => $warehouse->org\_unit\_id,

&#x20;               'warehouse\_id' => $warehouse->id,

&#x20;               'so\_number' => 'SO-' . date('Ymd') . '-001',

&#x20;               'status' => 'confirmed',

&#x20;               'currency\_id' => $usd->id,

&#x20;               'order\_date' => now()->subDays(5),

&#x20;               'created\_by' => $user->id,

&#x20;               'approved\_by' => $user->id,

&#x20;           ]);



&#x20;           $products = Product::where('tenant\_id', $tenant->id)->where('type', 'physical')->take(2)->get();

&#x20;           $subtotal = 0;

&#x20;           foreach ($products as $product) {

&#x20;               $qty = rand(2, 10);

&#x20;               $price = 29.99;

&#x20;               $lineTotal = $qty \* $price;

&#x20;               $subtotal += $lineTotal;

&#x20;               SalesOrderLine::create(\[

&#x20;                   'sales\_order\_id' => $so->id,

&#x20;                   'product\_id' => $product->id,

&#x20;                   'uom\_id' => $product->base\_uom\_id,

&#x20;                   'ordered\_qty' => $qty,

&#x20;                   'unit\_price' => $price,

&#x20;                   'line\_total' => $lineTotal,

&#x20;                   'reserved\_qty' => $qty,

&#x20;               ]);

&#x20;           }

&#x20;           $so->update(\['subtotal' => $subtotal, 'grand\_total' => $subtotal]);

&#x20;       }

&#x20;   }

}



\---



\## ShipmentFromSoSeeder.php – Ship against SO (stock issue \& COGS)



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

&#x20;   public function run(): void

&#x20;   {

&#x20;       $orders = SalesOrder::where('status', 'confirmed')->get();

&#x20;       $usd = Currency::where('code', 'USD')->first();

&#x20;       $cogsAccount = Account::where('code', '5000')->first();

&#x20;       $inventoryAccount = Account::where('code', '1300')->first();

&#x20;       $fiscalPeriod = FiscalPeriod::where('status', 'open')->first();



&#x20;       foreach ($orders as $so) {

&#x20;           DB::transaction(function () use ($so, $usd, $cogsAccount, $inventoryAccount, $fiscalPeriod) {

&#x20;               $user = User::where('tenant\_id', $so->tenant\_id)->first();

&#x20;               $location = WarehouseLocation::where('warehouse\_id', $so->warehouse\_id)->where('type', 'bin')->first();

&#x20;               if (!$user || !$location) return;



&#x20;               $shipment = Shipment::create(\[

&#x20;                   'tenant\_id' => $so->tenant\_id,

&#x20;                   'customer\_id' => $so->customer\_id,

&#x20;                   'sales\_order\_id' => $so->id,

&#x20;                   'warehouse\_id' => $so->warehouse\_id,

&#x20;                   'shipment\_number' => 'SHIP-' . $so->so\_number,

&#x20;                   'status' => 'shipped',

&#x20;                   'shipped\_date' => now()->subDays(1),

&#x20;                   'currency\_id' => $usd->id,

&#x20;               ]);



&#x20;               $totalCogs = 0;

&#x20;               foreach ($so->lines as $line) {

&#x20;                   $shipmentLine = ShipmentLine::create(\[

&#x20;                       'shipment\_id' => $shipment->id,

&#x20;                       'sales\_order\_line\_id' => $line->id,

&#x20;                       'product\_id' => $line->product\_id,

&#x20;                       'from\_location\_id' => $location->id,

&#x20;                       'uom\_id' => $line->uom\_id,

&#x20;                       'shipped\_qty' => $line->ordered\_qty,

&#x20;                   ]);



&#x20;                   // Allocate cost layer (FIFO)

&#x20;                   $layer = InventoryCostLayer::where('product\_id', $line->product\_id)

&#x20;                       ->where('location\_id', $location->id)

&#x20;                       ->where('quantity\_remaining', '>', 0)

&#x20;                       ->orderBy('layer\_date')

&#x20;                       ->first();

&#x20;                   $unitCost = $layer ? $layer->unit\_cost : 10.00;

&#x20;                   $cogs = $line->ordered\_qty \* $unitCost;

&#x20;                   $totalCogs += $cogs;



&#x20;                   // Stock Movement

&#x20;                   StockMovement::create(\[

&#x20;                       'tenant\_id' => $so->tenant\_id,

&#x20;                       'product\_id' => $line->product\_id,

&#x20;                       'from\_location\_id' => $location->id,

&#x20;                       'movement\_type' => 'shipment',

&#x20;                       'reference\_type' => Shipment::class,

&#x20;                       'reference\_id' => $shipment->id,

&#x20;                       'uom\_id' => $line->uom\_id,

&#x20;                       'quantity' => $line->ordered\_qty,

&#x20;                       'unit\_cost' => $unitCost,

&#x20;                       'performed\_by' => $user->id,

&#x20;                   ]);



&#x20;                   // Reduce stock

&#x20;                   StockLevel::where(\['product\_id' => $line->product\_id, 'location\_id' => $location->id])

&#x20;                       ->decrement('quantity\_on\_hand', $line->ordered\_qty);



&#x20;                   // Consume cost layer

&#x20;                   if ($layer) {

&#x20;                       $layer->decrement('quantity\_remaining', $line->ordered\_qty);

&#x20;                   }



&#x20;                   $line->update(\['shipped\_qty' => $line->ordered\_qty]);

&#x20;               }



&#x20;               // COGS Journal Entry

&#x20;               $journalEntry = JournalEntry::create(\[...]);

&#x20;               JournalEntryLine::create(\['journal\_entry\_id' => $journalEntry->id, 'account\_id' => $cogsAccount->id, 'debit\_amount' => $totalCogs]);

&#x20;               JournalEntryLine::create(\['journal\_entry\_id' => $journalEntry->id, 'account\_id' => $inventoryAccount->id, 'credit\_amount' => $totalCogs]);



&#x20;               $so->update(\['status' => 'shipped']);

&#x20;           });

&#x20;       }

&#x20;   }

}



\---



\## SalesInvoiceSeeder.php, SalesPaymentSeeder.php – Similar to purchase but for AR/revenue.



// SalesInvoiceSeeder creates invoice, posts Dr AR, Cr Revenue.

// SalesPaymentSeeder receives payment, posts Dr Bank, Cr AR.



\---



\## DirectSaleSeeder.php – Shipment without SO (SMB)



// Create shipment with sales\_order\_id = null, directly issue stock, create invoice optionally.



\---



\## SalesReturnWithOriginalSeeder.php – Return with original SO reference



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

&#x20;   public function run(): void

&#x20;   {

&#x20;       $orders = SalesOrder::where('status', 'shipped')->take(1)->get();

&#x20;       $usd = Currency::where('code', 'USD')->first();

&#x20;       $salesReturnsAccount = Account::firstOrCreate(\['code' => '4100', 'name' => 'Sales Returns', 'type' => 'revenue', 'normal\_balance' => 'debit']);

&#x20;       $arAccount = Account::where('code', '1200')->first();

&#x20;       $inventoryAccount = Account::where('code', '1300')->first();

&#x20;       $cogsAccount = Account::where('code', '5000')->first();

&#x20;       $fiscalPeriod = FiscalPeriod::where('status', 'open')->first();



&#x20;       foreach ($orders as $so) {

&#x20;           DB::transaction(function () use ($so, $usd, $salesReturnsAccount, $arAccount, $inventoryAccount, $cogsAccount, $fiscalPeriod) {

&#x20;               $user = User::where('tenant\_id', $so->tenant\_id)->first();

&#x20;               $restockLocation = WarehouseLocation::where('warehouse\_id', $so->warehouse\_id)->where('type', 'bin')->first();



&#x20;               $salesReturn = SalesReturn::create(\[

&#x20;                   'tenant\_id' => $so->tenant\_id,

&#x20;                   'customer\_id' => $so->customer\_id,

&#x20;                   'original\_sales\_order\_id' => $so->id,

&#x20;                   'return\_number' => 'SR-' . date('Ymd') . '-001',

&#x20;                   'status' => 'approved',

&#x20;                   'return\_date' => now(),

&#x20;                   'return\_reason' => 'Wrong size',

&#x20;                   'currency\_id' => $usd->id,

&#x20;               ]);



&#x20;               $totalRevenueDebit = 0;

&#x20;               $totalInventoryCredit = 0;



&#x20;               foreach ($so->lines->take(1) as $line) {

&#x20;                   $returnQty = ceil($line->ordered\_qty \* 0.3); // 30% return

&#x20;                   $revenueDebit = $returnQty \* $line->unit\_price;

&#x20;                   $totalRevenueDebit += $revenueDebit;



&#x20;                   // Original cost from shipment movement

&#x20;                   $shipMovement = StockMovement::where('reference\_type', Shipment::class)

&#x20;                       ->where('product\_id', $line->product\_id)

&#x20;                       ->orderBy('performed\_at', 'desc')

&#x20;                       ->first();

&#x20;                   $originalCost = $shipMovement ? $shipMovement->unit\_cost : 10.00;

&#x20;                   $inventoryCredit = $returnQty \* $originalCost;

&#x20;                   $totalInventoryCredit += $inventoryCredit;



&#x20;                   $returnLine = SalesReturnLine::create(\[

&#x20;                       'sales\_return\_id' => $salesReturn->id,

&#x20;                       'original\_sales\_order\_line\_id' => $line->id,

&#x20;                       'product\_id' => $line->product\_id,

&#x20;                       'to\_location\_id' => $restockLocation->id,

&#x20;                       'uom\_id' => $line->uom\_id,

&#x20;                       'return\_qty' => $returnQty,

&#x20;                       'unit\_price' => $line->unit\_price,

&#x20;                       'condition' => 'good',

&#x20;                       'disposition' => 'restock',

&#x20;                   ]);



&#x20;                   // Stock Movement (return\_in)

&#x20;                   StockMovement::create(\[

&#x20;                       'tenant\_id' => $so->tenant\_id,

&#x20;                       'product\_id' => $line->product\_id,

&#x20;                       'to\_location\_id' => $restockLocation->id,

&#x20;                       'movement\_type' => 'return\_in',

&#x20;                       'reference\_type' => SalesReturn::class,

&#x20;                       'reference\_id' => $salesReturn->id,

&#x20;                       'uom\_id' => $line->uom\_id,

&#x20;                       'quantity' => $returnQty,

&#x20;                       'unit\_cost' => $originalCost,

&#x20;                       'performed\_by' => $user->id,

&#x20;                   ]);



&#x20;                   // Increase stock

&#x20;                   StockLevel::updateOrCreate(\[

&#x20;                       'tenant\_id' => $so->tenant\_id,

&#x20;                       'product\_id' => $line->product\_id,

&#x20;                       'location\_id' => $restockLocation->id,

&#x20;                   ], \['uom\_id' => $line->uom\_id])->increment('quantity\_on\_hand', $returnQty);



&#x20;                   // Re-insert cost layer

&#x20;                   InventoryCostLayer::create(\[

&#x20;                       'tenant\_id' => $so->tenant\_id,

&#x20;                       'product\_id' => $line->product\_id,

&#x20;                       'location\_id' => $restockLocation->id,

&#x20;                       'valuation\_method' => $line->product->valuation\_method,

&#x20;                       'layer\_date' => now(),

&#x20;                       'quantity\_in' => $returnQty,

&#x20;                       'quantity\_remaining' => $returnQty,

&#x20;                       'unit\_cost' => $originalCost,

&#x20;                       'reference\_type' => SalesReturn::class,

&#x20;                       'reference\_id' => $salesReturn->id,

&#x20;                   ]);

&#x20;               }



&#x20;               $salesReturn->update(\['subtotal' => $totalRevenueDebit, 'grand\_total' => $totalRevenueDebit]);



&#x20;               // Journal Entry

&#x20;               $journalEntry = JournalEntry::create(\[...]);

&#x20;               // Dr Sales Returns, Cr AR

&#x20;               JournalEntryLine::create(\['journal\_entry\_id' => $journalEntry->id, 'account\_id' => $salesReturnsAccount->id, 'debit\_amount' => $totalRevenueDebit]);

&#x20;               JournalEntryLine::create(\['journal\_entry\_id' => $journalEntry->id, 'account\_id' => $arAccount->id, 'credit\_amount' => $totalRevenueDebit]);

&#x20;               // Dr Inventory, Cr COGS (reversal)

&#x20;               JournalEntryLine::create(\['journal\_entry\_id' => $journalEntry->id, 'account\_id' => $inventoryAccount->id, 'debit\_amount' => $totalInventoryCredit]);

&#x20;               JournalEntryLine::create(\['journal\_entry\_id' => $journalEntry->id, 'account\_id' => $cogsAccount->id, 'credit\_amount' => $totalInventoryCredit]);



&#x20;               $salesReturn->update(\['journal\_entry\_id' => $journalEntry->id]);



&#x20;               // Credit Memo

&#x20;               CreditMemo::create(\[

&#x20;                   'tenant\_id' => $so->tenant\_id,

&#x20;                   'party\_type' => 'customer',

&#x20;                   'party\_id' => $so->customer\_id,

&#x20;                   'return\_order\_type' => SalesReturn::class,

&#x20;                   'return\_order\_id' => $salesReturn->id,

&#x20;                   'credit\_memo\_number' => 'CM-' . $salesReturn->return\_number,

&#x20;                   'amount' => $totalRevenueDebit,

&#x20;                   'status' => 'issued',

&#x20;                   'issued\_date' => now(),

&#x20;                   'journal\_entry\_id' => $journalEntry->id,

&#x20;               ]);

&#x20;           });

&#x20;       }

&#x20;   }

}



\---



\## SalesReturnRestockingFeeSeeder.php – Return with restocking fee



// Similar to above but adds restocking\_fee to line and separate revenue account.

// Journal entry includes restocking fee as credit to Restocking Fee Revenue.



\---



\## SalesReturnRefundSeeder.php – Refund credit memo as cash



// Creates a payment (outbound) linked to a credit memo, posts Dr AR/CreditMemo Liability, Cr Bank.





\---



\## SalesReturnWithoutOriginalSeeder.php – Return without original reference



// No original\_sales\_order\_id; uses current average cost for inventory restock.



\---



\## Purchase Order Lifecycle Seeder



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

&#x20;   public function run(): void

&#x20;   {

&#x20;       $tenant = Tenant::first();

&#x20;       $supplier = Supplier::where('tenant\_id', $tenant->id)->first();

&#x20;       $warehouse = Warehouse::where('tenant\_id', $tenant->id)->first();

&#x20;       $user = User::where('tenant\_id', $tenant->id)->first();

&#x20;       $product = Product::where('tenant\_id', $tenant->id)->first();

&#x20;       $usd = Currency::where('code', 'USD')->first();



&#x20;       if (!$supplier || !$warehouse || !$user || !$product) return;



&#x20;       // 1. CREATE Draft PO

&#x20;       $draftPo = PurchaseOrder::create(\[

&#x20;           'tenant\_id' => $tenant->id,

&#x20;           'supplier\_id' => $supplier->id,

&#x20;           'warehouse\_id' => $warehouse->id,

&#x20;           'po\_number' => 'PO-DRAFT-001',

&#x20;           'status' => 'draft',

&#x20;           'currency\_id' => $usd->id,

&#x20;           'order\_date' => now(),

&#x20;           'created\_by' => $user->id,

&#x20;       ]);

&#x20;       PurchaseOrderLine::create(\[

&#x20;           'purchase\_order\_id' => $draftPo->id,

&#x20;           'product\_id' => $product->id,

&#x20;           'uom\_id' => $product->base\_uom\_id,

&#x20;           'ordered\_qty' => 10,

&#x20;           'unit\_price' => 15.00,

&#x20;       ]);



&#x20;       // 2. UPDATE to Cancelled (soft delete demonstration)

&#x20;       $draftPo->update(\['status' => 'cancelled']);

&#x20;       $draftPo->delete(); // soft delete



&#x20;       // 3. CREATE another PO that goes through full flow

&#x20;       $activePo = PurchaseOrder::create(\[

&#x20;           'tenant\_id' => $tenant->id,

&#x20;           'supplier\_id' => $supplier->id,

&#x20;           'warehouse\_id' => $warehouse->id,

&#x20;           'po\_number' => 'PO-ACTIVE-001',

&#x20;           'status' => 'draft',

&#x20;           'currency\_id' => $usd->id,

&#x20;           'order\_date' => now()->subDays(2),

&#x20;           'created\_by' => $user->id,

&#x20;       ]);

&#x20;       PurchaseOrderLine::create(\[

&#x20;           'purchase\_order\_id' => $activePo->id,

&#x20;           'product\_id' => $product->id,

&#x20;           'uom\_id' => $product->base\_uom\_id,

&#x20;           'ordered\_qty' => 20,

&#x20;           'unit\_price' => 15.00,

&#x20;       ]);



&#x20;       // UPDATE to confirmed

&#x20;       $activePo->update(\[

&#x20;           'status' => 'confirmed',

&#x20;           'approved\_by' => $user->id,

&#x20;       ]);

&#x20;   }

}



\---



\## Sales Order Lifecycle Seeder



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

&#x20;   public function run(): void

&#x20;   {

&#x20;       $tenant = Tenant::first();

&#x20;       $customer = Customer::where('tenant\_id', $tenant->id)->first();

&#x20;       $warehouse = Warehouse::where('tenant\_id', $tenant->id)->first();

&#x20;       $user = User::where('tenant\_id', $tenant->id)->first();

&#x20;       $product = Product::where('tenant\_id', $tenant->id)->first();

&#x20;       $usd = Currency::where('code', 'USD')->first();



&#x20;       if (!$customer || !$warehouse || !$user || !$product) return;



&#x20;       // CREATE draft SO

&#x20;       $draftSo = SalesOrder::create(\[

&#x20;           'tenant\_id' => $tenant->id,

&#x20;           'customer\_id' => $customer->id,

&#x20;           'warehouse\_id' => $warehouse->id,

&#x20;           'so\_number' => 'SO-DRAFT-001',

&#x20;           'status' => 'draft',

&#x20;           'currency\_id' => $usd->id,

&#x20;           'order\_date' => now(),

&#x20;           'created\_by' => $user->id,

&#x20;       ]);

&#x20;       SalesOrderLine::create(\[

&#x20;           'sales\_order\_id' => $draftSo->id,

&#x20;           'product\_id' => $product->id,

&#x20;           'uom\_id' => $product->base\_uom\_id,

&#x20;           'ordered\_qty' => 5,

&#x20;           'unit\_price' => 49.99,

&#x20;       ]);



&#x20;       // UPDATE to Cancelled \& soft delete

&#x20;       $draftSo->update(\['status' => 'cancelled']);

&#x20;       $draftSo->delete();



&#x20;       // CREATE another SO for full flow

&#x20;       $activeSo = SalesOrder::create(\[

&#x20;           'tenant\_id' => $tenant->id,

&#x20;           'customer\_id' => $customer->id,

&#x20;           'warehouse\_id' => $warehouse->id,

&#x20;           'so\_number' => 'SO-ACTIVE-001',

&#x20;           'status' => 'draft',

&#x20;           'currency\_id' => $usd->id,

&#x20;           'order\_date' => now()->subDays(3),

&#x20;           'created\_by' => $user->id,

&#x20;       ]);

&#x20;       SalesOrderLine::create(\[

&#x20;           'sales\_order\_id' => $activeSo->id,

&#x20;           'product\_id' => $product->id,

&#x20;           'uom\_id' => $product->base\_uom\_id,

&#x20;           'ordered\_qty' => 8,

&#x20;           'unit\_price' => 49.99,

&#x20;       ]);



&#x20;       // UPDATE to confirmed

&#x20;       $activeSo->update(\[

&#x20;           'status' => 'confirmed',

&#x20;           'approved\_by' => $user->id,

&#x20;       ]);

&#x20;   }

}



\---



\## Invoice Lifecycle Seeder (Purchase \& Sales)



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

&#x20;   public function run(): void

&#x20;   {

&#x20;       $grn = GrnHeader::where('status', 'posted')->first();

&#x20;       if (!$grn) return;



&#x20;       $user = User::where('tenant\_id', $grn->tenant\_id)->first();

&#x20;       $apAccount = Account::where('code', '2000')->first();

&#x20;       $grIrAccount = Account::where('code', '1500')->first();

&#x20;       $fiscalPeriod = FiscalPeriod::where('status', 'open')->first();



&#x20;       // CREATE draft invoice

&#x20;       $invoice = PurchaseInvoice::create(\[

&#x20;           'tenant\_id' => $grn->tenant\_id,

&#x20;           'supplier\_id' => $grn->supplier\_id,

&#x20;           'grn\_header\_id' => $grn->id,

&#x20;           'invoice\_number' => 'INV-DRAFT-001',

&#x20;           'status' => 'draft',

&#x20;           'invoice\_date' => now(),

&#x20;           'due\_date' => now()->addDays(30),

&#x20;           'currency\_id' => $grn->currency\_id,

&#x20;           'subtotal' => 1000.00,

&#x20;           'grand\_total' => 1000.00,

&#x20;       ]);



&#x20;       // Add line

&#x20;       $line = $grn->lines->first();

&#x20;       PurchaseInvoiceLine::create(\[

&#x20;           'purchase\_invoice\_id' => $invoice->id,

&#x20;           'grn\_line\_id' => $line->id,

&#x20;           'product\_id' => $line->product\_id,

&#x20;           'uom\_id' => $line->uom\_id,

&#x20;           'quantity' => $line->received\_qty,

&#x20;           'unit\_price' => $line->unit\_cost,

&#x20;           'line\_total' => $line->line\_cost,

&#x20;       ]);



&#x20;       // UPDATE to approved (with journal entry)

&#x20;       $invoice->update(\['status' => 'approved']);

&#x20;       $journalEntry = JournalEntry::create(\[

&#x20;           'tenant\_id' => $grn->tenant\_id,

&#x20;           'fiscal\_period\_id' => $fiscalPeriod->id,

&#x20;           'entry\_type' => 'auto',

&#x20;           'reference\_type' => PurchaseInvoice::class,

&#x20;           'reference\_id' => $invoice->id,

&#x20;           'entry\_date' => $invoice->invoice\_date,

&#x20;           'status' => 'posted',

&#x20;           'created\_by' => $user->id,

&#x20;           'posted\_by' => $user->id,

&#x20;           'posted\_at' => now(),

&#x20;       ]);

&#x20;       JournalEntryLine::create(\['journal\_entry\_id' => $journalEntry->id, 'account\_id' => $grIrAccount->id, 'debit\_amount' => 1000.00]);

&#x20;       JournalEntryLine::create(\['journal\_entry\_id' => $journalEntry->id, 'account\_id' => $apAccount->id, 'credit\_amount' => 1000.00]);

&#x20;       $invoice->update(\['journal\_entry\_id' => $journalEntry->id]);



&#x20;       // CREATE another invoice that will be voided (soft delete)

&#x20;       $voidInvoice = PurchaseInvoice::create(\[

&#x20;           'tenant\_id' => $grn->tenant\_id,

&#x20;           'supplier\_id' => $grn->supplier\_id,

&#x20;           'invoice\_number' => 'INV-VOID-001',

&#x20;           'status' => 'cancelled',

&#x20;           'invoice\_date' => now(),

&#x20;           'due\_date' => now()->addDays(30),

&#x20;           'currency\_id' => $grn->currency\_id,

&#x20;           'subtotal' => 500.00,

&#x20;           'grand\_total' => 500.00,

&#x20;       ]);

&#x20;       $voidInvoice->delete(); // soft delete

&#x20;   }

}



\---



\## PurchaseCreateSeeder.php – Create PO, confirm, receive (GRN), invoice



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

&#x20;   public function run(): void

&#x20;   {

&#x20;       $tenant = Tenant::first();

&#x20;       $supplier = Supplier::where('tenant\_id', $tenant->id)->first();

&#x20;       $warehouse = Warehouse::where('tenant\_id', $tenant->id)->first();

&#x20;       $user = User::where('tenant\_id', $tenant->id)->first();

&#x20;       $product = Product::where('tenant\_id', $tenant->id)->first();

&#x20;       $location = WarehouseLocation::where('warehouse\_id', $warehouse->id)->first();

&#x20;       $usd = \\Modules\\Core\\Models\\Currency::where('code', 'USD')->first();

&#x20;       $inventoryAccount = Account::where('code', '1300')->first();

&#x20;       $apAccount = Account::where('code', '2000')->first();

&#x20;       $grIrAccount = Account::where('code', '1500')->first();

&#x20;       $fiscalPeriod = FiscalPeriod::where('status', 'open')->first();



&#x20;       DB::transaction(function () use ($tenant, $supplier, $warehouse, $user, $product, $location, $usd, $inventoryAccount, $apAccount, $grIrAccount, $fiscalPeriod) {

&#x20;           // CREATE Purchase Order

&#x20;           $po = PurchaseOrder::create(\[

&#x20;               'tenant\_id' => $tenant->id,

&#x20;               'supplier\_id' => $supplier->id,

&#x20;               'warehouse\_id' => $warehouse->id,

&#x20;               'po\_number' => 'PO-CREATE-001',

&#x20;               'status' => 'draft',

&#x20;               'currency\_id' => $usd->id,

&#x20;               'order\_date' => now(),

&#x20;               'expected\_date' => now()->addDays(7),

&#x20;               'created\_by' => $user->id,

&#x20;           ]);



&#x20;           PurchaseOrderLine::create(\[

&#x20;               'purchase\_order\_id' => $po->id,

&#x20;               'product\_id' => $product->id,

&#x20;               'uom\_id' => $product->base\_uom\_id,

&#x20;               'ordered\_qty' => 25,

&#x20;               'unit\_price' => 12.50,

&#x20;               'line\_total' => 312.50,

&#x20;           ]);



&#x20;           // UPDATE: Confirm PO

&#x20;           $po->update(\['status' => 'confirmed', 'approved\_by' => $user->id]);



&#x20;           // CREATE Goods Receipt (GRN)

&#x20;           $grn = GrnHeader::create(\[

&#x20;               'tenant\_id' => $tenant->id,

&#x20;               'supplier\_id' => $supplier->id,

&#x20;               'warehouse\_id' => $warehouse->id,

&#x20;               'purchase\_order\_id' => $po->id,

&#x20;               'grn\_number' => 'GRN-CREATE-001',

&#x20;               'status' => 'complete',

&#x20;               'received\_date' => now(),

&#x20;               'currency\_id' => $usd->id,

&#x20;               'created\_by' => $user->id,

&#x20;           ]);



&#x20;           $line = $po->lines->first();

&#x20;           GrnLine::create(\[

&#x20;               'grn\_header\_id' => $grn->id,

&#x20;               'purchase\_order\_line\_id' => $line->id,

&#x20;               'product\_id' => $line->product\_id,

&#x20;               'location\_id' => $location->id,

&#x20;               'uom\_id' => $line->uom\_id,

&#x20;               'expected\_qty' => $line->ordered\_qty,

&#x20;               'received\_qty' => $line->ordered\_qty,

&#x20;               'unit\_cost' => $line->unit\_price,

&#x20;               'line\_cost' => $line->ordered\_qty \* $line->unit\_price,

&#x20;           ]);



&#x20;           // Stock Movement (receipt)

&#x20;           StockMovement::create(\[

&#x20;               'tenant\_id' => $tenant->id,

&#x20;               'product\_id' => $product->id,

&#x20;               'to\_location\_id' => $location->id,

&#x20;               'movement\_type' => 'receipt',

&#x20;               'reference\_type' => GrnHeader::class,

&#x20;               'reference\_id' => $grn->id,

&#x20;               'uom\_id' => $product->base\_uom\_id,

&#x20;               'quantity' => 25,

&#x20;               'unit\_cost' => 12.50,

&#x20;               'performed\_by' => $user->id,

&#x20;           ]);



&#x20;           // Update Stock Level

&#x20;           StockLevel::updateOrCreate(\[

&#x20;               'tenant\_id' => $tenant->id,

&#x20;               'product\_id' => $product->id,

&#x20;               'location\_id' => $location->id,

&#x20;           ], \['uom\_id' => $product->base\_uom\_id])

&#x20;               ->increment('quantity\_on\_hand', 25);



&#x20;           // Cost Layer

&#x20;           InventoryCostLayer::create(\[

&#x20;               'tenant\_id' => $tenant->id,

&#x20;               'product\_id' => $product->id,

&#x20;               'location\_id' => $location->id,

&#x20;               'valuation\_method' => 'fifo',

&#x20;               'layer\_date' => now(),

&#x20;               'quantity\_in' => 25,

&#x20;               'quantity\_remaining' => 25,

&#x20;               'unit\_cost' => 12.50,

&#x20;               'reference\_type' => GrnHeader::class,

&#x20;               'reference\_id' => $grn->id,

&#x20;           ]);



&#x20;           // Journal Entry: Dr Inventory, Cr GR/IR

&#x20;           $je = JournalEntry::create(\[

&#x20;               'tenant\_id' => $tenant->id,

&#x20;               'fiscal\_period\_id' => $fiscalPeriod->id,

&#x20;               'entry\_type' => 'auto',

&#x20;               'reference\_type' => GrnHeader::class,

&#x20;               'reference\_id' => $grn->id,

&#x20;               'entry\_date' => now(),

&#x20;               'status' => 'posted',

&#x20;               'created\_by' => $user->id,

&#x20;               'posted\_by' => $user->id,

&#x20;               'posted\_at' => now(),

&#x20;           ]);

&#x20;           JournalEntryLine::create(\['journal\_entry\_id' => $je->id, 'account\_id' => $inventoryAccount->id, 'debit\_amount' => 312.50]);

&#x20;           JournalEntryLine::create(\['journal\_entry\_id' => $je->id, 'account\_id' => $grIrAccount->id, 'credit\_amount' => 312.50]);



&#x20;           // CREATE Purchase Invoice

&#x20;           $invoice = PurchaseInvoice::create(\[

&#x20;               'tenant\_id' => $tenant->id,

&#x20;               'supplier\_id' => $supplier->id,

&#x20;               'grn\_header\_id' => $grn->id,

&#x20;               'invoice\_number' => 'INV-CREATE-001',

&#x20;               'status' => 'approved',

&#x20;               'invoice\_date' => now(),

&#x20;               'due\_date' => now()->addDays(30),

&#x20;               'currency\_id' => $usd->id,

&#x20;               'subtotal' => 312.50,

&#x20;               'grand\_total' => 312.50,

&#x20;               'ap\_account\_id' => $apAccount->id,

&#x20;           ]);

&#x20;           PurchaseInvoiceLine::create(\[

&#x20;               'purchase\_invoice\_id' => $invoice->id,

&#x20;               'grn\_line\_id' => $grn->lines->first()->id,

&#x20;               'product\_id' => $product->id,

&#x20;               'uom\_id' => $product->base\_uom\_id,

&#x20;               'quantity' => 25,

&#x20;               'unit\_price' => 12.50,

&#x20;               'line\_total' => 312.50,

&#x20;           ]);



&#x20;           // Journal Entry: Dr GR/IR, Cr AP

&#x20;           $je2 = JournalEntry::create(\[...]);

&#x20;           JournalEntryLine::create(\['journal\_entry\_id' => $je2->id, 'account\_id' => $grIrAccount->id, 'debit\_amount' => 312.50]);

&#x20;           JournalEntryLine::create(\['journal\_entry\_id' => $je2->id, 'account\_id' => $apAccount->id, 'credit\_amount' => 312.50]);

&#x20;           $invoice->update(\['journal\_entry\_id' => $je2->id]);

&#x20;       });

&#x20;   }

}



\---



\## PurchaseUpdateSeeder.php – Demonstrate updates: PO quantity change, invoice status change, payment



<?php

// Updates an existing PO line quantity, then updates invoice to paid.

public function run(): void

{

&#x20;   $po = PurchaseOrder::where('po\_number', 'PO-CREATE-001')->first();

&#x20;   if ($po) {

&#x20;       // UPDATE line quantity

&#x20;       $line = $po->lines->first();

&#x20;       $line->update(\['ordered\_qty' => 30, 'line\_total' => 30 \* $line->unit\_price]);

&#x20;       $po->update(\['subtotal' => 30 \* $line->unit\_price, 'grand\_total' => 30 \* $line->unit\_price]);



&#x20;       // UPDATE invoice to paid (via payment)

&#x20;       $invoice = PurchaseInvoice::where('invoice\_number', 'INV-CREATE-001')->first();

&#x20;       // Create payment and allocate...

&#x20;       $invoice->update(\['status' => 'paid']);

&#x20;   }

}



\---



\## SalesReturnCreateSeeder.php – Create and approve a sales return with original reference, restock, credit memo



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

&#x20;   public function run(): void

&#x20;   {

&#x20;       $so = SalesOrder::where('status', 'shipped')->first();

&#x20;       if (!$so) return;



&#x20;       DB::transaction(function () use ($so) {

&#x20;           $user = User::where('tenant\_id', $so->tenant\_id)->first();

&#x20;           $restockLocation = WarehouseLocation::where('warehouse\_id', $so->warehouse\_id)->first();

&#x20;           $salesReturnsAccount = Account::where('code', '4100')->first();

&#x20;           $arAccount = Account::where('code', '1200')->first();

&#x20;           $inventoryAccount = Account::where('code', '1300')->first();

&#x20;           $cogsAccount = Account::where('code', '5000')->first();



&#x20;           $salesReturn = SalesReturn::create(\[

&#x20;               'tenant\_id' => $so->tenant\_id,

&#x20;               'customer\_id' => $so->customer\_id,

&#x20;               'original\_sales\_order\_id' => $so->id,

&#x20;               'return\_number' => 'SR-CREATE-001',

&#x20;               'status' => 'draft',

&#x20;               'return\_date' => now(),

&#x20;               'return\_reason' => 'Defective',

&#x20;               'currency\_id' => $so->currency\_id,

&#x20;           ]);



&#x20;           $line = $so->lines->first();

&#x20;           $returnQty = 2;

&#x20;           $revenueDebit = $returnQty \* $line->unit\_price;



&#x20;           $returnLine = SalesReturnLine::create(\[

&#x20;               'sales\_return\_id' => $salesReturn->id,

&#x20;               'original\_sales\_order\_line\_id' => $line->id,

&#x20;               'product\_id' => $line->product\_id,

&#x20;               'to\_location\_id' => $restockLocation->id,

&#x20;               'uom\_id' => $line->uom\_id,

&#x20;               'return\_qty' => $returnQty,

&#x20;               'unit\_price' => $line->unit\_price,

&#x20;               'condition' => 'defective',

&#x20;               'disposition' => 'restock',

&#x20;               'restocking\_fee' => 5.00,

&#x20;           ]);



&#x20;           // Approve return

&#x20;           $salesReturn->update(\['status' => 'approved']);



&#x20;           // Stock Movement (return\_in)

&#x20;           StockMovement::create(\[... 'movement\_type' => 'return\_in', 'quantity' => $returnQty, 'unit\_cost' => 10.00]);



&#x20;           // Update stock level

&#x20;           StockLevel::updateOrCreate(\[...])->increment('quantity\_on\_hand', $returnQty);



&#x20;           // Re-insert cost layer

&#x20;           InventoryCostLayer::create(\[... 'quantity\_in' => $returnQty, 'unit\_cost' => 10.00]);



&#x20;           // Journal Entry

&#x20;           $je = JournalEntry::create(\[...]);

&#x20;           JournalEntryLine::create(\['journal\_entry\_id' => $je->id, 'account\_id' => $salesReturnsAccount->id, 'debit\_amount' => $revenueDebit]);

&#x20;           JournalEntryLine::create(\['journal\_entry\_id' => $je->id, 'account\_id' => $arAccount->id, 'credit\_amount' => $revenueDebit + 5.00]);

&#x20;           JournalEntryLine::create(\['journal\_entry\_id' => $je->id, 'account\_id' => $inventoryAccount->id, 'debit\_amount' => $returnQty \* 10.00]);

&#x20;           JournalEntryLine::create(\['journal\_entry\_id' => $je->id, 'account\_id' => $cogsAccount->id, 'credit\_amount' => $returnQty \* 10.00]);



&#x20;           // Credit Memo

&#x20;           CreditMemo::create(\[... 'amount' => $revenueDebit + 5.00, 'status' => 'issued']);



&#x20;           $salesReturn->update(\['status' => 'closed']);

&#x20;       });

&#x20;   }

}



\---



\## Laravel Migrations (Full Production Code)



// 1. Create parties table

Schema::create('parties', function (Blueprint $table) {

&#x20;   $table->id();

&#x20;   $table->enum('party\_type', \['supplier', 'customer', 'both']);

&#x20;   $table->string('name');

&#x20;   $table->string('tax\_id')->nullable();

&#x20;   $table->string('email')->nullable();

&#x20;   $table->string('phone')->nullable();

&#x20;   $table->string('website')->nullable();

&#x20;   $table->boolean('is\_active')->default(true);

&#x20;   $table->timestamps();

&#x20;   $table->softDeletes();

});



// 2. Party addresses

Schema::create('party\_addresses', function (Blueprint $table) {

&#x20;   $table->id();

&#x20;   $table->foreignId('party\_id')->constrained('parties')->restrictOnDelete();

&#x20;   $table->enum('address\_type', \['billing', 'shipping', 'legal']);

&#x20;   $table->string('line1');

&#x20;   $table->string('line2')->nullable();

&#x20;   $table->string('city');

&#x20;   $table->string('state')->nullable();

&#x20;   $table->string('postal\_code')->nullable();

&#x20;   $table->string('country');

&#x20;   $table->boolean('is\_default')->default(false);

&#x20;   $table->timestamps();

});



// 3. UOMs

Schema::create('uoms', function (Blueprint $table) {

&#x20;   $table->id();

&#x20;   $table->string('code', 10)->unique();

&#x20;   $table->string('name', 50);

&#x20;   $table->string('category', 50);

&#x20;   $table->timestamps();

});



// 4. Product categories

Schema::create('product\_categories', function (Blueprint $table) {

&#x20;   $table->id();

&#x20;   $table->foreignId('parent\_id')->nullable()->constrained('product\_categories')->nullOnDelete();

&#x20;   $table->string('name');

&#x20;   $table->string('slug')->unique();

&#x20;   $table->timestamps();

});



// 5. Products

Schema::create('products', function (Blueprint $table) {

&#x20;   $table->id();

&#x20;   $table->string('sku')->unique();

&#x20;   $table->string('name');

&#x20;   $table->text('description')->nullable();

&#x20;   $table->enum('product\_type', \['simple', 'variant\_parent', 'bundle', 'digital', 'service']);

&#x20;   $table->boolean('is\_stockable')->default(true);

&#x20;   $table->boolean('is\_tracked\_batch')->default(false);

&#x20;   $table->boolean('is\_tracked\_serial')->default(false);

&#x20;   $table->decimal('weight', 12, 4)->nullable();

&#x20;   $table->foreignId('weight\_uom\_id')->nullable()->constrained('uoms')->nullOnDelete();

&#x20;   $table->foreignId('category\_id')->nullable()->constrained('product\_categories')->nullOnDelete();

&#x20;   $table->timestamps();

&#x20;   $table->softDeletes();

});



// 6. Product variants

Schema::create('product\_variants', function (Blueprint $table) {

&#x20;   $table->id();

&#x20;   $table->foreignId('product\_id')->constrained('products')->cascadeOnDelete();

&#x20;   $table->string('sku')->unique();

&#x20;   $table->json('attributes'); // e.g. {"color":"red"}

&#x20;   $table->string('barcode', 100)->nullable()->unique();

&#x20;   $table->boolean('is\_active')->default(true);

&#x20;   $table->timestamps();

});



// 7. Product UOM conversions

Schema::create('product\_uom\_conversions', function (Blueprint $table) {

&#x20;   $table->id();

&#x20;   $table->foreignId('product\_id')->constrained('products')->cascadeOnDelete();

&#x20;   $table->foreignId('from\_uom\_id')->constrained('uoms')->restrictOnDelete();

&#x20;   $table->foreignId('to\_uom\_id')->constrained('uoms')->restrictOnDelete();

&#x20;   $table->decimal('factor', 20, 10);

&#x20;   $table->unique(\['product\_id', 'from\_uom\_id', 'to\_uom\_id']);

&#x20;   $table->timestamps();

});



// 8. Warehouses \& storage locations

Schema::create('warehouses', function (Blueprint $table) {

&#x20;   $table->id();

&#x20;   $table->string('code', 50)->unique();

&#x20;   $table->string('name');

&#x20;   $table->text('address')->nullable();

&#x20;   $table->boolean('is\_active')->default(true);

&#x20;   $table->timestamps();

});



Schema::create('storage\_locations', function (Blueprint $table) {

&#x20;   $table->id();

&#x20;   $table->foreignId('warehouse\_id')->constrained('warehouses')->cascadeOnDelete();

&#x20;   $table->string('code', 100);

&#x20;   $table->string('barcode', 100)->nullable();

&#x20;   $table->timestamps();

&#x20;   $table->unique(\['warehouse\_id', 'code']);

});



// 9. Batches \& serial numbers

Schema::create('batches', function (Blueprint $table) {

&#x20;   $table->id();

&#x20;   $table->foreignId('product\_id')->constrained('products')->restrictOnDelete();

&#x20;   $table->string('batch\_number', 100);

&#x20;   $table->string('manufacturer\_batch', 100)->nullable();

&#x20;   $table->date('expiry\_date')->nullable();

&#x20;   $table->date('manufacture\_date')->nullable();

&#x20;   $table->string('barcode', 100)->nullable();

&#x20;   $table->boolean('is\_active')->default(true);

&#x20;   $table->timestamps();

&#x20;   $table->unique(\['product\_id', 'batch\_number']);

});



Schema::create('serial\_numbers', function (Blueprint $table) {

&#x20;   $table->id();

&#x20;   $table->foreignId('product\_id')->constrained('products')->restrictOnDelete();

&#x20;   $table->foreignId('variant\_id')->nullable()->constrained('product\_variants')->nullOnDelete();

&#x20;   $table->foreignId('batch\_id')->nullable()->constrained('batches')->nullOnDelete();

&#x20;   $table->string('serial\_number', 100)->unique();

&#x20;   $table->enum('status', \['in\_stock', 'sold', 'returned', 'scrapped'])->default('in\_stock');

&#x20;   $table->foreignId('current\_location\_id')->nullable()->constrained('storage\_locations')->nullOnDelete();

&#x20;   $table->timestamps();

});



// 10. Purchase side

Schema::create('purchase\_orders', function (Blueprint $table) {

&#x20;   $table->id();

&#x20;   $table->string('po\_number', 50)->unique();

&#x20;   $table->foreignId('supplier\_id')->constrained('parties')->restrictOnDelete();

&#x20;   $table->date('order\_date');

&#x20;   $table->date('expected\_date')->nullable();

&#x20;   $table->enum('status', \['draft', 'confirmed', 'partially\_received', 'received', 'cancelled'])->default('draft');

&#x20;   $table->decimal('total\_amount', 15, 2);

&#x20;   $table->char('currency', 3)->default('USD');

&#x20;   $table->text('notes')->nullable();

&#x20;   $table->timestamps();

});



Schema::create('purchase\_order\_lines', function (Blueprint $table) {

&#x20;   $table->id();

&#x20;   $table->foreignId('purchase\_order\_id')->constrained('purchase\_orders')->cascadeOnDelete();

&#x20;   $table->foreignId('product\_id')->constrained('products')->restrictOnDelete();

&#x20;   $table->foreignId('variant\_id')->nullable()->constrained('product\_variants')->nullOnDelete();

&#x20;   $table->foreignId('uom\_id')->constrained('uoms')->restrictOnDelete();

&#x20;   $table->decimal('quantity', 15, 5);

&#x20;   $table->decimal('unit\_price', 15, 5);

&#x20;   $table->decimal('discount\_percent', 8, 2)->default(0);

&#x20;   $table->decimal('tax\_rate', 8, 4)->default(0);

&#x20;   $table->decimal('total\_line', 15, 2);

&#x20;   $table->timestamps();

});



Schema::create('purchase\_receipts', function (Blueprint $table) {

&#x20;   $table->id();

&#x20;   $table->string('receipt\_number', 50)->unique();

&#x20;   $table->foreignId('purchase\_order\_id')->nullable()->constrained('purchase\_orders')->nullOnDelete();

&#x20;   $table->foreignId('supplier\_id')->constrained('parties')->restrictOnDelete();

&#x20;   $table->dateTime('receipt\_date');

&#x20;   $table->foreignId('warehouse\_id')->constrained('warehouses')->restrictOnDelete();

&#x20;   $table->enum('status', \['draft', 'completed', 'cancelled'])->default('draft');

&#x20;   $table->text('notes')->nullable();

&#x20;   $table->timestamps();

});



Schema::create('purchase\_receipt\_lines', function (Blueprint $table) {

&#x20;   $table->id();

&#x20;   $table->foreignId('receipt\_id')->constrained('purchase\_receipts')->cascadeOnDelete();

&#x20;   $table->foreignId('po\_line\_id')->nullable()->constrained('purchase\_order\_lines')->nullOnDelete();

&#x20;   $table->foreignId('product\_id')->constrained('products')->restrictOnDelete();

&#x20;   $table->foreignId('variant\_id')->nullable()->constrained('product\_variants')->nullOnDelete();

&#x20;   $table->foreignId('uom\_id')->constrained('uoms')->restrictOnDelete();

&#x20;   $table->decimal('quantity', 15, 5);

&#x20;   $table->foreignId('batch\_id')->nullable()->constrained('batches')->nullOnDelete();

&#x20;   $table->text('serial\_numbers')->nullable(); // comma separated

&#x20;   $table->foreignId('storage\_location\_id')->nullable()->constrained('storage\_locations')->nullOnDelete();

&#x20;   $table->timestamps();

});



// 11. Sales side (mirror structure)

Schema::create('sales\_orders', function (Blueprint $table) {

&#x20;   $table->id();

&#x20;   $table->string('so\_number', 50)->unique();

&#x20;   $table->foreignId('customer\_id')->constrained('parties')->restrictOnDelete();

&#x20;   $table->date('order\_date');

&#x20;   $table->date('requested\_date')->nullable();

&#x20;   $table->enum('status', \['draft', 'confirmed', 'partially\_delivered', 'delivered', 'cancelled'])->default('draft');

&#x20;   $table->decimal('total\_amount', 15, 2);

&#x20;   $table->char('currency', 3)->default('USD');

&#x20;   $table->timestamps();

});



Schema::create('sales\_order\_lines', function (Blueprint $table) {

&#x20;   $table->id();

&#x20;   $table->foreignId('sales\_order\_id')->constrained('sales\_orders')->cascadeOnDelete();

&#x20;   $table->foreignId('product\_id')->constrained('products');

&#x20;   $table->foreignId('variant\_id')->nullable()->constrained('product\_variants');

&#x20;   $table->foreignId('uom\_id')->constrained('uoms');

&#x20;   $table->decimal('quantity', 15, 5);

&#x20;   $table->decimal('unit\_price', 15, 5);

&#x20;   $table->decimal('discount\_percent', 8, 2)->default(0);

&#x20;   $table->decimal('tax\_rate', 8, 4)->default(0);

&#x20;   $table->decimal('total\_line', 15, 2);

&#x20;   $table->timestamps();

});



Schema::create('sales\_deliveries', function (Blueprint $table) {

&#x20;   $table->id();

&#x20;   $table->string('delivery\_number', 50)->unique();

&#x20;   $table->foreignId('sales\_order\_id')->nullable()->constrained('sales\_orders')->nullOnDelete();

&#x20;   $table->foreignId('customer\_id')->constrained('parties');

&#x20;   $table->dateTime('delivery\_date');

&#x20;   $table->foreignId('warehouse\_id')->constrained('warehouses');

&#x20;   $table->enum('status', \['draft', 'shipped', 'delivered', 'cancelled'])->default('draft');

&#x20;   $table->timestamps();

});



Schema::create('sales\_delivery\_lines', function (Blueprint $table) {

&#x20;   $table->id();

&#x20;   $table->foreignId('delivery\_id')->constrained('sales\_deliveries')->cascadeOnDelete();

&#x20;   $table->foreignId('so\_line\_id')->nullable()->constrained('sales\_order\_lines')->nullOnDelete();

&#x20;   $table->foreignId('product\_id')->constrained('products');

&#x20;   $table->foreignId('variant\_id')->nullable()->constrained('product\_variants');

&#x20;   $table->foreignId('uom\_id')->constrained('uoms');

&#x20;   $table->decimal('quantity', 15, 5);

&#x20;   $table->foreignId('batch\_id')->nullable()->constrained('batches');

&#x20;   $table->text('serial\_numbers')->nullable();

&#x20;   $table->foreignId('storage\_location\_id')->nullable()->constrained('storage\_locations');

&#x20;   $table->timestamps();

});



// 12. Returns (purchase \& sales) – similar pattern, omitted for brevity.



// 13. Stock movements (core inventory)

Schema::create('stock\_movements', function (Blueprint $table) {

&#x20;   $table->id();

&#x20;   $table->enum('movement\_type', \['purchase\_receipt', 'sales\_delivery', 'purchase\_return', 'sales\_return', 'adjustment', 'transfer']);

&#x20;   $table->string('reference\_type', 50); // polymorphic

&#x20;   $table->unsignedBigInteger('reference\_id');

&#x20;   $table->foreignId('product\_id')->constrained('products');

&#x20;   $table->foreignId('variant\_id')->nullable()->constrained('product\_variants');

&#x20;   $table->foreignId('from\_location\_id')->nullable()->constrained('storage\_locations');

&#x20;   $table->foreignId('to\_location\_id')->nullable()->constrained('storage\_locations');

&#x20;   $table->foreignId('batch\_id')->nullable()->constrained('batches');

&#x20;   $table->foreignId('serial\_id')->nullable()->constrained('serial\_numbers');

&#x20;   $table->decimal('quantity', 15, 5);

&#x20;   $table->foreignId('uom\_id')->constrained('uoms');

&#x20;   $table->dateTime('movement\_date');

&#x20;   $table->foreignId('created\_by')->nullable()->constrained('users');

&#x20;   $table->timestamps();



&#x20;   $table->index(\['reference\_type', 'reference\_id']);

});



// 14. Accounting

Schema::create('accounts', function (Blueprint $table) {

&#x20;   $table->id();

&#x20;   $table->string('code', 20)->unique();

&#x20;   $table->string('name');

&#x20;   $table->enum('account\_type', \['asset', 'liability', 'equity', 'revenue', 'expense']);

&#x20;   $table->foreignId('parent\_id')->nullable()->constrained('accounts')->nullOnDelete();

&#x20;   $table->boolean('is\_control')->default(false);

&#x20;   $table->boolean('is\_active')->default(true);

&#x20;   $table->timestamps();

});



Schema::create('journal\_entries', function (Blueprint $table) {

&#x20;   $table->id();

&#x20;   $table->string('entry\_number', 50)->unique();

&#x20;   $table->date('entry\_date');

&#x20;   $table->string('reference\_type', 50); // polymorphic

&#x20;   $table->unsignedBigInteger('reference\_id')->nullable();

&#x20;   $table->text('description')->nullable();

&#x20;   $table->boolean('is\_posted')->default(false);

&#x20;   $table->timestamp('posted\_at')->nullable();

&#x20;   $table->timestamps();



&#x20;   $table->index(\['reference\_type', 'reference\_id']);

});



Schema::create('journal\_entry\_lines', function (Blueprint $table) {

&#x20;   $table->id();

&#x20;   $table->foreignId('journal\_entry\_id')->constrained('journal\_entries')->cascadeOnDelete();

&#x20;   $table->foreignId('account\_id')->constrained('accounts')->restrictOnDelete();

&#x20;   $table->decimal('debit', 15, 2)->default(0);

&#x20;   $table->decimal('credit', 15, 2)->default(0);

&#x20;   $table->text('memo')->nullable();

&#x20;   $table->timestamps();



&#x20;   $table->check('debit >= 0 and credit >= 0');

});



Schema::create('payments', function (Blueprint $table) {

&#x20;   $table->id();

&#x20;   $table->string('payment\_number', 50)->unique();

&#x20;   $table->foreignId('party\_id')->constrained('parties');

&#x20;   $table->enum('payment\_type', \['supplier\_payment', 'customer\_receipt']);

&#x20;   $table->decimal('amount', 15, 2);

&#x20;   $table->date('payment\_date');

&#x20;   $table->string('reference', 255)->nullable();

&#x20;   $table->enum('status', \['pending', 'completed', 'failed'])->default('pending');

&#x20;   $table->foreignId('journal\_entry\_id')->nullable()->constrained('journal\_entries')->nullOnDelete();

&#x20;   $table->timestamps();

});



// 15. Current stock balances (for performance)

Schema::create('current\_stock\_balances', function (Blueprint $table) {

&#x20;   $table->id();

&#x20;   $table->foreignId('product\_id')->constrained('products');

&#x20;   $table->foreignId('variant\_id')->nullable()->constrained('product\_variants');

&#x20;   $table->foreignId('warehouse\_id')->constrained('warehouses');

&#x20;   $table->foreignId('storage\_location\_id')->nullable()->constrained('storage\_locations');

&#x20;   $table->foreignId('batch\_id')->nullable()->constrained('batches');

&#x20;   $table->decimal('quantity\_on\_hand', 15, 5)->default(0);

&#x20;   $table->decimal('quantity\_reserved', 15, 5)->default(0);

&#x20;   $table->timestamp('last\_updated')->useCurrent();

&#x20;   $table->unique(\['product\_id', 'variant\_id', 'warehouse\_id', 'storage\_location\_id', 'batch\_id'], 'stock\_balance\_unique');

});



\---



\## PROCUREMENT FLOW



Supplier → PURCHASE → Document Line → Inventory Ledger (IN)

&#x20;        → Journal Entry (Dr Inventory / Cr Payable)



\## SALES FLOW



Customer → SALE → Document Line → Inventory Ledger (OUT)

&#x20;        → Journal Entry (Dr Receivable / Cr Revenue)



\---



\## Purchase



Document(type=purchase)

&#x20;→ Line

&#x20;→ Inventory IN

&#x20;→ Accounting:

&#x20;   DR Inventory

&#x20;   CR Accounts Payable



\## Sale



Document(type=sale)

&#x20;→ Line

&#x20;→ Inventory OUT

&#x20;→ Accounting:

&#x20;   DR COGS

&#x20;   CR Inventory

&#x20;   DR Accounts Receivable

&#x20;   CR Revenue



\---





<?php

// app/Enums/TenantStatus.php

enum TenantStatus: string

{

&#x20;   case ACTIVE = 'active';

&#x20;   case SUSPENDED = 'suspended';

&#x20;   case TRIAL = 'trial';



&#x20;   public static function values(): array

&#x20;   {

&#x20;       return array\_column(self::cases(), 'value');

&#x20;   }

}



<?php

// app/Enums/PartyType.php

enum PartyType: string

{

&#x20;   case INDIVIDUAL = 'individual';

&#x20;   case ORGANIZATION = 'organization';



&#x20;   public static function values(): array

&#x20;   {

&#x20;       return array\_column(self::cases(), 'value');

&#x20;   }

}



<?php

// app/Enums/PartyStatus.php

enum PartyStatus: string

{

&#x20;   case ACTIVE = 'active';

&#x20;   case INACTIVE = 'inactive';

&#x20;   case BLOCKED = 'blocked';



&#x20;   public static function values(): array

&#x20;   {

&#x20;       return array\_column(self::cases(), 'value');

&#x20;   }

}



<?php

// app/Enums/ContactType.php

enum ContactType: string

{

&#x20;   case EMAIL = 'email';

&#x20;   case PHONE = 'phone';

&#x20;   case MOBILE = 'mobile';

&#x20;   case FAX = 'fax';

&#x20;   case WEBSITE = 'website';



&#x20;   public static function values(): array

&#x20;   {

&#x20;       return array\_column(self::cases(), 'value');

&#x20;   }

}



<?php

// app/Enums/AddressType.php

enum AddressType: string

{

&#x20;   case BILLING = 'billing';

&#x20;   case SHIPPING = 'shipping';

&#x20;   case POSTAL = 'postal';

&#x20;   case PHYSICAL = 'physical';

&#x20;   case REGISTERED = 'registered';



&#x20;   public static function values(): array

&#x20;   {

&#x20;       return array\_column(self::cases(), 'value');

&#x20;   }

}



<?php

// app/Enums/OrganizationUnitType.php

enum OrganizationUnitType: string

{

&#x20;   case COMPANY = 'company';

&#x20;   case DIVISION = 'division';

&#x20;   case DEPARTMENT = 'department';

&#x20;   case WAREHOUSE = 'warehouse';

&#x20;   case STORE = 'store';

&#x20;   case COST\_CENTER = 'cost\_center';

&#x20;   case PROFIT\_CENTER = 'profit\_center';



&#x20;   public static function values(): array

&#x20;   {

&#x20;       return array\_column(self::cases(), 'value');

&#x20;   }

}



<?php

// app/Enums/ProductType.php

enum ProductType: string

{

&#x20;   case PHYSICAL = 'physical';

&#x20;   case SERVICE = 'service';

&#x20;   case DIGITAL = 'digital';

&#x20;   case COMBO = 'combo';

&#x20;   case VARIABLE = 'variable';



&#x20;   public static function values(): array

&#x20;   {

&#x20;       return array\_column(self::cases(), 'value');

&#x20;   }

}



<?php

// app/Enums/ValuationMethod.php

enum ValuationMethod: string

{

&#x20;   case FIFO = 'FIFO';

&#x20;   case LIFO = 'LIFO';

&#x20;   case AVERAGE = 'AVERAGE';

&#x20;   case STANDARD = 'STANDARD';



&#x20;   public static function values(): array

&#x20;   {

&#x20;       return array\_column(self::cases(), 'value');

&#x20;   }

}



<?php

// app/Enums/UomType.php

enum UomType: string

{

&#x20;   case WEIGHT = 'weight';

&#x20;   case VOLUME = 'volume';

&#x20;   case LENGTH = 'length';

&#x20;   case AREA = 'area';

&#x20;   case COUNT = 'count';

&#x20;   case TIME = 'time';



&#x20;   public static function values(): array

&#x20;   {

&#x20;       return array\_column(self::cases(), 'value');

&#x20;   }

}



<?php

// app/Enums/InventoryItemStatus.php

enum InventoryItemStatus: string

{

&#x20;   case AVAILABLE = 'available';

&#x20;   case RESERVED = 'reserved';

&#x20;   case QUARANTINE = 'quarantine';

&#x20;   case DAMAGED = 'damaged';

&#x20;   case IN\_TRANSIT = 'in\_transit';



&#x20;   public static function values(): array

&#x20;   {

&#x20;       return array\_column(self::cases(), 'value');

&#x20;   }

}



<?php

// app/Enums/InventoryMovementType.php

enum InventoryMovementType: string

{

&#x20;   case RECEIPT = 'receipt';

&#x20;   case ISSUE = 'issue';

&#x20;   case TRANSFER\_IN = 'transfer\_in';

&#x20;   case TRANSFER\_OUT = 'transfer\_out';

&#x20;   case ADJUSTMENT = 'adjustment';

&#x20;   case RETURN = 'return';

&#x20;   case RESERVE = 'reserve';

&#x20;   case UNRESERVE = 'unreserve';

&#x20;   case CYCLE\_COUNT = 'cycle\_count';



&#x20;   public static function values(): array

&#x20;   {

&#x20;       return array\_column(self::cases(), 'value');

&#x20;   }

}



<?php

// app/Enums/LocationType.php

enum LocationType: string

{

&#x20;   case RECEIVING = 'receiving';

&#x20;   case STORAGE = 'storage';

&#x20;   case PICKING = 'picking';

&#x20;   case SHIPPING = 'shipping';

&#x20;   case QUALITY = 'quality';

&#x20;   case QUARANTINE = 'quarantine';

&#x20;   case RETURNS = 'returns';



&#x20;   public static function values(): array

&#x20;   {

&#x20;       return array\_column(self::cases(), 'value');

&#x20;   }

}



<?php

// app/Enums/DocumentDirection.php

enum DocumentDirection: string

{

&#x20;   case INBOUND = 'inbound';

&#x20;   case OUTBOUND = 'outbound';

&#x20;   case INTERNAL = 'internal';



&#x20;   public static function values(): array

&#x20;   {

&#x20;       return array\_column(self::cases(), 'value');

&#x20;   }

}



<?php

// app/Enums/DocumentStatus.php

enum DocumentStatus: string

{

&#x20;   case DRAFT = 'draft';

&#x20;   case PENDING = 'pending';

&#x20;   case APPROVED = 'approved';

&#x20;   case POSTED = 'posted';

&#x20;   case PARTIALLY\_RECEIVED = 'partially\_received';

&#x20;   case RECEIVED = 'received';

&#x20;   case PARTIALLY\_INVOICED = 'partially\_invoiced';

&#x20;   case INVOICED = 'invoiced';

&#x20;   case PAID = 'paid';

&#x20;   case CANCELLED = 'cancelled';



&#x20;   public static function values(): array

&#x20;   {

&#x20;       return array\_column(self::cases(), 'value');

&#x20;   }

}



<?php

// app/Enums/AccountType.php

enum AccountType: string

{

&#x20;   case ASSET = 'asset';

&#x20;   case LIABILITY = 'liability';

&#x20;   case EQUITY = 'equity';

&#x20;   case REVENUE = 'revenue';

&#x20;   case EXPENSE = 'expense';

&#x20;   case CONTRA\_ASSET = 'contra\_asset';

&#x20;   case CONTRA\_LIABILITY = 'contra\_liability';

&#x20;   case OTHER\_INCOME = 'other\_income';

&#x20;   case OTHER\_EXPENSE = 'other\_expense';



&#x20;   public static function values(): array

&#x20;   {

&#x20;       return array\_column(self::cases(), 'value');

&#x20;   }

}



<?php

// app/Enums/NormalBalance.php

enum NormalBalance: string

{

&#x20;   case DEBIT = 'debit';

&#x20;   case CREDIT = 'credit';



&#x20;   public static function values(): array

&#x20;   {

&#x20;       return array\_column(self::cases(), 'value');

&#x20;   }

}



<?php

// app/Enums/AidcTagType.php

enum AidcTagType: string

{

&#x20;   case BARCODE\_1D = 'barcode\_1d';

&#x20;   case BARCODE\_2D = 'barcode\_2d';

&#x20;   case QR = 'qr';

&#x20;   case RFID\_HF = 'rfid\_hf';

&#x20;   case RFID\_UHF = 'rfid\_uhf';

&#x20;   case NFC = 'nfc';

&#x20;   case GS1\_EPC = 'gs1\_epc';



&#x20;   public static function values(): array

&#x20;   {

&#x20;       return array\_column(self::cases(), 'value');

&#x20;   }

}



<?php

// app/Enums/AidcEntityType.php

enum AidcEntityType: string

{

&#x20;   case PRODUCT\_VARIANT = 'product\_variant';

&#x20;   case INVENTORY\_ITEM = 'inventory\_item';

&#x20;   case LOCATION = 'location';

&#x20;   case DOCUMENT = 'document';

&#x20;   case PARTY = 'party';

&#x20;   case ASSET = 'asset';



&#x20;   public static function values(): array

&#x20;   {

&#x20;       return array\_column(self::cases(), 'value');

&#x20;   }

}



use App\\Enums\\TenantStatus;



// Inside up()

$table->enum('status', TenantStatus::values())->default(TenantStatus::ACTIVE->value);



use App\\Enums\\PartyType;

use App\\Enums\\PartyStatus;



$table->enum('party\_type', PartyType::values());

$table->enum('status', PartyStatus::values())->default(PartyStatus::ACTIVE->value);









