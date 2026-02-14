import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '@/stores/auth';

// Layouts
const AuthLayout = () => import('@/layouts/AuthLayout.vue');
const DashboardLayout = () => import('@/layouts/DashboardLayout.vue');

// Auth Pages
const Login = () => import('@/pages/auth/Login.vue');
const Register = () => import('@/pages/auth/Register.vue');
const ForgotPassword = () => import('@/pages/auth/ForgotPassword.vue');

// Dashboard Pages
const Dashboard = () => import('@/pages/Dashboard.vue');

// Inventory Pages
const ProductList = () => import('@/pages/inventory/ProductList.vue');
const ProductCreate = () => import('@/pages/inventory/ProductCreate.vue');
const ProductEdit = () => import('@/pages/inventory/ProductEdit.vue');
const StockManagement = () => import('@/pages/inventory/StockManagement.vue');

// CRM Pages
const CustomerList = () => import('@/pages/crm/CustomerList.vue');
const CustomerCreate = () => import('@/pages/crm/CustomerCreate.vue');
const CustomerEdit = () => import('@/pages/crm/CustomerEdit.vue');

// Master Data Pages
const Units = () => import('@/pages/master-data/Units.vue');
const Currencies = () => import('@/pages/master-data/Currencies.vue');
const TaxRates = () => import('@/pages/master-data/TaxRates.vue');

const routes = [
  {
    path: '/auth',
    component: AuthLayout,
    children: [
      {
        path: 'login',
        name: 'login',
        component: Login,
        meta: { guest: true },
      },
      {
        path: 'register',
        name: 'register',
        component: Register,
        meta: { guest: true },
      },
      {
        path: 'forgot-password',
        name: 'forgot-password',
        component: ForgotPassword,
        meta: { guest: true },
      },
    ],
  },
  {
    path: '/',
    component: DashboardLayout,
    meta: { requiresAuth: true },
    children: [
      {
        path: '',
        name: 'dashboard',
        component: Dashboard,
      },
      {
        path: 'inventory',
        children: [
          {
            path: 'products',
            name: 'products',
            component: ProductList,
          },
          {
            path: 'products/create',
            name: 'products.create',
            component: ProductCreate,
          },
          {
            path: 'products/:id/edit',
            name: 'products.edit',
            component: ProductEdit,
          },
          {
            path: 'stock',
            name: 'stock',
            component: StockManagement,
          },
        ],
      },
      {
        path: 'crm',
        children: [
          {
            path: 'customers',
            name: 'customers',
            component: CustomerList,
          },
          {
            path: 'customers/create',
            name: 'customers.create',
            component: CustomerCreate,
          },
          {
            path: 'customers/:id/edit',
            name: 'customers.edit',
            component: CustomerEdit,
          },
        ],
      },
      {
        path: 'master-data',
        children: [
          {
            path: 'units',
            name: 'master-data.units',
            component: Units,
          },
          {
            path: 'currencies',
            name: 'master-data.currencies',
            component: Currencies,
          },
          {
            path: 'tax-rates',
            name: 'master-data.tax-rates',
            component: TaxRates,
          },
        ],
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
  
  if (to.meta.requiresAuth && !authStore.isAuthenticated) {
    next({ name: 'login' });
  } else if (to.meta.guest && authStore.isAuthenticated) {
    next({ name: 'dashboard' });
  } else {
    next();
  }
});

export default router;
