<template>
  <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 p-5 flex items-start gap-4">
    <div :class="`w-10 h-10 rounded-lg ${color} flex items-center justify-center shrink-0`">
      <span class="w-5 h-5 text-white" aria-hidden="true" v-html="icon" />
    </div>
    <div class="min-w-0 flex-1">
      <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">{{ label }}</p>
      <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ value }}</p>
      <p
        v-if="change"
        :class="trend === 'up' ? 'text-emerald-600 dark:text-emerald-400' : trend === 'down' ? 'text-red-600 dark:text-red-400' : 'text-gray-400'"
        class="mt-0.5 text-xs"
      >
        {{ change }}
      </p>
    </div>
  </div>
</template>

<script setup>
defineProps({
    label: { type: String, required: true },
    value: { type: [String, Number], required: true },
    change: { type: String, default: '' },
    trend: { type: String, default: 'neutral' },
    icon: { type: String, required: true },
    color: { type: String, default: 'bg-indigo-600' },
});
</script>
