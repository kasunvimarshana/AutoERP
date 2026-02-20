/**
 * Purchases module — suppliers, purchase orders, receipts, returns.
 */
import type { ModuleDefinition } from '@/core/registry/moduleRegistry';

export const purchasesModule: ModuleDefinition = {
  id: 'purchases',
  name: 'Purchases',
  featureFlag: 'purchases',
  permissions: ['purchase.view', 'purchase.create', 'purchase.update', 'purchase.delete'],
  navItems: [
    {
      to: '/purchases',
      label: 'Purchases',
      icon: '🚚',
      permission: 'purchase.view',
      group: 'Procurement',
    },
  ],
  routes: [
    {
      path: 'purchases',
      name: 'purchases',
      component: () => import('@/pages/PurchasesPage.vue'),
      meta: { permission: 'purchase.view', module: 'purchases' },
    },
  ],
};
