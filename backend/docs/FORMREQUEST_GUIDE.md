# FormRequest Validation Guide

## Quick Reference

### Product API Endpoints

#### Create Product
```php
POST /api/products
FormRequest: StoreProductRequest
Required: name, sku, type, selling_price
Optional: All other product fields
```

#### Update Product  
```php
PUT/PATCH /api/products/{id}
FormRequest: UpdateProductRequest
Required: None (all fields optional)
Note: SKU uniqueness check excludes current product
```

#### Calculate Price
```php
POST /api/products/{id}/calculate-price
FormRequest: CalculatePriceRequest
Required: quantity
Optional: context object
```

### Inventory API Endpoints

#### Stock IN
```php
POST /api/inventory/stock-in
FormRequest: StockInRequest
Required: product_id, quantity, transaction_type
Transaction Types: purchase, return, adjustment_in, production, transfer_in
```

#### Stock OUT
```php
POST /api/inventory/stock-out
FormRequest: StockOutRequest
Required: product_id, quantity, transaction_type
Transaction Types: sale, return_outbound, adjustment_out, consumption, transfer_out
Business Rule: Stock availability check
```

#### Stock Adjustment
```php
POST /api/inventory/stock-adjustment
FormRequest: StockAdjustmentRequest
Required: product_id, target_balance, notes
```

#### Stock Transfer
```php
POST /api/inventory/stock-transfer
FormRequest: StockTransferRequest
Required: product_id, quantity, from_location_id, to_location_id
Business Rules: Stock availability, location validation
```

## Best Practices

1. Always type-hint FormRequest in controller methods
2. Provide custom error messages
3. Implement authorization in `authorize()` method
4. Write tests for FormRequests
5. Document complex validation rules

## Resources

- Laravel Validation: https://laravel.com/docs/11.x/validation
- Form Requests: https://laravel.com/docs/11.x/validation#form-request-validation
