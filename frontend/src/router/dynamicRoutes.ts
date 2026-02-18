import { RouteRecordRaw } from 'vue-router';
import type { ModuleMetadata, RouteMetadata } from '@/types/metadata';

/**
 * Dynamic Route Generator
 * 
 * Generates Vue Router routes from module metadata.
 * Supports:
 * - Automatic CRUD route generation
 * - Permission-based route guards
 * - Lazy-loaded components
 * - Nested routes
 * - Dynamic route parameters
 */
export class DynamicRouteGenerator {
  /**
   * Generate routes from all modules
   */
  static generateRoutes(modules: Record<string, ModuleMetadata>): RouteRecordRaw[] {
    const routes: RouteRecordRaw[] = [];

    Object.entries(modules).forEach(([moduleId, module]) => {
      if (!module.enabled) return;

      // Generate routes from module metadata
      if (module.routes && module.routes.length > 0) {
        const moduleRoutes = this.generateModuleRoutes(moduleId, module);
        routes.push(...moduleRoutes);
      }

      // Generate CRUD routes from entities
      const entityRoutes = this.generateEntityRoutes(moduleId, module);
      routes.push(...entityRoutes);
    });

    return routes;
  }

  /**
   * Generate routes from module route metadata
   */
  private static generateModuleRoutes(
    moduleId: string,
    module: ModuleMetadata
  ): RouteRecordRaw[] {
    return module.routes.map(route => this.convertMetadataToRoute(route));
  }

  /**
   * Generate CRUD routes for module entities
   */
  private static generateEntityRoutes(
    moduleId: string,
    module: ModuleMetadata
  ): RouteRecordRaw[] {
    const routes: RouteRecordRaw[] = [];
    const entities = (module as any).config?.entities || {};

    Object.entries(entities).forEach(([entityId, entity]: [string, any]) => {
      const entityName = entityId;
      const entityLabel = entity.name || entityId;
      const basePermission = `${moduleId}.${entityId}`;

      // List route
      routes.push({
        path: `/${moduleId}/${entityId}`,
        name: `${moduleId}-${entityId}-list`,
        component: () => import('@/components/crud/DynamicCRUDView.vue'),
        props: {
          moduleId,
          entityId,
          mode: 'list'
        },
        meta: {
          title: entityLabel,
          permissions: [`${basePermission}.view`],
          breadcrumbs: [
            { label: 'Home', path: '/' },
            { label: module.name, path: `/${moduleId}` },
            { label: entityLabel }
          ],
          requiresAuth: true,
          layout: 'dashboard'
        }
      });

      // Create route
      routes.push({
        path: `/${moduleId}/${entityId}/create`,
        name: `${moduleId}-${entityId}-create`,
        component: () => import('@/components/crud/DynamicCRUDView.vue'),
        props: {
          moduleId,
          entityId,
          mode: 'create'
        },
        meta: {
          title: `Create ${entityLabel}`,
          permissions: [`${basePermission}.create`],
          breadcrumbs: [
            { label: 'Home', path: '/' },
            { label: module.name, path: `/${moduleId}` },
            { label: entityLabel, path: `/${moduleId}/${entityId}` },
            { label: 'Create' }
          ],
          requiresAuth: true,
          layout: 'dashboard'
        }
      });

      // Detail route
      routes.push({
        path: `/${moduleId}/${entityId}/:id`,
        name: `${moduleId}-${entityId}-detail`,
        component: () => import('@/components/crud/DynamicCRUDView.vue'),
        props: route => ({
          moduleId,
          entityId,
          recordId: route.params.id,
          mode: 'view'
        }),
        meta: {
          title: `${entityLabel} Details`,
          permissions: [`${basePermission}.view`],
          breadcrumbs: [
            { label: 'Home', path: '/' },
            { label: module.name, path: `/${moduleId}` },
            { label: entityLabel, path: `/${moduleId}/${entityId}` },
            { label: 'Details' }
          ],
          requiresAuth: true,
          layout: 'dashboard'
        }
      });

      // Edit route
      routes.push({
        path: `/${moduleId}/${entityId}/:id/edit`,
        name: `${moduleId}-${entityId}-edit`,
        component: () => import('@/components/crud/DynamicCRUDView.vue'),
        props: route => ({
          moduleId,
          entityId,
          recordId: route.params.id,
          mode: 'edit'
        }),
        meta: {
          title: `Edit ${entityLabel}`,
          permissions: [`${basePermission}.update`],
          breadcrumbs: [
            { label: 'Home', path: '/' },
            { label: module.name, path: `/${moduleId}` },
            { label: entityLabel, path: `/${moduleId}/${entityId}` },
            { label: 'Edit' }
          ],
          requiresAuth: true,
          layout: 'dashboard'
        }
      });
    });

    return routes;
  }

  /**
   * Convert route metadata to Vue Router route record
   */
  private static convertMetadataToRoute(routeMeta: RouteMetadata): RouteRecordRaw {
    const route: RouteRecordRaw = {
      path: routeMeta.path,
      name: routeMeta.name,
      component: () => this.loadComponent(routeMeta.component),
      meta: {
        ...routeMeta.meta,
        requiresAuth: routeMeta.meta.requiresAuth !== false
      }
    };

    if (routeMeta.children && routeMeta.children.length > 0) {
      route.children = routeMeta.children.map(child =>
        this.convertMetadataToRoute(child)
      );
    }

    return route;
  }

  /**
   * Dynamically load component
   */
  private static loadComponent(componentPath: string) {
    // Handle different component path formats
    if (componentPath.startsWith('@/')) {
      return import(/* @vite-ignore */ componentPath);
    } else if (componentPath.startsWith('/')) {
      return import(/* @vite-ignore */ `@${componentPath}`);
    } else {
      return import(/* @vite-ignore */ `@/views/${componentPath}`);
    }
  }

  /**
   * Register dynamic routes to an existing router
   */
  static registerDynamicRoutes(
    router: any,
    modules: Record<string, ModuleMetadata>
  ): void {
    const dynamicRoutes = this.generateRoutes(modules);

    // Find the dashboard layout route (parent for dynamic routes)
    const dashboardRoute = router.getRoutes().find((route: any) => 
      route.meta?.layout === 'dashboard' || route.name === 'dashboard-layout'
    );

    if (dashboardRoute) {
      // Add routes as children of dashboard layout
      dynamicRoutes.forEach(route => {
        router.addRoute(dashboardRoute.name, route);
      });
    } else {
      // Add routes to root
      dynamicRoutes.forEach(route => {
        router.addRoute(route);
      });
    }
  }

  /**
   * Generate navigation structure from routes
   */
  static generateNavigation(
    modules: Record<string, ModuleMetadata>,
    userPermissions: string[] = []
  ): NavigationItem[] {
    const navigation: NavigationItem[] = [];

    Object.entries(modules).forEach(([moduleId, module]) => {
      if (!module.enabled) return;

      // Check if user has permission for any entity in this module
      const entities = (module as any).config?.entities || {};
      const hasAccess = Object.keys(entities).some(entityId => 
        userPermissions.includes(`${moduleId}.${entityId}.view`) ||
        userPermissions.includes('*')
      );

      if (!hasAccess && userPermissions.length > 0) return;

      const moduleNav: NavigationItem = {
        id: moduleId,
        label: module.name || module.label,
        icon: this.getModuleIcon(moduleId),
        path: `/${moduleId}`,
        children: []
      };

      // Add entity navigation items
      Object.entries(entities).forEach(([entityId, entity]: [string, any]) => {
        const permission = `${moduleId}.${entityId}.view`;
        
        if (userPermissions.length === 0 || 
            userPermissions.includes(permission) || 
            userPermissions.includes('*')) {
          moduleNav.children?.push({
            id: `${moduleId}-${entityId}`,
            label: entity.name || entityId,
            icon: entity.icon,
            path: `/${moduleId}/${entityId}`,
            permission
          });
        }
      });

      if (moduleNav.children && moduleNav.children.length > 0) {
        navigation.push(moduleNav);
      }
    });

    return navigation;
  }

  /**
   * Get icon for module
   */
  private static getModuleIcon(moduleId: string): string {
    const iconMap: Record<string, string> = {
      core: 'cog',
      iam: 'users',
      inventory: 'cube',
      sales: 'shopping-cart',
      purchasing: 'shopping-bag',
      accounting: 'calculator',
      hr: 'user-group',
      manufacturing: 'cog',
      analytics: 'chart-bar'
    };

    return iconMap[moduleId] || 'folder';
  }
}

/**
 * Navigation item interface
 */
export interface NavigationItem {
  id: string;
  label: string;
  icon?: string;
  path?: string;
  permission?: string;
  children?: NavigationItem[];
  badge?: {
    value: string | number;
    variant: 'primary' | 'success' | 'warning' | 'danger';
  };
}
