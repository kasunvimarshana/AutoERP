export type AppIconName =
    | 'dashboard'
    | 'tenant'
    | 'users'
    | 'organization'
    | 'employees'
    | 'customers'
    | 'suppliers'
    | 'products'
    | 'pricing'
    | 'tax'
    | 'jobCards'
    | 'warehouses'
    | 'inventory'
    | 'purchase'
    | 'sales'
    | 'finance'
    | 'audit'
    | 'settings';

export type AppNavigationChild = {
    id: string;
    label: string;
    path: string;
    description: string;
};

export type AppNavigationSection = {
    id: string;
    label: string;
    icon: AppIconName;
    path?: string;
    description: string;
    children?: AppNavigationChild[];
};

export type AppPageMeta = {
    id: string;
    path: string;
    title: string;
    sectionId: string;
    sectionLabel: string;
    description: string;
    breadcrumbs: string[];
    isDashboard?: boolean;
};

export const appNavigationSections: AppNavigationSection[] = [
    {
        id: 'dashboard',
        label: 'Dashboard',
        icon: 'dashboard',
        path: '/',
        description: 'Operational overview, KPI monitoring, approvals, and activity visibility.',
    },
    {
        id: 'tenant-admin',
        label: 'Tenant Admin',
        icon: 'tenant',
        description: 'Tenant hierarchy, subscriptions, domains, and tenant-wide settings.',
        children: [
            { id: 'tenants', label: 'Tenants', path: '/tenant-admin/tenants', description: 'Tenant records and lifecycle.' },
            { id: 'plans', label: 'Plans', path: '/tenant-admin/plans', description: 'Plan definitions and commercial setup.' },
            { id: 'domains', label: 'Domains', path: '/tenant-admin/domains', description: 'Domain mapping and tenant access.' },
        ],
    },
    {
        id: 'users-access',
        label: 'Users & Access',
        icon: 'users',
        description: 'Users, roles, permissions, and access governance.',
        children: [
            { id: 'users', label: 'Users', path: '/users-access/users', description: 'Directory of system users.' },
            { id: 'add-user', label: 'Add User', path: '/users-access/users/new', description: 'Create a new tenant user.' },
            { id: 'roles', label: 'Roles', path: '/users-access/roles', description: 'Role catalog and assignments.' },
            { id: 'permissions', label: 'Permissions', path: '/users-access/permissions', description: 'Permission definitions and access review.' },
            { id: 'profile', label: 'Profile', path: '/users-access/profile', description: 'Personal profile maintenance and password updates.' },
        ],
    },
    {
        id: 'organization',
        label: 'Organization',
        icon: 'organization',
        description: 'Structure, units, and organizational ownership.',
        children: [
            { id: 'units', label: 'Organization Units', path: '/organization/units', description: 'Organization unit tree and hierarchy.' },
            { id: 'unit-types', label: 'Organization Unit Types', path: '/organization/unit-types', description: 'Organizational unit taxonomy.' },
            { id: 'assign-users', label: 'Assign Users', path: '/organization/assign-users', description: 'Assign users to organization units.' },
        ],
    },
    {
        id: 'employees',
        label: 'Employees',
        icon: 'employees',
        description: 'Employee master data and workforce records.',
        children: [
            { id: 'employee-list', label: 'Employee List', path: '/employees', description: 'Employee master listing.' },
            { id: 'add-employee', label: 'Add Employee', path: '/employees/new', description: 'Create an employee record if supported.' },
        ],
    },
    {
        id: 'customers',
        label: 'Customers',
        icon: 'customers',
        description: 'Customer master data, contacts, and account context.',
        children: [
            { id: 'customer-list', label: 'Customer List', path: '/customers', description: 'Customer account overview.' },
            { id: 'add-customer', label: 'Add Customer', path: '/customers/new', description: 'Create a new customer record.' },
        ],
    },
    {
        id: 'suppliers',
        label: 'Suppliers',
        icon: 'suppliers',
        description: 'Supplier directory, relationships, and supplier contacts.',
        children: [
            { id: 'supplier-list', label: 'Supplier List', path: '/suppliers', description: 'Supplier account overview.' },
            { id: 'add-supplier', label: 'Add Supplier', path: '/suppliers/new', description: 'Create a new supplier record.' },
        ],
    },
    {
        id: 'products',
        label: 'Products',
        icon: 'products',
        description: 'Catalog setup, variants, brands, and product master records.',
        children: [
            { id: 'catalog', label: 'Catalog', path: '/products', description: 'Product master list, detail shells, and setup flows.' },
            { id: 'add-product', label: 'Add Product', path: '/products/new', description: 'Create a new product record.' },
            { id: 'brands', label: 'Brands', path: '/products/brands', description: 'Brand and identity setup.' },
            { id: 'categories', label: 'Categories', path: '/products/categories', description: 'Category hierarchy and assignment setup.' },
            { id: 'units', label: 'Units', path: '/products/units', description: 'Units of measure and conversion foundations.' },
            { id: 'unit-conversions', label: 'Conversions', path: '/products/units/conversions', description: 'Conversion rules between units of measure.' },
        ],
    },
    {
        id: 'pricing',
        label: 'Pricing',
        icon: 'pricing',
        description: 'Price lists, customer pricing, and supplier pricing.',
        children: [
            { id: 'price-lists', label: 'Price Lists', path: '/pricing/price-lists', description: 'Manage list pricing.' },
            { id: 'customer-pricing', label: 'Customer Pricing', path: '/pricing/customer-pricing', description: 'Customer-specific pricing rules.' },
            { id: 'supplier-pricing', label: 'Supplier Pricing', path: '/pricing/supplier-pricing', description: 'Supplier-related purchasing prices.' },
        ],
    },
    {
        id: 'tax',
        label: 'Tax',
        icon: 'tax',
        description: 'Tax groups, rates, and transaction tax rules.',
        children: [
            { id: 'groups', label: 'Groups', path: '/tax/groups', description: 'Tax groups and categories.' },
            { id: 'rates', label: 'Rates', path: '/tax/rates', description: 'Tax rate definitions.' },
            { id: 'rules', label: 'Rules', path: '/tax/rules', description: 'Tax applicability rules.' },
        ],
    },
    {
        id: 'vehicles',
        label: 'Vehicle',
        icon: 'inventory',
        description: 'Vehicle registry, technical details, ownership context, and status tracking.',
        children: [
            { id: 'vehicle-dashboard', label: 'Dashboard', path: '/vehicles/dashboard', description: 'Vehicle registry summary and document alerts.' },
            { id: 'vehicle-list', label: 'Vehicle List', path: '/vehicles', description: 'Vehicle registry listing and maintenance.' },
            { id: 'add-vehicle', label: 'Add Vehicle', path: '/vehicles/create', description: 'Create a new vehicle registry record.' },
        ],
    },
    {
        id: 'job-cards',
        label: 'Job Cards',
        icon: 'jobCards',
        description: 'Service job card workflow, crew assignment, and order records.',
        children: [
            { id: 'job-card-list', label: 'Job Card List', path: '/job-cards', description: 'Review vehicle-scoped job cards.' },
            { id: 'new-job-card', label: 'New Job Card', path: '/job-cards/create', description: 'Initiate a new service record for a customer vehicle.' },
        ],
    },
    {
        id: 'warehouses',
        label: 'Warehouse',
        icon: 'warehouses',
        description: 'Warehouse network and location structure.',
        children: [
            { id: 'warehouse-list', label: 'Warehouse List', path: '/warehouses', description: 'Warehouse directory and maintenance.' },
            { id: 'warehouse-stock-levels', label: 'Stock Levels', path: '/warehouses/stock-levels', description: 'Cross-warehouse stock level visibility.' },
            { id: 'warehouse-stock-movements', label: 'Stock Movements', path: '/warehouses/stock-movements', description: 'Cross-warehouse stock movement visibility.' },
        ],
    },
    {
        id: 'inventory',
        label: 'Inventory',
        icon: 'inventory',
        description: 'Stock balances, movements, reservations, and operational controls.',
        children: [
            { id: 'inventory-dashboard', label: 'Inventory Dashboard', path: '/inventory', description: 'Operational inventory overview by warehouse and workflow.' },
            { id: 'transfer-orders', label: 'Transfer Orders', path: '/inventory/transfer-orders', description: 'Warehouse transfer workflow and approvals.' },
            { id: 'cycle-counts', label: 'Cycle Counts', path: '/inventory/cycle-counts', description: 'Cycle count planning and completion workflow.' },
            { id: 'stock-reservations', label: 'Stock Reservations', path: '/inventory/stock-reservations', description: 'Active stock reservations and release controls.' },
            { id: 'valuation-configs', label: 'Valuation Configs', path: '/inventory/valuation-configs', description: 'Inventory valuation and allocation policy setup.' },
        ],
    },
    {
        id: 'purchase',
        label: 'Purchase',
        icon: 'purchase',
        description: 'Procurement cycle, receiving, invoices, and returns.',
        children: [
            { id: 'purchase-orders', label: 'Purchase Orders', path: '/purchase/orders', description: 'Purchase order workspace.' },
            { id: 'purchase-grns', label: 'GRNs', path: '/purchase/grns', description: 'Goods receipt note tracking and posting.' },
            { id: 'purchase-invoices', label: 'Purchase Invoices', path: '/purchase/invoices', description: 'Supplier invoice tracking.' },
            { id: 'purchase-payments', label: 'Purchase Payments', path: '/purchase/payments/new', description: 'Record outbound supplier payments.' },
            { id: 'purchase-returns', label: 'Purchase Returns', path: '/purchase/returns', description: 'Purchase returns and supplier claims.' },
        ],
    },
    {
        id: 'sales',
        label: 'Sales',
        icon: 'sales',
        description: 'Sales cycle, invoicing, fulfillment, and returns.',
        children: [
            { id: 'sales-orders', label: 'Sales Orders', path: '/sales/orders', description: 'Sales order pipeline.' },
            { id: 'shipments', label: 'Shipments', path: '/sales/shipments', description: 'Shipment execution and fulfillment tracking.' },
            { id: 'sales-invoices', label: 'Sales Invoices', path: '/sales/invoices', description: 'Sales invoice control.' },
            { id: 'sales-returns', label: 'Sales Returns', path: '/sales/returns', description: 'Customer returns management.' },
        ],
    },
    {
        id: 'finance',
        label: 'Finance',
        icon: 'finance',
        description: 'Accounting, banking, receivables, payables, approvals, and reporting.',
        children: [
            { id: 'accounts', label: 'Accounts', path: '/finance/accounts', description: 'Chart of accounts and master data.' },
            { id: 'journal-entries', label: 'Journal Entries', path: '/finance/journal-entries', description: 'Manual and generated journal entries.' },
            { id: 'payments', label: 'Payments', path: '/finance/payments', description: 'Receipts, payments, and allocations.' },
            { id: 'reports', label: 'Reports', path: '/finance/reports', description: 'Financial analysis and reporting.' },
        ],
    },
    {
        id: 'audit-logs',
        label: 'Audit Logs',
        icon: 'audit',
        description: 'Operational traceability and recent system activity.',
        children: [{ id: 'activity', label: 'Activity', path: '/audit-logs/activity', description: 'Recent audit trail and trace history.' }],
    },
    {
        id: 'settings',
        label: 'Settings',
        icon: 'settings',
        description: 'Application defaults, preferences, and configuration touchpoints.',
        children: [
            { id: 'company', label: 'Company', path: '/settings/company', description: 'Company profile and presentation.' },
            { id: 'preferences', label: 'Preferences', path: '/settings/preferences', description: 'User and workspace defaults.' },
        ],
    },
];

const navigationPageRoutes: AppPageMeta[] = appNavigationSections.flatMap((section) => {
    if (section.path) {
        return [
            {
                id: section.id,
                path: section.path,
                title: section.label,
                sectionId: section.id,
                sectionLabel: section.label,
                description: section.description,
                breadcrumbs: [section.label],
                isDashboard: true,
            },
        ];
    }

    return (section.children ?? []).map((child) => ({
        id: child.id,
        path: child.path,
        title: child.label,
        sectionId: section.id,
        sectionLabel: section.label,
        description: child.description,
        breadcrumbs: [section.label, child.label],
    }));
});

const additionalAppPageRoutes: AppPageMeta[] = [
    {
        id: 'product-create',
        path: '/products/new',
        title: 'Add Product',
        sectionId: 'products',
        sectionLabel: 'Products',
        description: 'Create a new product record using the shared product setup layout.',
        breadcrumbs: ['Products', 'Add Product'],
    },
    {
        id: 'product-brand-create',
        path: '/products/brands/new',
        title: 'Add Brand',
        sectionId: 'products',
        sectionLabel: 'Products',
        description: 'Create a brand record using the shared master-data form.',
        breadcrumbs: ['Products', 'Brands', 'Add Brand'],
    },
    {
        id: 'product-category-create',
        path: '/products/categories/new',
        title: 'Add Category',
        sectionId: 'products',
        sectionLabel: 'Products',
        description: 'Create a category record using the shared master-data form.',
        breadcrumbs: ['Products', 'Categories', 'Add Category'],
    },
    {
        id: 'unit-of-measure-create',
        path: '/products/units/new',
        title: 'Add Unit of Measure',
        sectionId: 'products',
        sectionLabel: 'Products',
        description: 'Create a unit of measure using the shared product admin layout.',
        breadcrumbs: ['Products', 'Units', 'Add UOM'],
    },
    {
        id: 'customer-create',
        path: '/customers/new',
        title: 'Add Customer',
        sectionId: 'customers',
        sectionLabel: 'Customers',
        description: 'Create a customer using the shared Phase 3 master-data form layout.',
        breadcrumbs: ['Customers', 'Add Customer'],
    },
    {
        id: 'customer-detail',
        path: '/customers/:customerId',
        title: 'Customer Detail',
        sectionId: 'customers',
        sectionLabel: 'Customers',
        description: 'Customer profile workspace with overview, contacts, addresses, pricing, sales, and AR tabs.',
        breadcrumbs: ['Customers', 'Customer Detail'],
    },
    {
        id: 'customer-edit',
        path: '/customers/:customerId/edit',
        title: 'Edit Customer',
        sectionId: 'customers',
        sectionLabel: 'Customers',
        description: 'Edit customer master data using the shared add/edit form shell.',
        breadcrumbs: ['Customers', 'Edit Customer'],
    },
    {
        id: 'supplier-create',
        path: '/suppliers/new',
        title: 'Add Supplier',
        sectionId: 'suppliers',
        sectionLabel: 'Suppliers',
        description: 'Create a supplier using the shared Phase 3 master-data form layout.',
        breadcrumbs: ['Suppliers', 'Add Supplier'],
    },
    {
        id: 'supplier-detail',
        path: '/suppliers/:supplierId',
        title: 'Supplier Detail',
        sectionId: 'suppliers',
        sectionLabel: 'Suppliers',
        description: 'Supplier profile workspace with overview, contacts, products, pricing, purchases, and AP tabs.',
        breadcrumbs: ['Suppliers', 'Supplier Detail'],
    },
    {
        id: 'supplier-edit',
        path: '/suppliers/:supplierId/edit',
        title: 'Edit Supplier',
        sectionId: 'suppliers',
        sectionLabel: 'Suppliers',
        description: 'Edit supplier master data using the shared add/edit form shell.',
        breadcrumbs: ['Suppliers', 'Edit Supplier'],
    },
    {
        id: 'user-create',
        path: '/users-access/users/new',
        title: 'Add User',
        sectionId: 'users-access',
        sectionLabel: 'Users & Access',
        description: 'Create a tenant user with role assignments and profile data.',
        breadcrumbs: ['Users & Access', 'Add User'],
    },
    {
        id: 'user-detail',
        path: '/users-access/users/:userId',
        title: 'User Detail',
        sectionId: 'users-access',
        sectionLabel: 'Users & Access',
        description: 'User detail workspace with access summaries and linked profile information.',
        breadcrumbs: ['Users & Access', 'User Detail'],
    },
    {
        id: 'user-edit',
        path: '/users-access/users/:userId/edit',
        title: 'Edit User',
        sectionId: 'users-access',
        sectionLabel: 'Users & Access',
        description: 'Edit tenant users and role assignments.',
        breadcrumbs: ['Users & Access', 'Edit User'],
    },
    {
        id: 'role-create',
        path: '/users-access/roles/new',
        title: 'Add Role',
        sectionId: 'users-access',
        sectionLabel: 'Users & Access',
        description: 'Create a reusable role and assign permissions.',
        breadcrumbs: ['Users & Access', 'Roles', 'Add Role'],
    },
    {
        id: 'role-edit',
        path: '/users-access/roles/:roleId/edit',
        title: 'Edit Role',
        sectionId: 'users-access',
        sectionLabel: 'Users & Access',
        description: 'Maintain role permissions using the shared role editor.',
        breadcrumbs: ['Users & Access', 'Roles', 'Edit Role'],
    },
    {
        id: 'employee-create',
        path: '/employees/new',
        title: 'Add Employee',
        sectionId: 'employees',
        sectionLabel: 'Employees',
        description: 'Create an employee record using the shared large-card form layout.',
        breadcrumbs: ['Employees', 'Add Employee'],
    },
    {
        id: 'employee-detail',
        path: '/employees/:employeeId',
        title: 'Employee Detail',
        sectionId: 'employees',
        sectionLabel: 'Employees',
        description: 'Employee detail screen with hiring, assignment, and user-link context.',
        breadcrumbs: ['Employees', 'Employee Detail'],
    },
    {
        id: 'employee-edit',
        path: '/employees/:employeeId/edit',
        title: 'Edit Employee',
        sectionId: 'employees',
        sectionLabel: 'Employees',
        description: 'Edit employee data using the shared employee form shell.',
        breadcrumbs: ['Employees', 'Edit Employee'],
    },
    {
        id: 'warehouse-create',
        path: '/warehouses/new',
        title: 'Add Warehouse',
        sectionId: 'warehouses',
        sectionLabel: 'Warehouse',
        description: 'Create a warehouse record using the shared large-card ERP form layout.',
        breadcrumbs: ['Warehouse', 'Add Warehouse'],
    },
    {
        id: 'warehouse-detail',
        path: '/warehouses/:warehouseId',
        title: 'Warehouse Detail',
        sectionId: 'warehouses',
        sectionLabel: 'Warehouse',
        description: 'Warehouse detail with overview, locations, stock levels, and stock movements tabs.',
        breadcrumbs: ['Warehouse', 'Warehouse Detail'],
    },
    {
        id: 'warehouse-edit',
        path: '/warehouses/:warehouseId/edit',
        title: 'Edit Warehouse',
        sectionId: 'warehouses',
        sectionLabel: 'Warehouse',
        description: 'Edit warehouse master data with the shared add/edit form shell.',
        breadcrumbs: ['Warehouse', 'Edit Warehouse'],
    },
    {
        id: 'vehicle-create',
        path: '/vehicles/create',
        title: 'Add Vehicle',
        sectionId: 'vehicles',
        sectionLabel: 'Vehicle',
        description: 'Create a vehicle registry record using the shared large-card form layout.',
        breadcrumbs: ['Vehicle', 'Add Vehicle'],
    },
    {
        id: 'vehicle-detail',
        path: '/vehicles/:vehicleId',
        title: 'Vehicle Detail',
        sectionId: 'vehicles',
        sectionLabel: 'Vehicle',
        description: 'Vehicle registry detail with overview, technical details, owner details, and activity placeholder.',
        breadcrumbs: ['Vehicle', 'Vehicle Detail'],
    },
    {
        id: 'vehicle-edit',
        path: '/vehicles/:vehicleId/edit',
        title: 'Edit Vehicle',
        sectionId: 'vehicles',
        sectionLabel: 'Vehicle',
        description: 'Edit vehicle registry data using the shared add/edit form shell.',
        breadcrumbs: ['Vehicle', 'Edit Vehicle'],
    },
    {
        id: 'job-card-list',
        path: '/job-cards',
        title: 'Job Card List',
        sectionId: 'job-cards',
        sectionLabel: 'Job Cards',
        description: 'Vehicle-scoped job card list using supported backend routes.',
        breadcrumbs: ['Job Cards', 'Job Card List'],
    },
    {
        id: 'job-card-create',
        path: '/job-cards/create',
        title: 'New Job Card',
        sectionId: 'job-cards',
        sectionLabel: 'Job Cards',
        description: 'Initiate a new service record for a customer vehicle.',
        breadcrumbs: ['Job Cards', 'New Job Card'],
    },
    {
        id: 'vehicle-job-card-list',
        path: '/vehicles/:vehicleId/job-cards',
        title: 'Vehicle Job Cards',
        sectionId: 'job-cards',
        sectionLabel: 'Job Cards',
        description: 'Vehicle-specific job card list.',
        breadcrumbs: ['Job Cards', 'Vehicle Job Cards'],
    },
    {
        id: 'vehicle-job-card-detail',
        path: '/vehicles/:vehicleId/job-cards/:jobCardId',
        title: 'Job Card Detail',
        sectionId: 'job-cards',
        sectionLabel: 'Job Cards',
        description: 'Job card detail assembled from the supported vehicle job-card list route.',
        breadcrumbs: ['Job Cards', 'Detail'],
    },
    {
        id: 'transfer-order-detail',
        path: '/inventory/transfer-orders/:transferOrderId',
        title: 'Transfer Order Detail',
        sectionId: 'inventory',
        sectionLabel: 'Inventory',
        description: 'Transfer order workflow detail with approval and receipt actions.',
        breadcrumbs: ['Inventory', 'Transfer Orders', 'Transfer Order Detail'],
    },
    {
        id: 'cycle-count-detail',
        path: '/inventory/cycle-counts/:cycleCountId',
        title: 'Cycle Count Detail',
        sectionId: 'inventory',
        sectionLabel: 'Inventory',
        description: 'Cycle count detail with start and complete workflow controls.',
        breadcrumbs: ['Inventory', 'Cycle Counts', 'Cycle Count Detail'],
    },
    {
        id: 'purchase-order-create',
        path: '/purchase/orders/new',
        title: 'Add Purchase Order',
        sectionId: 'purchase',
        sectionLabel: 'Purchase',
        description: 'Create a purchase order using the shared workflow form layout.',
        breadcrumbs: ['Purchase', 'Purchase Orders', 'Add Purchase Order'],
    },
    {
        id: 'purchase-order-detail',
        path: '/purchase/orders/:purchaseOrderId',
        title: 'Purchase Order Detail',
        sectionId: 'purchase',
        sectionLabel: 'Purchase',
        description: 'Purchase order detail with totals, workflow status, and related-document placeholders.',
        breadcrumbs: ['Purchase', 'Purchase Orders', 'Purchase Order Detail'],
    },
    {
        id: 'grn-detail',
        path: '/purchase/grns/:grnId',
        title: 'GRN Detail',
        sectionId: 'purchase',
        sectionLabel: 'Purchase',
        description: 'Goods receipt note detail with posting workflow.',
        breadcrumbs: ['Purchase', 'GRNs', 'GRN Detail'],
    },
    {
        id: 'purchase-invoice-detail',
        path: '/purchase/invoices/:invoiceId',
        title: 'Purchase Invoice Detail',
        sectionId: 'purchase',
        sectionLabel: 'Purchase',
        description: 'Purchase invoice detail with totals and approve workflow.',
        breadcrumbs: ['Purchase', 'Purchase Invoices', 'Purchase Invoice Detail'],
    },
    {
        id: 'purchase-return-detail',
        path: '/purchase/returns/:purchaseReturnId',
        title: 'Purchase Return Detail',
        sectionId: 'purchase',
        sectionLabel: 'Purchase',
        description: 'Purchase return detail with post workflow and credit-note context.',
        breadcrumbs: ['Purchase', 'Purchase Returns', 'Purchase Return Detail'],
    },
    {
        id: 'sales-order-create',
        path: '/sales/orders/new',
        title: 'Add Sales Order',
        sectionId: 'sales',
        sectionLabel: 'Sales',
        description: 'Create a sales order using the shared workflow form layout.',
        breadcrumbs: ['Sales', 'Sales Orders', 'Add Sales Order'],
    },
    {
        id: 'sales-order-detail',
        path: '/sales/orders/:salesOrderId',
        title: 'Sales Order Detail',
        sectionId: 'sales',
        sectionLabel: 'Sales',
        description: 'Sales order detail with totals, status workflow, and related-document placeholders.',
        breadcrumbs: ['Sales', 'Sales Orders', 'Sales Order Detail'],
    },
    {
        id: 'shipment-detail',
        path: '/sales/shipments/:shipmentId',
        title: 'Shipment Detail',
        sectionId: 'sales',
        sectionLabel: 'Sales',
        description: 'Shipment detail with process workflow and fulfillment summary.',
        breadcrumbs: ['Sales', 'Shipments', 'Shipment Detail'],
    },
    {
        id: 'sales-invoice-detail',
        path: '/sales/invoices/:salesInvoiceId',
        title: 'Sales Invoice Detail',
        sectionId: 'sales',
        sectionLabel: 'Sales',
        description: 'Sales invoice detail with totals and posting workflow.',
        breadcrumbs: ['Sales', 'Sales Invoices', 'Sales Invoice Detail'],
    },
    {
        id: 'sales-return-detail',
        path: '/sales/returns/:salesReturnId',
        title: 'Sales Return Detail',
        sectionId: 'sales',
        sectionLabel: 'Sales',
        description: 'Sales return detail with approve and receive workflow controls.',
        breadcrumbs: ['Sales', 'Sales Returns', 'Sales Return Detail'],
    },
];

export const appPageRoutes: AppPageMeta[] = [...navigationPageRoutes, ...additionalAppPageRoutes];

function normalizePathname(pathname: string) {
    if (pathname !== '/' && pathname.endsWith('/')) {
        return pathname.slice(0, -1);
    }

    return pathname || '/';
}

function matchRoutePattern(routePath: string, pathname: string) {
    if (routePath === pathname) {
        return true;
    }

    const routeSegments = normalizePathname(routePath).split('/').filter(Boolean);
    const pathSegments = normalizePathname(pathname).split('/').filter(Boolean);

    if (routeSegments.length !== pathSegments.length) {
        return false;
    }

    return routeSegments.every((segment, index) => segment.startsWith(':') || segment === pathSegments[index]);
}

function matchesDynamicPrefix(routePath: string, pathname: string) {
    const routeSegments = normalizePathname(routePath).split('/').filter(Boolean);
    const pathSegments = normalizePathname(pathname).split('/').filter(Boolean);

    if (routeSegments.length >= pathSegments.length) {
        return false;
    }

    return routeSegments.every((segment, index) => segment.startsWith(':') || segment === pathSegments[index]);
}

export function findAppPageMeta(pathname: string) {
    const normalizedPathname = normalizePathname(pathname);
    const exactMatch = appPageRoutes.find((route) => matchRoutePattern(route.path, normalizedPathname));

    if (exactMatch) {
        return exactMatch;
    }

    const prefixMatches = appPageRoutes
        .filter((route) => route.path !== '/' && matchesDynamicPrefix(route.path, normalizedPathname))
        .sort((left, right) => right.path.length - left.path.length);

    return prefixMatches[0] ?? null;
}
