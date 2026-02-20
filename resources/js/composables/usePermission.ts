/**
 * Composable for declarative permission checks in component templates.
 *
 * Usage:
 *   const { can, canAny, canAll } = usePermission();
 *   if (can('product.create')) { ... }
 */
import { computed } from 'vue';
import { useAuthStore } from '@/stores/auth';

export function usePermission() {
  const auth = useAuthStore();

  /** Returns true if the current user has the given permission. */
  const can = (permission: string): boolean => auth.hasPermission(permission);

  /** Returns true if the current user has ANY of the given permissions. */
  const canAny = (permissions: string[]): boolean =>
    permissions.some((p) => auth.hasPermission(p));

  /** Returns true if the current user has ALL of the given permissions. */
  const canAll = (permissions: string[]): boolean =>
    permissions.every((p) => auth.hasPermission(p));

  /** Returns true if the current user has the given role. */
  const hasRole = (role: string): boolean => auth.hasRole(role);

  /** Reactive reference — rebuilds when auth.user changes. */
  const isSuperAdmin = computed<boolean>(() =>
    auth.user?.roles?.some((r) => r.name === 'super-admin') ?? false,
  );

  return { can, canAny, canAll, hasRole, isSuperAdmin };
}
