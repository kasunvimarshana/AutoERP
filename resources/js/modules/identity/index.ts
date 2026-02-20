/**
 * Identity module — users, roles, multi-organization, multi-tenant support.
 */
import type { ModuleDefinition } from '@/core/registry/moduleRegistry';

export const identityModule: ModuleDefinition = {
  id: 'identity',
  name: 'Identity',
  featureFlag: 'identity',
  permissions: ['user.view', 'user.create', 'user.update', 'user.delete'],
  navItems: [
    {
      to: '/users',
      label: 'Users & Roles',
      icon: '👥',
      permission: 'user.view',
      group: 'Administration',
    },
  ],
  routes: [
    {
      path: 'users',
      name: 'users',
      component: () => import('@/pages/UsersPage.vue'),
      meta: { permission: 'user.view', module: 'identity' },
    },
  ],
};
