import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import metadataService from '../services/metadataService';

export const useMetadataStore = defineStore('metadata', () => {
  // State
  const catalog = ref([]);
  const entities = ref({});
  const menus = ref([]);
  const features = ref({});
  const loading = ref(false);
  const error = ref(null);

  // Getters
  const getEntity = computed(() => (name) => {
    return entities.value[name] || null;
  });

  const getEntityByModule = computed(() => (module) => {
    return catalog.value.filter(entity => entity.module === module);
  });

  const isFeatureEnabled = computed(() => (featureName) => {
    return features.value[featureName] === true;
  });

  const getMenuTree = computed(() => {
    return menus.value;
  });

  // Actions
  async function fetchCatalog() {
    loading.value = true;
    error.value = null;
    try {
      const response = await metadataService.getCatalog();
      catalog.value = response.data || [];
      
      // Index entities by name for quick lookup
      catalog.value.forEach(entity => {
        entities.value[entity.name] = entity;
      });
      
      return catalog.value;
    } catch (err) {
      error.value = err.message;
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function fetchEntity(name) {
    loading.value = true;
    error.value = null;
    try {
      const response = await metadataService.getEntity(name);
      const entity = response.data;
      
      // Cache the entity
      entities.value[name] = entity;
      
      return entity;
    } catch (err) {
      error.value = err.message;
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function fetchModuleEntities(module) {
    loading.value = true;
    error.value = null;
    try {
      const response = await metadataService.getModuleEntities(module);
      const moduleEntities = response.data || [];
      
      // Cache entities
      moduleEntities.forEach(entity => {
        entities.value[entity.name] = entity;
      });
      
      return moduleEntities;
    } catch (err) {
      error.value = err.message;
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function fetchFieldConfig(entityName, context = 'form') {
    loading.value = true;
    error.value = null;
    try {
      const response = await metadataService.getFieldConfig(entityName, context);
      return response.data || [];
    } catch (err) {
      error.value = err.message;
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function fetchValidationRules(entityName) {
    loading.value = true;
    error.value = null;
    try {
      const response = await metadataService.getValidationRules(entityName);
      return response.data || {};
    } catch (err) {
      error.value = err.message;
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function fetchMenu() {
    loading.value = true;
    error.value = null;
    try {
      const response = await metadataService.getMenu();
      menus.value = response.data || [];
      return menus.value;
    } catch (err) {
      error.value = err.message;
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function fetchFeatures() {
    loading.value = true;
    error.value = null;
    try {
      const response = await metadataService.getFeatures();
      const featureList = response.data || [];
      
      // Index features by name
      features.value = {};
      featureList.forEach(feature => {
        features.value[feature.name] = feature.is_enabled;
      });
      
      return features.value;
    } catch (err) {
      error.value = err.message;
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function checkFeature(featureName) {
    try {
      const response = await metadataService.checkFeature(featureName);
      const isEnabled = response.data?.enabled || false;
      features.value[featureName] = isEnabled;
      return isEnabled;
    } catch (err) {
      error.value = err.message;
      return false;
    }
  }

  async function checkMultipleFeatures(featureNames) {
    try {
      const response = await metadataService.checkMultipleFeatures(featureNames);
      Object.assign(features.value, response.data || {});
      return response.data || {};
    } catch (err) {
      error.value = err.message;
      return {};
    }
  }

  async function initialize() {
    // Initialize all metadata on app startup
    await Promise.all([
      fetchCatalog(),
      fetchMenu(),
      fetchFeatures(),
    ]);
  }

  function clearCache() {
    catalog.value = [];
    entities.value = {};
    menus.value = [];
    features.value = {};
  }

  return {
    // State
    catalog,
    entities,
    menus,
    features,
    loading,
    error,
    
    // Getters
    getEntity,
    getEntityByModule,
    isFeatureEnabled,
    getMenuTree,
    
    // Actions
    fetchCatalog,
    fetchEntity,
    fetchModuleEntities,
    fetchFieldConfig,
    fetchValidationRules,
    fetchMenu,
    fetchFeatures,
    checkFeature,
    checkMultipleFeatures,
    initialize,
    clearCache,
  };
});
