import { defineStore } from 'pinia'
import { ref } from 'vue'
import iamService from '../services/iamService'
import { useNotificationStore } from './notificationStore'

export const useIAMStore = defineStore('iam', () => {
  const users = ref([])
  const roles = ref([])
  const permissions = ref([])
  const loading = ref(false)
  
  const notificationStore = useNotificationStore()
  
  // Users
  const fetchUsers = async (params = {}) => {
    loading.value = true
    try {
      const response = await iamService.getUsers(params)
      users.value = response.data
      return response
    } catch (error) {
      notificationStore.error('Failed to fetch users')
      throw error
    } finally {
      loading.value = false
    }
  }
  
  const createUser = async (data) => {
    try {
      const response = await iamService.createUser(data)
      users.value.unshift(response.data)
      notificationStore.success('User created successfully')
      return response
    } catch (error) {
      notificationStore.error('Failed to create user')
      throw error
    }
  }
  
  const updateUser = async (id, data) => {
    try {
      const response = await iamService.updateUser(id, data)
      const index = users.value.findIndex(u => u.id === id)
      if (index !== -1) {
        users.value[index] = response.data
      }
      notificationStore.success('User updated successfully')
      return response
    } catch (error) {
      notificationStore.error('Failed to update user')
      throw error
    }
  }
  
  const deleteUser = async (id) => {
    try {
      await iamService.deleteUser(id)
      users.value = users.value.filter(u => u.id !== id)
      notificationStore.success('User deleted successfully')
    } catch (error) {
      notificationStore.error('Failed to delete user')
      throw error
    }
  }
  
  // Roles
  const fetchRoles = async (params = {}) => {
    loading.value = true
    try {
      const response = await iamService.getRoles(params)
      roles.value = response.data
      return response
    } catch (error) {
      notificationStore.error('Failed to fetch roles')
      throw error
    } finally {
      loading.value = false
    }
  }
  
  const createRole = async (data) => {
    try {
      const response = await iamService.createRole(data)
      roles.value.unshift(response.data)
      notificationStore.success('Role created successfully')
      return response
    } catch (error) {
      notificationStore.error('Failed to create role')
      throw error
    }
  }
  
  // Permissions
  const fetchPermissions = async (params = {}) => {
    try {
      const response = await iamService.getPermissions(params)
      permissions.value = response.data
      return response
    } catch (error) {
      notificationStore.error('Failed to fetch permissions')
      throw error
    }
  }
  
  return {
    users,
    roles,
    permissions,
    loading,
    fetchUsers,
    createUser,
    updateUser,
    deleteUser,
    fetchRoles,
    createRole,
    fetchPermissions
  }
})
