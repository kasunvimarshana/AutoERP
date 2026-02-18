<template>
  <div class="border-2 border-dashed border-gray-300 rounded-md p-4 text-center text-gray-500">
    <p class="text-sm">
      Custom component placeholder
    </p>
    <p class="text-xs mt-1">
      Component: {{ componentName }}
    </p>
  </div>
</template>

<script setup lang="ts">
defineProps<{
  componentName?: string;
}>();
</script>
