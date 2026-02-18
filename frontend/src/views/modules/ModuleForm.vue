<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <button
          class="text-sm text-gray-600 hover:text-gray-900 mb-2"
          @click="router.back()"
        >
          ← Back
        </button>
        <h1 class="text-2xl font-bold text-gray-900">
          {{ route.params.id ? 'Edit' : 'Create' }} 
          {{ (route.params.module as string)[0].toUpperCase() + (route.params.module as string).substring(1) }}
        </h1>
      </div>
    </div>

    <div
      v-if="notification"
      class="rounded-md bg-red-50 p-4"
    >
      <p class="text-sm text-red-800">
        {{ notification }}
      </p>
    </div>

    <div
      v-if="!formConfig"
      class="flex justify-center items-center py-12"
    >
      <div class="spinner h-8 w-8" />
    </div>
    
    <div
      v-else
      class="card p-6"
    >
      <DynamicForm 
        :metadata="formConfig" 
        :initial-data="existingData" 
        @submit="handleSubmit" 
        @cancel="router.back()" 
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import DynamicForm from '@/components/forms/DynamicForm.vue';
import { metadataApi } from '@/api/metadata';

const route = useRoute();
const router = useRouter();

const formConfig = ref<any>(null);
const existingData = ref<any>({});
const notification = ref('');

onMounted(async () => {
  const moduleName = route.params.module as string;
  const recordId = route.params.id ? parseInt(route.params.id as string) : null;
  
  try {
    formConfig.value = await metadataApi.getFormMetadata('modules.form');
  } catch (err: any) {
    notification.value = err.message || 'Failed to load form configuration';
    return;
  }
  
  if (recordId) {
    try {
      existingData.value = await metadataApi.getModuleRecord(moduleName, recordId);
    } catch (err: any) {
      notification.value = err.message || 'Failed to load existing record';
    }
  }
});

async function handleSubmit(formValues: any) {
  const moduleName = route.params.module as string;
  const recordId = route.params.id ? parseInt(route.params.id as string) : null;
  
  notification.value = '';
  
  try {
    if (recordId) {
      await metadataApi.updateModuleRecord(moduleName, recordId, formValues);
    } else {
      await metadataApi.createModuleRecord(moduleName, formValues);
    }
    
    router.push(`/modules/${moduleName}`);
  } catch (err: any) {
    notification.value = err.message || 'Failed to save record';
    throw err;
  }
}
</script>
