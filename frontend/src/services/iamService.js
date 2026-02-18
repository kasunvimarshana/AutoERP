import apiClient from './api'

export default {
  // Users
  getUsers(params = {}) {
    return apiClient.get('/iam/users', { params })
  },
  getUser(id) {
    return apiClient.get(`/iam/users/${id}`)
  },
  createUser(data) {
    return apiClient.post('/iam/users', data)
  },
  updateUser(id, data) {
    return apiClient.put(`/iam/users/${id}`, data)
  },
  deleteUser(id) {
    return apiClient.delete(`/iam/users/${id}`)
  },
  
  // Roles
  getRoles(params = {}) {
    return apiClient.get('/iam/roles', { params })
  },
  getRole(id) {
    return apiClient.get(`/iam/roles/${id}`)
  },
  createRole(data) {
    return apiClient.post('/iam/roles', data)
  },
  updateRole(id, data) {
    return apiClient.put(`/iam/roles/${id}`, data)
  },
  deleteRole(id) {
    return apiClient.delete(`/iam/roles/${id}`)
  },
  
  // Permissions
  getPermissions(params = {}) {
    return apiClient.get('/iam/permissions', { params })
  },
  getPermission(id) {
    return apiClient.get(`/iam/permissions/${id}`)
  },
  createPermission(data) {
    return apiClient.post('/iam/permissions', data)
  },
  updatePermission(id, data) {
    return apiClient.put(`/iam/permissions/${id}`, data)
  },
  deletePermission(id) {
    return apiClient.delete(`/iam/permissions/${id}`)
  }
}
