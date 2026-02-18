#!/bin/bash

# IAM Store
cat > src/stores/iamStore.js << 'EOF'
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
EOF

# CRM Store
cat > src/stores/crmStore.js << 'EOF'
import { defineStore } from 'pinia'
import { ref } from 'vue'
import crmService from '../services/crmService'
import { useNotificationStore } from './notificationStore'

export const useCRMStore = defineStore('crm', () => {
  const customers = ref([])
  const contacts = ref([])
  const loading = ref(false)
  
  const notificationStore = useNotificationStore()
  
  const fetchCustomers = async (params = {}) => {
    loading.value = true
    try {
      const response = await crmService.getCustomers(params)
      customers.value = response.data
      return response
    } catch (error) {
      notificationStore.error('Failed to fetch customers')
      throw error
    } finally {
      loading.value = false
    }
  }
  
  const createCustomer = async (data) => {
    try {
      const response = await crmService.createCustomer(data)
      customers.value.unshift(response.data)
      notificationStore.success('Customer created successfully')
      return response
    } catch (error) {
      notificationStore.error('Failed to create customer')
      throw error
    }
  }
  
  const updateCustomer = async (id, data) => {
    try {
      const response = await crmService.updateCustomer(id, data)
      const index = customers.value.findIndex(c => c.id === id)
      if (index !== -1) {
        customers.value[index] = response.data
      }
      notificationStore.success('Customer updated successfully')
      return response
    } catch (error) {
      notificationStore.error('Failed to update customer')
      throw error
    }
  }
  
  const deleteCustomer = async (id) => {
    try {
      await crmService.deleteCustomer(id)
      customers.value = customers.value.filter(c => c.id !== id)
      notificationStore.success('Customer deleted successfully')
    } catch (error) {
      notificationStore.error('Failed to delete customer')
      throw error
    }
  }
  
  const fetchContacts = async (params = {}) => {
    loading.value = true
    try {
      const response = await crmService.getContacts(params)
      contacts.value = response.data
      return response
    } catch (error) {
      notificationStore.error('Failed to fetch contacts')
      throw error
    } finally {
      loading.value = false
    }
  }
  
  const createContact = async (data) => {
    try {
      const response = await crmService.createContact(data)
      contacts.value.unshift(response.data)
      notificationStore.success('Contact created successfully')
      return response
    } catch (error) {
      notificationStore.error('Failed to create contact')
      throw error
    }
  }
  
  return {
    customers,
    contacts,
    loading,
    fetchCustomers,
    createCustomer,
    updateCustomer,
    deleteCustomer,
    fetchContacts,
    createContact
  }
})
EOF

# Inventory Store (extend existing)
cat > src/stores/inventoryStore.js << 'EOF'
import { defineStore } from 'pinia'
import { ref } from 'vue'
import inventoryService from '../services/inventoryService'
import { useNotificationStore } from './notificationStore'

export const useInventoryStore = defineStore('inventory', () => {
  const products = ref([])
  const stockLedgers = ref([])
  const stockMovements = ref([])
  const warehouses = ref([])
  const loading = ref(false)
  
  const notificationStore = useNotificationStore()
  
  // Products
  const fetchProducts = async (params = {}) => {
    loading.value = true
    try {
      const response = await inventoryService.getProducts(params)
      products.value = response.data
      return response
    } catch (error) {
      notificationStore.error('Failed to fetch products')
      throw error
    } finally {
      loading.value = false
    }
  }
  
  const createProduct = async (data) => {
    try {
      const response = await inventoryService.createProduct(data)
      products.value.unshift(response.data)
      notificationStore.success('Product created successfully')
      return response
    } catch (error) {
      notificationStore.error('Failed to create product')
      throw error
    }
  }
  
  const updateProduct = async (id, data) => {
    try {
      const response = await inventoryService.updateProduct(id, data)
      const index = products.value.findIndex(p => p.id === id)
      if (index !== -1) {
        products.value[index] = response.data
      }
      notificationStore.success('Product updated successfully')
      return response
    } catch (error) {
      notificationStore.error('Failed to update product')
      throw error
    }
  }
  
  const deleteProduct = async (id) => {
    try {
      await inventoryService.deleteProduct(id)
      products.value = products.value.filter(p => p.id !== id)
      notificationStore.success('Product deleted successfully')
    } catch (error) {
      notificationStore.error('Failed to delete product')
      throw error
    }
  }
  
  // Stock Ledgers
  const fetchStockLedgers = async (params = {}) => {
    loading.value = true
    try {
      const response = await inventoryService.getStockLedgers(params)
      stockLedgers.value = response.data
      return response
    } catch (error) {
      notificationStore.error('Failed to fetch stock ledgers')
      throw error
    } finally {
      loading.value = false
    }
  }
  
  // Stock Movements
  const fetchStockMovements = async (params = {}) => {
    loading.value = true
    try {
      const response = await inventoryService.getStockMovements(params)
      stockMovements.value = response.data
      return response
    } catch (error) {
      notificationStore.error('Failed to fetch stock movements')
      throw error
    } finally {
      loading.value = false
    }
  }
  
  // Warehouses
  const fetchWarehouses = async (params = {}) => {
    loading.value = true
    try {
      const response = await inventoryService.getWarehouses(params)
      warehouses.value = response.data
      return response
    } catch (error) {
      notificationStore.error('Failed to fetch warehouses')
      throw error
    } finally {
      loading.value = false
    }
  }
  
  const createWarehouse = async (data) => {
    try {
      const response = await inventoryService.createWarehouse(data)
      warehouses.value.unshift(response.data)
      notificationStore.success('Warehouse created successfully')
      return response
    } catch (error) {
      notificationStore.error('Failed to create warehouse')
      throw error
    }
  }
  
  return {
    products,
    stockLedgers,
    stockMovements,
    warehouses,
    loading,
    fetchProducts,
    createProduct,
    updateProduct,
    deleteProduct,
    fetchStockLedgers,
    fetchStockMovements,
    fetchWarehouses,
    createWarehouse
  }
})
EOF

echo "Module stores created successfully"
