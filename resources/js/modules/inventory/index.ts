/**
 * Inventory module — Product CRUD, stock levels, warehouses, low-stock alerts.
 */
import type { ModuleDefinition } from '@/core/registry/moduleRegistry';

export const inventoryModule: ModuleDefinition = {
  id: 'inventory',
  name: 'Inventory',
  featureFlag: 'inventory',
  permissions: [
    'product.view',
    'product.create',
    'product.update',
    'product.delete',
    'inventory.view',
  ],
  navItems: [
    {
      to: '/products',
      label: 'Products',
      icon: '📦',
      permission: 'product.view',
      group: 'Products & Stock',
    },
    {
      to: '/inventory',
      label: 'Inventory',
      icon: '🏭',
      permission: 'inventory.view',
      group: 'Products & Stock',
    },
  ],
  routes: [
    {
      path: 'products',
      name: 'products',
      component: () => import('@/pages/ProductsPage.vue'),
      meta: { permission: 'product.view', module: 'inventory' },
    },
    {
      path: 'inventory',
      name: 'inventory',
      component: () => import('@/pages/InventoryPage.vue'),
      meta: { permission: 'inventory.view', module: 'inventory' },
    },
  ],
};
