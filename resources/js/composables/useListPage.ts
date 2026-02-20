/**
 * Generic composable that handles the standard list-page pattern:
 * loading state, error handling, data fetching, and server-side pagination.
 *
 * Usage:
 *   const { items, loading, error, page, total, load } = useListPage<Product>('/products');
 */
import { ref, computed } from 'vue';
import type { Ref } from 'vue';
import http from '@/services/http';
import type { PaginatedResponse } from '@/types/index';

export interface ListPageOptions<T> {
  /** API endpoint path relative to the base URL, e.g. "/products" */
  endpoint: string;
  /** Reactive extra query params (called on each load) */
  params?: () => Record<string, unknown>;
  /** Optional transform applied to the raw array before storing */
  transform?: (data: T[]) => T[];
}

export function useListPage<T>(options: ListPageOptions<T>) {
  const items: Ref<T[]> = ref([]) as Ref<T[]>;
  const loading = ref(false);
  const error = ref<string | null>(null);
  const page = ref(1);
  const perPage = ref(15);
  const total = ref(0);
  const lastPage = ref(1);

  const hasNextPage = computed(() => page.value < lastPage.value);
  const hasPrevPage = computed(() => page.value > 1);

  async function load(): Promise<void> {
    loading.value = true;
    error.value = null;
    try {
      const params: Record<string, unknown> = {
        page: page.value,
        per_page: perPage.value,
        ...(options.params?.() ?? {}),
      };

      const { data } = await http.get<PaginatedResponse<T> | T[]>(options.endpoint, { params });

      if (Array.isArray(data)) {
        const arr = data as T[];
        items.value = options.transform ? options.transform(arr) : arr;
        total.value = arr.length;
        lastPage.value = 1;
      } else {
        const paginated = data as PaginatedResponse<T>;
        const arr = paginated.data;
        items.value = options.transform ? options.transform(arr) : arr;
        total.value = paginated.total;
        lastPage.value = paginated.last_page;
        page.value = paginated.current_page;
      }
    } catch (e: unknown) {
      const err = e as { response?: { data?: { message?: string } } };
      error.value = err.response?.data?.message ?? 'Failed to load data.';
    } finally {
      loading.value = false;
    }
  }

  function nextPage(): void {
    if (hasNextPage.value) {
      page.value++;
      void load();
    }
  }

  function prevPage(): void {
    if (hasPrevPage.value) {
      page.value--;
      void load();
    }
  }

  function goToPage(p: number): void {
    page.value = p;
    void load();
  }

  return {
    items,
    loading,
    error,
    page,
    perPage,
    total,
    lastPage,
    hasNextPage,
    hasPrevPage,
    load,
    nextPage,
    prevPage,
    goToPage,
  };
}
