# Sales Module

Manages sales orders, POS transactions, invoicing and payment collection.

## Responsibilities
- Sales order creation with BCMath line total and order total calculations
- POS (Point of Sale) transaction processing with change calculation
- Cash register open/close lifecycle
- Payment recording and tracking
- Invoice number generation per tenant/organisation/month

## API Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | /api/v1/sales | List sales |
| POST | /api/v1/sales | Create sale |
| GET | /api/v1/sales/{id} | Get sale |
| DELETE | /api/v1/sales/{id} | Delete sale |
| POST | /api/v1/pos/transaction | POS transaction |
| GET | /api/v1/pos/registers | List cash registers |
| POST | /api/v1/pos/open-register | Open register |
| POST | /api/v1/pos/close-register | Close register |

## Financial Precision
All monetary calculations use BCMath with scale=4. Floating-point arithmetic is strictly forbidden.
