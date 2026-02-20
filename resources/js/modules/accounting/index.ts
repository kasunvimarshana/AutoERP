/**
 * Accounting module — chart of accounts, journal entries, periods, payments.
 */
import type { ModuleDefinition } from '@/core/registry/moduleRegistry';

export const accountingModule: ModuleDefinition = {
  id: 'accounting',
  name: 'Accounting',
  featureFlag: 'accounting',
  permissions: ['accounting.view', 'accounting.create', 'accounting.update'],
  navItems: [
    {
      to: '/accounting',
      label: 'Accounting',
      icon: '📊',
      permission: 'accounting.view',
      group: 'Finance',
    },
  ],
  routes: [
    {
      path: 'accounting',
      name: 'accounting',
      component: () => import('@/pages/AccountingPage.vue'),
      meta: { permission: 'accounting.view', module: 'accounting' },
    },
  ],
};
