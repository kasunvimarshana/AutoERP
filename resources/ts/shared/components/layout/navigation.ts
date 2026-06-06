export type NavigationItem = {
    disabled?: boolean;
    icon: 'customers' | 'dashboard' | 'finance' | 'invoice' | 'items' | 'payments' | 'purchase' | 'service' | 'settings' | 'suppliers' | 'uom' | 'users' | 'vehicles' | 'warehouse';
    label: string;
    to: string;
};

export type NavigationSection = {
    label: string;
    items: NavigationItem[];
};

export const navigation: NavigationSection[] = [
    {
        label: 'Workspace',
        items: [
            { icon: 'dashboard', label: 'Dashboard', to: '/dashboard' },
        ],
    },
    {
        label: 'Masters',
        items: [
            { icon: 'customers', label: 'Customers', to: '/customers' },
            { icon: 'suppliers', label: 'Suppliers', to: '/suppliers' },
            { icon: 'vehicles', label: 'Vehicles', to: '/vehicles' },
            { icon: 'items', label: 'Items', to: '/items' },
            { icon: 'uom', label: 'UOM', to: '/uoms' },
            { icon: 'warehouse', label: 'Warehouses', to: '/warehouses' },
        ],
    },
    {
        label: 'Operations',
        items: [
            { icon: 'purchase', label: 'Purchase orders', to: '/purchase/orders' },
            { icon: 'purchase', label: 'Goods receipts', to: '/purchase/grns' },
            { icon: 'purchase', label: 'Purchase returns', to: '/purchase/returns' },
            { icon: 'service', label: 'Service jobs', to: '/vehicle-service/jobs' },
            { icon: 'service', label: 'Service types', to: '/vehicle-service/types' },
        ],
    },
    {
        label: 'Finance',
        items: [
            { icon: 'invoice', label: 'Invoices', to: '/invoices' },
            { icon: 'payments', label: 'Payments', to: '/payments' },
            { icon: 'finance', label: 'Journal entries', to: '/finance/journals' },
        ],
    },
    {
        label: 'Administration',
        items: [
            { icon: 'users', label: 'Users', to: '/administration/users' },
            { icon: 'settings', label: 'Settings', to: '/settings' },
        ],
    },
];

export function currentNavigationItem(pathname: string) {
    return navigation
        .flatMap((section) => section.items)
        .filter((item) => pathname === item.to || pathname.startsWith(`${item.to}/`))
        .sort((left, right) => right.to.length - left.to.length)[0];
}
