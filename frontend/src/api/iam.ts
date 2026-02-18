import { apiClient } from './client'
import type { PaginatedResponse } from '@/types/api'

export interface User {
  id: number
  name: string
  email: string
  phone?: string
  is_active: boolean
  email_verified_at?: string
  roles: Role[]
  permissions: string[]
  created_at: string
  updated_at: string
}

export interface Role {
  id: number
  name: string
  display_name?: string
  description?: string
  guard_name: string
  level?: number
  parent_id?: number
  permissions: Permission[]
  created_at: string
  updated_at: string
}

export interface Permission {
  id: number
  name: string
  display_name?: string
  description?: string
  guard_name: string
  resource?: string
  action?: string
  created_at: string
  updated_at: string
}

export interface UserQueryParams {
  page?: number
  per_page?: number
  search?: string
  is_active?: boolean
  role?: string
}

export interface RoleQueryParams {
  page?: number
  per_page?: number
  search?: string
}

export interface PermissionQueryParams {
  page?: number
  per_page?: number
  search?: string
  resource?: string
}

export interface UserFormData {
  name: string
  email: string
  phone?: string
  password?: string
  password_confirmation?: string
  is_active?: boolean
  role_ids?: number[]
}

export interface RoleFormData {
  name: string
  display_name?: string
  description?: string
  parent_id?: number
  permission_ids?: number[]
}

export interface ChangePasswordData {
  current_password: string
  new_password: string
  new_password_confirmation: string
}

export const iamApi = {
  // User endpoints
  async getUsers(params: UserQueryParams = {}): Promise<PaginatedResponse<User>> {
    const response = await apiClient.get('/users', { params })
    return response.data
  },

  async getUser(id: string | number): Promise<User> {
    const response = await apiClient.get(`/users/${id}`)
    return response.data
  },

  async createUser(data: UserFormData): Promise<User> {
    const response = await apiClient.post('/users', data)
    return response.data
  },

  async updateUser(id: string | number, data: Partial<UserFormData>): Promise<User> {
    const response = await apiClient.put(`/users/${id}`, data)
    return response.data
  },

  async deleteUser(id: string | number): Promise<void> {
    await apiClient.delete(`/users/${id}`)
  },

  async activateUser(id: string | number): Promise<User> {
    const response = await apiClient.post(`/users/${id}/activate`)
    return response.data
  },

  async deactivateUser(id: string | number): Promise<User> {
    const response = await apiClient.post(`/users/${id}/deactivate`)
    return response.data
  },

  async getUserProfile(): Promise<User> {
    const response = await apiClient.get('/users/profile')
    return response.data
  },

  async updateUserProfile(data: Partial<UserFormData>): Promise<User> {
    const response = await apiClient.put('/users/profile', data)
    return response.data
  },

  async changePassword(data: ChangePasswordData): Promise<void> {
    await apiClient.post('/users/change-password', data)
  },

  async searchUsers(query: string): Promise<User[]> {
    const response = await apiClient.get('/users/search', { params: { q: query } })
    return response.data
  },

  // Role endpoints
  async getRoles(params: RoleQueryParams = {}): Promise<PaginatedResponse<Role>> {
    const response = await apiClient.get('/roles', { params })
    return response.data
  },

  async getRole(id: string | number): Promise<Role> {
    const response = await apiClient.get(`/roles/${id}`)
    return response.data
  },

  async getRoleHierarchy(): Promise<Role[]> {
    const response = await apiClient.get('/roles/hierarchy')
    return response.data
  },

  async getRolePermissions(id: string | number): Promise<Permission[]> {
    const response = await apiClient.get(`/roles/${id}/permissions`)
    return response.data
  },

  async createRole(data: RoleFormData): Promise<Role> {
    const response = await apiClient.post('/roles', data)
    return response.data
  },

  async updateRole(id: string | number, data: Partial<RoleFormData>): Promise<Role> {
    const response = await apiClient.put(`/roles/${id}`, data)
    return response.data
  },

  async deleteRole(id: string | number): Promise<void> {
    await apiClient.delete(`/roles/${id}`)
  },

  async assignPermissionsToRole(roleId: string | number, permissionIds: number[]): Promise<Role> {
    const response = await apiClient.post(`/roles/${roleId}/permissions/assign`, {
      permission_ids: permissionIds,
    })
    return response.data
  },

  async revokePermissionsFromRole(roleId: string | number, permissionIds: number[]): Promise<Role> {
    const response = await apiClient.post(`/roles/${roleId}/permissions/revoke`, {
      permission_ids: permissionIds,
    })
    return response.data
  },

  async syncRolePermissions(roleId: string | number, permissionIds: number[]): Promise<Role> {
    const response = await apiClient.post(`/roles/${roleId}/permissions/sync`, {
      permission_ids: permissionIds,
    })
    return response.data
  },

  // User-Role assignment
  async assignRoleToUser(userId: string | number, roleId: number): Promise<User> {
    const response = await apiClient.post(`/users/${userId}/roles/assign`, { role_id: roleId })
    return response.data
  },

  async removeRoleFromUser(userId: string | number, roleId: number): Promise<User> {
    const response = await apiClient.post(`/users/${userId}/roles/remove`, { role_id: roleId })
    return response.data
  },

  async syncUserRoles(userId: string | number, roleIds: number[]): Promise<User> {
    const response = await apiClient.post(`/users/${userId}/roles/sync`, { role_ids: roleIds })
    return response.data
  },

  // Permission endpoints
  async getPermissions(params: PermissionQueryParams = {}): Promise<PaginatedResponse<Permission>> {
    const response = await apiClient.get('/permissions', { params })
    return response.data
  },

  async getPermission(id: string | number): Promise<Permission> {
    const response = await apiClient.get(`/permissions/${id}`)
    return response.data
  },

  async getPermissionsGrouped(): Promise<Record<string, Permission[]>> {
    const response = await apiClient.get('/permissions/grouped')
    return response.data
  },

  async getPermissionsByResource(): Promise<Record<string, Permission[]>> {
    const response = await apiClient.get('/permissions/by-resource')
    return response.data
  },

  async createPermission(data: Partial<Permission>): Promise<Permission> {
    const response = await apiClient.post('/permissions', data)
    return response.data
  },

  async createBulkPermissions(permissions: Partial<Permission>[]): Promise<Permission[]> {
    const response = await apiClient.post('/permissions/bulk', { permissions })
    return response.data
  },

  async deletePermission(id: string | number): Promise<void> {
    await apiClient.delete(`/permissions/${id}`)
  },
}

export default iamApi
