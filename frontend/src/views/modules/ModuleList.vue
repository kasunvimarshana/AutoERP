<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">
          {{ moduleLabel }}
        </h1>
        <p class="text-sm text-gray-500 mt-1">
          Manage your {{ moduleLabel.toLowerCase() }}
        </p>
      </div>
      <button
        v-if="canCreate"
        class="btn btn-primary"
        @click="handleCreate"
      >
        <PlusIcon class="h-5 w-5 mr-2" />
        Create New
      </button>
    </div>

    <div
      v-if="tableConfig"
      class="card"
    >
      <DynamicTable 
        :metadata="tableConfig" 
        @action="handleTableAction"
      />
    </div>
    <div
      v-else
      class="flex justify-center items-center py-12"
    >
      <div class="spinner h-8 w-8" />
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { PlusIcon } from '@heroicons/vue/24/outline';
import { metadataApi } from '@/api/metadata';
import { usePermissions } from '@/composables/usePermissions';
import DynamicTable from '@/components/tables/DynamicTable.vue';

const route = useRoute();
const router = useRouter();
const { canCreate: canCreateFn } = usePermissions();

const moduleName = computed(() => route.params.module as string);
const moduleLabel = computed(() => moduleName.value.charAt(0).toUpperCase() + moduleName.value.slice(1));
const canCreate = computed(() => canCreateFn.value(moduleName.value));

const tableConfig = ref<any>(null);

onMounted(async () => {
  try {
    tableConfig.value = await metadataApi.getTableMetadata('modules.list');
    if (tableConfig.value) {
      tableConfig.value.apiEndpoint = `/api/modules/${moduleName.value}`;
    }
  } catch (err) {
    console.error('Failed to load table config:', err);
  }
});

function handleCreate() {
  router.push(`/modules/${moduleName.value}/create`);
}

async function handleTableAction(actionType: string, recordData: any) {
  if (actionType === 'view') {
    router.push(`/modules/${moduleName.value}/${recordData.id}`);
  } else if (actionType === 'edit') {
    router.push(`/modules/${moduleName.value}/${recordData.id}/edit`);
  } else if (actionType === 'delete') {
    try {
      await metadataApi.deleteModuleRecord(moduleName.value, recordData.id);
      if (tableConfig.value) {
        tableConfig.value = { ...tableConfig.value };
      }
    } catch (err) {
      console.error('Failed to delete record:', err);
    }
  }
}
</script>
