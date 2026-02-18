import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import api from '@/api/client';
import type { ModuleMetadata, ModuleStatistics } from '@/composables/useModuleMetadata';

/**
 * Module Store
 * 
 * Central state management for module metadata.
 * Provides reactive module configuration for the entire application.
 */
export const useModuleStore = defineStore('modules', () => {
  // State
  const modules = ref<Record<string, ModuleMetadata>>({});
  const statistics = ref<ModuleStatistics | null>(null);
  const loading = ref(false);
  const error = ref<string | null>(null);
  const initialized = ref(false);

  // Getters
  const enabledModules = computed(() => {
    return Object.values(modules.value).filter(module => module.enabled);
  });

  const allPermissions = computed(() => {
    return Object.values(modules.value).flatMap(module => module.permissions);
  });

  const moduleList = computed(() => {
    return Object.values(modules.value);
  });

  // Actions
  const loadModules = async () => {
    if (initialized.value && !error.value) {
      return; // Already loaded successfully
    }

    loading.value = true;
    error.value = null;

    try {
      const response = await api.get('/modules');
      
      if (response.data.success) {
        modules.value = response.data.data;
        statistics.value = response.data.statistics;
        initialized.value = true;
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

  const getModule = (moduleId: string): ModuleMetadata | null => {
    return modules.value[moduleId] || null;
  };

  const isModuleEnabled = (moduleId: string): boolean => {
    const module = getModule(moduleId);
    return module ? module.enabled : false;
  };

  const isFeatureEnabled = (moduleId: string, feature: string): boolean => {
    const module = getModule(moduleId);
    return module?.config?.features?.[feature] === true;
  };

  const getModuleEntities = (moduleId: string) => {
    const module = getModule(moduleId);
    return module?.config?.entities || {};
  };

  const hasPermission = (permission: string): boolean => {
    return allPermissions.value.includes(permission);
  };

  const buildNavigation = (userPermissions: string[] = []) => {
    return enabledModules.value
      .filter(module => {
        // Filter modules based on user permissions
        if (module.permissions.length === 0) return true;
        return module.permissions.some(perm => userPermissions.includes(perm));
      })
      .map(module => {
        const entities = module.config.entities || {};
        const firstEntity = Object.values(entities)[0];
        
        return {
          id: module.id,
          name: module.name,
          icon: firstEntity?.icon || 'cube',
          path: `/${module.id}`,
          children: Object.entries(entities).map(([key, entity]) => ({
            id: `${module.id}.${key}`,
            name: entity.name,
            icon: entity.icon,
            path: entity.routes.list,
            permissions: [`${module.id}.${key}.view`],
          })),
        };
      });
  };

  const reset = () => {
    modules.value = {};
    statistics.value = null;
    loading.value = false;
    error.value = null;
    initialized.value = false;
  };

  return {
    // State
    modules,
    statistics,
    loading,
    error,
    initialized,
    
    // Getters
    enabledModules,
    allPermissions,
    moduleList,
    
    // Actions
    loadModules,
    getModule,
    isModuleEnabled,
    isFeatureEnabled,
    getModuleEntities,
    hasPermission,
    buildNavigation,
    reset,
  };
});
