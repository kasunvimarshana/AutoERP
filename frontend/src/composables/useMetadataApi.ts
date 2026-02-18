import { api } from '@/api/client';

/**
 * Composable for metadata API operations
 */
export function useMetadataApi() {
  /**
   * Fetch widget data from an API endpoint
   */
  const fetchWidgetData = async (endpoint: string, params?: Record<string, any>) => {
    try {
      const response = await api.get(endpoint, { params });
      return response.data;
    } catch (error: any) {
      throw new Error(error.response?.data?.message || 'Failed to fetch widget data');
    }
  };

  /**
   * Execute a custom action on a module record
   */
  const executeAction = async (
    moduleName: string,
    recordId: string | number,
    action: string,
    data?: any
  ) => {
    try {
      const response = await api.post(
        `/api/metadata/modules/${moduleName}/records/${recordId}/actions/${action}`,
        data
      );
      return response.data;
    } catch (error: any) {
      throw new Error(error.response?.data?.message || 'Failed to execute action');
    }
  };

  /**
   * Fetch dynamic form metadata
   */
  const getFormMetadata = async (formId: string) => {
    try {
      const response = await api.get(`/api/metadata/forms/${formId}`);
      return response.data;
    } catch (error: any) {
      throw new Error(error.response?.data?.message || 'Failed to fetch form metadata');
    }
  };

  /**
   * Fetch dynamic table metadata
   */
  const getTableMetadata = async (tableId: string) => {
    try {
      const response = await api.get(`/api/metadata/tables/${tableId}`);
      return response.data;
    } catch (error: any) {
      throw new Error(error.response?.data?.message || 'Failed to fetch table metadata');
    }
  };

  /**
   * Fetch dashboard metadata
   */
  const getDashboardMetadata = async (dashboardId?: string) => {
    try {
      const endpoint = dashboardId
        ? `/api/metadata/dashboards/${dashboardId}`
        : '/api/metadata/dashboards';
      const response = await api.get(endpoint);
      return response.data;
    } catch (error: any) {
      throw new Error(error.response?.data?.message || 'Failed to fetch dashboard metadata');
    }
  };

  /**
   * Fetch workflow metadata
   */
  const getWorkflowMetadata = async (workflowId: string) => {
    try {
      const response = await api.get(`/api/metadata/workflows/${workflowId}`);
      return response.data;
    } catch (error: any) {
      throw new Error(error.response?.data?.message || 'Failed to fetch workflow metadata');
    }
  };

  /**
   * Execute a workflow transition
   */
  const executeWorkflowTransition = async (
    workflowId: string,
    recordId: string | number,
    transitionId: string,
    data?: any
  ) => {
    try {
      const response = await api.post(
        `/api/metadata/workflows/${workflowId}/records/${recordId}/transitions/${transitionId}`,
        data
      );
      return response.data;
    } catch (error: any) {
      throw new Error(error.response?.data?.message || 'Failed to execute workflow transition');
    }
  };

  return {
    fetchWidgetData,
    executeAction,
    getFormMetadata,
    getTableMetadata,
    getDashboardMetadata,
    getWorkflowMetadata,
    executeWorkflowTransition,
  };
}
