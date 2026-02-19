import { ref, computed } from 'vue';

export function usePagination(options = {}) {
  const currentPage = ref(options.initialPage || 1);
  const perPage = ref(options.perPage || 15);
  const total = ref(options.total || 0);

  const totalPages = computed(() => {
    return Math.ceil(total.value / perPage.value);
  });

  const startIndex = computed(() => {
    return (currentPage.value - 1) * perPage.value;
  });

  const endIndex = computed(() => {
    return Math.min(startIndex.value + perPage.value, total.value);
  });

  const hasNext = computed(() => {
    return currentPage.value < totalPages.value;
  });

  const hasPrevious = computed(() => {
    return currentPage.value > 1;
  });

  const goToPage = (page) => {
    if (page >= 1 && page <= totalPages.value) {
      currentPage.value = page;
    }
  };

  const nextPage = () => {
    if (hasNext.value) {
      currentPage.value++;
    }
  };

  const previousPage = () => {
    if (hasPrevious.value) {
      currentPage.value--;
    }
  };

  const setTotal = (newTotal) => {
    total.value = newTotal;
    if (currentPage.value > totalPages.value) {
      currentPage.value = Math.max(1, totalPages.value);
    }
  };

  const setPerPage = (newPerPage) => {
    perPage.value = newPerPage;
    currentPage.value = 1;
  };

  const reset = () => {
    currentPage.value = 1;
    total.value = 0;
  };

  return {
    currentPage,
    perPage,
    total,
    totalPages,
    startIndex,
    endIndex,
    hasNext,
    hasPrevious,
    goToPage,
    nextPage,
    previousPage,
    setTotal,
    setPerPage,
    reset,
  };
}
