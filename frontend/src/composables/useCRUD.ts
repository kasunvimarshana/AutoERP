/**
 * Generic CRUD Operations Composable
 * Provides reusable CRUD operations for any entity
 */

import { ref, computed, Ref } from 'vue';
import { useRouter } from 'vue-router';
import type { AxiosInstance } from 'axios';
import apiClient from '@/api/client';
import { useErrorHandler } from './useErrorHandler';

export interface CRUDConfig {
  module: string;
  entity: string;
  apiClient?: AxiosInstance;
  baseUrl?: string;
}

export interface PaginationData {
  currentPage: number;
  lastPage: number;
  perPage: number;
  total: number;
  from: number;
  to: number;
}

export interface CRUDFilters {
  [key: string]: any;
}

export interface CRUDSort {
  field: string;
  direction: 'asc' | 'desc';
}

export interface CRUDResponse<T> {
  data: T[];
  meta?: {
    pagination?: PaginationData;
    [key: string]: any;
  };
}

export function useCRUD<T = any>(config: CRUDConfig) {
  const { module, entity, baseUrl } = config;
  const client = config.apiClient || apiClient;
  const router = useRouter();
  const { handleError, handleAsync } = useErrorHandler();

  // State
  const items = ref<T[]>([]) as Ref<T[]>;
  const currentItem = ref<T | null>(null) as Ref<T | null>;
  const loading = ref(false);
  const saving = ref(false);
  const deleting = ref(false);
  
  const pagination = ref<PaginationData>({
    currentPage: 1,
    lastPage: 1,
    perPage: 15,
    total: 0,
    from: 0,
    to: 0,
  });

  const filters = ref<CRUDFilters>({});
  const sort = ref<CRUDSort | null>(null);

  // Computed
  const hasItems = computed(() => items.value.length > 0);
  const isEmpty = computed(() => !loading.value && items.value.length === 0);
  const hasNextPage = computed(() => pagination.value.currentPage < pagination.value.lastPage);
  const hasPrevPage = computed(() => pagination.value.currentPage > 1);

  // Build base URL
  const getBaseUrl = () => {
    if (baseUrl) return baseUrl;
    return `/api/v1/${module}/${entity}`;
  };

  /**
   * Build query parameters
   */
  const buildQueryParams = (page = 1, additionalParams: Record<string, any> = {}) => {
    const params: Record<string, any> = {
      page,
      per_page: pagination.value.perPage,
      ...additionalParams,
    };

    // Add filters
    if (Object.keys(filters.value).length > 0) {
      params.filters = JSON.stringify(filters.value);
    }

    // Add sort
    if (sort.value) {
      params.sort_by = sort.value.field;
      params.sort_direction = sort.value.direction;
    }

    return params;
  };

  /**
   * Fetch all items (list)
   */
  const fetchAll = async (page = 1, queryParams: Record<string, any> = {}) => {
    loading.value = true;
    const { data, error } = await handleAsync(async () => {
      const params = buildQueryParams(page, queryParams);
      const response = await client.get<CRUDResponse<T>>(getBaseUrl(), { params });
      return response.data;
    });

    loading.value = false;

    if (data) {
      items.value = data.data;
      if (data.meta?.pagination) {
        pagination.value = data.meta.pagination;
      }
      return { data: items.value, error: null };
    }

    return { data: null, error };
  };

  /**
   * Fetch single item by ID
   */
  const fetchOne = async (id: string | number) => {
    loading.value = true;
    const { data, error } = await handleAsync(async () => {
      const response = await client.get<{ data: T }>(`${getBaseUrl()}/${id}`);
      return response.data.data;
    });

    loading.value = false;

    if (data) {
      currentItem.value = data;
      return { data, error: null };
    }

    return { data: null, error };
  };

  /**
   * Create new item
   */
  const create = async (data: Partial<T>, refreshList = false) => {
    saving.value = true;
    const result = await handleAsync(async () => {
      const response = await client.post<{ data: T }>(getBaseUrl(), data);
      return response.data.data;
    });

    saving.value = false;

    if (result.data) {
      currentItem.value = result.data;
      // Optionally refresh list
      if (refreshList) {
        await fetchAll(pagination.value.currentPage);
      }
      return { data: result.data, error: null };
    }

    return { data: null, error: result.error };
  };

  /**
   * Update existing item
   */
  const update = async (id: string | number, data: Partial<T>) => {
    saving.value = true;
    const result = await handleAsync(async () => {
      const response = await client.put<{ data: T }>(`${getBaseUrl()}/${id}`, data);
      return response.data.data;
    });

    saving.value = false;

    if (result.data) {
      currentItem.value = result.data;
      // Update in list if present
      const index = items.value.findIndex((item: any) => item.id === id);
      if (index !== -1) {
        items.value[index] = result.data;
      }
      return { data: result.data, error: null };
    }

    return { data: null, error: result.error };
  };

  /**
   * Delete item
   */
  const remove = async (id: string | number) => {
    deleting.value = true;
    const result = await handleAsync(async () => {
      await client.delete(`${getBaseUrl()}/${id}`);
      return true;
    });

    deleting.value = false;

    if (result.data) {
      // Remove from list
      items.value = items.value.filter((item: any) => item.id !== id);
      // Update pagination total
      pagination.value.total = Math.max(0, pagination.value.total - 1);
      return { data: true, error: null };
    }

    return { data: false, error: result.error };
  };

  /**
   * Bulk delete items
   */
  const bulkRemove = async (ids: Array<string | number>) => {
    deleting.value = true;
    const result = await handleAsync(async () => {
      await client.post(`${getBaseUrl()}/bulk-delete`, { ids });
      return true;
    });

    deleting.value = false;

    if (result.data) {
      // Remove from list
      items.value = items.value.filter((item: any) => !ids.includes(item.id));
      // Update pagination total
      pagination.value.total = Math.max(0, pagination.value.total - ids.length);
      return { data: true, error: null };
    }

    return { data: false, error: result.error };
  };

  /**
   * Change page
   */
  const changePage = async (page: number) => {
    if (page < 1 || page > pagination.value.lastPage) return;
    await fetchAll(page);
  };

  /**
   * Next page
   */
  const nextPage = async () => {
    if (hasNextPage.value) {
      await changePage(pagination.value.currentPage + 1);
    }
  };

  /**
   * Previous page
   */
  const prevPage = async () => {
    if (hasPrevPage.value) {
      await changePage(pagination.value.currentPage - 1);
    }
  };

  /**
   * Update filters
   */
  const updateFilters = async (newFilters: CRUDFilters) => {
    filters.value = { ...newFilters };
    await fetchAll(1); // Reset to first page when filtering
  };

  /**
   * Update sort
   */
  const updateSort = async (field: string, direction: 'asc' | 'desc') => {
    sort.value = { field, direction };
    await fetchAll(pagination.value.currentPage);
  };

  /**
   * Clear filters
   */
  const clearFilters = async () => {
    filters.value = {};
    await fetchAll(1);
  };

  /**
   * Refresh current page
   */
  const refresh = async () => {
    await fetchAll(pagination.value.currentPage);
  };

  /**
   * Navigate to create page
   */
  const navigateToCreate = () => {
    router.push({ name: `${module}-${entity}-create` });
  };

  /**
   * Navigate to edit page
   */
  const navigateToEdit = (id: string | number) => {
    router.push({ name: `${module}-${entity}-edit`, params: { id } });
  };

  /**
   * Navigate to detail page
   */
  const navigateToDetail = (id: string | number) => {
    router.push({ name: `${module}-${entity}-detail`, params: { id } });
  };

  /**
   * Navigate to list page
   */
  const navigateToList = () => {
    router.push({ name: `${module}-${entity}` });
  };

  /**
   * Reset state
   */
  const reset = () => {
    items.value = [];
    currentItem.value = null;
    loading.value = false;
    saving.value = false;
    deleting.value = false;
    filters.value = {};
    sort.value = null;
    pagination.value = {
      currentPage: 1,
      lastPage: 1,
      perPage: 15,
      total: 0,
      from: 0,
      to: 0,
    };
  };

  return {
    // State
    items,
    currentItem,
    loading,
    saving,
    deleting,
    pagination,
    filters,
    sort,

    // Computed
    hasItems,
    isEmpty,
    hasNextPage,
    hasPrevPage,

    // Methods
    fetchAll,
    fetchOne,
    create,
    update,
    remove,
    bulkRemove,
    changePage,
    nextPage,
    prevPage,
    updateFilters,
    updateSort,
    clearFilters,
    refresh,
    navigateToCreate,
    navigateToEdit,
    navigateToDetail,
    navigateToList,
    reset,
  };
}
