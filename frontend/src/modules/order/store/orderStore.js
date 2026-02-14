import { defineStore } from 'pinia'
import { ref } from 'vue'
import orderApi from '../api/orderApi'

export const useOrderStore = defineStore('order', () => {
  const items = ref([])
  const currentItem = ref(null)
  const loading = ref(false)
  const error = ref(null)

  const fetchAll = async (params = {}) => {
    loading.value = true
    error.value = null
    try {
      const response = await orderApi.getAll(params)
      items.value = response.data
      return response.data
    } catch (err) {
      error.value = err.message
      throw err
    } finally {
      loading.value = false
    }
  }

  const fetchById = async (id) => {
    loading.value = true
    error.value = null
    try {
      const response = await orderApi.getById(id)
      currentItem.value = response.data
      return response.data
    } catch (err) {
      error.value = err.message
      throw err
    } finally {
      loading.value = false
    }
  }

  const create = async (data) => {
    loading.value = true
    error.value = null
    try {
      const response = await orderApi.create(data)
      items.value.push(response.data)
      return response.data
    } catch (err) {
      error.value = err.message
      throw err
    } finally {
      loading.value = false
    }
  }

  const update = async (id, data) => {
    loading.value = true
    error.value = null
    try {
      const response = await orderApi.update(id, data)
      const index = items.value.findIndex(item => item.id === id)
      if (index !== -1) {
        items.value[index] = response.data
      }
      return response.data
    } catch (err) {
      error.value = err.message
      throw err
    } finally {
      loading.value = false
    }
  }

  const remove = async (id) => {
    loading.value = true
    error.value = null
    try {
      await orderApi.delete(id)
      items.value = items.value.filter(item => item.id !== id)
    } catch (err) {
      error.value = err.message
      throw err
    } finally {
      loading.value = false
    }
  }

  return {
    items,
    currentItem,
    loading,
    error,
    fetchAll,
    fetchById,
    create,
    update,
    remove
  }
})
