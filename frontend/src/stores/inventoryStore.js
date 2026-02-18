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
