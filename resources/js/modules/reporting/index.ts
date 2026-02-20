/**
 * Reporting module — dynamic filterable reports for inventory, sales, finance.
 */
import type { ModuleDefinition } from '@/core/registry/moduleRegistry';

export const reportingModule: ModuleDefinition = {
  id: 'reporting',
  name: 'Reporting',
  featureFlag: 'reporting',
  permissions: ['report.view'],
  navItems: [
    { to: '/reports', label: 'Reports', icon: '📈', permission: 'report.view', group: 'Analytics' },
  ],
  routes: [
    {
      path: 'reports',
      name: 'reports',
      component: () => import('@/pages/ReportingPage.vue'),
      meta: { permission: 'report.view', module: 'reporting' },
    },
  ],
};
