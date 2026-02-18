import apiClient from './api';

const metadataService = {
  /**
   * Get complete metadata catalog
   */
  async getCatalog() {
    return await apiClient.get('/metadata/catalog');
  },

  /**
   * Get entity metadata by name
   */
  async getEntity(name) {
    return await apiClient.get(`/metadata/entity/${name}`);
  },

  /**
   * Get entities by module
   */
  async getModuleEntities(module) {
    return await apiClient.get(`/metadata/module/${module}`);
  },

  /**
   * Get field configuration for an entity
   */
  async getFieldConfig(entityName, context = 'form') {
    return await apiClient.get(`/metadata/entity/${entityName}/fields`, {
      params: { context }
    });
  },

  /**
   * Get validation rules for an entity
   */
  async getValidationRules(entityName) {
    return await apiClient.get(`/metadata/entity/${entityName}/validation`);
  },

  /**
   * Clear metadata cache
   */
  async clearCache() {
    return await apiClient.post('/metadata/cache/clear');
  },

  /**
   * Create entity metadata (admin only)
   */
  async createEntity(data) {
    return await apiClient.post('/metadata/entities', data);
  },

  /**
   * Update entity metadata (admin only)
   */
  async updateEntity(id, data) {
    return await apiClient.put(`/metadata/entities/${id}`, data);
  },

  /**
   * Get menu structure for current user
   */
  async getMenu() {
    return await apiClient.get('/menu');
  },

  /**
   * Get all menus (admin view)
   */
  async getAllMenus() {
    return await apiClient.get('/menu/all');
  },

  /**
   * Create menu item
   */
  async createMenu(data) {
    return await apiClient.post('/menu', data);
  },

  /**
   * Update menu item
   */
  async updateMenu(id, data) {
    return await apiClient.put(`/menu/${id}`, data);
  },

  /**
   * Delete menu item
   */
  async deleteMenu(id) {
    return await apiClient.delete(`/menu/${id}`);
  },

  /**
   * Reorder menu items
   */
  async reorderMenus(items) {
    return await apiClient.post('/menu/reorder', { items });
  },

  /**
   * Get enabled features
   */
  async getFeatures() {
    return await apiClient.get('/features');
  },

  /**
   * Check if a feature is enabled
   */
  async checkFeature(name) {
    return await apiClient.get(`/features/check/${name}`);
  },

  /**
   * Check multiple features at once
   */
  async checkMultipleFeatures(features) {
    return await apiClient.post('/features/check-multiple', { features });
  },

  /**
   * Get features by module
   */
  async getModuleFeatures(module) {
    return await apiClient.get(`/features/module/${module}`);
  },

  /**
   * Enable a feature
   */
  async enableFeature(name) {
    return await apiClient.post(`/features/${name}/enable`);
  },

  /**
   * Disable a feature
   */
  async disableFeature(name) {
    return await apiClient.post(`/features/${name}/disable`);
  },

  /**
   * Create a feature flag
   */
  async createFeature(data) {
    return await apiClient.post('/features', data);
  },

  /**
   * Update a feature flag
   */
  async updateFeature(id, data) {
    return await apiClient.put(`/features/${id}`, data);
  }
};

export default metadataService;
