import { ref, computed } from 'vue';
import api from '@/api/client';

/**
 * Module Metadata Interface
 */
export interface ModuleMetadata {
  id: string;
  name: string;
  version: string;
  dependencies: string[];
  config: ModuleConfig;
  permissions: string[];
  routes: RouteConfig;
  enabled: boolean;
}

export interface ModuleConfig {
  entities?: Record<string, EntityConfig>;
  features?: Record<string, boolean | string[]>;
}

export interface EntityConfig {
  name: string;
  icon: string;
  routes: Record<string, string>;
}

export interface RouteConfig {
  api?: {
    prefix: string;
    middleware: string[];
  };
  web?: {
    prefix: string;
    middleware: string[];
  };
}

/**
 * Module Metadata Statistics
 */
export interface ModuleStatistics {
  total: number;
  enabled: number;
  disabled: number;
  modules: string[];
}

/**
 * Composable for managing module metadata
 * 
 * Provides reactive access to module configuration loaded from the backend.
 * Enables metadata-driven routing, navigation, and feature discovery.
 */
export function useModuleMetadata() {
  const modules = ref<Record<string, ModuleMetadata>>({});
  const statistics = ref<ModuleStatistics | null>(null);
  const loading = ref(false);
  const error = ref<string | null>(null);

  /**
   * Get enabled modules
   */
  const enabledModules = computed(() => {
    return Object.values(modules.value).filter(module => module.enabled);
  });

  /**
   * Get all module permissions
   */
  const allPermissions = computed(() => {
    return Object.values(modules.value).flatMap(module => module.permissions);
  });

  /**
   * Load module metadata from backend
   */
  const loadModules = async () => {
    loading.value = true;
    error.value = null;

    try {
      const response = await api.get('/modules');
      
      if (response.data.success) {
        modules.value = response.data.data;
        statistics.value = response.data.statistics;
      } else {
        error.value = 'Failed to load module metadata';
      }
    } catch (err: any) {
      error.value = err.message || 'Failed to load module metadata';
      console.error('Module metadata load error:', err);
    } finally {
      loading.value = false;
    }
  };

  /**
   * Get module by ID
   */
  const getModule = (moduleId: string): ModuleMetadata | null => {
    return modules.value[moduleId] || null;
  };

  /**
   * Check if module is enabled
   */
  const isModuleEnabled = (moduleId: string): boolean => {
    const module = getModule(moduleId);
    return module ? module.enabled : false;
  };

  /**
   * Check if feature is enabled for a module
   */
  const isFeatureEnabled = (moduleId: string, feature: string): boolean => {
    const module = getModule(moduleId);
    return module?.config?.features?.[feature] === true;
  };

  /**
   * Get module entities
   */
  const getModuleEntities = (moduleId: string): Record<string, EntityConfig> => {
    const module = getModule(moduleId);
    return module?.config?.entities || {};
  };

  /**
   * Build navigation items from module metadata
   */
  const buildNavigation = (userPermissions: string[] = []) => {
    return enabledModules.value
      .filter(module => {
        // Filter modules based on user permissions
        if (module.permissions.length === 0) return true;
        return module.permissions.some(perm => userPermissions.includes(perm));
      })
      .map(module => ({
        id: module.id,
        name: module.name,
        icon: module.config.entities?.[Object.keys(module.config.entities)[0]]?.icon || 'cube',
        path: `/${module.id}`,
        children: Object.entries(module.config.entities || {}).map(([key, entity]) => ({
          id: `${module.id}.${key}`,
          name: entity.name,
          icon: entity.icon,
          path: entity.routes.list,
          permissions: [`${module.id}.${key}.view`],
        })),
      }));
  };

  return {
    modules,
    statistics,
    loading,
    error,
    enabledModules,
    allPermissions,
    loadModules,
    getModule,
    isModuleEnabled,
    isFeatureEnabled,
    getModuleEntities,
    buildNavigation,
  };
}
