/**
 * Barrel export for all ERP/CRM modules.
 * To register a new module:
 *   1. Create the module file in modules/{name}/index.ts
 *   2. Export it here
 *   3. Add it to the `allModules` array
 */
import { inventoryModule } from './inventory';
import { posModule } from './pos';
import { purchasesModule } from './purchases';
import { crmModule } from './crm';
import { accountingModule } from './accounting';
import { reportingModule } from './reporting';
import { identityModule } from './identity';
import type { ModuleDefinition } from '@/core/registry/moduleRegistry';

export const allModules: ModuleDefinition[] = [
  inventoryModule,
  posModule,
  purchasesModule,
  crmModule,
  accountingModule,
  reportingModule,
  identityModule,
];

export {
  inventoryModule,
  posModule,
  purchasesModule,
  crmModule,
  accountingModule,
  reportingModule,
  identityModule,
};
