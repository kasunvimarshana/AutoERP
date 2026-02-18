import { RouteRecordRaw } from 'vue-router';
import type { ModuleMetadata } from '@/composables/useModuleMetadata';

/**
 * Dynamic Route Generator
 * 
 * Generates Vue Router routes from module metadata.
 * Enables runtime-configurable routing based on backend module configuration.
 */
export class DynamicRouteGenerator {
  /**
   * Generate routes from module metadata
   */
  static generateRoutes(modules: Record<string, ModuleMetadata>): RouteRecordRaw[] {
    const routes: RouteRecordRaw[] = [];

    Object.values(modules).forEach(module => {
      if (!module.enabled) return;

      const entities = module.config.entities || {};
      
      Object.entries(entities).forEach(([entityKey, entity]) => {
        const entityRoutes = this.generateEntityRoutes(module.id, entityKey, entity);
        routes.push(...entityRoutes);
      });
    });

    return routes;
  }

  /**
   * Generate routes for a specific entity
   */
  private static generateEntityRoutes(
    moduleId: string,
    entityKey: string,
    entity: any
  ): RouteRecordRaw[] {
    const routes: RouteRecordRaw[] = [];
    const basePath = `/${moduleId}/${entityKey}`;

    // List route
    if (entity.routes.list) {
      routes.push({
        path: entity.routes.list,
        name: `${moduleId}.${entityKey}.list`,
        component: () => import('@/views/modules/ModuleList.vue'),
        meta: {
          title: entity.name,
          module: moduleId,
          entity: entityKey,
          permissions: [`${moduleId}.${entityKey}.view`],
        },
      });
    }

    // Create route
    if (entity.routes.create) {
      routes.push({
        path: entity.routes.create,
        name: `${moduleId}.${entityKey}.create`,
        component: () => import('@/views/modules/ModuleForm.vue'),
        meta: {
          title: `Create ${entity.name}`,
          module: moduleId,
          entity: entityKey,
          permissions: [`${moduleId}.${entityKey}.create`],
        },
      });
    }

    // View route
    if (entity.routes.view) {
      routes.push({
        path: entity.routes.view,
        name: `${moduleId}.${entityKey}.view`,
        component: () => import('@/views/modules/ModuleDetail.vue'),
        meta: {
          title: `View ${entity.name}`,
          module: moduleId,
          entity: entityKey,
          permissions: [`${moduleId}.${entityKey}.view`],
        },
      });
    }

    // Edit route
    if (entity.routes.edit) {
      routes.push({
        path: entity.routes.edit,
        name: `${moduleId}.${entityKey}.edit`,
        component: () => import('@/views/modules/ModuleForm.vue'),
        meta: {
          title: `Edit ${entity.name}`,
          module: moduleId,
          entity: entityKey,
          permissions: [`${moduleId}.${entityKey}.edit`],
        },
      });
    }

    return routes;
  }

  /**
   * Register dynamic routes with router
   */
  static registerDynamicRoutes(
    router: any,
    modules: Record<string, ModuleMetadata>
  ): void {
    const dynamicRoutes = this.generateRoutes(modules);
    
    dynamicRoutes.forEach(route => {
      // Check if route already exists
      if (!router.hasRoute(route.name)) {
        // Add to dashboard layout children
        const dashboardRoute = router.getRoutes().find((r: any) => r.path === '/');
        if (dashboardRoute) {
          router.addRoute('/', route);
        }
      }
    });
  }
}

/**
 * Helper function to load and register dynamic routes
 */
export async function loadDynamicRoutes(router: any, loadModules: () => Promise<any>) {
  try {
    // Load module metadata
    await loadModules();
    
    // Get modules from global state or pass them
    // This will be called after modules are loaded
  } catch (error) {
    console.error('Failed to load dynamic routes:', error);
  }
}
