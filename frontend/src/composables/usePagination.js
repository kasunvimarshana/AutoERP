import { ref, computed } from 'vue'

export function usePagination(initialPage = 1, initialPerPage = 10) {
  const currentPage = ref(initialPage)
  const perPage = ref(initialPerPage)
  const total = ref(0)

  const totalPages = computed(() => Math.ceil(total.value / perPage.value))

  const nextPage = () => {
    if (currentPage.value < totalPages.value) {
      currentPage.value++
    }
  }

  const prevPage = () => {
    if (currentPage.value > 1) {
      currentPage.value--
    }
  }

  const goToPage = (page) => {
    if (page >= 1 && page <= totalPages.value) {
      currentPage.value = page
    }
  }

  const reset = () => {
    currentPage.value = initialPage
    total.value = 0
  }

  return {
    currentPage,
    perPage,
    total,
    totalPages,
    nextPage,
    prevPage,
    goToPage,
    reset
  }
}
