import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '@/stores/auth';

const routes = [
  {
    path: '/',
    name: 'home',
    component: () => import('@/pages/Home.vue'),
    meta: { requiresAuth: false },
  },
  {
    path: '/login',
    name: 'login',
    component: () => import('@/pages/auth/Login.vue'),
    meta: { requiresAuth: false, guestOnly: true },
  },
  {
    path: '/register',
    name: 'register',
    component: () => import('@/pages/auth/Register.vue'),
    meta: { requiresAuth: false, guestOnly: true },
  },
  {
    path: '/forgot-password',
    name: 'forgot-password',
    component: () => import('@/pages/auth/ForgotPassword.vue'),
    meta: { requiresAuth: false, guestOnly: true },
  },
  {
    path: '/reset-password',
    name: 'reset-password',
    component: () => import('@/pages/auth/ResetPassword.vue'),
    meta: { requiresAuth: false, guestOnly: true },
  },
  {
    path: '/verify-email/:id/:hash',
    name: 'verify-email',
    component: () => import('@/pages/auth/VerifyEmail.vue'),
    meta: { requiresAuth: false },
  },
  {
    path: '/dashboard',
    name: 'dashboard',
    component: () => import('@/pages/Dashboard.vue'),
    meta: { requiresAuth: true },
  },
  {
    path: '/profile',
    name: 'profile',
    component: () => import('@/pages/Profile.vue'),
    meta: { requiresAuth: true },
  },
  {
    path: '/users',
    name: 'users',
    component: () => import('@/pages/users/UserList.vue'),
    meta: { requiresAuth: true },
  },
  // Customer Routes
  {
    path: '/customers',
    name: 'customers',
    component: () => import('@/pages/customers/CustomerList.vue'),
    meta: { requiresAuth: true },
  },
  {
    path: '/customers/:id',
    name: 'customer-detail',
    component: () => import('@/pages/customers/CustomerDetail.vue'),
    meta: { requiresAuth: true },
  },
  // Vehicle Routes
  {
    path: '/vehicles',
    name: 'vehicles',
    component: () => import('@/pages/vehicles/VehicleList.vue'),
    meta: { requiresAuth: true },
  },
  {
    path: '/vehicles/:id',
    name: 'vehicle-detail',
    component: () => import('@/pages/vehicles/VehicleDetail.vue'),
    meta: { requiresAuth: true },
  },
  // Service Record Routes
  {
    path: '/service-records',
    name: 'service-records',
    component: () => import('@/pages/service-records/ServiceRecordList.vue'),
    meta: { requiresAuth: true },
  },
  {
    path: '/service-records/:id',
    name: 'service-record-detail',
    component: () => import('@/pages/service-records/ServiceRecordDetail.vue'),
    meta: { requiresAuth: true },
  },
  {
    path: '/:pathMatch(.*)*',
    name: 'not-found',
    component: () => import('@/pages/NotFound.vue'),
  },
];

const router = createRouter({
  history: createWebHistory(),
  routes,
});

// Navigation guard
router.beforeEach((to, from, next) => {
  const authStore = useAuthStore();
  const requiresAuth = to.matched.some(record => record.meta.requiresAuth);
  const guestOnly = to.matched.some(record => record.meta.guestOnly);

  if (requiresAuth && !authStore.isAuthenticated) {
    // Redirect to login if route requires auth and user is not authenticated
    next({ name: 'login', query: { redirect: to.fullPath } });
  } else if (guestOnly && authStore.isAuthenticated) {
    // Redirect to dashboard if route is guest-only and user is authenticated
    next({ name: 'dashboard' });
  } else {
    next();
  }
});

export default router;
