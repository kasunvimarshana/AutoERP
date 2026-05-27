# Universal Modular Business Management Platform – Business Context & Requirements

## 1. Purpose of This Document

This document explains the business need, product vision, and functional behavior of a modular business management application. It is written so any AI agent, developer, business analyst, or architect can understand what the application is supposed to do without needing to inspect migrations, source code, or technologies.

This document focuses on business behavior only:

- What the application does
- What business problems it solves
- How the main modules should behave
- How invoices, inventory, finance, items, purchase, sales, vehicle service, and vehicle rental connect
- Why the system must be flexible, modular, and plug-and-play

---

## 2. High-Level Product Vision

The application is intended to be a flexible modular business management platform. It should not be limited to one fixed business type. Instead, it should allow different businesses to enable the modules they need and ignore or remove the modules they do not need.

The system should behave like a reusable business operating platform where modules can be plugged in, removed, customized, or extended.

Example businesses may use different module combinations:

- A simple shop may use only Sales, Inventory, Invoice, and Payment.
- A trading business may use Purchase, Sales, Inventory, Invoice, Finance, and Payments.
- A vehicle service center may use Customer Vehicles, Service Jobs, Labour, Spare Parts, Invoice, Inventory, and Payments.
- A vehicle rental business may use Vehicles, Agreements, Running Charts, Drivers, Rental Invoices, Finance, and Payments.

The main goal is to create a strong shared core with independent business modules around it.

---

## 3. Core Product Philosophy

### 3.1 Modular Architecture From a Business View

Each major business function should be treated as a module.

Current important modules are:

- Invoice
- Inventory
- Finance
- Item
- Purchase
- Sales
- Vehicle Service
- Vehicle Rental

The platform may also include or later add:

- Users
- Roles and permissions
- Configuration
- Audit
- Payments
- Customers
- Suppliers
- Extensions
- Other future business modules

Each module should be isolated enough that it can be added, removed, or changed without breaking the whole application.

### 3.2 Plug-and-Play Business Modules

The system should support a plug-and-play concept. A business should be able to choose what it needs.

For example:

- A company that does not rent vehicles should not be forced to use Vehicle Rental.
- A company that does not do vehicle service should not be forced to use Vehicle Service.
- A company that only sells services should still be able to use Invoice and Finance without full Inventory.

Future modules should be able to connect to the same core foundation.

### 3.3 Shared Core With Module-Specific Logic

Some modules are core and shared by all businesses:

- Invoice
- Inventory
- Finance
- Item
- Payment
- User and permission management
- Configuration
- Audit

However, each business module can have its own rules.

For example:

- A sales invoice is different from a vehicle service invoice.
- A purchase invoice is different from a rental invoice.
- Vehicle rental invoicing depends on agreements and running charts.
- Vehicle service invoicing depends on labour, spare parts, non-inventory items, external services, and employee assignments.

The system should not force all modules to use one rigid flow.

---

## 4. Overall Application Purpose

The application should help a business manage its daily operations from end to end.

It should support:

- Buying goods and services
- Selling goods and services
- Managing inventory and stock movement
- Creating invoices from different business modules
- Receiving customer payments
- Paying suppliers and external providers
- Tracking income and expenses
- Mapping transactions to chart of accounts
- Managing service jobs
- Managing vehicle rental agreements
- Tracking vehicle usage through running charts
- Handling customer-supplied items
- Handling external services
- Assigning employees to labour work
- Supporting different workflows for different businesses

The product should act as a flexible ERP-like platform for many businesses, not only one specific company.

---

# 5. Invoice Module

## 5.1 Purpose

Invoice is one of the heart modules of the application. It should not belong only to Sales. It should be reusable by many modules.

Invoices can be generated from:

- Sales
- Purchase
- Vehicle Service
- Vehicle Rental
- Future custom business modules

The invoice module should store the final billing result while allowing each source module to calculate its own business-specific details.

## 5.2 Why Invoice Must Be Flexible

Different modules create invoices in different ways.

### Sales Invoice

A sales invoice may include:

- Inventory products
- Services
- Discounts
- Taxes
- Customer payment
- Stock reduction

### Purchase Invoice

A purchase invoice may include:

- Supplier bill details
- Items from purchase order
- Items from GRN
- Supplier payable
- Inventory cost
- Taxes and charges

### Vehicle Service Invoice

A vehicle service invoice may include:

- Spare parts
- Labour items
- Service items
- Combo service packages
- Customer-supplied items
- Non-inventory items
- External services
- Employee labour assignments
- Job card references

### Vehicle Rental Invoice

A vehicle rental invoice may include:

- Rental agreement charges
- Running chart charges
- Kilometer-based charges
- Hour-based charges
- Daily/monthly rental charges
- Driver charges
- Overtime
- Night shift
- Weekend/double-rate charges
- Replacement vehicle charges
- Fuel, toll, parking, or other expenses
- Third-party provider-related charges

Because of this, invoice must be a shared core, but the details and calculations must be allowed to vary by source module.

## 5.3 Invoice Source Tracking

Every invoice should identify where it came from.

Possible sources:

- Sales order
- Sales delivery
- Direct sales invoice
- Purchase order
- Goods received note
- Supplier invoice
- Vehicle service job card
- Vehicle rental agreement
- Rental running chart
- Manual invoice
- Future module source

This traceability is important because users should be able to go from invoice back to the original business activity.

## 5.4 Invoice Line Flexibility

Invoice lines should support many types of lines:

- Inventory product
- Service item
- Labour item
- Combo item
- Non-inventory item
- Customer-supplied item
- External service
- Rental charge
- Kilometer charge
- Hour charge
- Day charge
- Overtime charge
- Night shift charge
- Driver charge
- Discount
- Tax
- Adjustment

A single fixed invoice item format is not enough for this application.

## 5.5 Calculation Responsibility

The invoice module should not try to understand every module’s full business logic.

Recommended business idea:

- Source module calculates business-specific charges.
- Invoice module stores invoice header, lines, totals, taxes, discounts, payments, and references.
- Invoice module preserves enough details to audit and print the invoice.

Examples:

- Vehicle Rental calculates running chart charges, then sends invoice lines to Invoice.
- Vehicle Service calculates labour, spare parts, and external services, then sends invoice lines to Invoice.
- Sales calculates item totals and stock impact.
- Purchase calculates supplier payable and stock value.

---

# 6. Inventory Module

## 6.1 Purpose

Inventory manages stock movement and availability. It should be reusable across Purchase, Sales, Vehicle Service, and future modules.

Inventory should track:

- Stock in
- Stock out
- Adjustments
- Returns
- Transfers
- Damage
- Usage in service jobs
- Sales dispatch
- Purchase receiving

## 6.2 Inventory in Purchase

Purchase may increase inventory. Different businesses update stock at different stages.

Possible purchase flows:

1. Purchase Request → Purchase Order → GRN → Supplier Invoice → Payment
2. Purchase Order → GRN → Supplier Invoice → Payment
3. GRN → Supplier Invoice → Payment
4. Direct Supplier Invoice → Payment

The system should allow businesses to choose which steps they use.

## 6.3 Inventory in Sales

Sales may reduce inventory.

Stock may be reduced:

- On sales order confirmation
- On delivery note
- On invoice
- On manual dispatch

The system should support configurable stock deduction behavior.

## 6.4 Inventory in Vehicle Service

Vehicle service jobs may consume spare parts from inventory.

Examples:

- Engine oil
- Filters
- Brake pads
- Bulbs
- Cleaning products
- Accessories

When these are used in a service job, stock should reduce according to service rules.

## 6.5 Non-Inventory and Customer-Supplied Items

The system must distinguish inventory items from non-inventory items.

Examples of non-inventory cases:

- Customer brings a part and asks the service center to install it.
- Business buys a one-time item externally for a job.
- Business uses an external service provider.
- Business adds only a handling charge or commission.

These should be invoiceable but should not incorrectly affect stock.

## 6.6 Inventory Traceability

For every stock change, the system should be able to answer:

- Why did stock change?
- Which module caused it?
- Which invoice/job/order caused it?
- Who performed the action?
- When did it happen?

---

# 7. Finance Module

## 7.1 Purpose

Finance connects business activity to accounting impact.

Each module can create:

- Income
- Expense
- Receivable
- Payable
- Tax
- Discount
- Inventory value movement
- Cash/bank movement
- Supplier/customer settlement

## 7.2 Chart of Accounts Mapping

The system should allow configurable chart of accounts mapping per module and transaction type.

Examples:

### Sales

- Sales income account
- Sales discount account
- Sales tax account
- Accounts receivable
- Cash/bank account
- Cost of goods sold
- Inventory asset account

### Purchase

- Purchase expense account
- Inventory asset account
- Supplier payable account
- Purchase tax account
- Freight/landing cost account
- Cash/bank payment account

### Vehicle Service

- Service income account
- Labour income account
- Spare parts income account
- External service expense account
- Employee incentive expense account
- Inventory consumption account
- Customer receivable account

### Vehicle Rental

- Rental income account
- Driver charge income account
- Overtime income account
- External vehicle provider payable account
- Fuel/toll/extra charge accounts
- Damage/recovery account
- Customer receivable account

## 7.3 Defaults and Overrides

Each module should have default account mappings, but users should be able to override them when needed.

This is important because different businesses categorize income, expenses, tax, and payments differently.

## 7.4 Payments

Payments should support:

- Customer receipts
- Supplier payments
- Partial payments
- Advance payments
- Refunds
- Credit notes
- Debit notes
- Cash
- Bank
- Card
- Cheque
- Online transfer

Payments should connect to invoices, suppliers, customers, and finance postings.

---

# 8. Item Module

## 8.1 Purpose

Item is a core module because all business modules use items in different ways.

Items are not only physical products. The system should support many item types.

## 8.2 Item Types

Possible item types:

- Product
- Service
- Labour item
- Combo item
- Non-inventory item
- Variable item
- Rental charge item
- Fee item
- Discount item
- Adjustment item

## 8.3 Product Items

Product items are physical goods that may affect inventory.

Examples:

- Spare parts
- Oil
- Filters
- Accessories
- Retail goods

## 8.4 Service Items

Service items represent work or service sold to a customer.

Examples:

- Body wash
- Full service
- Inspection
- Repair
- Cleaning
- Detailing

## 8.5 Labour Items

Labour items represent work performed by employees.

Examples:

- Wash labour
- Vacuum labour
- Engine check labour
- Repair labour
- Detailing labour

Labour items are important in Vehicle Service because employees may be assigned to each labour task.

## 8.6 Combo Items

A combo item groups multiple items together.

Example: Full Service Package may include:

- Body wash
- Vacuum
- Engine check
- Interior cleaning
- Oil change labour

When a combo is selected, the system should be able to expand the internal items so labour assignment and costing can happen correctly.

## 8.7 Variable Items

Some items are custom or context-based.

Examples:

- One-time external service
- Custom labour
- Special rental charge
- Extra kilometer charge
- Miscellaneous fee

The system should support these without losing traceability.

---

# 9. Purchase Module

## 9.1 Purpose

Purchase manages buying goods and services from suppliers.

It should support both detailed and simple purchasing workflows.

## 9.2 Purchase Flow Variations

Different businesses use different purchase flows.

### Detailed Flow

1. Purchase request
2. Purchase order
3. Goods received note
4. Supplier invoice
5. Payment

### Medium Flow

1. Purchase order
2. Goods received note
3. Supplier invoice
4. Payment

### Simple Flow

1. Goods received note
2. Supplier invoice
3. Payment

### Direct Flow

1. Supplier invoice
2. Payment

The system should not force all businesses to use every step.

## 9.3 Purchase Request

A purchase request is an internal request to buy items. It may require approval.

Not every business needs purchase requests.

## 9.4 Purchase Order

A purchase order is a formal document sent to a supplier.

It may include:

- Supplier
- Items
- Quantities
- Prices
- Expected delivery date
- Terms
- Approval status

## 9.5 Goods Received Note

GRN records goods actually received.

It may affect inventory and may differ from the original purchase order.

## 9.6 Supplier Invoice

Supplier invoice records the amount payable to the supplier.

It can come from:

- Purchase order
- GRN
- Direct purchase
- External service
- One-time expense

## 9.7 Purchase Payment

The system should track supplier payments, including full, partial, and advance payments.

---

# 10. Sales Module

## 10.1 Purpose

Sales manages selling goods and services to customers.

It should support different business sales flows.

## 10.2 Sales Flow Variations

### Simple Sales

1. Select customer
2. Add items
3. Create invoice
4. Receive payment
5. Reduce stock

### Delivery-Based Sales

1. Sales order
2. Delivery note
3. Invoice
4. Payment

### Quotation-Based Sales

1. Quotation
2. Sales order
3. Delivery
4. Invoice
5. Payment

The system should allow different businesses to use different levels of complexity.

## 10.3 Sales Items

Sales may include:

- Inventory products
- Services
- Non-inventory items
- Combo items
- Discounts
- Taxes
- Charges

## 10.4 Sales and Inventory

Sales may reduce stock, but the exact point should be configurable.

Examples:

- Deduct on invoice
- Deduct on delivery
- Deduct on order confirmation

## 10.5 Sales and Finance

Sales should generate proper finance impact:

- Sales income
- Tax payable
- Customer receivable
- Payment receipt
- Inventory reduction
- Cost of goods sold

---

# 11. Vehicle Service Module

## 11.1 Purpose

Vehicle Service is designed for a vehicle service center.

It should manage:

- Customers
- Vehicles
- Service jobs
- Supervisors
- Job types
- Labour items
- Spare parts
- Non-inventory items
- External services
- Employee assignments
- Service invoices
- Payments
- Service history

## 11.2 Service Job Creation

When a vehicle arrives, the user should create a service job.

A service job should include:

- Customer
- Vehicle
- Supervisor
- Job type
- Date/time
- Notes
- Status
- Items/services required

## 11.3 Customer and Vehicle

The job should be linked to a customer and vehicle.

If the customer or vehicle does not exist, the system should allow adding them.

Vehicle service history must be traceable.

## 11.4 Supervisor

Each job can have a supervisor or responsible person.

The supervisor oversees the job.

## 11.5 Job Types

Job types should be dynamic and configurable.

Examples:

- Full service
- Body wash
- Vacuum
- Repair
- Maintenance
- Inspection
- Detailing

## 11.6 Service Job Items

A job can include:

- Inventory spare parts
- Labour items
- Service items
- Combo items
- Non-inventory items
- External service items
- Customer-supplied items

## 11.7 Labour Assignment

Labour items should be assignable to employees.

One labour item can be assigned to:

- One employee
- Two employees
- Multiple employees

The job creator should decide the assignment.

## 11.8 Labour Incentive / Sharing

The system should support splitting labour value or incentive between employees.

Examples:

- Employee A receives 50%, Employee B receives 50%
- Employee A receives a fixed amount
- Multiple employees share by custom percentages

Different service centers may pay employees differently, so this must be flexible.

## 11.9 Combo Service Handling

A combo item may include multiple labour/service items.

Example combo:

- Body wash
- Vacuum
- Interior clean
- Exterior polish

When selected, the combo should expand so employees can be assigned to the actual internal labour tasks.

## 11.10 Inventory Spare Parts

Service jobs may use spare parts from inventory.

These should reduce stock according to rules.

## 11.11 Customer-Supplied Items

Sometimes the customer brings a part from outside.

The system should record it, but it should not reduce company inventory.

The business may still charge labour or fitting charges.

## 11.12 External Services

Sometimes the service center outsources part of the job.

Examples:

- External repair
- External painting
- External scanning
- External fitting

The system should record cost and decide whether to:

- Charge the same amount to customer
- Add margin/commission
- Treat it as expense
- Absorb it

## 11.13 Vehicle Service Invoice

The service invoice may include:

- Labour charges
- Spare parts
- Services
- Combo services
- Non-inventory items
- External services
- Discounts
- Taxes
- Payments

The service module should prepare invoice details and send them to the invoice module.

## 11.14 Service History

The system should keep vehicle service history:

- What work was done
- Which parts were used
- Which employees worked
- Which supervisor handled it
- What was charged
- When the vehicle came
- What problems were reported

---

# 12. Vehicle Rental Module

## 12.1 Purpose

Vehicle Rental manages renting vehicles to customers.

It should support:

- Company-owned vehicles
- Vehicles received from external providers
- Vehicles rented to customers
- Agreements
- With-driver rentals
- Without-driver rentals
- Running charts
- Usage tracking
- Replacement vehicles
- Customer invoicing
- Provider payments

## 12.2 Vehicle Source

Vehicles may be:

- Owned by the company
- Rented from another owner/provider
- Temporary replacement vehicles
- External vehicles used for customer rental

The system must track the vehicle source because payment and profit calculation may differ.

## 12.3 Rental Agreement

A rental agreement defines rental conditions.

It may include:

- Customer
- Vehicle
- Start date
- End date
- Rate type
- With driver / without driver
- Kilometer limit
- Hour limit
- Daily rate
- Monthly rate
- Yearly rate
- Extra kilometer rate
- Extra hour rate
- Overtime rules
- Night shift rules
- Weekend rules
- Driver charges
- Fuel/toll rules
- Damage rules
- Replacement rules
- Payment terms

## 12.4 Rental Period Types

Rental can be charged by:

- Hour
- Day
- Week
- Month
- Year
- Fixed period
- Kilometer usage
- Combination of time and kilometer usage

The system should not assume only one rental method.

## 12.5 With Driver / Without Driver

If rental is with driver, the system may track:

- Driver assigned
- Driver hours
- Overtime
- Night shift
- Double rate
- Allowances
- Driver charges

If without driver, these may not apply.

## 12.6 Running Chart

Running chart is a key part of rental billing.

It records actual vehicle usage.

A running chart may include:

- Date
- Agreement
- Vehicle
- Driver
- Start kilometer
- End kilometer
- Total kilometers
- Start time
- End time
- Total hours
- Route/trip details
- Day out
- Night out
- Overtime
- Weekend work
- Extra kilometers
- Extra hours
- Fuel
- Toll
- Parking
- Other expenses
- Replacement vehicle usage
- Remarks

Final invoice calculation may depend on these records.

## 12.7 Kilometer-Based Charging

Some agreements include allowed kilometers.

Example:

- 100 km per day included
- Extra kilometers charged separately

The system should calculate:

- Allowed kilometers
- Actual kilometers
- Extra kilometers
- Extra kilometer amount

## 12.8 Hour-Based Charging

Some agreements include allowed hours.

Example:

- 8 hours per day included
- Extra hours charged as overtime

The system should calculate:

- Allowed hours
- Actual hours
- Extra hours
- Overtime amount

## 12.9 Daily, Monthly, Yearly, and Period Charges

The system should support:

- Daily rent
- Monthly rent
- Yearly rent
- Custom period rent
- Pro-rata charge
- Extra day charge

## 12.10 Overtime, Night Shift, Weekend, and Double Rate

Rental rules can be complex.

Examples:

- After 8 hours, apply overtime.
- Saturday may have special rules.
- Certain hours may be double rate.
- Night shift may add additional charge.
- Day-out or outstation trip may add charge.
- Holidays may have different rates.

Rules should be configurable.

## 12.11 Replacement Vehicle

If a vehicle breaks down during an agreement, the business must be able to assign a replacement vehicle.

The system should track:

- Original vehicle
- Breakdown reason
- Replacement vehicle
- Replacement start date/time
- Replacement end date/time
- Running chart for replacement vehicle
- Customer invoice impact
- External provider payment impact
- Vehicle availability impact

At final invoice time, customer billing should include the correct usage across original and replacement vehicles.

## 12.12 External Provider Payments

Sometimes the company rents a vehicle from another provider and rents it to the customer.

The system must support two financial sides:

1. Customer billing
2. Provider payable

The business may keep a margin or commission.

## 12.13 Rental Invoice

Rental invoices may include:

- Base rent
- Kilometer charges
- Extra kilometer charges
- Hour charges
- Extra hour charges
- Driver charges
- Overtime
- Night shift
- Double rate
- Fuel
- Toll
- Parking
- Outstation charges
- Replacement vehicle charges
- Damages
- Discounts
- Taxes

The rental module should calculate these and pass invoice lines to the invoice module.

---

# 13. Cross-Module Behavior

## 13.1 Same Core, Different Behavior

The same core features are reused but behave differently by module.

Invoice:

- Sales invoice creates income and may reduce stock.
- Purchase invoice creates payable and may increase stock.
- Service invoice includes labour, spare parts, and job details.
- Rental invoice depends on agreement and running chart.

Inventory:

- Purchase increases stock.
- Sales reduces stock.
- Vehicle service consumes stock.
- Non-inventory/customer-supplied items do not affect stock.

Finance:

- Each module posts to different accounts.
- Each module may have different income, expense, receivable, and payable rules.

## 13.2 Workflow Flexibility

The system should support simple and complex workflows.

No business should be forced into a workflow it does not use.

Examples:

- Some businesses use PO, GRN, invoice, payment.
- Some skip PO.
- Some skip GRN.
- Some directly invoice.
- Some sales workflows use delivery notes.
- Some service workflows need job cards.
- Some rental workflows need running charts.

---

# 14. Configuration Needs

The system should allow configuration for:

- Enabled modules
- Invoice formats
- Number sequences
- Approval workflows
- Inventory deduction points
- Finance account mappings
- Tax rules
- Discount rules
- Payment methods
- Rental rate rules
- Service job types
- Labour assignment rules
- Employee incentive rules
- Item types
- Units of measure
- Customer/supplier categories

Configuration is necessary because each business operates differently.

---

# 15. Users, Roles, and Permissions

The system should support module-aware permissions.

Example roles:

- Admin
- Manager
- Accountant
- Sales user
- Purchase user
- Service supervisor
- Rental manager
- Cashier
- Inventory staff

Examples:

- Sales user may not access purchase costs.
- Service supervisor may manage jobs but not finance settings.
- Accountant may access invoices, payments, and accounts.
- Rental manager may manage agreements and running charts.

---

# 16. Audit and Traceability

The system should track important business actions.

Examples:

- Who created an invoice
- Who updated a service job
- Who changed a rental agreement
- Who approved a purchase order
- Who assigned a replacement vehicle
- Who changed payment details
- Who changed account mappings

Audit is important for accountability.

---

# 17. Key Business Problems Solved

This application solves these problems:

1. Different businesses have different workflows.
2. One fixed ERP process does not fit every company.
3. Invoice behavior differs by module.
4. Inventory behavior differs by transaction type.
5. Service businesses need labour, spare parts, external services, and non-inventory handling.
6. Vehicle rental needs agreement, running chart, overtime, kilometer/hour calculations, and replacement vehicle tracking.
7. Finance mappings must be flexible.
8. Modules should be plug-and-play.
9. Future modules should be easy to add.
10. Business actions must be traceable.

---

# 18. Recommended Product Direction

The best product direction is to treat this as a modular ERP/business operating platform.

The platform should have:

- Strong shared core modules
- Flexible module-specific workflows
- Configurable invoice behavior
- Configurable finance mappings
- Module-specific calculation engines
- Clear source references
- Strong audit trails
- Flexible item model
- Inventory movement traceability
- Vehicle service labour assignment engine
- Vehicle rental running chart engine

The system should avoid assuming that all businesses follow the same process.

---

# 19. AI Agent Guidance

Any AI agent helping with this application should remember:

1. Invoice is not only a Sales feature.
2. Invoice lines must be flexible by module.
3. Purchase workflows must be optional and configurable.
4. Sales stock reduction timing must be configurable.
5. Vehicle Service is not simple sales; it has job cards, labour, employees, spare parts, and external services.
6. Vehicle Rental is not simple billing; it depends on agreements and running charts.
7. Rental must support kilometers, hours, days, months, overtime, night shift, double rate, and replacement vehicles.
8. External provider payments must be separate from customer invoices.
9. Labour items may need employee assignment and incentive splitting.
10. Combo items should expand into internal labour/service items.
11. Non-inventory and customer-supplied items must not affect stock.
12. Finance mappings must be configurable per module.
13. Modules should be isolated enough to be added, removed, or split later.
14. Future business modules should be possible without redesigning the whole application.

---

# 20. Final Summary

This application is a flexible modular business management platform. It is designed to support many business types through a shared core and plug-and-play business modules.

Current main business modules:

- Purchase
- Sales
- Vehicle Service
- Vehicle Rental

Current main core modules:

- Invoice
- Inventory
- Finance
- Item
- Payment
- Users
- Configuration
- Audit

The most important requirement is flexibility.

Each business module should have its own workflow, invoice behavior, inventory behavior, finance mapping, and calculation logic while still using the same shared foundation.

Vehicle Service needs strong support for job cards, labour items, employee assignments, spare parts, customer-supplied items, non-inventory items, external services, and service history.

Vehicle Rental needs strong support for agreements, running charts, kilometer/hour/day/month calculations, with-driver and without-driver rentals, overtime, night shift, double rate, replacement vehicles, provider payments, and rental invoices.

Overall, the application should become a reusable modular ERP foundation that can grow with any business type.
