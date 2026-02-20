/**
 * Module registry — central registry for all ERP/CRM plugin modules.
 * Each module declares its routes, permissions, and feature flag.
 * Modules are loaded dynamically at startup based on feature flags.
 *
 * NOTE: To add a new module:
 *   1. Add its feature flag key to the env (VITE_MODULE_{KEY})
 *   2. Create the module file and call registerModule()
 *   3. The FLAGS object below is built dynamically from registered modules.
 */
import type { RouteRecordRaw } from 'vue-router';

export interface ModuleDefinition {
  /** Unique module identifier */
  id: string;
  /** Human-readable name */
  name: string;
  /** Feature flag key (can be controlled via VITE_MODULE_{KEY} env var) */
  featureFlag: string;
  /** Vue Router routes contributed by this module */
  routes: RouteRecordRaw[];
  /** Permissions required to access any part of this module */
  permissions: string[];
  /** Navigation menu items contributed by this module */
  navItems: ModuleNavItem[];
}

export interface ModuleNavItem {
  to: string;
  label: string;
  icon: string;
  permission?: string;
  group: string;
}

const _registry: Map<string, ModuleDefinition> = new Map();

/** Read a VITE feature-flag env var. Returns `true` unless explicitly set to 'false'. */
function readFlag(key: string): boolean {
  // import.meta.env keys must be statically known to Vite, so we use a
  // general approach: any VITE_MODULE_* var set to 'false' disables the module.
  const envKey = `VITE_MODULE_${key.toUpperCase()}`;
  return (import.meta.env[envKey] as string | undefined) !== 'false';
}

export function registerModule(mod: ModuleDefinition): void {
  _registry.set(mod.id, mod);
}

export function getEnabledModules(): ModuleDefinition[] {
  return [..._registry.values()].filter((m) => readFlag(m.featureFlag));
}

export function isModuleEnabled(id: string): boolean {
  const mod = _registry.get(id);
  return mod !== undefined && readFlag(mod.featureFlag);
}
