import client from './client';
import type { ModuleMetadata } from '@/composables/useModuleMetadata';

/**
 * Module Metadata API Service
 * 
 * Provides API methods for fetching module configuration and metadata.
 */
export const moduleApi = {
  /**
   * Get all module metadata
   */
  async getAll() {
    const response = await client.get('/modules');
    return response.data;
  },

  /**
   * Get specific module metadata
   */
  async getModule(moduleId: string) {
    const response = await client.get(`/modules/${moduleId}`);
    return response.data;
  },

  /**
   * Get all module routes
   */
  async getRoutes() {
    const response = await client.get('/modules/routes');
    return response.data;
  },

  /**
   * Get all module permissions
   */
  async getPermissions() {
    const response = await client.get('/modules/permissions');
    return response.data;
  },
};

export default moduleApi;
