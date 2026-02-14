import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@stores/auth'

const routes = [
  {
    path: '/login',
    name: 'login',
    component: () => import('@modules/auth/views/LoginView.vue'),
    meta: { guest: true }
  },
  {
    path: '/register',
    name: 'register',
    component: () => import('@modules/auth/views/RegisterView.vue'),
    meta: { guest: true }
  },
  {
    path: '/',
    component: () => import('@layouts/DashboardLayout.vue'),
    meta: { requiresAuth: true },
    children: [
      {
        path: '',
        name: 'dashboard',
        component: () => import('@/views/DashboardView.vue')
      },
      {
        path: 'crm/customers',
        name: 'customers',
        component: () => import('@modules/crm/views/CustomerListView.vue')
      },
      {
        path: 'crm/customers/create',
        name: 'customers-create',
        component: () => import('@modules/crm/views/CustomerFormView.vue')
      },
      {
        path: 'crm/customers/:id',
        name: 'customers-view',
        component: () => import('@modules/crm/views/CustomerDetailView.vue')
      },
      {
        path: 'crm/customers/:id/edit',
        name: 'customers-edit',
        component: () => import('@modules/crm/views/CustomerFormView.vue')
      },
      {
        path: 'crm/contacts',
        name: 'contacts',
        component: () => import('@modules/crm/views/ContactListView.vue')
      },
      {
        path: 'crm/contacts/create',
        name: 'contacts-create',
        component: () => import('@modules/crm/views/ContactFormView.vue')
      },
      {
        path: 'crm/contacts/:id/edit',
        name: 'contacts-edit',
        component: () => import('@modules/crm/views/ContactFormView.vue')
      },
      {
        path: 'crm/vehicles',
        name: 'vehicles',
        component: () => import('@modules/crm/views/VehicleListView.vue')
      },
      {
        path: 'crm/vehicles/create',
        name: 'vehicles-create',
        component: () => import('@modules/crm/views/VehicleFormView.vue')
      },
      {
        path: 'crm/vehicles/:id/edit',
        name: 'vehicles-edit',
        component: () => import('@modules/crm/views/VehicleFormView.vue')
      },
      {
        path: 'inventory/products',
        name: 'products',
        component: () => import('@modules/inventory/views/ProductListView.vue')
      },
      {
        path: 'inventory/products/create',
        name: 'products-create',
        component: () => import('@modules/inventory/views/ProductFormView.vue')
      },
      {
        path: 'inventory/products/:id',
        name: 'products-view',
        component: () => import('@modules/inventory/views/ProductDetailView.vue')
      },
      {
        path: 'inventory/products/:id/edit',
        name: 'products-edit',
        component: () => import('@modules/inventory/views/ProductFormView.vue')
      },
      {
        path: 'billing/invoices',
        name: 'invoices',
        component: () => import('@modules/billing/views/InvoiceListView.vue')
      },
      {
        path: 'billing/invoices/create',
        name: 'invoices-create',
        component: () => import('@modules/billing/views/InvoiceFormView.vue')
      },
      {
        path: 'billing/invoices/:id',
        name: 'invoices-view',
        component: () => import('@modules/billing/views/InvoiceDetailView.vue')
      },
      {
        path: 'billing/invoices/:id/edit',
        name: 'invoices-edit',
        component: () => import('@modules/billing/views/InvoiceFormView.vue')
      },
      {
        path: 'appointments',
        name: 'appointments',
        component: () => import('@modules/appointments/views/AppointmentListView.vue')
      },
      {
        path: 'appointments/create',
        name: 'appointments-create',
        component: () => import('@modules/appointments/views/AppointmentFormView.vue')
      },
      {
        path: 'appointments/:id/edit',
        name: 'appointments-edit',
        component: () => import('@modules/appointments/views/AppointmentFormView.vue')
      }
    ]
  },
  {
    path: '/forbidden',
    name: 'forbidden',
    component: () => import('@/views/ForbiddenView.vue')
  },
  {
    path: '/:pathMatch(.*)*',
    name: 'not-found',
    component: () => import('@/views/NotFoundView.vue')
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

router.beforeEach(async (to, from, next) => {
  const authStore = useAuthStore()
  
  if (to.meta.requiresAuth && !authStore.isAuthenticated) {
    next({ name: 'login', query: { redirect: to.fullPath } })
  } else if (to.meta.guest && authStore.isAuthenticated) {
    next({ name: 'dashboard' })
  } else {
    if (authStore.isAuthenticated && !authStore.user) {
      await authStore.fetchUser()
    }
    next()
  }
})

export default router
