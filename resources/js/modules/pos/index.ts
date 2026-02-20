/**
 * POS (Point of Sale) module — transactions, sales screen, barcode scanning,
 * tax, discount, payment, refunds, location summaries.
 */
import type { ModuleDefinition } from '@/core/registry/moduleRegistry';

export const posModule: ModuleDefinition = {
  id: 'pos',
  name: 'Point of Sale',
  featureFlag: 'pos',
  permissions: ['pos.view', 'order.view', 'invoice.view'],
  navItems: [
    { to: '/orders', label: 'Orders', icon: '🛒', permission: 'order.view', group: 'Sales' },
    { to: '/pos', label: 'Point of Sale', icon: '🖥️', permission: 'pos.view', group: 'Sales' },
    { to: '/invoices', label: 'Invoices', icon: '🧾', permission: 'invoice.view', group: 'Sales' },
  ],
  routes: [
    {
      path: 'orders',
      name: 'orders',
      component: () => import('@/pages/OrdersPage.vue'),
      meta: { permission: 'order.view', module: 'pos' },
    },
    {
      path: 'pos',
      name: 'pos',
      component: () => import('@/pages/PosPage.vue'),
      meta: { permission: 'pos.view', module: 'pos' },
    },
    {
      path: 'invoices',
      name: 'invoices',
      component: () => import('@/pages/InvoicesPage.vue'),
      meta: { permission: 'invoice.view', module: 'pos' },
    },
  ],
};
