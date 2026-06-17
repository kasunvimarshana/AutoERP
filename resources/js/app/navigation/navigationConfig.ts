import type { NavigationSection } from './navigationTypes';
import { accessPermissions, protectedAccessRoles } from '@/modules/access/accessPermissions';
import { itemPermissions } from '@/modules/item/itemPermissions';

const tenantAccess = (modules: NonNullable<NavigationSection['items'][number]['access']>['modules']) => ({
    requiresTenant: true,
    modules,
});
const operationalAccess = (modules: NonNullable<NavigationSection['items'][number]['access']>['modules']) => ({
    ...tenantAccess(modules),
    requiresOrganizationUnit: true,
});

const accessControlPermissions = [
    accessPermissions.usersView,
    accessPermissions.usersCreate,
    accessPermissions.usersUpdate,
    accessPermissions.usersAssignRoles,
    accessPermissions.usersManageOrganizationAccess,
    accessPermissions.rolesView,
    accessPermissions.rolesCreate,
    accessPermissions.rolesUpdate,
    accessPermissions.rolesAssignPermissions,
    accessPermissions.permissionsView,
];

const vehicleRentalPermissions = [
    'vehicle-rental.reservations.manage',
    'vehicle-rental.agreements.manage',
    'vehicle-rental.allocations.manage',
    'vehicle-rental.inspections.record',
    'vehicle-rental.links.manage',
    'vehicle-rental.links.approve',
    'vehicle-rental.usage.approve',
    'vehicle-rental.usage.record',
    'vehicle-rental.usage.mileage-override',
    'vehicle-rental.usage.classify-holiday',
    'vehicle-rental.expenses.approve',
    'vehicle-rental.expenses.record',
    'vehicle-rental.charges.generate',
    'vehicle-rental.charges.approve',
    'vehicle-rental.financial.create',
];

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
                id: 'vehicle',
                type: 'module',
                label: 'Vehicle',
                icon: 'vehicle',
                access: tenantAccess(['vehicle']),
                children: [
                    { id: 'vehicle-makes', type: 'link', label: 'Makes', to: '/vehicles/makes', match: ['/vehicles/makes'], access: tenantAccess(['vehicle']) },
                    { id: 'vehicle-types', type: 'link', label: 'Types', to: '/vehicles/types', match: ['/vehicles/types'], access: tenantAccess(['vehicle']) },
                    { id: 'vehicle-categories', type: 'link', label: 'Categories', to: '/vehicles/categories', match: ['/vehicles/categories'], access: tenantAccess(['vehicle']) },
                    { id: 'vehicle-models', type: 'link', label: 'Models', to: '/vehicles/models', match: ['/vehicles/models'], access: tenantAccess(['vehicle']) },
                    { id: 'vehicle-list', type: 'link', label: 'Vehicles', to: '/vehicles', match: ['/vehicles'], access: tenantAccess(['vehicle']) },
                ],
            },
            {
                id: 'items',
                type: 'module',
                label: 'Items',
                icon: 'item',
                access: {
                    ...tenantAccess(['item']),
                    permissions: [
                        itemPermissions.view,
                        itemPermissions.create,
                        itemPermissions.manageCategories,
                        itemPermissions.manageBrands,
                    ],
                },
                children: [
                    {
                        id: 'item-categories',
                        type: 'link',
                        label: 'Categories',
                        to: '/item-categories',
                        match: ['/item-categories'],
                        access: { ...tenantAccess(['item']), permissions: [itemPermissions.view, itemPermissions.manageCategories] },
                    },
                    {
                        id: 'item-category-create',
                        type: 'link',
                        label: 'Create Category',
                        to: '/item-categories/create',
                        match: ['/item-categories/create'],
                        access: { ...tenantAccess(['item']), permissions: [itemPermissions.manageCategories] },
                    },
                    {
                        id: 'item-brands',
                        type: 'link',
                        label: 'Brands',
                        to: '/item-brands',
                        match: ['/item-brands'],
                        access: { ...tenantAccess(['item']), permissions: [itemPermissions.view, itemPermissions.manageBrands] },
                    },
                    {
                        id: 'item-brand-create',
                        type: 'link',
                        label: 'Create Brand',
                        to: '/item-brands/create',
                        match: ['/item-brands/create'],
                        access: { ...tenantAccess(['item']), permissions: [itemPermissions.manageBrands] },
                    },
                    {
                        id: 'item-list',
                        type: 'link',
                        label: 'Items',
                        to: '/items',
                        match: ['/items'],
                        access: { ...tenantAccess(['item']), permissions: [itemPermissions.view] },
                    },
                    {
                        id: 'item-create',
                        type: 'link',
                        label: 'Create Item',
                        to: '/items/create',
                        match: ['/items/create'],
                        access: { ...tenantAccess(['item']), permissions: [itemPermissions.create] },
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
                    roles: [protectedAccessRoles.superAdmin],
                    permissions: accessControlPermissions,
                },
                children: [
                    {
                        id: 'user-list',
                        type: 'link',
                        label: 'User List',
                        to: '/access/users',
                        access: {
                            ...tenantAccess(['user']),
                            permissions: [accessPermissions.usersView],
                        },
                    },
                    {
                        id: 'roles',
                        type: 'link',
                        label: 'Roles',
                        to: '/access/roles',
                        icon: 'role',
                        access: {
                            ...tenantAccess(['user']),
                            permissions: [accessPermissions.rolesView],
                        },
                    },
                    {
                        id: 'permissions',
                        type: 'link',
                        label: 'Permissions',
                        to: '/access/permissions',
                        icon: 'permission',
                        access: {
                            ...tenantAccess(['user']),
                            permissions: [accessPermissions.permissionsView],
                        },
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
                access: operationalAccess(['purchase']),
                children: [
                    { id: 'fast-purchase', type: 'link', label: 'Fast Purchase', to: '/purchase/fast-purchase', match: ['/purchase/fast-purchase'], access: operationalAccess(['purchase']) },
                    { id: 'purchase-orders', type: 'link', label: 'Purchase Orders', to: '/purchase/orders', match: ['/purchase/orders'], access: operationalAccess(['purchase']) },
                    { id: 'goods-receipts', type: 'link', label: 'Goods Receipts', to: '/purchase/goods-receipts', match: ['/purchase/goods-receipts'], access: operationalAccess(['purchase']) },
                    { id: 'purchase-returns', type: 'link', label: 'Purchase Returns', to: '/purchase/returns', match: ['/purchase/returns', '/purchase/manual-supplier-returns'], access: operationalAccess(['purchase']) },
                    { id: 'supplier-invoices', type: 'link', label: 'Supplier Invoices', to: '/invoices?view=supplier', match: ['/invoices'], access: operationalAccess(['invoice', 'purchase']) },
                    { id: 'supplier-payments', type: 'link', label: 'Supplier Payments', to: '/payments?view=supplier', match: ['/payments'], exclude: ['/payments/create', '/payments/cheque-templates'], access: operationalAccess(['payment', 'purchase']) },
                ],
            },
            {
                id: 'sales',
                type: 'module',
                label: 'Sales',
                icon: 'sales',
                access: operationalAccess(['sales']),
                children: [
                    { id: 'fast-sales', type: 'link', label: 'Fast Sales', to: '/sales/fast-sales', match: ['/sales/fast-sales'], access: operationalAccess(['sales']) },
                    { id: 'sales-orders', type: 'link', label: 'Sales Orders', to: '/sales/orders', match: ['/sales/orders'], access: operationalAccess(['sales']) },
                    { id: 'goods-deliveries', type: 'link', label: 'Goods Deliveries', to: '/sales/deliveries', match: ['/sales/deliveries'], access: operationalAccess(['sales']) },
                    { id: 'sales-returns', type: 'link', label: 'Sales Returns', to: '/sales/returns', match: ['/sales/returns'], access: operationalAccess(['sales']) },
                    { id: 'customer-invoices', type: 'link', label: 'Customer Invoices', to: '/invoices?view=customer', match: ['/invoices'], access: operationalAccess(['invoice', 'sales']) },
                    { id: 'customer-receipts', type: 'link', label: 'Customer Receipts', to: '/payments?view=customer', match: ['/payments'], exclude: ['/payments/create', '/payments/cheque-templates'], access: operationalAccess(['payment', 'sales']) },
                ],
            },
            {
                id: 'vehicle-service',
                type: 'module',
                label: 'Vehicle Service',
                icon: 'service',
                access: operationalAccess(['vehicle-service']),
                children: [
                    { id: 'service-jobs', type: 'link', label: 'Service Jobs', to: '/vehicle-service/jobs', match: ['/vehicle-service/jobs'], access: operationalAccess(['vehicle-service']) },
                    { id: 'service-invoices', type: 'link', label: 'Service Invoices', to: '/invoices?view=service', match: ['/invoices'], access: operationalAccess(['invoice', 'vehicle-service']) },
                    { id: 'service-receipts', type: 'link', label: 'Customer Receipts', to: '/payments?view=service', match: ['/payments'], exclude: ['/payments/create', '/payments/cheque-templates'], access: operationalAccess(['payment', 'vehicle-service']) },
                ],
            },
            {
                id: 'vehicle-rental',
                type: 'module',
                label: 'Vehicle Rental',
                icon: 'rental',
                access: {
                    ...operationalAccess(['vehicle-rental']),
                    permissions: vehicleRentalPermissions,
                },
                children: [
                    { id: 'owner-agreements', type: 'link', label: 'Owner / Supplier Agreements', to: '/vehicle-rental/agreements?direction=inbound', match: ['/vehicle-rental/agreements'], access: operationalAccess(['vehicle-rental']) },
                    { id: 'customer-agreements', type: 'link', label: 'Customer Agreements', to: '/vehicle-rental/agreements?direction=outbound', match: ['/vehicle-rental/agreements'], access: operationalAccess(['vehicle-rental']) },
                    { id: 'lessee-running-charts', type: 'link', label: 'Customer Running Charts', to: '/vehicle-rental/running-chart?mode=lessee', match: ['/vehicle-rental/running-chart'], access: operationalAccess(['vehicle-rental']) },
                    { id: 'lessor-running-charts', type: 'link', label: 'Owner / Supplier Running Charts', to: '/vehicle-rental/running-chart?mode=lessor', match: ['/vehicle-rental/running-chart'], access: operationalAccess(['vehicle-rental']) },
                    { id: 'linked-running-charts', type: 'link', label: 'Linked Running Charts', to: '/vehicle-rental/running-chart?mode=linked', match: ['/vehicle-rental/running-chart'], access: operationalAccess(['vehicle-rental']) },
                    { id: 'owner-payables', type: 'link', label: 'Owner / Supplier Payables', to: '/invoices?view=rental-payable', match: ['/invoices'], access: operationalAccess(['invoice', 'vehicle-rental']) },
                    { id: 'rental-invoices', type: 'link', label: 'Customer Invoices', to: '/invoices?view=rental-customer', match: ['/invoices'], access: operationalAccess(['invoice', 'vehicle-rental']) },
                    { id: 'rental-settlements', type: 'link', label: 'Settlements', to: '/payments?view=rental', match: ['/payments'], exclude: ['/payments/create', '/payments/cheque-templates'], access: operationalAccess(['payment', 'vehicle-rental']) },
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
                    roles: [protectedAccessRoles.superAdmin],
                    permissions: accessControlPermissions,
                },
            },
            { id: 'settings', type: 'link', label: 'Settings', to: '/settings', icon: 'settings', access: tenantAccess(['configuration']) },
        ],
    },
];
