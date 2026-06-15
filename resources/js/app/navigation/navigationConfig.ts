import type { NavigationSection } from './navigationTypes';

const tenantAccess = (modules: NonNullable<NavigationSection['items'][number]['access']>['modules']) => ({
    requiresTenant: true,
    modules,
});

export const navigationSections: NavigationSection[] = [
    {
        id: 'primary',
        items: [
            {
                id: 'dashboard',
                type: 'link',
                label: 'Dashboard',
                to: '/dashboard',
                icon: 'dashboard',
                match: ['/dashboard'],
            },
        ],
    },
    {
        id: 'master-data',
        label: 'Master Data',
        items: [
            {
                id: 'suppliers',
                type: 'module',
                label: 'Suppliers',
                icon: 'supplier',
                access: tenantAccess(['supplier']),
                children: [
                    {
                        id: 'supplier-list',
                        type: 'link',
                        label: 'Supplier List',
                        to: '/suppliers',
                        match: ['/suppliers'],
                        access: tenantAccess(['supplier']),
                    },
                    {
                        id: 'supplier-vehicles',
                        type: 'link',
                        label: 'Supplier Vehicles',
                        to: '/vehicles?ownership=supplier',
                        match: ['/vehicles'],
                        access: tenantAccess(['vehicle', 'supplier']),
                    },
                ],
            },
            {
                id: 'customers',
                type: 'module',
                label: 'Customers',
                icon: 'customer',
                access: tenantAccess(['customer']),
                children: [
                    {
                        id: 'customer-list',
                        type: 'link',
                        label: 'Customer List',
                        to: '/customers',
                        match: ['/customers'],
                        access: tenantAccess(['customer']),
                    },
                    {
                        id: 'customer-vehicles',
                        type: 'link',
                        label: 'Customer Vehicles',
                        to: '/vehicles?ownership=customer',
                        match: ['/vehicles'],
                        access: tenantAccess(['vehicle', 'customer']),
                    },
                ],
            },
            {
                id: 'items',
                type: 'module',
                label: 'Items',
                icon: 'item',
                access: tenantAccess(['item']),
                children: [
                    {
                        id: 'item-list',
                        type: 'link',
                        label: 'Item List',
                        to: '/items',
                        match: ['/items'],
                        access: tenantAccess(['item']),
                    },
                ],
            },
        ],
    },
    {
        id: 'access-control',
        label: 'Access Control',
        items: [
            {
                id: 'users',
                type: 'module',
                label: 'Users',
                icon: 'users',
                access: {
                    ...tenantAccess(['user']),
                    roles: ['super admin'],
                    permissionPrefixes: ['user.', 'users.', 'role.', 'roles.', 'permission.', 'permissions.'],
                },
                children: [
                    {
                        id: 'user-list',
                        type: 'link',
                        label: 'User List',
                        to: '/access/users',
                        access: tenantAccess(['user']),
                    },
                    {
                        id: 'roles',
                        type: 'link',
                        label: 'Roles',
                        to: '/access/roles',
                        icon: 'role',
                        access: tenantAccess(['user']),
                    },
                    {
                        id: 'permissions',
                        type: 'link',
                        label: 'Permissions',
                        to: '/access/permissions',
                        icon: 'permission',
                        access: tenantAccess(['user']),
                    },
                ],
            },
        ],
    },
    {
        id: 'operations',
        label: 'Operations',
        items: [
            {
                id: 'purchase',
                type: 'module',
                label: 'Purchase',
                icon: 'purchase',
                access: tenantAccess(['purchase']),
                children: [
                    { id: 'purchase-orders', type: 'link', label: 'Purchase Orders', to: '/purchase/orders', match: ['/purchase/orders'], access: tenantAccess(['purchase']) },
                    { id: 'goods-receipts', type: 'link', label: 'Goods Receipts', to: '/purchase/goods-receipts', match: ['/purchase/goods-receipts'], access: tenantAccess(['purchase']) },
                    { id: 'purchase-returns', type: 'link', label: 'Purchase Returns', to: '/purchase/returns', match: ['/purchase/returns', '/purchase/manual-supplier-returns'], access: tenantAccess(['purchase']) },
                    { id: 'supplier-invoices', type: 'link', label: 'Supplier Invoices', to: '/invoices?view=supplier', match: ['/invoices'], access: tenantAccess(['invoice', 'purchase']) },
                    { id: 'supplier-payments', type: 'link', label: 'Supplier Payments', to: '/payments?view=supplier', match: ['/payments'], exclude: ['/payments/create', '/payments/cheque-templates'], access: tenantAccess(['payment', 'purchase']) },
                ],
            },
            {
                id: 'sales',
                type: 'module',
                label: 'Sales',
                icon: 'sales',
                access: tenantAccess(['sales']),
                children: [
                    { id: 'sales-orders', type: 'link', label: 'Sales Orders', to: '/sales/orders', match: ['/sales/orders'], access: tenantAccess(['sales']) },
                    { id: 'goods-deliveries', type: 'link', label: 'Goods Deliveries', to: '/sales/deliveries', match: ['/sales/deliveries'], access: tenantAccess(['sales']) },
                    { id: 'sales-returns', type: 'link', label: 'Sales Returns', to: '/sales/returns', match: ['/sales/returns'], access: tenantAccess(['sales']) },
                    { id: 'customer-invoices', type: 'link', label: 'Customer Invoices', to: '/invoices?view=customer', match: ['/invoices'], access: tenantAccess(['invoice', 'sales']) },
                    { id: 'customer-receipts', type: 'link', label: 'Customer Receipts', to: '/payments?view=customer', match: ['/payments'], exclude: ['/payments/create', '/payments/cheque-templates'], access: tenantAccess(['payment', 'sales']) },
                ],
            },
            {
                id: 'vehicle-service',
                type: 'module',
                label: 'Vehicle Service',
                icon: 'service',
                access: tenantAccess(['vehicle-service']),
                children: [
                    { id: 'service-jobs', type: 'link', label: 'Service Jobs', to: '/vehicle-service/jobs', match: ['/vehicle-service/jobs'], access: tenantAccess(['vehicle-service']) },
                    { id: 'service-invoices', type: 'link', label: 'Service Invoices', to: '/invoices?view=service', match: ['/invoices'], access: tenantAccess(['invoice', 'vehicle-service']) },
                    { id: 'service-receipts', type: 'link', label: 'Customer Receipts', to: '/payments?view=service', match: ['/payments'], exclude: ['/payments/create', '/payments/cheque-templates'], access: tenantAccess(['payment', 'vehicle-service']) },
                ],
            },
            {
                id: 'vehicle-rental',
                type: 'module',
                label: 'Vehicle Rental',
                icon: 'rental',
                access: {
                    ...tenantAccess(['vehicle-rental']),
                    permissionPrefixes: ['vehicle-rental.'],
                },
                children: [
                    { id: 'owner-agreements', type: 'link', label: 'Owner / Supplier Agreements', to: '/vehicle-rental/agreements?direction=inbound', match: ['/vehicle-rental/agreements'], access: tenantAccess(['vehicle-rental']) },
                    { id: 'customer-agreements', type: 'link', label: 'Customer Agreements', to: '/vehicle-rental/agreements?direction=outbound', match: ['/vehicle-rental/agreements'], access: tenantAccess(['vehicle-rental']) },
                    { id: 'owner-payables', type: 'link', label: 'Owner / Supplier Payables', to: '/invoices?view=rental-payable', match: ['/invoices'], access: tenantAccess(['invoice', 'vehicle-rental']) },
                    { id: 'rental-invoices', type: 'link', label: 'Customer Invoices', to: '/invoices?view=rental-customer', match: ['/invoices'], access: tenantAccess(['invoice', 'vehicle-rental']) },
                    { id: 'rental-settlements', type: 'link', label: 'Settlements', to: '/payments?view=rental', match: ['/payments'], exclude: ['/payments/create', '/payments/cheque-templates'], access: tenantAccess(['payment', 'vehicle-rental']) },
                ],
            },
        ],
    },
    {
        id: 'finance',
        label: 'Finance',
        items: [
            { id: 'invoices', type: 'link', label: 'Invoices', to: '/invoices', icon: 'invoice', access: tenantAccess(['invoice']) },
            { id: 'payments', type: 'link', label: 'Payments', to: '/payments', icon: 'payment', exclude: ['/payments/create', '/payments/cheque-templates'], access: tenantAccess(['payment']) },
            { id: 'vouchers', type: 'link', label: 'Vouchers', to: '/vouchers', icon: 'voucher', access: tenantAccess(['voucher']) },
        ],
    },
    {
        id: 'administration',
        label: 'Administration',
        items: [
            {
                id: 'users-access',
                type: 'link',
                label: 'Users & Access',
                to: '/administration/access',
                icon: 'users',
                access: {
                    ...tenantAccess(['user']),
                    roles: ['super admin'],
                    permissionPrefixes: ['user.', 'users.', 'role.', 'roles.', 'permission.', 'permissions.'],
                },
            },
            { id: 'settings', type: 'link', label: 'Settings', to: '/settings', icon: 'settings', access: tenantAccess(['configuration']) },
        ],
    },
];
