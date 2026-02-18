<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <button
          class="text-sm text-gray-600 hover:text-gray-900 mb-2"
          @click="router.back()"
        >
          ← Back
        </button>
        <h1 class="text-2xl font-bold text-gray-900">
          {{ moduleLabel }} Details
        </h1>
      </div>
      <div class="space-x-3">
        <button
          v-if="canUpdate"
          class="btn btn-primary"
          @click="handleEdit"
        >
          Edit
        </button>
        <button
          v-if="canDelete"
          class="btn btn-danger"
          @click="handleDelete"
        >
          Delete
        </button>
      </div>
    </div>

    <!-- Details Card -->
    <div class="card">
      <LoadingSpinner v-if="loading" />
      
      <div
        v-else-if="record"
        class="p-6"
      >
        <dl class="grid grid-cols-1 gap-6 sm:grid-cols-2">
          <div
            v-for="(value, key) in record"
            :key="key"
          >
            <dt class="text-sm font-medium text-gray-500">
              {{ formatLabel(key) }}
            </dt>
            <dd class="mt-1 text-sm text-gray-900">
              {{ value || '-' }}
            </dd>
          </div>
        </dl>
      </div>

      <div
        v-else
        class="p-8 text-center text-gray-500"
      >
        Record not found
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { metadataApi } from '@/api/metadata';
import { usePermissions } from '@/composables/usePermissions';
import LoadingSpinner from '@/components/common/LoadingSpinner.vue';

const route = useRoute();
const router = useRouter();
const { canUpdate: canUpdateFn, canDelete: canDeleteFn } = usePermissions();

const moduleName = computed(() => route.params.module as string);
const moduleLabel = computed(() => moduleName.value.charAt(0).toUpperCase() + moduleName.value.slice(1));
const recordId = computed(() => parseInt(route.params.id as string));

const canUpdate = computed(() => canUpdateFn.value(moduleName.value));
const canDelete = computed(() => canDeleteFn.value(moduleName.value));

const loading = ref(false);
const record = ref<any>(null);

async function loadRecord() {
  loading.value = true;
  try {
    record.value = await metadataApi.getModuleRecord(moduleName.value, recordId.value);
  } catch (err) {
    console.error('Failed to load record:', err);
  } finally {
    loading.value = false;
  }
}

function formatLabel(key: string): string {
  return key
    .split('_')
    .map(word => word.charAt(0).toUpperCase() + word.slice(1))
    .join(' ');
}

function handleEdit() {
  router.push(`/modules/${moduleName.value}/${recordId.value}/edit`);
}

async function handleDelete() {
  if (!confirm('Are you sure you want to delete this record?')) return;
  
  try {
    await metadataApi.deleteModuleRecord(moduleName.value, recordId.value);
    router.push(`/modules/${moduleName.value}`);
  } catch (err) {
    console.error('Failed to delete record:', err);
  }
}

onMounted(() => {
  loadRecord();
});
</script>
