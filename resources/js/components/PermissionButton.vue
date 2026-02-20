<template>
  <button
    v-if="can(permission)"
    v-bind="$attrs"
    :type="type"
    :disabled="disabled"
    :class="buttonClass"
  >
    <slot />
  </button>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { usePermission } from '@/composables/usePermission';

const { can } = usePermission();

const props = withDefaults(
  defineProps<{
    permission: string;
    variant?: 'primary' | 'danger' | 'secondary' | 'ghost';
    size?: 'sm' | 'md';
    type?: 'button' | 'submit' | 'reset';
    disabled?: boolean;
  }>(),
  {
    variant: 'primary',
    size: 'md',
    type: 'button',
    disabled: false,
  },
);

const buttonClass = computed<string>(() => {
  const base =
    'inline-flex items-center gap-1.5 font-medium rounded-lg transition-colors disabled:opacity-60 focus:outline-none focus:ring-2 focus:ring-offset-1';

  const size = props.size === 'sm' ? 'px-3 py-1 text-xs' : 'px-4 py-1.5 text-sm';

  const variant = {
    primary: 'bg-blue-600 hover:bg-blue-700 text-white focus:ring-blue-500',
    danger: 'bg-red-600 hover:bg-red-700 text-white focus:ring-red-500',
    secondary: 'bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 focus:ring-gray-400',
    ghost: 'text-blue-600 hover:underline focus:ring-blue-400',
  }[props.variant];

  return `${base} ${size} ${variant}`;
});
</script>
