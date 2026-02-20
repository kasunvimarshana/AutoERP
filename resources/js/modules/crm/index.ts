/**
 * CRM module — contacts, leads, opportunities, activity logs.
 */
import type { ModuleDefinition } from '@/core/registry/moduleRegistry';

export const crmModule: ModuleDefinition = {
  id: 'crm',
  name: 'CRM',
  featureFlag: 'crm',
  permissions: ['crm.view', 'crm.create', 'crm.update', 'crm.delete'],
  navItems: [{ to: '/crm', label: 'CRM', icon: '🤝', permission: 'crm.view', group: 'Customers' }],
  routes: [
    {
      path: 'crm',
      name: 'crm',
      component: () => import('@/pages/CrmPage.vue'),
      meta: { permission: 'crm.view', module: 'crm' },
    },
  ],
};
