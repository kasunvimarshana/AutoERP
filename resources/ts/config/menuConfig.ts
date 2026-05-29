export type MenuItem = {
    icon: string;
    label: string;
    path: string;
    children?: Array<Omit<MenuItem, 'icon'>>;
};

export type MenuGroup = {
    items: MenuItem[];
    label?: string;
};

export const menuConfig: MenuGroup[] = [
    {
        items: [{ icon: 'grid', label: 'Dashboard', path: '/dashboard' }],
    },
    {
        label: 'Master Data',
        items: [
            { icon: 'users', label: 'Customers', path: '/customers' },
            { icon: 'truck', label: 'Suppliers', path: '/suppliers' },
            { icon: 'id', label: 'Employees', path: '/hr/employees' },
            { icon: 'car', label: 'Vehicles', path: '/vehicles' },
            { icon: 'box', label: 'Items', path: '/items' },
            { icon: 'ruler', label: 'UOM', path: '/uom' },
            { icon: 'tag', label: 'Pricing', path: '/pricing' },
        ],
    },
    {
        label: 'Operations',
        items: [
            {
                icon: 'cart',
                label: 'Purchase',
                path: '/purchase',
                children: [
                    { label: 'Dashboard', path: '/purchase' },
                    { label: 'Orders', path: '/purchase/orders' },
                    { label: 'GRNs', path: '/purchase/grns' },
                    { label: 'Supplier Invoices', path: '/purchase/invoices' },
                    { label: 'Supplier Payments', path: '/purchase/payments' },
                    { label: 'Advances', path: '/purchase/advances' },
                    { label: 'Returns', path: '/purchase/returns' },
                    { label: 'Refunds', path: '/purchase/refunds' },
                ],
            },
            {
                icon: 'receipt',
                label: 'Sales',
                path: '/sales',
                children: [
                    { label: 'Dashboard', path: '/sales' },
                    { label: 'Orders', path: '/sales/orders' },
                    { label: 'Deliveries / GDN', path: '/sales/deliveries' },
                    { label: 'Customer Invoices', path: '/sales/invoices' },
                    { label: 'Customer Payments', path: '/sales/payments' },
                    { label: 'Advances', path: '/sales/advances' },
                    { label: 'Returns', path: '/sales/returns' },
                    { label: 'Refunds', path: '/sales/refunds' },
                ],
            },
            {
                icon: 'clipboard',
                label: 'Vehicle Service',
                path: '/vehicle-service',
                children: [
                    { label: 'Dashboard', path: '/vehicle-service' },
                    { label: 'Job Cards', path: '/vehicle-service/job-cards' },
                    { label: 'Service Invoices', path: '/vehicle-service/invoices' },
                    { label: 'Service Payments', path: '/vehicle-service/payments' },
                    { label: 'Service History', path: '/vehicle-service/history' },
                ],
            },
            {
                icon: 'key',
                label: 'Vehicle Rental',
                path: '/vehicle-rental',
                children: [
                    { label: 'Dashboard', path: '/vehicle-rental' },
                    { label: 'Availability', path: '/vehicle-rental/availability' },
                    { label: 'Agreements', path: '/vehicle-rental/agreements' },
                    { label: 'Running Charts', path: '/vehicle-rental/running-charts' },
                    { label: 'Rental Invoices', path: '/vehicle-rental/invoices' },
                    { label: 'Rental Payments', path: '/vehicle-rental/payments' },
                    { label: 'Provider Payables', path: '/vehicle-rental/provider-payables' },
                ],
            },
            { icon: 'file', label: 'Vouchers', path: '/vouchers' },
        ],
    },
    {
        label: 'Core',
        items: [
            { icon: 'doc', label: 'Documents', path: '/documents' },
            { icon: 'warehouse', label: 'Inventory', path: '/inventory/stock-levels' },
            { icon: 'bank', label: 'Finance', path: '/finance/accounts' },
            { icon: 'card', label: 'Payments', path: '/payments' },
        ],
    },
    {
        label: 'Administration',
        items: [
            { icon: 'building', label: 'Tenant', path: '/tenant' },
            { icon: 'users', label: 'Users & Permissions', path: '/settings/users' },
            { icon: 'grid', label: 'Organization Units', path: '/settings/organization-units' },
            { icon: 'settings', label: 'Configuration', path: '/configuration' },
            { icon: 'doc', label: 'Audit', path: '/audit' },
            { icon: 'settings', label: 'Settings', path: '/settings' },
        ],
    },
];
