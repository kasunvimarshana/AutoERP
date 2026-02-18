import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import { tenantApi } from '@/api/tenant';
import type { Tenant, TenantSettings } from '@/types';

export const useTenantStore = defineStore('tenant', () => {
  // State
  const tenant = ref<Tenant | null>(null);
  const loading = ref(false);
  const error = ref<string | null>(null);

  // Getters
  const tenantId = computed(() => tenant.value?.id || null);
  const tenantName = computed(() => tenant.value?.name || '');
  const tenantLogo = computed(() => tenant.value?.logo || null);
  const tenantSettings = computed<TenantSettings | null>(() => tenant.value?.settings || null);
  const tenantFeatures = computed(() => tenant.value?.settings?.features || []);
  const subscriptionStatus = computed(() => tenant.value?.subscription?.status || null);
  const isActive = computed(() => subscriptionStatus.value === 'active' || subscriptionStatus.value === 'trial');

  // Actions
  async function initialize() {
    loading.value = true;
    error.value = null;

    try {
      tenant.value = await tenantApi.getCurrent();
      return tenant.value;
    } catch (err: any) {
      error.value = err.message || 'Failed to load tenant';
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function updateTenant(data: Partial<Tenant>) {
    loading.value = true;
    error.value = null;

    try {
      tenant.value = await tenantApi.update(data);
      return tenant.value;
    } catch (err: any) {
      error.value = err.message || 'Failed to update tenant';
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function updateSettings(settings: Record<string, any>) {
    loading.value = true;
    error.value = null;

    try {
      tenant.value = await tenantApi.updateSettings(settings);
      return tenant.value;
    } catch (err: any) {
      error.value = err.message || 'Failed to update settings';
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function uploadLogo(file: File) {
    loading.value = true;
    error.value = null;

    try {
      const response = await tenantApi.uploadLogo(file);
      if (tenant.value) {
        tenant.value.logo = response.url;
      }
      return response.url;
    } catch (err: any) {
      error.value = err.message || 'Failed to upload logo';
      throw err;
    } finally {
      loading.value = false;
    }
  }

  function setTenant(newTenant: Tenant) {
    tenant.value = newTenant;
  }

  function clearTenant() {
    tenant.value = null;
  }

  function hasFeature(feature: string): boolean {
    return tenantFeatures.value.includes(feature);
  }

  function getSetting(key: string, defaultValue?: any): any {
    if (!tenantSettings.value) return defaultValue;
    return (tenantSettings.value as any)[key] ?? defaultValue;
  }

  return {
    // State
    tenant,
    loading,
    error,
    // Getters
    tenantId,
    tenantName,
    tenantLogo,
    tenantSettings,
    tenantFeatures,
    subscriptionStatus,
    isActive,
    // Actions
    initialize,
    updateTenant,
    updateSettings,
    uploadLogo,
    setTenant,
    clearTenant,
    hasFeature,
    getSetting,
  };
});
