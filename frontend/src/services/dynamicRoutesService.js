/**
 * Dynamic Routes Service
 * 
 * Generates routes dynamically from metadata configuration,
 * eliminating the need for hardcoded route definitions.
 */

import metadataService from './metadataService';

/**
 * Generic entity view component that works with metadata
 */
const GenericEntityView = () => import('../views/GenericEntityView.vue');

/**
 * Generate routes from metadata entities
 * 
 * @param {Array} entities - Metadata entities from backend
 * @returns {Array} Array of route objects
 */
export function generateRoutesFromMetadata(entities) {
  const routes = [];

  entities.forEach(entity => {
    if (!entity.is_active) return;

    const modulePath = entity.module;
    const entityPath = entity.name.replace(/_/g, '-'); // Convert snake_case to kebab-case
    const basePath = `${modulePath}/${entityPath}`;

    // List route
    routes.push({
      path: basePath,
      name: `${entity.name}_list`,
      component: GenericEntityView,
      meta: {
        requiresAuth: true,
        requiresPermission: entity.permissions?.view,
        entity: entity.name,
        viewType: 'list',
        title: entity.label_plural,
        breadcrumb: [
          { label: 'Home', path: '/' },
          { label: entity.module.toUpperCase(), path: `/${modulePath}` },
          { label: entity.label_plural, path: `/${basePath}` }
        ]
      }
    });

    // Create route
    routes.push({
      path: `${basePath}/create`,
      name: `${entity.name}_create`,
      component: GenericEntityView,
      meta: {
        requiresAuth: true,
        requiresPermission: entity.permissions?.create,
        entity: entity.name,
        viewType: 'create',
        title: `Create ${entity.label}`,
        breadcrumb: [
          { label: 'Home', path: '/' },
          { label: entity.module.toUpperCase(), path: `/${modulePath}` },
          { label: entity.label_plural, path: `/${basePath}` },
          { label: 'Create', path: `/${basePath}/create` }
        ]
      }
    });

    // Edit route
    routes.push({
      path: `${basePath}/:id/edit`,
      name: `${entity.name}_edit`,
      component: GenericEntityView,
      meta: {
        requiresAuth: true,
        requiresPermission: entity.permissions?.edit,
        entity: entity.name,
        viewType: 'edit',
        title: `Edit ${entity.label}`,
        breadcrumb: [
          { label: 'Home', path: '/' },
          { label: entity.module.toUpperCase(), path: `/${modulePath}` },
          { label: entity.label_plural, path: `/${basePath}` },
          { label: 'Edit', path: `/${basePath}/:id/edit` }
        ]
      }
    });

    // Detail/View route
    routes.push({
      path: `${basePath}/:id`,
      name: `${entity.name}_detail`,
      component: GenericEntityView,
      meta: {
        requiresAuth: true,
        requiresPermission: entity.permissions?.view,
        entity: entity.name,
        viewType: 'detail',
        title: `View ${entity.label}`,
        breadcrumb: [
          { label: 'Home', path: '/' },
          { label: entity.module.toUpperCase(), path: `/${modulePath}` },
          { label: entity.label_plural, path: `/${basePath}` },
          { label: 'View', path: `/${basePath}/:id` }
        ]
      }
    });
  });

  return routes;
}

/**
 * Load dynamic routes from backend metadata
 * 
 * @returns {Promise<Array>} Generated routes
 */
export async function loadDynamicRoutes() {
  try {
    const response = await metadataService.getCatalog();
    const entities = response.data || [];
    
    return generateRoutesFromMetadata(entities);
  } catch (error) {
    console.error('Failed to load dynamic routes:', error);
    return [];
  }
}

/**
 * Add dynamic routes to router instance
 * 
 * @param {Router} router - Vue Router instance
 * @param {Array} dynamicRoutes - Routes to add
 */
export function addDynamicRoutes(router, dynamicRoutes) {
  const dashboardRoute = router.getRoutes().find(r => r.name === 'Dashboard')?.path || '/';
  const parentRoute = router.getRoutes().find(r => r.path === '/');

  if (!parentRoute) {
    console.error('Parent route not found');
    return;
  }

  dynamicRoutes.forEach(route => {
    try {
      router.addRoute(parentRoute.name || '/', route);
    } catch (error) {
      console.error(`Failed to add route ${route.path}:`, error);
    }
  });
}

/**
 * Generate breadcrumbs from route metadata
 * 
 * @param {Object} route - Current route
 * @param {Object} params - Route params for dynamic values
 * @returns {Array} Breadcrumb items
 */
export function generateBreadcrumbs(route, params = {}) {
  if (!route.meta?.breadcrumb) {
    return [{ label: 'Home', path: '/' }];
  }

  return route.meta.breadcrumb.map(item => ({
    label: item.label,
    path: replacePlaceholders(item.path, params),
  }));
}

/**
 * Replace path placeholders with actual values
 * 
 * @param {String} path - Path with placeholders
 * @param {Object} params - Replacement values
 * @returns {String} Path with replaced values
 */
function replacePlaceholders(path, params) {
  let result = path;
  Object.keys(params).forEach(key => {
    result = result.replace(`:${key}`, params[key]);
  });
  return result;
}

/**
 * Check if user has permission to access route
 * 
 * @param {Object} route - Route to check
 * @param {Array} userPermissions - User's permissions
 * @returns {Boolean} Whether user has access
 */
export function canAccessRoute(route, userPermissions = []) {
  if (!route.meta?.requiresPermission) {
    return true;
  }

  const requiredPermission = route.meta.requiresPermission;
  
  // If array, check if user has ANY of the permissions
  if (Array.isArray(requiredPermission)) {
    return requiredPermission.some(perm => userPermissions.includes(perm));
  }

  // Single permission check
  return userPermissions.includes(requiredPermission);
}

/**
 * Filter routes based on user permissions
 * 
 * @param {Array} routes - All routes
 * @param {Array} userPermissions - User's permissions
 * @returns {Array} Filtered routes
 */
export function filterRoutesByPermissions(routes, userPermissions = []) {
  return routes.filter(route => canAccessRoute(route, userPermissions));
}

/**
 * Get route by entity name and view type
 * 
 * @param {String} entityName - Entity name
 * @param {String} viewType - View type (list, create, edit, detail)
 * @param {Object} router - Router instance
 * @returns {Object|null} Route object
 */
export function getEntityRoute(entityName, viewType = 'list', router) {
  const routeName = `${entityName}_${viewType}`;
  return router.getRoutes().find(r => r.name === routeName);
}

/**
 * Navigate to entity view
 * 
 * @param {Object} router - Router instance
 * @param {String} entityName - Entity name
 * @param {String} viewType - View type
 * @param {Object} params - Route params (e.g., { id: 123 })
 */
export function navigateToEntity(router, entityName, viewType = 'list', params = {}) {
  const routeName = `${entityName}_${viewType}`;
  router.push({ name: routeName, params });
}

export default {
  generateRoutesFromMetadata,
  loadDynamicRoutes,
  addDynamicRoutes,
  generateBreadcrumbs,
  canAccessRoute,
  filterRoutesByPermissions,
  getEntityRoute,
  navigateToEntity,
};
