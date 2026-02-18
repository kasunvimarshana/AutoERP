import { useAuthStore } from '../stores/authStore';

/**
 * Permission directive - shows/hides element based on user permissions
 * Usage: v-permission="'products.view'"
 * Usage: v-permission="['products.view', 'products.edit']" (any)
 * Usage: v-permission.all="['products.view', 'products.edit']" (all)
 */
export const permissionDirective = {
  mounted(el, binding) {
    const authStore = useAuthStore();
    const { value, modifiers } = binding;

    if (!authStore.user) {
      el.style.display = 'none';
      return;
    }

    // Super admin has all permissions
    if (authStore.user.roles?.some(role => role.slug === 'super-admin')) {
      return;
    }

    let hasAccess = false;

    if (Array.isArray(value)) {
      // Check array of permissions
      if (modifiers.all) {
        // User must have all permissions
        hasAccess = value.every(permission => authStore.hasPermission(permission));
      } else {
        // User must have at least one permission (default)
        hasAccess = value.some(permission => authStore.hasPermission(permission));
      }
    } else if (typeof value === 'string') {
      // Check single permission
      hasAccess = authStore.hasPermission(value);
    }

    if (!hasAccess) {
      el.style.display = 'none';
      el.setAttribute('data-permission-hidden', 'true');
    }
  },

  updated(el, binding) {
    // Re-check permission on update
    const authStore = useAuthStore();
    const { value, modifiers } = binding;

    if (!authStore.user) {
      el.style.display = 'none';
      return;
    }

    // Super admin has all permissions
    if (authStore.user.roles?.some(role => role.slug === 'super-admin')) {
      el.style.display = '';
      el.removeAttribute('data-permission-hidden');
      return;
    }

    let hasAccess = false;

    if (Array.isArray(value)) {
      if (modifiers.all) {
        hasAccess = value.every(permission => authStore.hasPermission(permission));
      } else {
        hasAccess = value.some(permission => authStore.hasPermission(permission));
      }
    } else if (typeof value === 'string') {
      hasAccess = authStore.hasPermission(value);
    }

    if (hasAccess) {
      el.style.display = '';
      el.removeAttribute('data-permission-hidden');
    } else {
      el.style.display = 'none';
      el.setAttribute('data-permission-hidden', 'true');
    }
  }
};

/**
 * Role directive - shows/hides element based on user roles
 * Usage: v-role="'admin'"
 * Usage: v-role="['admin', 'manager']" (any)
 * Usage: v-role.all="['admin', 'manager']" (all)
 */
export const roleDirective = {
  mounted(el, binding) {
    const authStore = useAuthStore();
    const { value, modifiers } = binding;

    if (!authStore.user) {
      el.style.display = 'none';
      return;
    }

    let hasAccess = false;

    if (Array.isArray(value)) {
      // Check array of roles
      if (modifiers.all) {
        // User must have all roles
        hasAccess = value.every(role => authStore.hasRole(role));
      } else {
        // User must have at least one role (default)
        hasAccess = value.some(role => authStore.hasRole(role));
      }
    } else if (typeof value === 'string') {
      // Check single role
      hasAccess = authStore.hasRole(value);
    }

    if (!hasAccess) {
      el.style.display = 'none';
      el.setAttribute('data-role-hidden', 'true');
    }
  },

  updated(el, binding) {
    // Re-check role on update
    const authStore = useAuthStore();
    const { value, modifiers } = binding;

    if (!authStore.user) {
      el.style.display = 'none';
      return;
    }

    let hasAccess = false;

    if (Array.isArray(value)) {
      if (modifiers.all) {
        hasAccess = value.every(role => authStore.hasRole(role));
      } else {
        hasAccess = value.some(role => authStore.hasRole(role));
      }
    } else if (typeof value === 'string') {
      hasAccess = authStore.hasRole(value);
    }

    if (hasAccess) {
      el.style.display = '';
      el.removeAttribute('data-role-hidden');
    } else {
      el.style.display = 'none';
      el.setAttribute('data-role-hidden', 'true');
    }
  }
};

/**
 * Can directive - shows/hides element based on entity.action permission
 * Usage: v-can="{ entity: 'products', action: 'view' }"
 * Usage: v-can:products="'view'" (shorthand)
 */
export const canDirective = {
  mounted(el, binding) {
    const authStore = useAuthStore();
    const { value, arg } = binding;

    if (!authStore.user) {
      el.style.display = 'none';
      return;
    }

    // Super admin has all permissions
    if (authStore.user.roles?.some(role => role.slug === 'super-admin')) {
      return;
    }

    let permission;

    if (arg && typeof value === 'string') {
      // Shorthand: v-can:products="'view'"
      permission = `${arg}.${value}`;
    } else if (value && value.entity && value.action) {
      // Full form: v-can="{ entity: 'products', action: 'view' }"
      permission = `${value.entity}.${value.action}`;
    }

    if (permission && !authStore.hasPermission(permission)) {
      el.style.display = 'none';
      el.setAttribute('data-can-hidden', 'true');
    }
  },

  updated(el, binding) {
    const authStore = useAuthStore();
    const { value, arg } = binding;

    if (!authStore.user) {
      el.style.display = 'none';
      return;
    }

    // Super admin has all permissions
    if (authStore.user.roles?.some(role => role.slug === 'super-admin')) {
      el.style.display = '';
      el.removeAttribute('data-can-hidden');
      return;
    }

    let permission;

    if (arg && typeof value === 'string') {
      permission = `${arg}.${value}`;
    } else if (value && value.entity && value.action) {
      permission = `${value.entity}.${value.action}`;
    }

    if (permission && authStore.hasPermission(permission)) {
      el.style.display = '';
      el.removeAttribute('data-can-hidden');
    } else {
      el.style.display = 'none';
      el.setAttribute('data-can-hidden', 'true');
    }
  }
};

/**
 * Export all directives for easy registration
 */
export default {
  permission: permissionDirective,
  role: roleDirective,
  can: canDirective
};
