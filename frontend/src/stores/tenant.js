import { defineStore } from 'pinia'
import { ref, computed } from 'vue'

export const useTenantStore = defineStore('tenant', () => {
  const currentTenant = ref(null)
  const tenants = ref([])
  const loading = ref(false)

  const tenantId = computed(() => currentTenant.value?.id || null)
  const tenantName = computed(() => currentTenant.value?.name || '')

  function setCurrentTenant(tenant) {
    currentTenant.value = tenant
    if (tenant) {
      localStorage.setItem('tenant_id', tenant.id)
    } else {
      localStorage.removeItem('tenant_id')
    }
  }

  function loadTenantFromStorage() {
    const tenantId = localStorage.getItem('tenant_id')
    if (tenantId && tenants.value.length > 0) {
      const tenant = tenants.value.find(t => t.id === tenantId)
      if (tenant) {
        currentTenant.value = tenant
      }
    }
  }

  return {
    currentTenant,
    tenants,
    loading,
    tenantId,
    tenantName,
    setCurrentTenant,
    loadTenantFromStorage
  }
})
