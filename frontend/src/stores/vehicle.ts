import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { vehicleService } from '@/services/vehicleService'
import type { Vehicle, VehicleFilters } from '@/types/vehicle'

export const useVehicleStore = defineStore('vehicle', () => {
  // State
  const vehicles = ref<Vehicle[]>([])
  const currentVehicle = ref<Vehicle | null>(null)
  const loading = ref(false)
  const error = ref<string | null>(null)
  const pagination = ref({
    currentPage: 1,
    perPage: 15,
    total: 0,
    lastPage: 1,
  })

  // Getters
  const activeVehicles = computed(() => vehicles.value.filter((v) => v.status === 'active'))

  const serviceDueVehicles = computed(() =>
    vehicles.value.filter((v) => {
      if (v.next_service_date && new Date(v.next_service_date) <= new Date()) {
        return true
      }
      if (v.next_service_mileage && v.current_mileage >= v.next_service_mileage) {
        return true
      }
      return false
    }),
  )

  const totalVehicles = computed(() => vehicles.value.length)

  // Actions
  async function fetchVehicles(filters?: VehicleFilters) {
    loading.value = true
    error.value = null
    try {
      const response = await vehicleService.getVehicles(filters)
      vehicles.value = response.data?.data || response.data || []
      if (response.data?.meta) {
        pagination.value = {
          currentPage: response.data.meta.current_page,
          perPage: response.data.meta.per_page,
          total: response.data.meta.total,
          lastPage: response.data.meta.last_page,
        }
      }
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to fetch vehicles'
      throw err
    } finally {
      loading.value = false
    }
  }

  async function fetchVehicle(id: number) {
    loading.value = true
    error.value = null
    try {
      const response = await vehicleService.getVehicle(id)
      currentVehicle.value = response.data
      return response.data
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to fetch vehicle'
      throw err
    } finally {
      loading.value = false
    }
  }

  async function createVehicle(data: Partial<Vehicle>) {
    loading.value = true
    error.value = null
    try {
      const response = await vehicleService.createVehicle(data)
      vehicles.value.push(response.data)
      return response.data
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to create vehicle'
      throw err
    } finally {
      loading.value = false
    }
  }

  async function updateVehicle(id: number, data: Partial<Vehicle>) {
    loading.value = true
    error.value = null
    try {
      const response = await vehicleService.updateVehicle(id, data)
      const index = vehicles.value.findIndex((v) => v.id === id)
      if (index !== -1) {
        vehicles.value[index] = response.data
      }
      if (currentVehicle.value?.id === id) {
        currentVehicle.value = response.data
      }
      return response.data
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to update vehicle'
      throw err
    } finally {
      loading.value = false
    }
  }

  async function deleteVehicle(id: number) {
    loading.value = true
    error.value = null
    try {
      await vehicleService.deleteVehicle(id)
      vehicles.value = vehicles.value.filter((v) => v.id !== id)
      if (currentVehicle.value?.id === id) {
        currentVehicle.value = null
      }
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to delete vehicle'
      throw err
    } finally {
      loading.value = false
    }
  }

  async function transferOwnership(
    id: number,
    data: { new_customer_id: number; reason?: string; notes?: string },
  ) {
    loading.value = true
    error.value = null
    try {
      const response = await vehicleService.transferOwnership(id, data)
      const index = vehicles.value.findIndex((v) => v.id === id)
      if (index !== -1) {
        vehicles.value[index] = response.data
      }
      if (currentVehicle.value?.id === id) {
        currentVehicle.value = response.data
      }
      return response.data
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to transfer ownership'
      throw err
    } finally {
      loading.value = false
    }
  }

  async function updateMileage(id: number, mileage: number) {
    loading.value = true
    error.value = null
    try {
      const response = await vehicleService.updateMileage(id, mileage)
      const index = vehicles.value.findIndex((v) => v.id === id)
      if (index !== -1) {
        vehicles.value[index] = response.data
      }
      if (currentVehicle.value?.id === id) {
        currentVehicle.value = response.data
      }
      return response.data
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to update mileage'
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
    vehicles,
    currentVehicle,
    loading,
    error,
    pagination,
    // Getters
    activeVehicles,
    serviceDueVehicles,
    totalVehicles,
    // Actions
    fetchVehicles,
    fetchVehicle,
    createVehicle,
    updateVehicle,
    deleteVehicle,
    transferOwnership,
    updateMileage,
    clearError,
  }
})
