import { ref, computed, watch } from 'vue';
import type { PaginatedResponse, PaginationMeta } from '@/types';

interface UsePaginationOptions {
  perPage?: number;
  onPageChange?: (page: number) => void;
}

export function usePagination(options: UsePaginationOptions = {}) {
  const { perPage: initialPerPage = 10, onPageChange } = options;

  const currentPage = ref(1);
  const perPage = ref(initialPerPage);
  const total = ref(0);
  const lastPage = ref(1);
  const from = ref(0);
  const to = ref(0);

  const hasNextPage = computed(() => currentPage.value < lastPage.value);
  const hasPrevPage = computed(() => currentPage.value > 1);

  const setMeta = (meta: PaginationMeta) => {
    currentPage.value = meta.current_page;
    lastPage.value = meta.last_page;
    perPage.value = meta.per_page;
    total.value = meta.total;
    from.value = meta.from;
    to.value = meta.to;
  };

  const setPage = (page: number) => {
    if (page < 1 || page > lastPage.value) return;
    currentPage.value = page;
  };

  const nextPage = () => {
    if (hasNextPage.value) {
      currentPage.value++;
    }
  };

  const prevPage = () => {
    if (hasPrevPage.value) {
      currentPage.value--;
    }
  };

  const firstPage = () => {
    currentPage.value = 1;
  };

  const goToLastPage = () => {
    currentPage.value = lastPage.value;
  };

  const setPerPage = (value: number) => {
    perPage.value = value;
    currentPage.value = 1;
  };

  const reset = () => {
    currentPage.value = 1;
    perPage.value = initialPerPage;
    total.value = 0;
    lastPage.value = 1;
    from.value = 0;
    to.value = 0;
  };

  watch(currentPage, (newPage) => {
    if (onPageChange) {
      onPageChange(newPage);
    }
  });

  return {
    currentPage,
    perPage,
    total,
    lastPage,
    from,
    to,
    hasNextPage,
    hasPrevPage,
    setMeta,
    setPage,
    nextPage,
    prevPage,
    firstPage,
    goToLastPage,
    setPerPage,
    reset,
  };
}
