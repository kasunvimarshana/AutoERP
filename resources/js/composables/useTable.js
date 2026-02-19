import { ref, computed } from 'vue';

export function useTable(options = {}) {
  const sortBy = ref(options.sortBy || null);
  const sortOrder = ref(options.sortOrder || 'asc');
  const filters = ref(options.filters || {});
  const search = ref(options.search || '');

  const sortData = (data) => {
    if (!sortBy.value || !data) return data;

    return [...data].sort((a, b) => {
      const aValue = getNestedValue(a, sortBy.value);
      const bValue = getNestedValue(b, sortBy.value);

      let comparison = 0;
      if (aValue > bValue) comparison = 1;
      if (aValue < bValue) comparison = -1;

      return sortOrder.value === 'asc' ? comparison : -comparison;
    });
  };

  const filterData = (data) => {
    if (!data) return data;

    let filtered = [...data];

    // Apply search
    if (search.value) {
      filtered = filtered.filter(row => {
        return Object.values(row).some(value => 
          String(value).toLowerCase().includes(search.value.toLowerCase())
        );
      });
    }

    // Apply filters
    Object.entries(filters.value).forEach(([key, value]) => {
      if (value !== null && value !== undefined && value !== '') {
        filtered = filtered.filter(row => {
          const rowValue = getNestedValue(row, key);
          return String(rowValue) === String(value);
        });
      }
    });

    return filtered;
  };

  const processData = (data) => {
    let processed = filterData(data);
    processed = sortData(processed);
    return processed;
  };

  const sort = (column) => {
    if (sortBy.value === column) {
      sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc';
    } else {
      sortBy.value = column;
      sortOrder.value = 'asc';
    }
  };

  const setFilter = (key, value) => {
    filters.value[key] = value;
  };

  const clearFilter = (key) => {
    delete filters.value[key];
  };

  const clearAllFilters = () => {
    filters.value = {};
    search.value = '';
  };

  const reset = () => {
    sortBy.value = null;
    sortOrder.value = 'asc';
    filters.value = {};
    search.value = '';
  };

  const isSorted = computed(() => (column) => {
    return sortBy.value === column;
  });

  const getSortIcon = computed(() => (column) => {
    if (sortBy.value !== column) return null;
    return sortOrder.value === 'asc' ? 'up' : 'down';
  });

  return {
    sortBy,
    sortOrder,
    filters,
    search,
    sortData,
    filterData,
    processData,
    sort,
    setFilter,
    clearFilter,
    clearAllFilters,
    reset,
    isSorted,
    getSortIcon,
  };
}

/**
 * Get nested value from object using dot notation path
 * @param {Object} obj - The object to traverse
 * @param {string} path - Dot-separated path (e.g., 'user.address.city')
 * @returns {*} The value at the specified path, or undefined if not found
 */
function getNestedValue(obj, path) {
  return path.split('.').reduce((current, key) => current?.[key], obj);
}
