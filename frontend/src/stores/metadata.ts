import { ref, computed } from 'vue';
import { defineStore } from 'pinia';
import type { 
  TenantConfiguration, 
  ModuleMetadata, 
  NavigationItemMetadata,
  RouteMetadata,
  FormMetadata,
  TableMetadata,
  DashboardMetadata,
  PermissionConfiguration
} from '@/types/metadata';
import { metadataApi } from '@/api/metadata';

export const useMetadataStore = defineStore('metadata', () => {
  // State
  const tenantConfig = ref<TenantConfiguration | null>(null);
  const moduleMetadata = ref<Record<string, ModuleMetadata>>({});
  const navigationMetadata = ref<NavigationItemMetadata[]>([]);
  const routeMetadata = ref<RouteMetadata[]>([]);
  const permissions = ref<PermissionConfiguration>({
    user: [],
    role: [],
    tenant: []
  });
  const isLoading = ref(false);
  const error = ref<string | null>(null);

  // Getters
  const enabledModules = computed(() => {
    return Object.values(moduleMetadata.value)
      .filter(module => module.enabled)
      .sort((a, b) => a.order - b.order);
  });

  const theme = computed(() => tenantConfig.value?.theme);
  const locale = computed(() => tenantConfig.value?.locale);
  const currency = computed(() => tenantConfig.value?.currency);
  const timezone = computed(() => tenantConfig.value?.timezone);

  const visibleNavigation = computed(() => {
    return filterNavigationByPermissions(navigationMetadata.value);
  });

  // Actions
  async function loadTenantConfiguration() {
    isLoading.value = true;
    error.value = null;

    try {
      const response = await metadataApi.getTenantConfiguration();
      tenantConfig.value = response.data;
      moduleMetadata.value = response.data.modules || {};
      
      // Build navigation from modules
      buildNavigation();
      
      // Build routes from modules
      buildRoutes();
      
      return response.data;
    } catch (err: any) {
      error.value = err.message || 'Failed to load tenant configuration';
      throw err;
    } finally {
      isLoading.value = false;
    }
  }

  async function loadUserPermissions() {
    try {
      const response = await metadataApi.getUserPermissions();
      permissions.value = response.data;
      return response.data;
    } catch (err: any) {
      error.value = err.message || 'Failed to load permissions';
      throw err;
    }
  }

  async function loadModuleMetadata(moduleName: string) {
    try {
      const response = await metadataApi.getModuleMetadata(moduleName);
      moduleMetadata.value[moduleName] = response.data;
      return response.data;
    } catch (err: any) {
      error.value = err.message || `Failed to load ${moduleName} metadata`;
      throw err;
    }
  }

  async function loadFormMetadata(formId: string): Promise<FormMetadata> {
    try {
      const response = await metadataApi.getFormMetadata(formId);
      return response.data;
    } catch (err: any) {
      error.value = err.message || `Failed to load form metadata: ${formId}`;
      throw err;
    }
  }

  async function loadTableMetadata(tableId: string): Promise<TableMetadata> {
    try {
      const response = await metadataApi.getTableMetadata(tableId);
      return response.data;
    } catch (err: any) {
      error.value = err.message || `Failed to load table metadata: ${tableId}`;
      throw err;
    }
  }

  async function loadDashboardMetadata(dashboardId: string): Promise<DashboardMetadata> {
    try {
      const response = await metadataApi.getDashboardMetadata(dashboardId);
      return response.data;
    } catch (err: any) {
      error.value = err.message || `Failed to load dashboard metadata: ${dashboardId}`;
      throw err;
    }
  }

  function hasPermission(permission: string): boolean {
    return permissions.value.user.includes(permission) || 
           permissions.value.user.includes('*');
  }

  function hasAnyPermission(requiredPermissions: string[]): boolean {
    if (!requiredPermissions || requiredPermissions.length === 0) {
      return true;
    }
    return requiredPermissions.some(permission => hasPermission(permission));
  }

  function hasAllPermissions(requiredPermissions: string[]): boolean {
    if (!requiredPermissions || requiredPermissions.length === 0) {
      return true;
    }
    return requiredPermissions.every(permission => hasPermission(permission));
  }

  function isFeatureEnabled(feature: string): boolean {
    return tenantConfig.value?.features?.[feature] === true;
  }

  function isModuleEnabled(moduleName: string): boolean {
    return moduleMetadata.value[moduleName]?.enabled === true;
  }

  function buildNavigation() {
    const navItems: NavigationItemMetadata[] = [];

    Object.values(moduleMetadata.value).forEach(module => {
      if (module.enabled && module.navigation) {
        navItems.push(...module.navigation);
      }
    });

    // Sort by order
    navigationMetadata.value = navItems.sort((a, b) => a.order - b.order);
  }

  function buildRoutes() {
    const routes: RouteMetadata[] = [];

    Object.values(moduleMetadata.value).forEach(module => {
      if (module.enabled && module.routes) {
        routes.push(...module.routes);
      }
    });

    routeMetadata.value = routes;
  }

  function filterNavigationByPermissions(items: NavigationItemMetadata[]): NavigationItemMetadata[] {
    return items
      .filter(item => {
        if (!item.visible) return false;
        if (!item.permissions || item.permissions.length === 0) return true;
        return hasAnyPermission(item.permissions);
      })
      .map(item => ({
        ...item,
        children: item.children ? filterNavigationByPermissions(item.children) : undefined
      }))
      .filter(item => !item.children || item.children.length > 0);
  }

  function updateTheme(themeConfig: Partial<TenantConfiguration['theme']>) {
    if (tenantConfig.value) {
      tenantConfig.value.theme = {
        ...tenantConfig.value.theme,
        ...themeConfig
      };
    }
  }

  function reset() {
    tenantConfig.value = null;
    moduleMetadata.value = {};
    navigationMetadata.value = [];
    routeMetadata.value = [];
    permissions.value = {
      user: [],
      role: [],
      tenant: []
    };
    error.value = null;
  }

  return {
    // State
    tenantConfig,
    moduleMetadata,
    navigationMetadata,
    routeMetadata,
    permissions,
    isLoading,
    error,

    // Getters
    enabledModules,
    theme,
    locale,
    currency,
    timezone,
    visibleNavigation,

    // Actions
    loadTenantConfiguration,
    loadUserPermissions,
    loadModuleMetadata,
    loadFormMetadata,
    loadTableMetadata,
    loadDashboardMetadata,
    hasPermission,
    hasAnyPermission,
    hasAllPermissions,
    isFeatureEnabled,
    isModuleEnabled,
    updateTheme,
    reset
  };
});
