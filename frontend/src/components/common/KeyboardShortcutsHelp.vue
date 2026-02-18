<template>
  <Teleport to="body">
    <div
      v-if="isOpen"
      class="fixed inset-0 z-50 overflow-y-auto"
      aria-labelledby="keyboard-shortcuts-title"
      role="dialog"
      aria-modal="true"
    >
      <!-- Background overlay -->
      <div
        class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
        @click="$emit('close')"
      />

      <!-- Modal panel -->
      <div class="flex min-h-full items-center justify-center p-4">
        <div
          class="relative transform overflow-hidden rounded-lg bg-white shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-2xl"
        >
          <!-- Header -->
          <div class="bg-white px-6 pt-6 pb-4 border-b border-gray-200">
            <div class="flex items-center justify-between">
              <h3
                id="keyboard-shortcuts-title"
                class="text-lg font-semibold text-gray-900"
              >
                Keyboard Shortcuts
              </h3>
              <button
                type="button"
                class="text-gray-400 hover:text-gray-500"
                @click="$emit('close')"
              >
                <svg
                  class="h-6 w-6"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke-width="1.5"
                  stroke="currentColor"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M6 18L18 6M6 6l12 12"
                  />
                </svg>
              </button>
            </div>
          </div>

          <!-- Content -->
          <div class="px-6 py-4 max-h-96 overflow-y-auto">
            <div class="space-y-6">
              <div>
                <h4 class="text-sm font-semibold text-gray-900 mb-3">
                  Navigation
                </h4>
                <div class="space-y-2">
                  <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-600">Create new record</span>
                    <kbd class="px-2 py-1 text-xs font-semibold text-gray-800 bg-gray-100 border border-gray-200 rounded">
                      Ctrl + N
                    </kbd>
                  </div>
                  <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-600">Edit current record</span>
                    <kbd class="px-2 py-1 text-xs font-semibold text-gray-800 bg-gray-100 border border-gray-200 rounded">
                      Ctrl + E
                    </kbd>
                  </div>
                  <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-600">Go back to list</span>
                    <kbd class="px-2 py-1 text-xs font-semibold text-gray-800 bg-gray-100 border border-gray-200 rounded">
                      Esc
                    </kbd>
                  </div>
                </div>
              </div>

              <div>
                <h4 class="text-sm font-semibold text-gray-900 mb-3">
                  General
                </h4>
                <div class="space-y-2">
                  <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-600">Show keyboard shortcuts</span>
                    <kbd class="px-2 py-1 text-xs font-semibold text-gray-800 bg-gray-100 border border-gray-200 rounded">
                      ?
                    </kbd>
                  </div>
                  <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-600">Search</span>
                    <kbd class="px-2 py-1 text-xs font-semibold text-gray-800 bg-gray-100 border border-gray-200 rounded">
                      Ctrl + K
                    </kbd>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Footer -->
          <div class="bg-gray-50 px-6 py-4">
            <button
              type="button"
              class="w-full sm:w-auto inline-flex justify-center rounded-md bg-primary-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-500"
              @click="$emit('close')"
            >
              Close
            </button>
          </div>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup lang="ts">
defineProps<{
  isOpen: boolean;
}>();

defineEmits<{
  close: [];
}>();
</script>
