# Taxation Module Implementation

## Overview

The Taxation Module is a comprehensive, enterprise-grade tax management system for AutoERP. It handles advanced tax scenarios including compound taxes, multi-jurisdiction taxation, tax exemptions, reverse charges, and provides detailed audit trails of all tax calculations.

## Architecture

The module follows Clean Architecture principles with strict separation of concerns:

```
Controller → Service → Repository → Model
```

### Key Components

1. **Models**: Domain entities with business logic
   - `TaxGroup` - Groups of tax rates (GST+PST, VAT+Sales Tax)
   - `TaxGroupRate` - Pivot table for tax rates in groups
   - `TaxExemption` - Customer/product tax exemptions
   - `TaxJurisdiction` - Location-based tax rules
   - `TaxCalculation` - Audit log of tax calculations

2. **Repositories**: Data access layer
   - `TaxGroupRepository`
   - `TaxExemptionRepository`
   - `TaxJurisdictionRepository`
   - `TaxCalculationRepository`

3. **Services**: Business logic layer
   - `TaxationService` - Core tax calculation engine
   - `TaxGroupService` - Tax group management
   - `TaxExemptionService` - Exemption management
   - `TaxJurisdictionService` - Jurisdiction management

4. **Controllers**: HTTP request handlers
   - `TaxGroupController`
   - `TaxExemptionController`
   - `TaxJurisdictionController`
   - `TaxCalculationController`

5. **DTOs**: Type-safe data transfer
   - `TaxCalculationRequestDTO`
   - `TaxCalculationResultDTO`

## Features

### 1. Tax Groups (Compound Taxes)

Tax groups allow multiple tax rates to be combined:

- **Application Types**:
  - `compound`: Tax on tax (GST on subtotal + PST on GST+subtotal)
  - `stacked`: Sequential taxes (GST + PST on subtotal)
  - `highest`: Apply only the highest rate
  - `average`: Apply average of all rates

- **Configuration**:
  - Sequence ordering for tax rates
  - Apply on previous flag for compound calculations
  - Effective date ranges
  - Active/inactive status

### 2. Tax Exemptions

Support for entity-specific tax exemptions:

- **Entity Types**: customer, product, product_category, vendor
- **Exemption Types**: 
  - Full exemption (100% tax waiver)
  - Partial exemption (percentage-based reduction)
- **Certificate tracking**
- **Validity periods**

### 3. Tax Jurisdictions

Location-based tax determination:

- **Jurisdiction Levels**:
  - Country-level
  - State/province-level
  - City-level
  - Postal code-level
  - Custom jurisdictions

- **Features**:
  - Priority-based matching
  - Reverse charge mechanism support
  - Custom tax rules per jurisdiction

### 4. Tax Calculations

Comprehensive tax calculation engine:

- **Calculation Methods**:
  - Exclusive tax (tax added to base amount)
  - Inclusive tax (tax extracted from total amount)
  
- **Features**:
  - Compound tax calculations
  - Exemption application
  - Jurisdiction-based tax determination
  - Rounding rules (4 decimal precision)
  - Detailed breakdown per tax rate
  - Audit trail of all calculations

## API Endpoints

### Tax Groups

```
GET    /api/v1/taxation/tax-groups              - List all tax groups
POST   /api/v1/taxation/tax-groups              - Create tax group
GET    /api/v1/taxation/tax-groups/active       - Get active tax groups
GET    /api/v1/taxation/tax-groups/{id}         - Get tax group details
PUT    /api/v1/taxation/tax-groups/{id}         - Update tax group
DELETE /api/v1/taxation/tax-groups/{id}         - Delete tax group
POST   /api/v1/taxation/tax-groups/{id}/attach-tax-rate     - Attach tax rate
DELETE /api/v1/taxation/tax-groups/{id}/detach-tax-rate/{taxRateId} - Detach tax rate
```

### Tax Exemptions

```
GET    /api/v1/taxation/tax-exemptions          - List all exemptions
POST   /api/v1/taxation/tax-exemptions          - Create exemption
GET    /api/v1/taxation/tax-exemptions/active   - Get active exemptions
GET    /api/v1/taxation/tax-exemptions/by-entity - Get by entity
GET    /api/v1/taxation/tax-exemptions/{id}     - Get exemption details
PUT    /api/v1/taxation/tax-exemptions/{id}     - Update exemption
DELETE /api/v1/taxation/tax-exemptions/{id}     - Delete exemption
```

### Tax Jurisdictions

```
GET    /api/v1/taxation/tax-jurisdictions       - List all jurisdictions
POST   /api/v1/taxation/tax-jurisdictions       - Create jurisdiction
GET    /api/v1/taxation/tax-jurisdictions/active - Get active jurisdictions
GET    /api/v1/taxation/tax-jurisdictions/find-by-location - Find by location
GET    /api/v1/taxation/tax-jurisdictions/{id}  - Get jurisdiction details
PUT    /api/v1/taxation/tax-jurisdictions/{id}  - Update jurisdiction
DELETE /api/v1/taxation/tax-jurisdictions/{id}  - Delete jurisdiction
```

### Tax Calculations

```
POST   /api/v1/taxation/calculations/calculate             - Calculate tax
POST   /api/v1/taxation/calculations/calculate-and-save    - Calculate and save
GET    /api/v1/taxation/calculations/history               - Calculation history
GET    /api/v1/taxation/calculations/summary               - Tax summary
GET    /api/v1/taxation/calculations/breakdown             - Tax breakdown
```

## Usage Examples

### 1. Create a Tax Group (GST + PST)

```json
POST /api/v1/taxation/tax-groups
{
  "name": "Canadian GST + PST",
  "code": "CA_GST_PST",
  "description": "5% GST + 7% PST (compound)",
  "application_type": "compound",
  "is_inclusive": false,
  "is_active": true,
  "tax_rates": [
    {
      "tax_rate_id": 1,
      "sequence": 1,
      "apply_on_previous": false
    },
    {
      "tax_rate_id": 2,
      "sequence": 2,
      "apply_on_previous": true
    }
  ]
}
```

### 2. Create a Tax Exemption

```json
POST /api/v1/taxation/tax-exemptions
{
  "name": "Non-Profit Organization Exemption",
  "exemption_number": "NPO-2024-001",
  "entity_type": "customer",
  "entity_id": 123,
  "tax_group_id": 1,
  "exemption_type": "full",
  "reason": "Registered non-profit organization",
  "certificate_number": "NPO-CERT-123456",
  "valid_from": "2024-01-01",
  "valid_to": "2024-12-31",
  "is_active": true
}
```

### 3. Create a Tax Jurisdiction

```json
POST /api/v1/taxation/tax-jurisdictions
{
  "name": "British Columbia",
  "code": "CA_BC",
  "jurisdiction_type": "state",
  "country_code": "CA",
  "state_code": "BC",
  "tax_group_id": 1,
  "priority": 10,
  "is_reverse_charge": false,
  "is_active": true
}
```

### 4. Calculate Tax (Exclusive)

```json
POST /api/v1/taxation/calculations/calculate
{
  "amount": 1000.00,
  "customer_id": 123,
  "product_id": 456,
  "tax_group_id": 1,
  "is_inclusive": false,
  "country_code": "CA",
  "state_code": "BC"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Tax calculated successfully",
  "data": {
    "base_amount": 1000.00,
    "tax_amount": 123.50,
    "total_amount": 1123.50,
    "is_inclusive": false,
    "tax_breakdown": [
      {
        "tax_rate_id": 1,
        "tax_name": "GST",
        "tax_rate": 5.00,
        "base_amount": 1000.00,
        "calculated_tax": 50.00,
        "exempted_amount": 0.00,
        "final_tax": 50.00,
        "is_compound": false
      },
      {
        "tax_rate_id": 2,
        "tax_name": "PST",
        "tax_rate": 7.00,
        "base_amount": 1050.00,
        "calculated_tax": 73.50,
        "exempted_amount": 0.00,
        "final_tax": 73.50,
        "is_compound": true
      }
    ],
    "applied_taxes": [
      {"tax_rate_id": 1, "tax_name": "GST", "rate": 5.00, "amount": 50.00},
      {"tax_rate_id": 2, "tax_name": "PST", "rate": 7.00, "amount": 73.50}
    ],
    "exemptions_applied": [],
    "jurisdiction_id": 1,
    "calculation_method": "exclusive"
  }
}
```

### 5. Calculate Tax (Inclusive)

```json
POST /api/v1/taxation/calculations/calculate
{
  "amount": 1123.50,
  "tax_group_id": 1,
  "is_inclusive": true
}
```

**Response:**
```json
{
  "success": true,
  "message": "Tax calculated successfully",
  "data": {
    "base_amount": 1000.00,
    "tax_amount": 123.50,
    "total_amount": 1123.50,
    "is_inclusive": true,
    "tax_breakdown": [...],
    "calculation_method": "inclusive"
  }
}
```

## Database Schema

### tax_groups
```sql
- id, uuid, tenant_id
- name, code, description
- application_type (compound/stacked/highest/average)
- is_inclusive, is_active
- effective_from, effective_to
- metadata, timestamps
```

### tax_group_rates
```sql
- id, tax_group_id, tax_rate_id
- sequence, apply_on_previous, is_active
- timestamps
```

### tax_exemptions
```sql
- id, uuid, tenant_id
- name, exemption_number
- entity_type, entity_id
- tax_rate_id, tax_group_id
- exemption_type, exemption_rate
- reason, certificate_number
- valid_from, valid_to, is_active
- metadata, timestamps
```

### tax_jurisdictions
```sql
- id, uuid, tenant_id
- name, code, jurisdiction_type
- country_code, state_code, city_name, postal_code
- tax_rate_id, tax_group_id
- priority, is_reverse_charge, is_active
- rules, metadata, timestamps
```

### tax_calculations
```sql
- id, uuid, tenant_id
- entity_type, entity_id
- base_amount, tax_amount, total_amount
- is_inclusive, tax_breakdown, applied_taxes
- exemptions_applied
- customer_id, product_id, branch_id
- tax_jurisdiction_id, calculation_method
- metadata, calculated_at, timestamps
```

## Multi-Tenancy

All models use the `TenantScoped` trait to ensure complete tenant isolation:
- Automatic tenant_id assignment on create
- Global scope filters all queries by tenant
- Prevents cross-tenant data access

## Audit Trail

All calculations are logged in the `tax_calculations` table with:
- Full breakdown of applied taxes
- Exemptions applied
- Jurisdiction information
- Calculation timestamp
- User context (via created_by)

## Security

- All endpoints require authentication (`auth:sanctum`)
- Tenant context middleware ensures tenant isolation
- Input validation via FormRequests
- DB transactions for data consistency
- SQL injection prevention via Eloquent ORM

## Integration with Other Modules

The Taxation module integrates seamlessly with:

1. **Invoicing**: Calculate taxes on invoices
2. **POS**: Real-time tax calculation at point of sale
3. **Procurement**: Purchase tax calculations
4. **Payments**: Tax handling in payment processing
5. **Reporting**: Tax reports and summaries

### Example Integration (Invoice)

```php
use App\Core\DTOs\Taxation\TaxCalculationRequestDTO;
use App\Modules\Taxation\Services\TaxationService;

$taxationService = app(TaxationService::class);

$requestDTO = TaxCalculationRequestDTO::fromArray([
    'amount' => $invoice->subtotal,
    'customer_id' => $invoice->customer_id,
    'branch_id' => $invoice->branch_id,
    'tax_group_id' => $invoice->tax_group_id,
    'is_inclusive' => false,
    'country_code' => $customer->country_code,
    'state_code' => $customer->state_code,
]);

$result = $taxationService->calculateTax($requestDTO);

// Save calculation
$taxationService->saveTaxCalculation(
    'invoice',
    $invoice->id,
    $result,
    $invoice->customer_id
);

// Update invoice
$invoice->tax_amount = $result->taxAmount;
$invoice->total_amount = $result->totalAmount;
$invoice->save();
```

## Testing

Run tests for the taxation module:

```bash
php artisan test --filter=Taxation
```

## Performance Considerations

- Use eager loading for relationships: `with(['taxRates', 'taxGroup'])`
- Cache frequently accessed tax configurations
- Index foreign keys and commonly queried columns
- Use DB transactions for complex operations
- Pagination for large result sets

## Future Enhancements

1. Tax rate history tracking
2. Automated tax rate updates via API
3. Multi-currency tax calculations
4. Tax filing reports
5. Integration with external tax services
6. Digital tax certificates
7. Automated exemption validation

## Support

For issues or questions, please contact the development team or file an issue in the project repository.

## License

Proprietary - AutoERP Platform
