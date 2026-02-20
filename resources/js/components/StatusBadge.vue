<template>
  <span class="px-2 py-0.5 rounded-full text-xs font-medium" :class="colorClass">
    {{ label ?? status }}
  </span>
</template>

<script setup lang="ts">
import { computed } from 'vue';

const STATUS_MAP: Record<string, string> = {
  // Order / Purchase
  draft: 'bg-gray-100 text-gray-700',
  confirmed: 'bg-green-100 text-green-700',
  completed: 'bg-blue-100 text-blue-700',
  cancelled: 'bg-red-100 text-red-700',
  ordered: 'bg-blue-100 text-blue-700',
  received: 'bg-green-100 text-green-700',
  // Invoice
  sent: 'bg-blue-100 text-blue-700',
  paid: 'bg-green-100 text-green-700',
  void: 'bg-red-100 text-red-700',
  overdue: 'bg-orange-100 text-orange-700',
  // POS
  open: 'bg-yellow-100 text-yellow-700',
  // Lead
  new: 'bg-sky-100 text-sky-700',
  contacted: 'bg-yellow-100 text-yellow-700',
  qualified: 'bg-green-100 text-green-700',
  lost: 'bg-red-100 text-red-700',
  converted: 'bg-purple-100 text-purple-700',
  // Account types
  asset: 'bg-blue-100 text-blue-700',
  liability: 'bg-red-100 text-red-700',
  equity: 'bg-purple-100 text-purple-700',
  revenue: 'bg-green-100 text-green-700',
  expense: 'bg-orange-100 text-orange-700',
  // Journal
  posted: 'bg-green-100 text-green-700',
  // User
  active: 'bg-green-100 text-green-700',
  suspended: 'bg-red-100 text-red-700',
};

const props = defineProps<{
  status: string;
  label?: string;
}>();

const colorClass = computed(() => STATUS_MAP[props.status] ?? 'bg-gray-100 text-gray-700');
</script>
