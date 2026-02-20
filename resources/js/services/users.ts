import http from '@/services/http';
import type { User, Role, Permission, PaginatedResponse } from '@/types/index';

export interface CreateUserPayload {
  name: string;
  email: string;
  password: string;
  roles?: string[];
}

export interface UpdateUserPayload {
  name?: string;
  email?: string;
  password?: string;
  roles?: string[];
}

export const userService = {
  list(params?: Record<string, unknown>) {
    return http.get<PaginatedResponse<User> | User[]>('/users', { params });
  },
  create(payload: CreateUserPayload) {
    return http.post<User>('/users', payload);
  },
  update(id: number, payload: UpdateUserPayload) {
    return http.put<User>(`/users/${id}`, payload);
  },
  suspend(id: number) {
    return http.patch<User>(`/users/${id}/suspend`);
  },
  activate(id: number) {
    return http.patch<User>(`/users/${id}/activate`);
  },
};

export const roleService = {
  list(params?: Record<string, unknown>) {
    return http.get<PaginatedResponse<Role> | Role[]>('/roles', { params });
  },
  listPermissions() {
    return http.get<Permission[]>('/roles/permissions');
  },
  syncPermissions(id: number, permissions: string[]) {
    return http.patch(`/roles/${id}/sync-permissions`, { permissions });
  },
};
