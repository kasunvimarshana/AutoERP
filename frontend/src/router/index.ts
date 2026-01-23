import { createRouter, createWebHistory } from 'vue-router'
import { authGuard, guestGuard, roleGuard, permissionGuard } from '@/middleware/auth'
import { useAuthStore } from '@/stores/auth'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    // Public routes
    {
      path: '/',
      name: 'home',
      component: () => import('../views/HomeView.vue'),
    },
    {
      path: '/about',
      name: 'about',
      component: () => import('../views/AboutView.vue'),
    },

    // Authentication routes (Guest only)
    {
      path: '/login',
      name: 'login',
      component: () => import('../views/auth/LoginView.vue'),
      beforeEnter: guestGuard,
      meta: { layout: 'auth' },
    },
    {
      path: '/register',
      name: 'register',
      component: () => import('../views/auth/RegisterView.vue'),
      beforeEnter: guestGuard,
      meta: { layout: 'auth' },
    },
    {
      path: '/forgot-password',
      name: 'forgot-password',
      component: () => import('../views/auth/ForgotPasswordView.vue'),
      beforeEnter: guestGuard,
      meta: { layout: 'auth' },
    },
    {
      path: '/reset-password',
      name: 'reset-password',
      component: () => import('../views/auth/ResetPasswordView.vue'),
      beforeEnter: guestGuard,
      meta: { layout: 'auth' },
    },

    // Protected routes (Authenticated only)
    {
      path: '/dashboard',
      name: 'dashboard',
      component: () => import('../views/DashboardView.vue'),
      beforeEnter: authGuard,
      meta: { layout: 'app', requiresAuth: true },
    },

    // Customer Management
    {
      path: '/customers',
      name: 'customers',
      component: () => import('../views/customers/CustomerListView.vue'),
      beforeEnter: authGuard,
      meta: { 
        layout: 'app', 
        requiresAuth: true,
        breadcrumb: 'Customers'
      },
    },
    {
      path: '/customers/create',
      name: 'customers-create',
      component: () => import('../views/customers/CustomerFormView.vue'),
      beforeEnter: authGuard,
      meta: { 
        layout: 'app', 
        requiresAuth: true,
        breadcrumb: 'Create Customer'
      },
    },
    {
      path: '/customers/:id',
      name: 'customers-detail',
      component: () => import('../views/customers/CustomerDetailView.vue'),
      beforeEnter: authGuard,
      meta: { 
        layout: 'app', 
        requiresAuth: true,
        breadcrumb: 'Customer Details'
      },
    },
    {
      path: '/customers/:id/edit',
      name: 'customers-edit',
      component: () => import('../views/customers/CustomerFormView.vue'),
      beforeEnter: authGuard,
      meta: { 
        layout: 'app', 
        requiresAuth: true,
        breadcrumb: 'Edit Customer'
      },
    },

    // Vehicle Management
    {
      path: '/vehicles',
      name: 'vehicles',
      component: () => import('../views/vehicles/VehicleListView.vue'),
      beforeEnter: authGuard,
      meta: { 
        layout: 'app', 
        requiresAuth: true,
        breadcrumb: 'Vehicles'
      },
    },

    // Appointments
    {
      path: '/appointments',
      name: 'appointments',
      component: () => import('../views/appointments/AppointmentListView.vue'),
      beforeEnter: authGuard,
      meta: { 
        layout: 'app', 
        requiresAuth: true,
        breadcrumb: 'Appointments'
      },
    },

    // Job Cards
    {
      path: '/job-cards',
      name: 'job-cards',
      component: () => import('../views/jobcards/JobCardListView.vue'),
      beforeEnter: authGuard,
      meta: { 
        layout: 'app', 
        requiresAuth: true,
        breadcrumb: 'Job Cards'
      },
    },

    // Inventory
    {
      path: '/inventory',
      name: 'inventory',
      component: () => import('../views/inventory/InventoryListView.vue'),
      beforeEnter: authGuard,
      meta: { 
        layout: 'app', 
        requiresAuth: true,
        breadcrumb: 'Inventory'
      },
    },

    // Invoicing
    {
      path: '/invoices',
      name: 'invoices',
      component: () => import('../views/invoices/InvoiceListView.vue'),
      beforeEnter: authGuard,
      meta: { 
        layout: 'app', 
        requiresAuth: true,
        breadcrumb: 'Invoices'
      },
    },

    // Settings (Admin only)
    {
      path: '/settings',
      name: 'settings',
      component: () => import('../views/settings/SettingsView.vue'),
      beforeEnter: [authGuard, roleGuard(['super_admin', 'admin'])],
      meta: { 
        layout: 'app', 
        requiresAuth: true,
        requiresRole: ['super_admin', 'admin'],
        breadcrumb: 'Settings'
      },
    },

    // Error pages
    {
      path: '/unauthorized',
      name: 'unauthorized',
      component: () => import('../views/errors/UnauthorizedView.vue'),
      meta: { layout: 'error' },
    },
    {
      path: '/:pathMatch(.*)*',
      name: 'not-found',
      component: () => import('../views/errors/NotFoundView.vue'),
      meta: { layout: 'error' },
    },
  ],
})

// Global navigation guard
router.beforeEach((to, from, next) => {
  // Initialize auth store if needed
  const authStore = useAuthStore()
  
  // You can add global logic here
  // For example, logging, analytics, etc.
  
  next()
})

export default router
