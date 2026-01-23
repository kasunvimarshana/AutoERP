import { ref, computed, type Ref } from 'vue'
import type { QueryParams, PaginatedResponse } from '@/types/api'

/**
 * usePagination Composable
 * Provides pagination state and methods for list views
 */

interface UsePaginationOptions {
  initialPage?: number
  initialPerPage?: number
  onPageChange?: (page: number) => void
}

export function usePagination(options: UsePaginationOptions = {}) {
  const currentPage = ref(options.initialPage || 1)
  const perPage = ref(options.initialPerPage || 15)
  const totalItems = ref(0)
  const totalPages = ref(0)

  const hasPrevPage = computed(() => currentPage.value > 1)
  const hasNextPage = computed(() => currentPage.value < totalPages.value)

  function setPage(page: number) {
    if (page >= 1 && page <= totalPages.value) {
      currentPage.value = page
      
      if (options.onPageChange) {
        options.onPageChange(page)
      }
    }
  }

  function nextPage() {
    if (hasNextPage.value) {
      setPage(currentPage.value + 1)
    }
  }

  function prevPage() {
    if (hasPrevPage.value) {
      setPage(currentPage.value - 1)
    }
  }

  function setPerPage(value: number) {
    perPage.value = value
    currentPage.value = 1 // Reset to first page
  }

  function updateFromResponse(response: PaginatedResponse<any>) {
    if (response.meta) {
      currentPage.value = response.meta.current_page || 1
      perPage.value = response.meta.per_page || 15
      totalItems.value = response.meta.total || 0
      totalPages.value = response.meta.last_page || 1
    }
  }

  function getQueryParams(): QueryParams {
    return {
      page: currentPage.value,
      per_page: perPage.value,
    }
  }

  function reset() {
    currentPage.value = options.initialPage || 1
    perPage.value = options.initialPerPage || 15
    totalItems.value = 0
    totalPages.value = 0
  }

  return {
    currentPage,
    perPage,
    totalItems,
    totalPages,
    hasPrevPage,
    hasNextPage,
    setPage,
    nextPage,
    prevPage,
    setPerPage,
    updateFromResponse,
    getQueryParams,
    reset,
  }
}
