import { defineStore } from 'pinia'
import { ref } from 'vue'

export const useUIStore = defineStore('ui', () => {
  const loading = ref(false)
  const sidebarOpen = ref(true)
  const modal = ref(null)
  
  const setLoading = (value) => {
    loading.value = value
  }
  
  const toggleSidebar = () => {
    sidebarOpen.value = !sidebarOpen.value
  }
  
  const openModal = (component, props = {}) => {
    modal.value = { component, props }
  }
  
  const closeModal = () => {
    modal.value = null
  }
  
  return {
    loading,
    sidebarOpen,
    modal,
    setLoading,
    toggleSidebar,
    openModal,
    closeModal
  }
})
