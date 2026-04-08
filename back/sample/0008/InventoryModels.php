<?php namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

// ═════════════════════════════════════════════════════════════════════════════
// StockLedgerEntry — IMMUTABLE. Never update or delete after creation.
// ═════════════════════════════════════════════════════════════════════════════
class StockLedgerEntry extends Model
{
    // No Auditable trait — the ledger itself IS the audit
    protected $fillable = [
        'organization_id', 'reference_number',
        'product_id', 'product_variant_id', 'warehouse_id', 'storage_location_id',
        'lot_id', 'batch_id', 'serial_number_id', 'uom_id',
        'movement_type', 'direction',
        'quantity', 'quantity_before', 'quantity_after',
        'valuation_method', 'unit_cost', 'total_cost',
        'average_cost_before', 'average_cost_after',
        'source_document_type', 'source_document_id', 'source_document_number',
        'source_line_type', 'source_line_id',
        'reason_code', 'notes', 'created_by', 'movement_date',
    ];

    protected $casts = [
        'movement_date'  => 'datetime',
        'quantity'       => 'decimal:4',
        'quantity_before'=> 'decimal:4',
        'quantity_after' => 'decimal:4',
        'unit_cost'      => 'decimal:4',
        'total_cost'     => 'decimal:4',
    ];

    // Prevent any updates after creation
    public static function boot()
    {
        parent::boot();
        static::updating(fn () => throw new \RuntimeException('Stock ledger entries are immutable.'));
        static::deleting(fn ()  => throw new \RuntimeException('Stock ledger entries cannot be deleted.'));
    }

    // Relationships
    public function product()    { return $this->belongsTo(Product::class); }
    public function variant()    { return $this->belongsTo(ProductVariant::class, 'product_variant_id'); }
    public function warehouse()  { return $this->belongsTo(Warehouse::class); }
    public function lot()        { return $this->belongsTo(Lot::class); }
    public function batch()      { return $this->belongsTo(Batch::class); }
    public function serial()     { return $this->belongsTo(SerialNumber::class, 'serial_number_id'); }
    public function createdBy()  { return $this->belongsTo(\App\Models\User::class, 'created_by'); }

    // Scopes
    public function scopeIn($q)  { return $q->where('direction', 'IN'); }
    public function scopeOut($q) { return $q->where('direction', 'OUT'); }
    public function scopeForProduct($q, int $productId) { return $q->where('product_id', $productId); }
    public function scopeInPeriod($q, string $from, string $to) {
        return $q->whereBetween('movement_date', [$from, $to]);
    }
}


// ═════════════════════════════════════════════════════════════════════════════
// StockPosition — real-time inventory snapshot
// ═════════════════════════════════════════════════════════════════════════════
class StockPosition extends Model
{
    use Auditable;

    protected array $auditExclude = ['updated_at', 'last_movement_at'];
    protected array $auditTags    = ['stock', 'positions'];

    protected $fillable = [
        'organization_id', 'product_id', 'product_variant_id',
        'warehouse_id', 'storage_location_id', 'lot_id', 'batch_id', 'uom_id',
        'qty_on_hand', 'qty_available', 'qty_reserved', 'qty_on_order',
        'qty_in_transit', 'qty_quarantine', 'qty_damaged', 'qty_returned',
        'average_cost', 'total_cost_value',
        'last_movement_at', 'last_counted_at',
    ];

    protected $casts = [
        'qty_on_hand'      => 'decimal:4',
        'qty_available'    => 'decimal:4',
        'average_cost'     => 'decimal:6',
        'total_cost_value' => 'decimal:4',
        'last_movement_at' => 'datetime',
        'last_counted_at'  => 'datetime',
    ];

    public function product()         { return $this->belongsTo(Product::class); }
    public function variant()         { return $this->belongsTo(ProductVariant::class, 'product_variant_id'); }
    public function warehouse()       { return $this->belongsTo(Warehouse::class); }
    public function storageLocation() { return $this->belongsTo(StorageLocation::class); }
    public function lot()             { return $this->belongsTo(Lot::class); }
    public function batch()           { return $this->belongsTo(Batch::class); }

    public function getIsNegativeAttribute(): bool { return $this->qty_on_hand < 0; }
    public function getIsZeroAttribute(): bool     { return $this->qty_on_hand == 0; }
}


// ═════════════════════════════════════════════════════════════════════════════
// Batch
// ═════════════════════════════════════════════════════════════════════════════
class Batch extends Model
{
    use SoftDeletes, Auditable;

    protected array $auditTags = ['batches', 'tracking'];

    protected $fillable = [
        'organization_id', 'product_id', 'product_variant_id',
        'batch_number', 'external_batch_ref', 'status',
        'manufacture_date', 'expiry_date', 'best_before_date', 'received_date',
        'supplier_name', 'country_of_origin', 'certificate_number',
        'qc_status', 'qc_notes', 'qc_tested_at', 'qc_tested_by',
        'unit_cost', 'currency', 'landed_costs',
        'notes', 'custom_fields',
    ];

    protected $casts = [
        'manufacture_date' => 'date',
        'expiry_date'      => 'date',
        'best_before_date' => 'date',
        'received_date'    => 'date',
        'qc_tested_at'     => 'datetime',
        'landed_costs'     => 'array',
        'custom_fields'    => 'array',
    ];

    public function product()       { return $this->belongsTo(Product::class); }
    public function variant()       { return $this->belongsTo(ProductVariant::class, 'product_variant_id'); }
    public function lots()          { return $this->hasMany(Lot::class); }
    public function serialNumbers() { return $this->hasMany(SerialNumber::class); }
    public function documents()     { return $this->hasMany(BatchDocument::class); }
    public function qcTestedBy()    { return $this->belongsTo(\App\Models\User::class, 'qc_tested_by'); }

    public function getIsExpiredAttribute(): bool  { return $this->expiry_date && $this->expiry_date->isPast(); }
    public function getIsExpiringSoonAttribute(): bool {
        return $this->expiry_date
            && !$this->is_expired
            && $this->expiry_date->diffInDays(now()) <= 30;
    }

    public function scopeActive($q)    { return $q->where('status', 'active'); }
    public function scopeExpired($q)   { return $q->where('expiry_date', '<', now()); }
    public function scopeQcPending($q) { return $q->where('qc_status', 'pending'); }
}


// ═════════════════════════════════════════════════════════════════════════════
// Lot
// ═════════════════════════════════════════════════════════════════════════════
class Lot extends Model
{
    use SoftDeletes, Auditable;

    protected array $auditTags = ['lots', 'tracking'];

    protected $fillable = [
        'organization_id', 'batch_id', 'product_id', 'product_variant_id',
        'warehouse_id', 'storage_location_id',
        'lot_number', 'status',
        'initial_quantity', 'available_quantity', 'reserved_quantity',
        'damaged_quantity', 'quarantine_quantity', 'uom_id',
        'unit_cost', 'valuation_method',
        'manufacture_date', 'expiry_date', 'best_before_date', 'received_at',
        'notes', 'custom_fields',
    ];

    protected $casts = [
        'manufacture_date'   => 'date',
        'expiry_date'        => 'date',
        'best_before_date'   => 'date',
        'received_at'        => 'datetime',
        'available_quantity' => 'decimal:4',
        'reserved_quantity'  => 'decimal:4',
        'custom_fields'      => 'array',
    ];

    public function product()         { return $this->belongsTo(Product::class); }
    public function variant()         { return $this->belongsTo(ProductVariant::class, 'product_variant_id'); }
    public function batch()           { return $this->belongsTo(Batch::class); }
    public function warehouse()       { return $this->belongsTo(Warehouse::class); }
    public function storageLocation() { return $this->belongsTo(StorageLocation::class); }
    public function stockPositions()  { return $this->hasMany(StockPosition::class); }
    public function ledgerEntries()   { return $this->hasMany(StockLedgerEntry::class); }

    public function scopeAvailable($q)    { return $q->where('status', 'available'); }
    public function scopeExpiringSoon($q, int $days = 30) {
        return $q->whereNotNull('expiry_date')
                 ->where('expiry_date', '>', now())
                 ->where('expiry_date', '<=', now()->addDays($days));
    }

    public function getIsExpiredAttribute(): bool { return $this->expiry_date?->isPast() ?? false; }
}


// ═════════════════════════════════════════════════════════════════════════════
// SerialNumber
// ═════════════════════════════════════════════════════════════════════════════
class SerialNumber extends Model
{
    use SoftDeletes, Auditable;

    protected array $auditTags = ['serials', 'tracking'];

    protected $fillable = [
        'organization_id', 'product_id', 'product_variant_id',
        'batch_id', 'lot_id', 'warehouse_id', 'storage_location_id',
        'serial_number', 'status',
        'unit_cost', 'selling_price',
        'manufacture_date', 'warranty_expiry_date', 'received_date', 'sold_at',
        'sold_to_customer_id', 'mac_address', 'imei', 'notes', 'custom_fields',
    ];

    protected $casts = [
        'manufacture_date'    => 'date',
        'warranty_expiry_date'=> 'date',
        'received_date'       => 'date',
        'sold_at'             => 'datetime',
        'custom_fields'       => 'array',
    ];

    public function product()    { return $this->belongsTo(Product::class); }
    public function batch()      { return $this->belongsTo(Batch::class); }
    public function lot()        { return $this->belongsTo(Lot::class); }
    public function warehouse()  { return $this->belongsTo(Warehouse::class); }

    public function scopeInStock($q)  { return $q->where('status', 'in_stock'); }
    public function scopeSold($q)     { return $q->where('status', 'sold'); }
    public function scopeDefective($q){ return $q->where('status', 'defective'); }
    public function getIsUnderWarrantyAttribute(): bool {
        return $this->warranty_expiry_date && $this->warranty_expiry_date->isFuture();
    }
}


// ═════════════════════════════════════════════════════════════════════════════
// CostingLayer
// ═════════════════════════════════════════════════════════════════════════════
class CostingLayer extends Model
{
    protected $fillable = [
        'organization_id', 'product_id', 'product_variant_id',
        'warehouse_id', 'lot_id', 'batch_id',
        'valuation_method', 'layer_reference',
        'initial_quantity', 'remaining_quantity',
        'unit_cost', 'total_cost',
        'manufacture_date', 'expiry_date', 'received_at',
        'is_fully_consumed', 'fully_consumed_at',
    ];

    protected $casts = [
        'received_at'        => 'datetime',
        'manufacture_date'   => 'date',
        'expiry_date'        => 'date',
        'fully_consumed_at'  => 'datetime',
        'is_fully_consumed'  => 'boolean',
        'remaining_quantity' => 'decimal:4',
        'unit_cost'          => 'decimal:6',
    ];

    public function product()      { return $this->belongsTo(Product::class); }
    public function warehouse()    { return $this->belongsTo(Warehouse::class); }
    public function consumptions() { return $this->hasMany(CostingLayerConsumption::class); }

    public function scopeActive($q)     { return $q->where('is_fully_consumed', false); }
    public function scopeFifo($q)       { return $q->orderBy('received_at', 'asc'); }
    public function scopeLifo($q)       { return $q->orderBy('received_at', 'desc'); }
    public function scopeFefo($q)       { return $q->orderBy('expiry_date', 'asc'); }
}


// ═════════════════════════════════════════════════════════════════════════════
// Warehouse
// ═════════════════════════════════════════════════════════════════════════════
class Warehouse extends Model
{
    use SoftDeletes, Auditable;
    protected array $auditTags = ['warehouses'];

    protected $fillable = [
        'organization_id', 'name', 'code', 'type',
        'address', 'contact', 'is_active', 'allows_negative_stock',
        'valuation_method', 'stock_rotation', 'sort_order',
    ];

    protected $casts = [
        'address'               => 'array',
        'contact'               => 'array',
        'is_active'             => 'boolean',
        'allows_negative_stock' => 'boolean',
    ];

    public function zones()         { return $this->hasMany(WarehouseZone::class); }
    public function stockPositions(){ return $this->hasMany(StockPosition::class); }
    public function organization()  { return $this->belongsTo(Organization::class); }
    public function scopeActive($q) { return $q->where('is_active', true); }
}


// ═════════════════════════════════════════════════════════════════════════════
// Supplier
// ═════════════════════════════════════════════════════════════════════════════
class Supplier extends Model
{
    use SoftDeletes, Auditable;
    protected array $auditTags = ['suppliers'];

    protected $fillable = [
        'organization_id', 'name', 'code', 'type', 'currency',
        'payment_terms_days', 'payment_method', 'lead_time_days',
        'minimum_order_value', 'tax_id',
        'address', 'contact', 'banking', 'status', 'performance_score', 'custom_fields',
    ];

    protected $casts = [
        'address'      => 'array',
        'contact'      => 'array',
        'banking'      => 'array',
        'custom_fields'=> 'array',
    ];

    public function supplierProducts() { return $this->hasMany(SupplierProduct::class); }
    public function purchaseOrders()   { return $this->hasMany(PurchaseOrder::class); }
    public function scopeActive($q)    { return $q->where('status', 'active'); }
}


// ═════════════════════════════════════════════════════════════════════════════
// PurchaseOrder
// ═════════════════════════════════════════════════════════════════════════════
class PurchaseOrder extends Model
{
    use SoftDeletes, Auditable;
    protected array $auditTags = ['purchase_orders', 'procurement'];

    protected $fillable = [
        'organization_id', 'supplier_id', 'warehouse_id', 'created_by', 'approved_by',
        'po_number', 'status', 'currency', 'exchange_rate',
        'order_date', 'expected_delivery_date', 'approved_at',
        'subtotal', 'discount_amount', 'tax_amount', 'shipping_cost', 'other_charges', 'total_amount',
        'amount_received_value', 'payment_terms', 'notes', 'internal_notes',
        'shipping_address', 'billing_address', 'custom_fields',
    ];

    protected $casts = [
        'order_date'              => 'date',
        'expected_delivery_date'  => 'date',
        'approved_at'             => 'datetime',
        'shipping_address'        => 'array',
        'billing_address'         => 'array',
        'custom_fields'           => 'array',
    ];

    public function supplier()     { return $this->belongsTo(Supplier::class); }
    public function warehouse()    { return $this->belongsTo(Warehouse::class); }
    public function lines()        { return $this->hasMany(PurchaseOrderLine::class); }
    public function receipts()     { return $this->hasMany(GoodsReceipt::class); }
    public function createdBy()    { return $this->belongsTo(\App\Models\User::class, 'created_by'); }
    public function approvedBy()   { return $this->belongsTo(\App\Models\User::class, 'approved_by'); }

    public function scopeDraft($q)     { return $q->where('status', 'draft'); }
    public function scopeOpen($q)      { return $q->whereNotIn('status', ['received', 'cancelled', 'closed']); }
    public function scopeOverdue($q)   { return $q->where('expected_delivery_date', '<', today())->whereIn('status', ['approved', 'sent', 'partially_received']); }
}


// ═════════════════════════════════════════════════════════════════════════════
// SalesOrder
// ═════════════════════════════════════════════════════════════════════════════
class SalesOrder extends Model
{
    use SoftDeletes, Auditable;
    protected array $auditTags = ['sales_orders', 'sales'];

    protected $fillable = [
        'organization_id', 'customer_id', 'warehouse_id', 'price_list_id',
        'created_by', 'sales_rep_id',
        'order_number', 'channel', 'external_order_id', 'status',
        'fulfillment_status', 'payment_status',
        'currency', 'exchange_rate',
        'order_date', 'requested_delivery_date', 'promised_delivery_date', 'actual_delivery_date',
        'subtotal', 'discount_amount', 'tax_amount', 'shipping_amount', 'total_amount', 'amount_paid',
        'allocation_algorithm', 'priority',
        'shipping_address', 'billing_address', 'notes', 'internal_notes', 'custom_fields',
    ];

    protected $casts = [
        'order_date'              => 'date',
        'requested_delivery_date' => 'date',
        'promised_delivery_date'  => 'date',
        'actual_delivery_date'    => 'date',
        'shipping_address'        => 'array',
        'billing_address'         => 'array',
        'custom_fields'           => 'array',
    ];

    public function customer()    { return $this->belongsTo(Customer::class); }
    public function warehouse()   { return $this->belongsTo(Warehouse::class); }
    public function lines()       { return $this->hasMany(SalesOrderLine::class); }
    public function allocations() { return $this->hasMany(StockAllocation::class); }
    public function shipments()   { return $this->hasManyThrough(Shipment::class, ShipmentItem::class, 'sales_order_id', 'id', 'id', 'shipment_id'); }
    public function returns()     { return $this->hasMany(ReturnAuthorization::class); }
    public function createdBy()   { return $this->belongsTo(\App\Models\User::class, 'created_by'); }

    public function getBalanceDueAttribute(): float { return max(0, $this->total_amount - $this->amount_paid); }
    public function getIsFullyPaidAttribute(): bool { return $this->amount_paid >= $this->total_amount; }
    public function getIsOverdueAttribute(): bool   { return $this->payment_status === 'unpaid' && $this->order_date->addDays(30)->isPast(); }

    public function scopeUnfulfilled($q) { return $q->where('fulfillment_status', 'unfulfilled'); }
    public function scopeOpen($q)        { return $q->whereNotIn('status', ['delivered', 'cancelled']); }
    public function scopeByChannel($q, string $channel) { return $q->where('channel', $channel); }
}


// ═════════════════════════════════════════════════════════════════════════════
// StockAlert
// ═════════════════════════════════════════════════════════════════════════════
class StockAlert extends Model
{
    protected $fillable = [
        'organization_id', 'product_id', 'product_variant_id', 'warehouse_id',
        'alert_type', 'current_quantity', 'threshold_quantity', 'expiry_date',
        'status', 'acknowledged_by', 'acknowledged_at', 'resolved_at',
    ];

    protected $casts = [
        'expiry_date'      => 'date',
        'acknowledged_at'  => 'datetime',
        'resolved_at'      => 'datetime',
    ];

    public function product()  { return $this->belongsTo(Product::class); }
    public function warehouse(){ return $this->belongsTo(Warehouse::class); }

    public function acknowledge(): void {
        $this->update(['status' => 'acknowledged', 'acknowledged_by' => auth()->id(), 'acknowledged_at' => now()]);
    }

    public function resolve(): void {
        $this->update(['status' => 'resolved', 'resolved_at' => now()]);
    }

    public function scopeActive($q) { return $q->where('status', 'active'); }
    public function scopeByType($q, string $type) { return $q->where('alert_type', $type); }
}


// ═════════════════════════════════════════════════════════════════════════════
// ProductVariant
// ═════════════════════════════════════════════════════════════════════════════
class ProductVariant extends Model
{
    use SoftDeletes, Auditable;
    protected array $auditTags = ['products', 'variants'];

    protected $fillable = [
        'product_id', 'name', 'sku', 'barcode', 'barcode_type', 'images',
        'cost_price', 'selling_price', 'compare_at_price', 'wholesale_price',
        'weight', 'length', 'width', 'height',
        'valuation_method', 'standard_cost',
        'reorder_point', 'reorder_quantity', 'min_stock_level', 'max_stock_level',
        'track_batches', 'track_serials', 'track_expiry',
        'is_active', 'sort_order', 'custom_fields',
    ];

    protected $casts = [
        'images'       => 'array',
        'custom_fields'=> 'array',
        'is_active'    => 'boolean',
        'track_batches'=> 'boolean',
        'track_serials'=> 'boolean',
        'track_expiry' => 'boolean',
    ];

    public function product()         { return $this->belongsTo(Product::class); }
    public function attributeValues() { return $this->hasMany(VariantAttributeValue::class); }
    public function stockPositions()  { return $this->hasMany(StockPosition::class); }
    public function lots()            { return $this->hasMany(Lot::class); }
    public function batches()         { return $this->hasMany(Batch::class); }

    public function getDisplayNameAttribute(): string {
        return $this->attributeValues->map(fn ($v) => $v->attribute->display_value ?? $v->attribute->value)->join(' / ');
    }
}


// ═════════════════════════════════════════════════════════════════════════════
// ReorderRule
// ═════════════════════════════════════════════════════════════════════════════
class ReorderRule extends Model
{
    use Auditable;
    protected array $auditTags = ['reorder_rules'];

    protected $fillable = [
        'organization_id', 'product_id', 'product_variant_id', 'warehouse_id',
        'preferred_supplier_id',
        'method', 'min_qty', 'max_qty', 'reorder_qty', 'days_of_supply', 'safety_stock',
        'is_active', 'auto_generate_po', 'last_triggered_at',
    ];

    protected $casts = ['is_active' => 'boolean', 'auto_generate_po' => 'boolean', 'last_triggered_at' => 'datetime'];

    public function product()           { return $this->belongsTo(Product::class); }
    public function preferredSupplier() { return $this->belongsTo(Supplier::class, 'preferred_supplier_id'); }
    public function warehouse()         { return $this->belongsTo(Warehouse::class); }
}
