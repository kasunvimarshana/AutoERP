import type { NavigationItem, NavigationRouteMatcher, NavigationSection } from './navigationTypes';

const withoutContext = (...keys: string[]): Record<string, undefined> =>
    Object.fromEntries(keys.map((key) => [key, undefined]));

const contextual = (
    path: string,
    query: Record<string, string>,
    detailPath?: string,
): NavigationRouteMatcher[] => [
    { path, exact: true, query },
    ...(detailPath ? [{ path: detailPath, query }] : []),
];

const workspace = (
    id: string,
    label: string,
    route: string,
    activeRoutes?: NavigationRouteMatcher[],
): NavigationItem => ({ id, label, route, activeRoutes });

const rentalPermissions = [
    'vehicle-rental.reservations.manage',
    'vehicle-rental.agreements.manage',
    'vehicle-rental.allocations.manage',
    'vehicle-rental.inspections.record',
    'vehicle-rental.usage.record',
    'vehicle-rental.usage.approve',
    'vehicle-rental.expenses.record',
    'vehicle-rental.charges.generate',
    'vehicle-rental.financial.create',
];

export const navigationSections: NavigationSection[] = [
    {
        id: 'dashboard',
        items: [
            {
                id: 'dashboard',
                label: 'Dashboard',
                route: '/dashboard',
                icon: 'DB',
                activeRoutes: [{ path: '/dashboard' }],
            },
        ],
    },
    {
        id: 'operations',
        label: 'Operations',
        items: [
            {
                id: 'sales',
                label: 'Sales',
                route: '/sales',
                icon: 'SA',
                requiredModule: 'sales',
                requiredFeature: 'sales',
                children: [
                    workspace('sales-overview', 'Overview', '/sales', [{ path: '/sales', exact: true }]),
                    workspace('sales-quotations', 'Quotations', '/sales/quotations'),
                    workspace('sales-orders', 'Orders', '/sales/orders'),
                    workspace('sales-deliveries', 'Deliveries', '/sales/deliveries'),
                    workspace('sales-returns', 'Returns', '/sales/returns'),
                    workspace(
                        'sales-invoices',
                        'Invoices',
                        '/invoices?invoice_type=sales&direction=outbound',
                        contextual('/invoices', { invoice_type: 'sales', direction: 'outbound' }, '/invoices/:id'),
                    ),
                    workspace(
                        'sales-receipts',
                        'Customer Receipts',
                        '/payments?payment_type=customer_receipt&direction=inbound',
                        contextual('/payments', { payment_type: 'customer_receipt', direction: 'inbound' }, '/payments/:id'),
                    ),
                ],
            },
            {
                id: 'purchase',
                label: 'Purchase',
                route: '/purchase',
                icon: 'PU',
                requiredModule: 'purchase',
                requiredFeature: 'purchase',
                children: [
                    workspace('purchase-overview', 'Overview', '/purchase', [{ path: '/purchase', exact: true }]),
                    workspace('purchase-orders', 'Purchase Orders', '/purchase/orders'),
                    workspace('purchase-receipts', 'Goods Receipts', '/purchase/goods-receipts'),
                    workspace('purchase-returns', 'Returns', '/purchase/returns'),
                    workspace(
                        'purchase-invoices',
                        'Supplier Invoices',
                        '/invoices?invoice_type=purchase&direction=inbound',
                        contextual('/invoices', { invoice_type: 'purchase', direction: 'inbound' }, '/invoices/:id'),
                    ),
                    workspace(
                        'purchase-payments',
                        'Supplier Payments',
                        '/payments?payment_type=supplier_payment&direction=outbound',
                        contextual('/payments', { payment_type: 'supplier_payment', direction: 'outbound' }, '/payments/:id'),
                    ),
                ],
            },
            {
                id: 'inventory',
                label: 'Inventory',
                route: '/inventory?view=dashboard',
                icon: 'IN',
                requiredModule: 'inventory',
                requiredFeature: 'inventory',
                children: [
                    workspace('inventory-overview', 'Overview', '/inventory?view=dashboard'),
                    workspace('inventory-stock', 'Stock', '/inventory?view=availability'),
                    workspace('inventory-movements', 'Movements', '/inventory?view=audit'),
                    workspace('inventory-transfers', 'Transfers', '/inventory?view=transfers'),
                    workspace('inventory-adjustments', 'Adjustments', '/inventory?view=adjustments'),
                    workspace('inventory-counts', 'Stock Counts', '/inventory?view=counts'),
                    workspace('inventory-valuation', 'Valuation', '/inventory?view=costing'),
                ],
            },
            {
                id: 'vehicle-service',
                label: 'Vehicle Service',
                route: '/vehicle-service',
                icon: 'VS',
                requiredModule: 'vehicle-service',
                requiredFeature: 'vehicle-service',
                children: [
                    workspace('vehicle-service-overview', 'Overview', '/vehicle-service', [{ path: '/vehicle-service', exact: true }]),
                    workspace('vehicle-service-jobs', 'Service Jobs', '/vehicle-service/jobs'),
                    workspace(
                        'vehicle-service-customer-vehicles',
                        'Customer Vehicles',
                        '/vehicles?scope=customer&context=vehicle-service',
                        contextual('/vehicles', { scope: 'customer', context: 'vehicle-service' }, '/vehicles/:id'),
                    ),
                    workspace(
                        'vehicle-service-invoices',
                        'Invoices',
                        '/invoices?invoice_type=service&direction=outbound',
                        contextual('/invoices', { invoice_type: 'service', direction: 'outbound' }, '/invoices/:id'),
                    ),
                    workspace(
                        'vehicle-service-receipts',
                        'Customer Receipts',
                        '/payments?payment_type=service_receipt&direction=inbound',
                        contextual('/payments', { payment_type: 'service_receipt', direction: 'inbound' }, '/payments/:id'),
                    ),
                ],
            },
            {
                id: 'vehicle-rental',
                label: 'Vehicle Rental',
                route: '/vehicle-rental',
                icon: 'VR',
                requiredModule: 'vehicle-rental',
                requiredFeature: 'vehicle-rental',
                requiredPermissions: rentalPermissions,
                permissionMode: 'any',
                children: [
                    workspace('vehicle-rental-overview', 'Overview', '/vehicle-rental', [{ path: '/vehicle-rental', exact: true }]),
                    workspace('vehicle-rental-reservations', 'Reservations', '/vehicle-rental/reservations'),
                    workspace('vehicle-rental-agreements', 'Agreements', '/vehicle-rental/agreements'),
                    workspace('vehicle-rental-availability', 'Availability', '/vehicle-rental/availability'),
                    workspace('vehicle-rental-running-charts', 'Running Charts', '/vehicle-rental/running-chart'),
                    workspace(
                        'vehicle-rental-owner-vehicles',
                        'Supplier / Owner Vehicles',
                        '/vehicles?scope=supplier_owner&context=vehicle-rental',
                        contextual('/vehicles', { scope: 'supplier_owner', context: 'vehicle-rental' }, '/vehicles/:id'),
                    ),
                    workspace(
                        'vehicle-rental-invoices',
                        'Invoices & Payables',
                        '/invoices?invoice_type=rental',
                        contextual('/invoices', { invoice_type: 'rental' }, '/invoices/:id'),
                    ),
                    workspace(
                        'vehicle-rental-settlements',
                        'Settlements',
                        '/payments?source_type=VehicleRentalAgreement',
                        contextual('/payments', { source_type: 'VehicleRentalAgreement' }, '/payments/:id'),
                    ),
                ],
            },
            {
                id: 'hr',
                label: 'HR',
                route: '/hr',
                icon: 'HR',
                requiredModule: 'hr',
                requiredFeature: 'hr',
                children: [
                    workspace('hr-overview', 'Overview', '/hr', [{ path: '/hr', exact: true }]),
                    workspace('hr-employees', 'Employees', '/hr/employees'),
                    workspace('hr-commissions', 'Commissions', '/reports/vehicle-service/employee-commissions'),
                ],
            },
        ],
    },
    {
        id: 'finance',
        label: 'Finance',
        items: [
            {
                id: 'finance-overview',
                label: 'Overview',
                route: '/finance',
                icon: 'FI',
                requiredModule: 'finance',
                requiredFeature: 'finance',
                activeRoutes: [{ path: '/finance', exact: true }],
            },
            workspace(
                'finance-receivables',
                'Receivables',
                '/invoices?direction=outbound',
                [
                    { path: '/invoices', query: { direction: 'outbound', invoice_type: undefined } },
                    { path: '/invoices/:id', query: { direction: 'outbound', invoice_type: undefined } },
                ],
            ),
            workspace(
                'finance-payables',
                'Payables',
                '/invoices?direction=inbound',
                [
                    { path: '/invoices', query: { direction: 'inbound', invoice_type: undefined } },
                    { path: '/invoices/:id', query: { direction: 'inbound', invoice_type: undefined } },
                ],
            ),
            workspace('finance-cash-banking', 'Cash & Banking', '/finance/bank-reconciliations'),
            {
                ...workspace('finance-payments', 'Payments', '/payments', [
                    {
                        path: '/payments',
                        query: withoutContext('payment_type', 'direction', 'source_type'),
                    },
                    {
                        path: '/payments/:id',
                        query: withoutContext('payment_type', 'direction', 'source_type'),
                    },
                ]),
                icon: 'PM',
            },
            workspace('finance-vouchers', 'Vouchers', '/vouchers'),
            workspace('finance-accounting', 'Accounting', '/finance/accounts', [
                { path: '/finance/accounts' },
                { path: '/finance/journals' },
                { path: '/finance/ledger' },
                { path: '/finance/trial-balance' },
                { path: '/finance/account-balances' },
                { path: '/finance/fiscal-periods' },
                { path: '/finance/reversals' },
                { path: '/finance/budgets' },
            ]),
            workspace('finance-tax', 'Tax', '/tax/taxes', [
                { path: '/tax/taxes' },
                { path: '/tax/groups' },
                { path: '/tax/customer-profiles' },
                { path: '/tax/supplier-profiles' },
                { path: '/tax/posting-profiles' },
            ]),
            workspace('finance-reports', 'Financial Reports', '/finance/reports'),
        ],
    },
    {
        id: 'master-data',
        label: 'Master Data',
        items: [
            { ...workspace('customers', 'Customers', '/customers'), icon: 'CU', requiredModule: 'master-data' },
            { ...workspace('suppliers', 'Suppliers / Owners', '/suppliers'), icon: 'SU', requiredModule: 'master-data' },
            { ...workspace('items', 'Items', '/items'), icon: 'IT', requiredModule: 'master-data' },
            {
                id: 'vehicles',
                label: 'Vehicles',
                route: '/vehicles',
                icon: 'VE',
                requiredModule: 'master-data',
                children: [
                    workspace(
                        'vehicles-all',
                        'All Vehicles',
                        '/vehicles?scope=all',
                        [
                            { path: '/vehicles', query: { scope: 'all', context: undefined } },
                            { path: '/vehicles', query: { scope: undefined, context: undefined } },
                            { path: '/vehicles/:id', query: { scope: 'all', context: undefined } },
                            { path: '/vehicles/:id', query: { scope: undefined, context: undefined } },
                        ],
                    ),
                    workspace(
                        'vehicles-fleet',
                        'Fleet Vehicles',
                        '/vehicles?scope=fleet',
                        [
                            { path: '/vehicles', query: { scope: 'fleet', context: undefined } },
                            { path: '/vehicles/:id', query: { scope: 'fleet', context: undefined } },
                        ],
                    ),
                    workspace(
                        'vehicles-customer',
                        'Customer Vehicles',
                        '/vehicles?scope=customer',
                        [
                            { path: '/vehicles', query: { scope: 'customer', context: undefined } },
                            { path: '/vehicles/:id', query: { scope: 'customer', context: undefined } },
                        ],
                    ),
                    workspace(
                        'vehicles-supplier',
                        'Supplier / Owner Vehicles',
                        '/vehicles?scope=supplier_owner',
                        [
                            { path: '/vehicles', query: { scope: 'supplier_owner', context: undefined } },
                            { path: '/vehicles/:id', query: { scope: 'supplier_owner', context: undefined } },
                        ],
                    ),
                ],
            },
            { ...workspace('uom', 'Units of Measure', '/uoms'), icon: 'UM', requiredModule: 'master-data' },
            { ...workspace('employees', 'Employees', '/hr/employees'), icon: 'EM', requiredModule: 'hr' },
        ],
    },
    {
        id: 'reports',
        label: 'Reports',
        items: [
            workspace('reports-operational', 'Operational Reports', '/reports?group=operational'),
            workspace('reports-sales', 'Sales Reports', '/reports?group=sales'),
            workspace('reports-purchase', 'Purchase Reports', '/reports?group=purchase'),
            workspace('reports-inventory', 'Inventory Reports', '/reports?group=inventory'),
            workspace('reports-service', 'Vehicle Service Reports', '/reports?group=vehicle-service'),
            workspace('reports-rental', 'Vehicle Rental Reports', '/reports?group=vehicle-rental'),
            workspace('reports-financial', 'Financial Reports', '/reports?group=finance'),
            workspace('reports-tax', 'Tax Reports', '/tax/reports'),
        ],
    },
    {
        id: 'administration',
        label: 'Administration',
        items: [
            workspace('administration-posting-profiles', 'Posting Profiles', '/finance/posting-profiles'),
        ],
    },
];

export const navigationActions: NavigationItem[] = [
    { id: 'action-sales-order', label: 'New sales order', route: '/sales/orders/create', requiredModule: 'sales' },
    { id: 'action-purchase-order', label: 'New purchase order', route: '/purchase/orders/create', requiredModule: 'purchase' },
    { id: 'action-service-job', label: 'New service job', route: '/vehicle-service/jobs/create', requiredModule: 'vehicle-service' },
    {
        id: 'action-rental-agreement',
        label: 'New rental agreement',
        route: '/vehicle-rental/agreements/create',
        requiredModule: 'vehicle-rental',
        requiredPermissions: ['vehicle-rental.agreements.manage'],
    },
    { id: 'action-customer', label: 'New customer', route: '/customers/create', requiredModule: 'master-data' },
    { id: 'action-supplier', label: 'New supplier / owner', route: '/suppliers/create', requiredModule: 'master-data' },
    { id: 'action-vehicle', label: 'New vehicle', route: '/vehicles/create', requiredModule: 'master-data' },
];
