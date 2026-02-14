import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/modules/auth/store/authStore'

const routes = [
  {
    path: '/auth',
    component: () => import('@/components/layouts/AuthLayout.vue'),
    children: [
      {
        path: 'login',
        name: 'Login',
        component: () => import('@/modules/auth/views/LoginView.vue')
      },
      {
        path: 'register',
        name: 'Register',
        component: () => import('@/modules/auth/views/RegisterView.vue')
      },
      {
        path: 'forgot-password',
        name: 'ForgotPassword',
        component: () => import('@/modules/auth/views/ForgotPasswordView.vue')
      }
    ]
  },
  {
    path: '/',
    component: () => import('@/components/layouts/DashboardLayout.vue'),
    meta: { requiresAuth: true },
    children: [
      {
        path: '',
        name: 'Dashboard',
        component: () => import('@/modules/dashboard/views/DashboardView.vue')
      },
      {
        path: 'tenants',
        name: 'Tenants',
        component: () => import('@/modules/tenant/views/TenantListView.vue')
      },
      {
        path: 'tenants/:id',
        name: 'TenantDetail',
        component: () => import('@/modules/tenant/views/TenantDetailView.vue')
      },
      {
        path: 'users',
        name: 'Users',
        component: () => import('@/modules/user/views/UserListView.vue')
      },
      {
        path: 'users/:id',
        name: 'UserDetail',
        component: () => import('@/modules/user/views/UserDetailView.vue')
      },
      {
        path: 'customers',
        name: 'Customers',
        component: () => import('@/modules/customer/views/CustomerListView.vue')
      },
      {
        path: 'customers/:id',
        name: 'CustomerDetail',
        component: () => import('@/modules/customer/views/CustomerDetailView.vue')
      },
      {
        path: 'vehicles',
        name: 'Vehicles',
        component: () => import('@/modules/vehicle/views/VehicleListView.vue')
      },
      {
        path: 'vehicles/:id',
        name: 'VehicleDetail',
        component: () => import('@/modules/vehicle/views/VehicleDetailView.vue')
      },
      {
        path: 'inventory',
        name: 'Inventory',
        component: () => import('@/modules/inventory/views/InventoryListView.vue')
      },
      {
        path: 'inventory/:id',
        name: 'InventoryDetail',
        component: () => import('@/modules/inventory/views/InventoryDetailView.vue')
      },
      {
        path: 'products',
        name: 'Products',
        component: () => import('@/modules/product/views/ProductListView.vue')
      },
      {
        path: 'products/:id',
        name: 'ProductDetail',
        component: () => import('@/modules/product/views/ProductDetailView.vue')
      },
      {
        path: 'orders',
        name: 'Orders',
        component: () => import('@/modules/order/views/OrderListView.vue')
      },
      {
        path: 'orders/:id',
        name: 'OrderDetail',
        component: () => import('@/modules/order/views/OrderDetailView.vue')
      },
      {
        path: 'invoices',
        name: 'Invoices',
        component: () => import('@/modules/invoice/views/InvoiceListView.vue')
      },
      {
        path: 'invoices/:id',
        name: 'InvoiceDetail',
        component: () => import('@/modules/invoice/views/InvoiceDetailView.vue')
      },
      {
        path: 'payments',
        name: 'Payments',
        component: () => import('@/modules/payment/views/PaymentListView.vue')
      },
      {
        path: 'payments/:id',
        name: 'PaymentDetail',
        component: () => import('@/modules/payment/views/PaymentDetailView.vue')
      },
      {
        path: 'reports',
        name: 'Reports',
        component: () => import('@/modules/reports/views/ReportsView.vue')
      }
    ]
  },
  {
    path: '/:pathMatch(.*)*',
    name: 'NotFound',
    component: () => import('@/components/layouts/MainLayout.vue')
  }
]

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes
})

router.beforeEach((to, from, next) => {
  const authStore = useAuthStore()
  
  if (to.meta.requiresAuth && !authStore.isAuthenticated) {
    next({ name: 'Login' })
  } else if (to.path === '/auth/login' && authStore.isAuthenticated) {
    next({ name: 'Dashboard' })
  } else {
    next()
  }
})

export default router
