<template>
  <div
    v-if="meta && meta.last_page > 1"
    class="flex items-center justify-between px-4 py-3 border-t border-gray-200 dark:border-gray-800"
  >
    <p class="text-sm text-gray-500 dark:text-gray-400">
      Page <span class="font-medium text-gray-900 dark:text-white">{{ meta.current_page }}</span>
      of <span class="font-medium text-gray-900 dark:text-white">{{ meta.last_page }}</span>
      <span v-if="meta.total"> · {{ meta.total }} total</span>
    </p>
    <div class="flex gap-2">
      <button
        type="button"
        :disabled="meta.current_page <= 1"
        class="inline-flex items-center rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 px-3 py-1.5 text-xs font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 disabled:opacity-40 disabled:cursor-not-allowed transition-colors"
        aria-label="Previous page"
        @click="$emit('change', meta.current_page - 1)"
      >
        ← Prev
      </button>
      <button
        type="button"
        :disabled="meta.current_page >= meta.last_page"
        class="inline-flex items-center rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 px-3 py-1.5 text-xs font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 disabled:opacity-40 disabled:cursor-not-allowed transition-colors"
        aria-label="Next page"
        @click="$emit('change', meta.current_page + 1)"
      >
        Next →
      </button>
    </div>
  </div>
</template>

<script setup>
defineProps({
    meta: {
        type: Object,
        default: null,
    },
});

defineEmits(['change']);
</script>
