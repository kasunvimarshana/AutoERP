import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '../stores/auth';

/**
 * Vue Router Configuration
 * 
 * Features:
 * - JWT authentication guards
 * - RBAC/ABAC authorization
 * - Lazy loading for code splitting
 * - Metadata-driven route configuration
 */

const routes = [
    {
        path: '/',
        redirect: '/dashboard',
    },
    {
        path: '/login',
        name: 'login',
        component: () => import('../views/auth/Login.vue'),
        meta: { requiresGuest: true },
    },
    {
        path: '/register',
        name: 'register',
        component: () => import('../views/auth/Register.vue'),
        meta: { requiresGuest: true },
    },
    {
        path: '/dashboard',
        component: () => import('../views/dashboard/Layout.vue'),
        meta: { requiresAuth: true },
        children: [
            {
                path: '',
                name: 'dashboard',
                component: () => import('../views/dashboard/Dashboard.vue'),
            },
            // Core Module Routes
            {
                path: '/tenants',
                name: 'tenants',
                component: () => import('../modules/tenant/views/TenantList.vue'),
                meta: { permission: 'tenants.view' },
            },
            {
                path: '/organizations',
                name: 'organizations',
                component: () => import('../modules/tenant/views/OrganizationList.vue'),
                meta: { permission: 'organizations.view' },
            },
            // Auth Module Routes
            {
                path: '/users',
                name: 'users',
                component: () => import('../modules/auth/views/UserList.vue'),
                meta: { permission: 'users.view' },
            },
            {
                path: '/roles',
                name: 'roles',
                component: () => import('../modules/auth/views/RoleList.vue'),
                meta: { permission: 'roles.view' },
            },
            // Product Module Routes
            {
                path: '/products',
                name: 'products',
                component: () => import('../modules/product/views/ProductList.vue'),
                meta: { permission: 'products.view' },
            },
            {
                path: '/products/:id',
                name: 'product-detail',
                component: () => import('../modules/product/views/ProductDetail.vue'),
                meta: { permission: 'products.view' },
            },
            {
                path: '/categories',
                name: 'categories',
                component: () => import('../modules/product/views/CategoryList.vue'),
                meta: { permission: 'categories.view' },
            },
            // CRM Module Routes
            {
                path: '/customers',
                name: 'customers',
                component: () => import('../modules/crm/views/CustomerList.vue'),
                meta: { permission: 'customers.view' },
            },
            {
                path: '/leads',
                name: 'leads',
                component: () => import('../modules/crm/views/LeadList.vue'),
                meta: { permission: 'leads.view' },
            },
            {
                path: '/opportunities',
                name: 'opportunities',
                component: () => import('../modules/crm/views/OpportunityList.vue'),
                meta: { permission: 'opportunities.view' },
            },
            // Sales Module Routes
            {
                path: '/quotations',
                name: 'quotations',
                component: () => import('../modules/sales/views/QuotationList.vue'),
                meta: { permission: 'quotations.view' },
            },
            {
                path: '/orders',
                name: 'orders',
                component: () => import('../modules/sales/views/OrderList.vue'),
                meta: { permission: 'orders.view' },
            },
            {
                path: '/invoices',
                name: 'invoices',
                component: () => import('../modules/sales/views/InvoiceList.vue'),
                meta: { permission: 'invoices.view' },
            },
            // Purchase Module Routes
            {
                path: '/vendors',
                name: 'vendors',
                component: () => import('../modules/purchase/views/VendorList.vue'),
                meta: { permission: 'vendors.view' },
            },
            {
                path: '/purchase-orders',
                name: 'purchase-orders',
                component: () => import('../modules/purchase/views/PurchaseOrderList.vue'),
                meta: { permission: 'purchase_orders.view' },
            },
            {
                path: '/bills',
                name: 'bills',
                component: () => import('../modules/purchase/views/BillList.vue'),
                meta: { permission: 'bills.view' },
            },
            // Inventory Module Routes
            {
                path: '/warehouses',
                name: 'warehouses',
                component: () => import('../modules/inventory/views/WarehouseList.vue'),
                meta: { permission: 'warehouses.view' },
            },
            {
                path: '/stock',
                name: 'stock',
                component: () => import('../modules/inventory/views/StockList.vue'),
                meta: { permission: 'stock.view' },
            },
            // Accounting Module Routes
            {
                path: '/accounts',
                name: 'accounts',
                component: () => import('../modules/accounting/views/AccountList.vue'),
                meta: { permission: 'accounts.view' },
            },
            {
                path: '/journal-entries',
                name: 'journal-entries',
                component: () => import('../modules/accounting/views/JournalEntryList.vue'),
                meta: { permission: 'journal_entries.view' },
            },
            // Billing Module Routes
            {
                path: '/plans',
                name: 'plans',
                component: () => import('../modules/billing/views/PlanList.vue'),
                meta: { permission: 'plans.view' },
            },
            {
                path: '/subscriptions',
                name: 'subscriptions',
                component: () => import('../modules/billing/views/SubscriptionList.vue'),
                meta: { permission: 'subscriptions.view' },
            },
            // Reporting Module Routes
            {
                path: '/reports',
                name: 'reports',
                component: () => import('../modules/reporting/views/ReportList.vue'),
                meta: { permission: 'reports.view' },
            },
            {
                path: '/dashboards',
                name: 'custom-dashboards',
                component: () => import('../modules/reporting/views/DashboardList.vue'),
                meta: { permission: 'dashboards.view' },
            },
            // Document Module Routes
            {
                path: '/documents',
                name: 'documents',
                component: () => import('../modules/document/views/DocumentList.vue'),
                meta: { permission: 'documents.view' },
            },
            // Workflow Module Routes
            {
                path: '/workflows',
                name: 'workflows',
                component: () => import('../modules/workflow/views/WorkflowList.vue'),
                meta: { permission: 'workflows.view' },
            },
            // Notification Module Routes
            {
                path: '/notifications',
                name: 'notifications',
                component: () => import('../modules/notification/views/NotificationList.vue'),
            },
            // Settings
            {
                path: '/settings',
                name: 'settings',
                component: () => import('../views/dashboard/Settings.vue'),
            },
        ],
    },
    {
        path: '/:pathMatch(.*)*',
        name: 'not-found',
        component: () => import('../views/NotFound.vue'),
    },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

// Global navigation guard - Authentication
router.beforeEach(async (to, from, next) => {
    const authStore = useAuthStore();
    const requiresAuth = to.matched.some((record) => record.meta.requiresAuth);
    const requiresGuest = to.matched.some((record) => record.meta.requiresGuest);

    // Check if route requires authentication
    if (requiresAuth && !authStore.isAuthenticated) {
        next({ name: 'login', query: { redirect: to.fullPath } });
        return;
    }

    // Check if route requires guest (already logged in)
    if (requiresGuest && authStore.isAuthenticated) {
        next({ name: 'dashboard' });
        return;
    }

    // Check for permission-based access (RBAC/ABAC)
    if (to.meta.permission) {
        if (!authStore.hasPermission(to.meta.permission)) {
            // User doesn't have permission - redirect to dashboard
            console.warn(`Access denied: ${to.meta.permission}`);
            next({ name: 'dashboard' });
            return;
        }
    }

    // Check for role-based access
    if (to.meta.role) {
        if (!authStore.hasRole(to.meta.role)) {
            console.warn(`Access denied: requires role ${to.meta.role}`);
            next({ name: 'dashboard' });
            return;
        }
    }

    next();
});

export default router;
