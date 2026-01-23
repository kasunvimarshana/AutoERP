import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { customerService } from '@/services/customerService'
import type { Customer, CustomerFilters } from '@/types/customer'

export const useCustomerStore = defineStore('customer', () => {
  // State
  const customers = ref<Customer[]>([])
  const currentCustomer = ref<Customer | null>(null)
  const loading = ref(false)
  const error = ref<string | null>(null)
  const pagination = ref({
    currentPage: 1,
    perPage: 15,
    total: 0,
    lastPage: 1,
  })

  // Getters
  const activeCustomers = computed(() =>
    customers.value.filter((c) => c.status === 'active'),
  )

  const totalCustomers = computed(() => customers.value.length)

  // Actions
  async function fetchCustomers(filters?: CustomerFilters) {
    loading.value = true
    error.value = null
    try {
      const response = await customerService.getCustomers(filters)
      customers.value = response.data?.data || response.data || []
      if (response.data?.meta) {
        pagination.value = {
          currentPage: response.data.meta.current_page,
          perPage: response.data.meta.per_page,
          total: response.data.meta.total,
          lastPage: response.data.meta.last_page,
        }
      }
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to fetch customers'
      throw err
    } finally {
      loading.value = false
    }
  }

  async function fetchCustomer(id: number) {
    loading.value = true
    error.value = null
    try {
      const response = await customerService.getCustomer(id)
      currentCustomer.value = response.data
      return response.data
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to fetch customer'
      throw err
    } finally {
      loading.value = false
    }
  }

  async function createCustomer(data: Partial<Customer>) {
    loading.value = true
    error.value = null
    try {
      const response = await customerService.createCustomer(data)
      customers.value.push(response.data)
      return response.data
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to create customer'
      throw err
    } finally {
      loading.value = false
    }
  }

  async function updateCustomer(id: number, data: Partial<Customer>) {
    loading.value = true
    error.value = null
    try {
      const response = await customerService.updateCustomer(id, data)
      const index = customers.value.findIndex((c) => c.id === id)
      if (index !== -1) {
        customers.value[index] = response.data
      }
      if (currentCustomer.value?.id === id) {
        currentCustomer.value = response.data
      }
      return response.data
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to update customer'
      throw err
    } finally {
      loading.value = false
    }
  }

  async function deleteCustomer(id: number) {
    loading.value = true
    error.value = null
    try {
      await customerService.deleteCustomer(id)
      customers.value = customers.value.filter((c) => c.id !== id)
      if (currentCustomer.value?.id === id) {
        currentCustomer.value = null
      }
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to delete customer'
      throw err
    } finally {
      loading.value = false
    }
  }

  function clearError() {
    error.value = null
  }

  return {
    // State
    customers,
    currentCustomer,
    loading,
    error,
    pagination,
    // Getters
    activeCustomers,
    totalCustomers,
    // Actions
    fetchCustomers,
    fetchCustomer,
    createCustomer,
    updateCustomer,
    deleteCustomer,
    clearError,
  }
})
