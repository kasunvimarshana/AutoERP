<template>
  <div>
    <div class="mb-6 flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Document Management</h1>
        <p class="mt-1 text-sm text-gray-500">Upload, organize, and share documents</p>
      </div>
      <BaseButton variant="primary" @click="triggerFileUpload">
        <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
        </svg>
        Upload Document
      </BaseButton>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
      <BaseCard>
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <div class="p-3 bg-blue-100 rounded-lg">
              <svg class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
            </div>
          </div>
          <div class="ml-4">
            <p class="text-sm font-medium text-gray-500">Total Documents</p>
            <p class="text-2xl font-bold text-gray-900">{{ stats.total }}</p>
          </div>
        </div>
      </BaseCard>

      <BaseCard>
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <div class="p-3 bg-yellow-100 rounded-lg">
              <svg class="h-6 w-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
              </svg>
            </div>
          </div>
          <div class="ml-4">
            <p class="text-sm font-medium text-gray-500">Folders</p>
            <p class="text-2xl font-bold text-gray-900">{{ stats.folders }}</p>
          </div>
        </div>
      </BaseCard>

      <BaseCard>
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <div class="p-3 bg-purple-100 rounded-lg">
              <svg class="h-6 w-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4" />
              </svg>
            </div>
          </div>
          <div class="ml-4">
            <p class="text-sm font-medium text-gray-500">Total Size</p>
            <p class="text-2xl font-bold text-gray-900">{{ formatSize(stats.totalSize) }}</p>
          </div>
        </div>
      </BaseCard>

      <BaseCard>
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <div class="p-3 bg-green-100 rounded-lg">
              <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z" />
              </svg>
            </div>
          </div>
          <div class="ml-4">
            <p class="text-sm font-medium text-gray-500">Shared</p>
            <p class="text-2xl font-bold text-gray-900">{{ stats.shared }}</p>
          </div>
        </div>
      </BaseCard>
    </div>

    <BaseCard class="mb-6">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <BaseInput
          v-model="search"
          placeholder="Search documents..."
          type="search"
        />
        <BaseSelect
          v-model="folderFilter"
          :options="folderOptions"
          placeholder="Filter by folder"
        />
        <BaseSelect
          v-model="typeFilter"
          :options="typeOptions"
          placeholder="Filter by type"
        />
        <BaseSelect
          v-model="statusFilter"
          :options="statusOptions"
          placeholder="Filter by status"
        />
      </div>
    </BaseCard>

    <BaseCard>
      <input
        ref="fileInput"
        type="file"
        class="hidden"
        @change="handleFileSelect"
        multiple
      />
      
      <BaseTable
        :columns="columns"
        :data="filteredDocuments"
        :loading="loading"
        :actions="tableActions"
        @sort="handleSort"
        @action:download="downloadDocument"
        @action:share="shareDocument"
        @action:move="moveDocument"
        @action:delete="deleteDocumentAction"
      >
        <template #cell-name="{ value, row }">
          <div class="flex items-center">
            <svg class="h-5 w-5 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <span class="font-medium text-gray-900">{{ value }}</span>
          </div>
        </template>

        <template #cell-type="{ value }">
          <BaseBadge :variant="getTypeVariant(value)">
            {{ value?.toUpperCase() || 'OTHER' }}
          </BaseBadge>
        </template>

        <template #cell-size="{ value }">
          <span class="text-sm text-gray-600">{{ formatSize(value) }}</span>
        </template>

        <template #cell-folder="{ row }">
          <span class="text-sm text-gray-600">
            {{ row.folder?.name || 'Root' }}
          </span>
        </template>

        <template #cell-uploaded_by="{ row }">
          <span class="text-sm text-gray-600">
            {{ row.uploaded_by?.name || 'Unknown' }}
          </span>
        </template>

        <template #cell-uploaded_at="{ value }">
          <span class="text-sm text-gray-600">{{ formatDate(value) }}</span>
        </template>

        <template #cell-status="{ value }">
          <BaseBadge :variant="value === 'active' ? 'success' : 'secondary'">
            {{ value }}
          </BaseBadge>
        </template>
      </BaseTable>

      <div v-if="pagination.totalPages > 1" class="mt-4">
        <BasePagination
          :current-page="pagination.currentPage"
          :total-pages="pagination.totalPages"
          :total="pagination.total"
          :per-page="pagination.perPage"
          @page-change="handlePageChange"
        />
      </div>
    </BaseCard>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useDocumentStore } from '../stores/documentStore';
import { useTable } from '@/composables/useTable';
import { usePagination } from '@/composables/usePagination';
import { useNotifications } from '@/composables/useNotifications';
import BaseCard from '@/components/layout/BaseCard.vue';
import BaseButton from '@/components/common/BaseButton.vue';
import BaseInput from '@/components/common/BaseInput.vue';
import BaseSelect from '@/components/common/BaseSelect.vue';
import BaseTable from '@/components/data/BaseTable.vue';
import BasePagination from '@/components/data/BasePagination.vue';
import BaseBadge from '@/components/common/BaseBadge.vue';

const documentStore = useDocumentStore();
const { showSuccess, showError } = useNotifications();

const loading = ref(false);
const search = ref('');
const folderFilter = ref('');
const typeFilter = ref('');
const statusFilter = ref('');
const fileInput = ref(null);

const stats = computed(() => {
  const documents = documentStore.documents || [];
  const totalSize = documents.reduce((sum, d) => sum + (d.size || 0), 0);
  return {
    total: documents.length,
    folders: (documentStore.folders || []).length,
    totalSize: totalSize,
    shared: documents.filter(d => d.is_shared).length,
  };
});

const folderOptions = computed(() => [
  { label: 'All Folders', value: '' },
  ...((documentStore.folders || []).map(f => ({ label: f.name, value: f.id })))
]);

const typeOptions = [
  { label: 'All Types', value: '' },
  { label: 'PDF', value: 'pdf' },
  { label: 'Word', value: 'word' },
  { label: 'Excel', value: 'excel' },
  { label: 'Image', value: 'image' },
  { label: 'Other', value: 'other' },
];

const statusOptions = [
  { label: 'All Statuses', value: '' },
  { label: 'Active', value: 'active' },
  { label: 'Archived', value: 'archived' },
];

const columns = [
  { key: 'name', label: 'Name', sortable: true },
  { key: 'type', label: 'Type', sortable: true },
  { key: 'size', label: 'Size', sortable: true },
  { key: 'folder', label: 'Folder', sortable: false },
  { key: 'uploaded_by', label: 'Uploaded By', sortable: false },
  { key: 'uploaded_at', label: 'Uploaded At', sortable: true },
  { key: 'status', label: 'Status', sortable: true },
];

const tableActions = () => [
  { key: 'download', label: 'Download', icon: 'download' },
  { key: 'share', label: 'Share', icon: 'share' },
  { key: 'move', label: 'Move', icon: 'folder' },
  { key: 'delete', label: 'Delete', icon: 'trash', variant: 'danger' },
];

const { sortedData, handleSort } = useTable(computed(() => documentStore.documents || []));

const filteredDocuments = computed(() => {
  let data = sortedData.value;

  if (search.value) {
    const searchLower = search.value.toLowerCase();
    data = data.filter(doc =>
      doc.name?.toLowerCase().includes(searchLower) ||
      doc.description?.toLowerCase().includes(searchLower)
    );
  }

  if (folderFilter.value) {
    data = data.filter(doc => doc.folder_id === folderFilter.value);
  }

  if (typeFilter.value) {
    data = data.filter(doc => doc.type === typeFilter.value);
  }

  if (statusFilter.value) {
    data = data.filter(doc => doc.status === statusFilter.value);
  }

  return data;
});

const pagination = usePagination(filteredDocuments, 10);

const handlePageChange = (page) => {
  pagination.currentPage.value = page;
};

const fetchDocuments = async () => {
  loading.value = true;
  try {
    await documentStore.fetchDocuments();
    await documentStore.fetchFolders();
  } catch (error) {
    showError('Failed to load documents');
  } finally {
    loading.value = false;
  }
};

const triggerFileUpload = () => {
  fileInput.value?.click();
};

const handleFileSelect = async (event) => {
  const files = Array.from(event.target.files);
  if (!files.length) return;

  for (const file of files) {
    try {
      await documentStore.uploadDocument(file, {
        name: file.name,
        folder_id: folderFilter.value || null,
      });
      showSuccess(`${file.name} uploaded successfully`);
    } catch (error) {
      showError(`Failed to upload ${file.name}`);
    }
  }

  event.target.value = '';
  await fetchDocuments();
};

const downloadDocument = async (doc) => {
  try {
    await documentStore.downloadDocument(doc.id);
    showSuccess('Document downloaded successfully');
  } catch (error) {
    showError('Failed to download document');
  }
};

const shareDocument = async (doc) => {
  const userEmail = prompt('Enter email address to share with:');
  if (!userEmail) return;

  try {
    await documentStore.shareDocument(doc.id, { email: userEmail });
    showSuccess('Document shared successfully');
  } catch (error) {
    showError('Failed to share document');
  }
};

const moveDocument = async (doc) => {
  const folderId = prompt('Enter folder ID to move to:');
  if (!folderId) return;

  try {
    await documentStore.moveDocument(doc.id, folderId);
    showSuccess('Document moved successfully');
    await fetchDocuments();
  } catch (error) {
    showError('Failed to move document');
  }
};

const deleteDocumentAction = async (doc) => {
  if (!confirm(`Are you sure you want to delete "${doc.name}"?`)) return;

  try {
    await documentStore.deleteDocument(doc.id);
    showSuccess('Document deleted successfully');
    await fetchDocuments();
  } catch (error) {
    showError('Failed to delete document');
  }
};

const getTypeVariant = (type) => {
  const variants = {
    pdf: 'danger',
    word: 'primary',
    excel: 'success',
    image: 'info',
    other: 'secondary',
  };
  return variants[type] || 'secondary';
};

const formatSize = (bytes) => {
  if (!bytes) return '0 B';
  const k = 1024;
  const sizes = ['B', 'KB', 'MB', 'GB'];
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
};

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  });
};

onMounted(() => {
  fetchDocuments();
});
</script>
