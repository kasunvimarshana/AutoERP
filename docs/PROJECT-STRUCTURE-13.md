app/Modules/
├── Core/                 # Shared kernel, base classes, global utilities
├── Tenant/               # Multi‑tenancy management
├── OrganizationUnit/     # Hierarchical organizational structures
├── User/                 # Authentication, authorization, user profiles
├── Customer/             # Customer master data, AR account, addresses
├── Supplier/             # Supplier master data, AP account, addresses
├── Product/              # Product catalog, variants, categories, UoM
├── Pricing/              # Purchase & sales price lists, tiered pricing, price modifiers
├── Warehouse/            # Physical warehouses and location hierarchies
├── Inventory/            # Stock, traceability, cost layers, AIDC, trace logs
├── Purchase/             # Procurement: POs, GRNs, purchase invoices, returns
├── Sales/                # Order‑to‑cash: orders, shipments, sales invoices, returns
├── Finance/              # Double‑entry accounting, bank feeds, tax, payments/refunds
└── Shared/               # Cross‑module contracts, DTOs, events