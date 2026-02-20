<template>
  <div
    v-if="lastPage > 1"
    class="flex items-center justify-between px-4 py-3 border-t border-gray-200 bg-white"
  >
    <p class="text-sm text-gray-500">
      Showing <span class="font-medium">{{ from }}</span>–<span class="font-medium">{{ to }}</span>
      of <span class="font-medium">{{ total }}</span> results
    </p>

    <div class="flex items-center gap-1">
      <button
        type="button"
        :disabled="page <= 1"
        class="px-2.5 py-1.5 text-sm rounded border transition-colors"
        :class="page <= 1
          ? 'text-gray-300 border-gray-200 cursor-not-allowed'
          : 'text-gray-600 border-gray-300 hover:bg-gray-50'"
        @click="$emit('prev')"
      >
        ‹ Prev
      </button>

      <template v-for="p in visiblePages" :key="p">
        <span v-if="p === '…'" class="px-2 text-gray-400 text-sm">…</span>
        <button
          v-else
          type="button"
          class="min-w-[2rem] px-2.5 py-1.5 text-sm rounded border transition-colors"
          :class="p === page
            ? 'bg-blue-600 text-white border-blue-600'
            : 'text-gray-600 border-gray-300 hover:bg-gray-50'"
          @click="$emit('goTo', p as number)"
        >
          {{ p }}
        </button>
      </template>

      <button
        type="button"
        :disabled="page >= lastPage"
        class="px-2.5 py-1.5 text-sm rounded border transition-colors"
        :class="page >= lastPage
          ? 'text-gray-300 border-gray-200 cursor-not-allowed'
          : 'text-gray-600 border-gray-300 hover:bg-gray-50'"
        @click="$emit('next')"
      >
        Next ›
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{
  page: number;
  lastPage: number;
  perPage: number;
  total: number;
}>();

defineEmits<{
  (e: 'prev'): void;
  (e: 'next'): void;
  (e: 'goTo', page: number): void;
}>();

const from = computed(() => Math.min((props.page - 1) * props.perPage + 1, props.total));
const to = computed(() => Math.min(props.page * props.perPage, props.total));

const visiblePages = computed<(number | '…')[]>(() => {
  const { page, lastPage } = props;
  if (lastPage <= 7) {
    return Array.from({ length: lastPage }, (_, i) => i + 1);
  }
  const pages: (number | '…')[] = [1];
  if (page > 3) pages.push('…');
  for (let p = Math.max(2, page - 1); p <= Math.min(lastPage - 1, page + 1); p++) {
    pages.push(p);
  }
  if (page < lastPage - 2) pages.push('…');
  pages.push(lastPage);
  return pages;
});
</script>
