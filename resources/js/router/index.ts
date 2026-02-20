/**
 * Application router — composes static routes with dynamically registered
 * module routes from the module registry. All authenticated routes live under
 * the AdminLayout shell. Public routes use AuthLayout.
 */
import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import { registerModule, getEnabledModules } from '@/core/registry/moduleRegistry';
import { allModules } from '@/modules';

// ─── Register all modules ─────────────────────────────────────────────────────
allModules.forEach(registerModule);

// ─── Collect module routes ────────────────────────────────────────────────────
const moduleRoutes = getEnabledModules().flatMap((m) => m.routes);

// ─── Router ───────────────────────────────────────────────────────────────────
const router = createRouter({
  history: createWebHistory(),
  routes: [
    // ── Auth shell ──────────────────────────────────────────────────────────
    {
      path: '/login',
      component: () => import('@/core/layouts/AuthLayout.vue'),
      meta: { requiresAuth: false },
      children: [
        {
          path: '',
          name: 'login',
          component: () => import('@/pages/LoginPage.vue'),
        },
      ],
    },

    // ── Admin shell ─────────────────────────────────────────────────────────
    {
      path: '/',
      component: () => import('@/core/layouts/AdminLayout.vue'),
      meta: { requiresAuth: true },
      children: [
        { path: '', redirect: '/dashboard' },
        {
          path: 'dashboard',
          name: 'dashboard',
          component: () => import('@/pages/DashboardPage.vue'),
        },
        // Module routes injected here
        ...moduleRoutes,
      ],
    },

    { path: '/:pathMatch(.*)*', redirect: '/' },
  ],
});

// ─── Navigation guard ────────────────────────────────────────────────────────
router.beforeEach((to) => {
  const auth = useAuthStore();

  // Redirect unauthenticated users to login
  if (to.meta.requiresAuth !== false && !auth.isAuthenticated) {
    return { name: 'login', query: { redirect: to.fullPath } };
  }

  // Redirect already-authenticated users away from login
  if (to.name === 'login' && auth.isAuthenticated) {
    return { name: 'dashboard' };
  }

  // Permission check (skip when user profile not yet loaded — profile loads on mount)
  const permission = to.meta.permission as string | undefined;
  if (permission && auth.user && !auth.hasPermission(permission)) {
    return { name: 'dashboard' };
  }
});

export default router;
