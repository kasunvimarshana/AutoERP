import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import MainLayout from '../layouts/MainLayout.vue';
import AuthLayout from '../layouts/AuthLayout.vue';

// Lazy load views
const Dashboard = () => import('../modules/dashboard/views/Dashboard.vue');
const Login = () => import('../modules/auth/views/Login.vue');
const Register = () => import('../modules/auth/views/Register.vue');
const CustomerList = () => import('../modules/customers/views/CustomerList.vue');
const CustomerForm = () => import('../modules/customers/views/CustomerForm.vue');
const ProductList = () => import('../modules/products/views/ProductList.vue');
const ProductForm = () => import('../modules/products/views/ProductForm.vue');
const StockList = () => import('../modules/inventory/views/StockList.vue');
const StockAdjustment = () => import('../modules/inventory/views/StockAdjustment.vue');

const routes = [
    {
        path: '/',
        redirect: '/dashboard',
    },
    {
        path: '/',
        component: AuthLayout,
        meta: { guest: true },
        children: [
            {
                path: '/login',
                name: 'login',
                component: Login,
            },
            {
                path: '/register',
                name: 'register',
                component: Register,
            },
        ],
    },
    {
        path: '/',
        component: MainLayout,
        meta: { requiresAuth: true },
        children: [
            {
                path: '/dashboard',
                name: 'dashboard',
                component: Dashboard,
            },
            {
                path: '/customers',
                name: 'customers',
                component: CustomerList,
            },
            {
                path: '/customers/create',
                name: 'customers.create',
                component: CustomerForm,
            },
            {
                path: '/customers/:id/edit',
                name: 'customers.edit',
                component: CustomerForm,
            },
            {
                path: '/products',
                name: 'products',
                component: ProductList,
            },
            {
                path: '/products/create',
                name: 'products.create',
                component: ProductForm,
            },
            {
                path: '/products/:id/edit',
                name: 'products.edit',
                component: ProductForm,
            },
            {
                path: '/inventory',
                name: 'inventory',
                component: StockList,
            },
            {
                path: '/inventory/adjustment',
                name: 'inventory.adjustment',
                component: StockAdjustment,
            },
            {
                path: '/pos',
                name: 'pos',
                component: () => import('../modules/pos/views/POS.vue'),
            },
            {
                path: '/billing',
                name: 'billing',
                component: () => import('../modules/billing/views/BillingList.vue'),
            },
            {
                path: '/branches',
                name: 'branches',
                component: () => import('../modules/branches/views/BranchList.vue'),
            },
            {
                path: '/fleet',
                name: 'fleet',
                component: () => import('../modules/fleet/views/FleetList.vue'),
            },
            {
                path: '/crm',
                name: 'crm',
                component: () => import('../modules/crm/views/CRMDashboard.vue'),
            },
            {
                path: '/analytics',
                name: 'analytics',
                component: () => import('../modules/analytics/views/Analytics.vue'),
            },
        ],
    },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

// Navigation guards
router.beforeEach(async (to, from, next) => {
    const authStore = useAuthStore();
    
    // Check authentication on first load
    if (!authStore.user && authStore.token) {
        await authStore.checkAuth();
    }
    
    if (to.meta.requiresAuth && !authStore.isAuthenticated) {
        next({ name: 'login' });
    } else if (to.meta.guest && authStore.isAuthenticated) {
        next({ name: 'dashboard' });
    } else {
        next();
    }
});

export default router;
