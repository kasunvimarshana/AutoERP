<template>
  <AppLayout>
    <div class="space-y-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Media Library</h1>
          <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
            Tenant-scoped file uploads: documents, images, and attachments.
          </p>
        </div>
      </div>

      <div
        v-if="media.error"
        class="rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 px-4 py-3 text-sm text-red-700 dark:text-red-400"
        role="alert"
      >
        {{ media.error }}
      </div>

      <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 overflow-hidden">
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800" aria-label="Media files table">
            <thead class="bg-gray-50 dark:bg-gray-800/50">
              <tr>
                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">File Name</th>
                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">MIME Type</th>
                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Folder</th>
                <th scope="col" class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Size</th>
                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Disk</th>
                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Visibility</th>
                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Uploaded</th>
                <th scope="col" class="px-4 py-3" aria-label="Actions" />
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
              <tr v-if="media.loading">
                <td colspan="8" class="px-4 py-8 text-center text-sm text-gray-400 dark:text-gray-500">Loading…</td>
              </tr>
              <tr v-else-if="media.files.length === 0">
                <td colspan="8" class="px-4 py-8 text-center text-sm text-gray-400 dark:text-gray-500">No media files found.</td>
              </tr>
              <tr
                v-for="file in media.files"
                v-else
                :key="file.id"
                class="hover:bg-gray-50 dark:hover:bg-gray-800/40 transition-colors"
              >
                <!-- File name -->
                <td class="px-4 py-3 max-w-xs">
                  <p class="text-sm font-medium text-gray-900 dark:text-white truncate" :title="file.original_name">
                    {{ file.original_name }}
                  </p>
                  <p class="text-xs font-mono text-gray-400 dark:text-gray-500 truncate" :title="file.filename">
                    {{ file.filename }}
                  </p>
                </td>
                <!-- MIME type -->
                <td class="px-4 py-3 text-xs font-mono text-gray-500 dark:text-gray-400">{{ file.mime_type ?? '—' }}</td>
                <!-- Folder -->
                <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ file.folder ?? '—' }}</td>
                <!-- Size -->
                <td class="px-4 py-3 text-sm text-right text-gray-500 dark:text-gray-400 tabular-nums">
                  {{ formatFileSize(file.size_bytes) }}
                </td>
                <!-- Disk -->
                <td class="px-4 py-3">
                  <span class="inline-flex items-center rounded-md bg-gray-100 dark:bg-gray-800 px-2 py-0.5 text-xs font-mono font-medium text-gray-600 dark:text-gray-300 uppercase">
                    {{ file.disk ?? 'local' }}
                  </span>
                </td>
                <!-- Visibility -->
                <td class="px-4 py-3">
                  <span
                    :class="file.is_public
                      ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-400'
                      : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400'"
                    class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold"
                  >
                    {{ file.is_public ? 'Public' : 'Private' }}
                  </span>
                </td>
                <!-- Uploaded date -->
                <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400 whitespace-nowrap">
                  {{ formatDate(file.created_at) }}
                </td>
                <!-- Delete action -->
                <td class="px-4 py-3 text-right">
                  <button
                    type="button"
                    class="text-xs text-red-600 dark:text-red-400 hover:underline"
                    :aria-label="`Delete ${file.original_name}`"
                    @click="confirmDelete(file)"
                  >
                    Delete
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <Pagination :meta="media.meta" @change="media.fetchFiles" />
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { onMounted } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import Pagination from '@/components/Pagination.vue';
import { useMediaStore } from '@/stores/media';
import { useFormatters } from '@/composables/useFormatters';
const { formatDate, formatFileSize } = useFormatters();

const media = useMediaStore();

onMounted(() => media.fetchFiles());


function confirmDelete(file) {
    if (window.confirm(`Delete "${file.original_name}"? This cannot be undone.`)) {
        media.remove(file.id);
    }
}
</script>
