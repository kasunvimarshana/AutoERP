<template>
  <div class="bg-white shadow overflow-hidden sm:rounded-lg">
    <div class="px-4 py-5 sm:px-6 flex justify-between items-center">
      <div>
        <h3 class="text-lg leading-6 font-medium text-gray-900">
          <slot name="title">{{ title }}</slot>
        </h3>
        <p v-if="subtitle" class="mt-1 max-w-2xl text-sm text-gray-500">
          {{ subtitle }}
        </p>
      </div>
      <div v-if="$slots.actions">
        <slot name="actions"></slot>
      </div>
    </div>
    <div :class="{ 'px-4 py-5 sm:p-6': padding }">
      <slot></slot>
    </div>
  </div>
</template>

<script setup>
defineProps({
  title: String,
  subtitle: String,
  padding: {
    type: Boolean,
    default: true
  }
})
</script>
