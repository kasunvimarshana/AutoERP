import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '@/stores/auth';

const routes = [
    {
        path: '/',
        redirect: '/dashboard',
    },
    {
        path: '/login',
        name: 'login',
        component: () => import('@/pages/auth/Login.vue'),
        meta: { guest: true },
    },
    {
        path: '/register',
        name: 'register',
        component: () => import('@/pages/auth/Register.vue'),
        meta: { guest: true },
    },
    {
        path: '/forgot-password',
        name: 'forgot-password',
        component: () => import('@/pages/auth/ForgotPassword.vue'),
        meta: { guest: true },
    },
    {
        path: '/dashboard',
        name: 'dashboard',
        component: () => import('@/pages/Dashboard.vue'),
        meta: { requiresAuth: true },
    },
    {
        path: '/crm',
        name: 'crm',
        component: () => import('@/pages/crm/Index.vue'),
        meta: { requiresAuth: true },
    },
    {
        path: '/sales',
        name: 'sales',
        component: () => import('@/pages/sales/Index.vue'),
        meta: { requiresAuth: true },
    },
    {
        path: '/inventory',
        name: 'inventory',
        component: () => import('@/pages/inventory/Index.vue'),
        meta: { requiresAuth: true },
    },
    {
        path: '/purchase',
        name: 'purchase',
        component: () => import('@/pages/purchase/Index.vue'),
        meta: { requiresAuth: true },
    },
    {
        path: '/accounting',
        name: 'accounting',
        component: () => import('@/pages/accounting/Index.vue'),
        meta: { requiresAuth: true },
    },
    {
        path: '/hr',
        name: 'hr',
        component: () => import('@/pages/hr/Index.vue'),
        meta: { requiresAuth: true },
    },
    {
        path: '/projects',
        name: 'projects',
        component: () => import('@/pages/projects/Index.vue'),
        meta: { requiresAuth: true },
    },
    {
        path: '/helpdesk',
        name: 'helpdesk',
        component: () => import('@/pages/helpdesk/Index.vue'),
        meta: { requiresAuth: true },
    },
    {
        path: '/settings',
        name: 'settings',
        component: () => import('@/pages/settings/Index.vue'),
        meta: { requiresAuth: true },
    },
    {
        path: '/manufacturing',
        name: 'manufacturing',
        component: () => import('@/pages/manufacturing/Index.vue'),
        meta: { requiresAuth: true },
    },
    {
        path: '/pos',
        name: 'pos',
        component: () => import('@/pages/pos/Index.vue'),
        meta: { requiresAuth: true },
    },
    {
        path: '/pos/terminal/:sessionId',
        name: 'pos-terminal',
        component: () => import('@/pages/pos/Terminal.vue'),
        meta: { requiresAuth: true },
    },
    {
        path: '/logistics',
        name: 'logistics',
        component: () => import('@/pages/logistics/Index.vue'),
        meta: { requiresAuth: true },
    },
    {
        path: '/leave',
        name: 'leave',
        component: () => import('@/pages/leave/Index.vue'),
        meta: { requiresAuth: true },
    },
    {
        path: '/expense',
        name: 'expense',
        component: () => import('@/pages/expense/Index.vue'),
        meta: { requiresAuth: true },
    },
    {
        path: '/asset',
        name: 'asset',
        component: () => import('@/pages/asset/Index.vue'),
        meta: { requiresAuth: true },
    },
    {
        path: '/recruitment',
        name: 'recruitment',
        component: () => import('@/pages/recruitment/Index.vue'),
        meta: { requiresAuth: true },
    },
    {
        path: '/fleet',
        name: 'fleet',
        component: () => import('@/pages/fleet/Index.vue'),
        meta: { requiresAuth: true },
    },
    {
        path: '/budget',
        name: 'budget',
        component: () => import('@/pages/budget/Index.vue'),
        meta: { requiresAuth: true },
    },
    {
        path: '/documents',
        name: 'documents',
        component: () => import('@/pages/documents/Index.vue'),
        meta: { requiresAuth: true },
    },
    {
        path: '/contracts',
        name: 'contracts',
        component: () => import('@/pages/contracts/Index.vue'),
        meta: { requiresAuth: true },
    },
    {
        path: '/tax',
        name: 'tax',
        component: () => import('@/pages/tax/Index.vue'),
        meta: { requiresAuth: true },
    },
    {
        path: '/maintenance',
        name: 'maintenance',
        component: () => import('@/pages/maintenance/Index.vue'),
        meta: { requiresAuth: true },
    },
    {
        path: '/quality-control',
        name: 'quality-control',
        component: () => import('@/pages/quality-control/Index.vue'),
        meta: { requiresAuth: true },
    },
    {
        path: '/field-service',
        name: 'field-service',
        component: () => import('@/pages/field-service/Index.vue'),
        meta: { requiresAuth: true },
    },
    {
        path: '/ecommerce',
        name: 'ecommerce',
        component: () => import('@/pages/ecommerce/Index.vue'),
        meta: { requiresAuth: true },
    },
    {
        path: '/subscriptions',
        name: 'subscriptions',
        component: () => import('@/pages/subscriptions/Index.vue'),
        meta: { requiresAuth: true },
    },
    {
        path: '/workflows',
        name: 'workflows',
        component: () => import('@/pages/workflows/Index.vue'),
        meta: { requiresAuth: true },
    },
    {
        path: '/integration',
        name: 'integration',
        component: () => import('@/pages/integration/Index.vue'),
        meta: { requiresAuth: true },
    },
    {
        path: '/localisation',
        name: 'localisation',
        component: () => import('@/pages/localisation/Index.vue'),
        meta: { requiresAuth: true },
    },
    {
        path: '/communication',
        name: 'communication',
        component: () => import('@/pages/communication/Index.vue'),
        meta: { requiresAuth: true },
    },
    {
        path: '/reporting',
        name: 'reporting',
        component: () => import('@/pages/reporting/Index.vue'),
        meta: { requiresAuth: true },
    },
    {
        path: '/tenant',
        name: 'tenant',
        component: () => import('@/pages/tenant/Index.vue'),
        meta: { requiresAuth: true },
    },
    {
        path: '/users',
        name: 'users',
        component: () => import('@/pages/users/Index.vue'),
        meta: { requiresAuth: true },
    },
    {
        path: '/audit',
        name: 'audit',
        component: () => import('@/pages/audit/Index.vue'),
        meta: { requiresAuth: true },
    },
    {
        path: '/currency',
        name: 'currency',
        component: () => import('@/pages/currency/Index.vue'),
        meta: { requiresAuth: true },
    },
    {
        path: '/media',
        name: 'media',
        component: () => import('@/pages/media/Index.vue'),
        meta: { requiresAuth: true },
    },
    {
        path: '/notifications',
        name: 'notifications',
        component: () => import('@/pages/notifications/Index.vue'),
        meta: { requiresAuth: true },
    },
    // Catch-all — must be last
    {
        path: '/:pathMatch(.*)*',
        name: 'not-found',
        component: () => import('@/pages/errors/NotFound.vue'),
    },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

router.beforeEach((to) => {
    const auth = useAuthStore();

    if (to.meta.requiresAuth && !auth.isAuthenticated) {
        return { name: 'login' };
    }

    if (to.meta.guest && auth.isAuthenticated) {
        return { name: 'dashboard' };
    }
});

export default router;
