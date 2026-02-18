/**
 * Dynamic Navigation Service
 * 
 * Generates navigation menu structure from metadata entities and menus,
 * with permission-based visibility.
 */

import metadataService from './metadataService';
import { useAuthStore } from '../stores/authStore';

/**
 * Icon mapping for modules and entities
 */
const iconMap = {
  // Modules
  'dashboard': 'HomeIcon',
  'iam': 'UserGroupIcon',
  'inventory': 'CubeIcon',
  'crm': 'UsersIcon',
  'pos': 'ShoppingCartIcon',
  'procurement': 'ClipboardDocumentListIcon',
  'manufacturing': 'CogIcon',
  'finance': 'CurrencyDollarIcon',
  'reporting': 'ChartBarIcon',
  
  // Entities
  'user': 'UserIcon',
  'role': 'ShieldCheckIcon',
  'permission': 'KeyIcon',
  'product': 'ArchiveBoxIcon',
  'stock_ledger': 'ClipboardDocumentIcon',
  'customer': 'UserCircleIcon',
  'supplier': 'TruckIcon',
  'purchase_order': 'ShoppingBagIcon',
  'quotation': 'DocumentTextIcon',
  'sales_order': 'ShoppingBagIcon',
  'invoice': 'DocumentIcon',
  'payment': 'CreditCardIcon',
  'bom': 'ListBulletIcon',
  'production_order': 'WrenchIcon',
  'work_order': 'WrenchScrewdriverIcon',
  'account': 'BanknotesIcon',
  'journal_entry': 'PencilSquareIcon',
  'report': 'ChartBarSquareIcon',
  'dashboard': 'RectangleStackIcon',
};

/**
 * Generate navigation structure from metadata entities
 * Groups entities by module with permission checks
 * 
 * @param {Array} entities - Metadata entities
 * @param {Array} userPermissions - User's permissions
 * @returns {Array} Navigation structure
 */
export function generateNavigationFromEntities(entities, userPermissions = []) {
  const modules = {};

  // Group entities by module
  entities.forEach(entity => {
    if (!entity.is_active) return;

    // Check if user has view permission
    const viewPermission = entity.permissions?.view;
    if (viewPermission && !userPermissions.includes(viewPermission)) {
      return; // Skip if no permission
    }

    if (!modules[entity.module]) {
      modules[entity.module] = {
        name: formatModuleName(entity.module),
        path: null,
        icon: iconMap[entity.module] || 'FolderIcon',
        children: [],
        isOpen: false,
        order: getModuleOrder(entity.module),
      };
    }

    modules[entity.module].children.push({
      name: entity.label_plural,
      path: `/${entity.module}/${entity.name.replace(/_/g, '-')}`,
      icon: iconMap[entity.name] || 'DocumentIcon',
      entity: entity.name,
      permissions: entity.permissions,
    });
  });

  // Convert to array and sort
  const navigationItems = Object.values(modules)
    .sort((a, b) => a.order - b.order)
    .map(module => ({
      ...module,
      children: module.children.sort((a, b) => a.name.localeCompare(b.name)),
    }));

  // Add dashboard at the top
  navigationItems.unshift({
    name: 'Dashboard',
    path: '/dashboard',
    icon: 'HomeIcon',
    children: null,
    isOpen: false,
    order: 0,
  });

  return navigationItems;
}

/**
 * Generate navigation from backend menu configuration
 * 
 * @param {Array} menus - Menu items from backend
 * @param {Array} userPermissions - User's permissions
 * @returns {Array} Navigation structure
 */
export function generateNavigationFromMenus(menus, userPermissions = []) {
  const buildTree = (items, parentId = null) => {
    return items
      .filter(item => item.parent_id === parentId)
      .filter(item => {
        // Filter by permission
        if (item.permission && !userPermissions.includes(item.permission)) {
          return false;
        }
        return item.is_active;
      })
      .sort((a, b) => a.order - b.order)
      .map(item => ({
        name: item.label,
        path: item.route,
        icon: iconMap[item.icon] || item.icon || 'DocumentIcon',
        entity: item.entity_name,
        permission: item.permission,
        children: buildTree(items, item.id),
        isOpen: false,
      }))
      .map(item => ({
        ...item,
        children: item.children.length > 0 ? item.children : null,
      }));
  };

  return buildTree(menus);
}

/**
 * Load navigation from metadata
 * 
 * @param {String} source - 'entities' or 'menus'
 * @returns {Promise<Array>} Navigation structure
 */
export async function loadNavigation(source = 'entities') {
  try {
    const authStore = useAuthStore();
    const userPermissions = authStore.permissions || [];

    if (source === 'menus') {
      // Load from menu configuration
      const response = await metadataService.getMenus();
      const menus = response.data || [];
      return generateNavigationFromMenus(menus, userPermissions);
    } else {
      // Load from entity metadata
      const response = await metadataService.getCatalog();
      const entities = response.data || [];
      return generateNavigationFromEntities(entities, userPermissions);
    }
  } catch (error) {
    console.error('Failed to load navigation:', error);
    return [];
  }
}

/**
 * Format module name for display
 * 
 * @param {String} module - Module name
 * @returns {String} Formatted name
 */
function formatModuleName(module) {
  const names = {
    'iam': 'IAM',
    'crm': 'CRM',
    'pos': 'POS',
  };
  
  return names[module] || module.charAt(0).toUpperCase() + module.slice(1);
}

/**
 * Get module display order
 * 
 * @param {String} module - Module name
 * @returns {Number} Order
 */
function getModuleOrder(module) {
  const order = {
    'dashboard': 0,
    'iam': 1,
    'inventory': 2,
    'crm': 3,
    'pos': 4,
    'procurement': 5,
    'manufacturing': 6,
    'finance': 7,
    'reporting': 8,
  };
  
  return order[module] || 99;
}

/**
 * Filter navigation items by feature flags
 * 
 * @param {Array} items - Navigation items
 * @param {Object} features - Enabled features
 * @returns {Array} Filtered navigation
 */
export function filterNavigationByFeatures(items, features = {}) {
  return items
    .filter(item => {
      // Check if item's module/feature is enabled
      if (item.feature && !features[item.feature]) {
        return false;
      }
      return true;
    })
    .map(item => ({
      ...item,
      children: item.children 
        ? filterNavigationByFeatures(item.children, features)
        : null,
    }))
    .filter(item => {
      // Remove parent items with no children
      return !item.children || item.children.length > 0;
    });
}

/**
 * Search navigation items
 * 
 * @param {Array} items - Navigation items
 * @param {String} query - Search query
 * @returns {Array} Matching items
 */
export function searchNavigation(items, query) {
  const lowercaseQuery = query.toLowerCase();
  const results = [];

  const search = (items, parents = []) => {
    items.forEach(item => {
      const matches = item.name.toLowerCase().includes(lowercaseQuery);
      
      if (matches) {
        results.push({
          ...item,
          breadcrumb: [...parents, item.name],
        });
      }

      if (item.children) {
        search(item.children, [...parents, item.name]);
      }
    });
  };

  search(items);
  return results;
}

/**
 * Get breadcrumb trail for a path
 * 
 * @param {Array} items - Navigation items
 * @param {String} path - Current path
 * @returns {Array} Breadcrumb items
 */
export function getBreadcrumbFromNavigation(items, path) {
  const breadcrumb = [];

  const findPath = (items, targetPath, trail = []) => {
    for (const item of items) {
      const currentTrail = [...trail, { label: item.name, path: item.path }];

      if (item.path === targetPath) {
        return currentTrail;
      }

      if (item.children) {
        const result = findPath(item.children, targetPath, currentTrail);
        if (result) return result;
      }
    }
    return null;
  };

  return findPath(items, path) || [{ label: 'Home', path: '/' }];
}

/**
 * Check if navigation item is active
 * 
 * @param {Object} item - Navigation item
 * @param {String} currentPath - Current route path
 * @returns {Boolean} Whether item is active
 */
export function isNavigationItemActive(item, currentPath) {
  if (item.path === currentPath) {
    return true;
  }

  if (item.children) {
    return item.children.some(child => isNavigationItemActive(child, currentPath));
  }

  return false;
}

export default {
  generateNavigationFromEntities,
  generateNavigationFromMenus,
  loadNavigation,
  filterNavigationByFeatures,
  searchNavigation,
  getBreadcrumbFromNavigation,
  isNavigationItemActive,
};
