<template>
  <AppLayout>
    <div class="space-y-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Document Management</h1>
          <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Manage the document vault, categories, and lifecycle.</p>
        </div>
      </div>

      <div class="border-b border-gray-200 dark:border-gray-800">
        <nav class="-mb-px flex gap-6" aria-label="Document tabs">
          <button
            v-for="tab in tabs"
            :key="tab.key"
            type="button"
            :class="[
              activeTab === tab.key
                ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400 dark:border-indigo-400'
                : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-600',
              'whitespace-nowrap border-b-2 pb-3 text-sm font-medium transition-colors',
            ]"
            @click="switchTab(tab.key)"
          >
            {{ tab.label }}
            <span
              v-if="tab.count !== null"
              :class="activeTab === tab.key ? 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900 dark:text-indigo-300' : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400'"
              class="ml-2 rounded-full px-2 py-0.5 text-xs font-semibold"
            >{{ tab.count }}</span>
          </button>
        </nav>
      </div>

      <div
        v-if="documents.error"
        class="rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 px-4 py-3 text-sm text-red-700 dark:text-red-400"
        role="alert"
      >
        {{ documents.error }}
      </div>

      <!-- Documents tab -->
      <div v-if="activeTab === 'documents'">
        <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 overflow-hidden">
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800" aria-label="Documents table">
              <thead class="bg-gray-50 dark:bg-gray-800/50">
                <tr>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Title</th>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Category</th>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">MIME Type</th>
                  <th scope="col" class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Size</th>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Status</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                <tr v-if="documents.loading">
                  <td colspan="5" class="px-4 py-8 text-center text-sm text-gray-400 dark:text-gray-500">Loading…</td>
                </tr>
                <tr v-else-if="documents.documents.length === 0">
                  <td colspan="5" class="px-4 py-8 text-center text-sm text-gray-400 dark:text-gray-500">No documents found.</td>
                </tr>
                <tr
                  v-for="doc in documents.documents"
                  v-else
                  :key="doc.id"
                  class="hover:bg-gray-50 dark:hover:bg-gray-800/40 transition-colors"
                >
                  <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-white">{{ doc.title }}</td>
                  <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ doc.category_name ?? doc.document_category_id ?? '—' }}</td>
                  <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400 font-mono text-xs">{{ doc.mime_type ?? '—' }}</td>
                  <td class="px-4 py-3 text-sm text-right text-gray-500 dark:text-gray-400">{{ formatFileSize(doc.file_size) }}</td>
                  <td class="px-4 py-3"><StatusBadge :status="doc.status" /></td>
                </tr>
              </tbody>
            </table>
          </div>
          <Pagination :meta="documents.documentsMeta" @change="documents.fetchDocuments" />
        </div>
      </div>

      <!-- Categories tab -->
      <div v-if="activeTab === 'categories'">
        <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 overflow-hidden">
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800" aria-label="Document Categories table">
              <thead class="bg-gray-50 dark:bg-gray-800/50">
                <tr>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Name</th>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Description</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                <tr v-if="documents.loading">
                  <td colspan="2" class="px-4 py-8 text-center text-sm text-gray-400 dark:text-gray-500">Loading…</td>
                </tr>
                <tr v-else-if="documents.categories.length === 0">
                  <td colspan="2" class="px-4 py-8 text-center text-sm text-gray-400 dark:text-gray-500">No document categories found.</td>
                </tr>
                <tr
                  v-for="cat in documents.categories"
                  v-else
                  :key="cat.id"
                  class="hover:bg-gray-50 dark:hover:bg-gray-800/40 transition-colors"
                >
                  <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-white">{{ cat.name }}</td>
                  <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ cat.description ?? '—' }}</td>
                </tr>
              </tbody>
            </table>
          </div>
          <Pagination :meta="documents.categoriesMeta" @change="documents.fetchCategories" />
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import Pagination from '@/components/Pagination.vue';
import { useDocumentStore } from '@/stores/document';
import { useFormatters } from '@/composables/useFormatters';
const { formatFileSize } = useFormatters();

const documents = useDocumentStore();
const activeTab = ref('documents');

const tabs = computed(() => [
    { key: 'documents', label: 'Documents', count: documents.documentsMeta.total || null },
    { key: 'categories', label: 'Categories', count: documents.categoriesMeta.total || null },
]);

function switchTab(key) {
    activeTab.value = key;
    if (key === 'documents') documents.fetchDocuments();
    else if (key === 'categories') documents.fetchCategories();
}


onMounted(() => documents.fetchDocuments());
</script>
