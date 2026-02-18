<template>
  <form
    class="space-y-6"
    @submit.prevent="handleSubmit"
  >
    <div
      v-if="metadata.title || metadata.description"
      class="mb-6"
    >
      <h2
        v-if="metadata.title"
        class="text-2xl font-bold text-gray-900"
      >
        {{ metadata.title }}
      </h2>
      <p
        v-if="metadata.description"
        class="mt-1 text-sm text-gray-600"
      >
        {{ metadata.description }}
      </p>
    </div>

    <div 
      v-for="section in metadata.sections" 
      :key="section.id"
      class="bg-white shadow rounded-lg p-6"
    >
      <div
        v-if="section.title || section.description"
        class="mb-4"
      >
        <h3
          v-if="section.title"
          class="text-lg font-medium text-gray-900"
        >
          {{ section.title }}
        </h3>
        <p
          v-if="section.description"
          class="mt-1 text-sm text-gray-600"
        >
          {{ section.description }}
        </p>
      </div>

      <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
        <div
          v-for="field in visibleFields(section.fields)"
          :key="field.name"
          :class="getFieldClass(field)"
        >
          <label
            :for="field.name"
            class="block text-sm font-medium text-gray-700"
          >
            {{ field.label }}
            <span
              v-if="field.required"
              class="text-red-500"
            >*</span>
          </label>

          <!-- Text, Email, Password -->
          <div
            v-if="field.type === 'text' || field.type === 'email' || field.type === 'password'"
            class="mt-1 relative rounded-md shadow-sm"
          >
            <div
              v-if="field.prefix"
              class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"
            >
              <span class="text-gray-500 sm:text-sm">{{ field.prefix }}</span>
            </div>
            <input
              :id="field.name"
              v-model="formData[field.name]"
              :type="field.type"
              :name="field.name"
              :placeholder="field.placeholder"
              :required="field.required"
              :disabled="field.disabled"
              :readonly="field.readonly"
              :class="[
                'block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500',
                field.prefix ? 'pl-7' : '',
                field.suffix ? 'pr-12' : ''
              ]"
            >
            <div
              v-if="field.suffix"
              class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none"
            >
              <span class="text-gray-500 sm:text-sm">{{ field.suffix }}</span>
            </div>
          </div>

          <!-- Number -->
          <input
            v-else-if="field.type === 'number'"
            :id="field.name"
            v-model.number="formData[field.name]"
            type="number"
            :name="field.name"
            :placeholder="field.placeholder"
            :required="field.required"
            :disabled="field.disabled"
            :readonly="field.readonly"
            :min="field.min"
            :max="field.max"
            :step="field.step"
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
          >

          <!-- Textarea -->
          <textarea
            v-else-if="field.type === 'textarea'"
            :id="field.name"
            v-model="formData[field.name]"
            :name="field.name"
            :placeholder="field.placeholder"
            :required="field.required"
            :disabled="field.disabled"
            :readonly="field.readonly"
            :rows="field.rows || 4"
            :cols="field.cols"
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
          />

          <!-- Select -->
          <select
            v-else-if="field.type === 'select'"
            :id="field.name"
            v-model="formData[field.name]"
            :name="field.name"
            :required="field.required"
            :disabled="field.disabled"
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
          >
            <option
              value=""
              disabled
            >
              {{ field.placeholder || 'Select an option' }}
            </option>
            <option
              v-for="option in field.options"
              :key="option.value"
              :value="option.value"
              :disabled="option.disabled"
            >
              {{ option.label }}
            </option>
          </select>

          <!-- Multiselect -->
          <select
            v-else-if="field.type === 'multiselect'"
            :id="field.name"
            v-model="formData[field.name]"
            :name="field.name"
            multiple
            :required="field.required"
            :disabled="field.disabled"
            :size="Math.min((field.options?.length || DEFAULT_MULTISELECT_SIZE), MAX_MULTISELECT_SIZE)"
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
          >
            <option
              v-for="option in field.options"
              :key="option.value"
              :value="option.value"
              :disabled="option.disabled"
            >
              {{ option.label }}
            </option>
          </select>

          <!-- Checkbox -->
          <div
            v-else-if="field.type === 'checkbox'"
            class="mt-1"
          >
            <div
              v-if="field.options && field.options.length > 0"
              class="space-y-2"
            >
              <div
                v-for="option in field.options"
                :key="option.value"
                class="flex items-center"
              >
                <input
                  :id="`${field.name}_${option.value}`"
                  v-model="formData[field.name]"
                  type="checkbox"
                  :name="field.name"
                  :value="option.value"
                  :disabled="field.disabled || option.disabled"
                  class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded"
                >
                <label
                  :for="`${field.name}_${option.value}`"
                  class="ml-2 text-sm text-gray-700"
                >
                  {{ option.label }}
                </label>
              </div>
            </div>
            <div
              v-else
              class="flex items-center"
            >
              <input
                :id="field.name"
                v-model="formData[field.name]"
                type="checkbox"
                :name="field.name"
                :disabled="field.disabled"
                class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded"
              >
              <label
                :for="field.name"
                class="ml-2 text-sm text-gray-700"
              >
                {{ field.label }}
              </label>
            </div>
          </div>

          <!-- Radio -->
          <div
            v-else-if="field.type === 'radio'"
            class="mt-1 space-y-2"
          >
            <div
              v-for="option in field.options"
              :key="option.value"
              class="flex items-center"
            >
              <input
                :id="`${field.name}_${option.value}`"
                v-model="formData[field.name]"
                type="radio"
                :name="field.name"
                :value="option.value"
                :required="field.required"
                :disabled="field.disabled || option.disabled"
                class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300"
              >
              <label
                :for="`${field.name}_${option.value}`"
                class="ml-2 text-sm text-gray-700"
              >
                {{ option.label }}
              </label>
            </div>
          </div>

          <!-- Date -->
          <input
            v-else-if="field.type === 'date'"
            :id="field.name"
            v-model="formData[field.name]"
            type="date"
            :name="field.name"
            :required="field.required"
            :disabled="field.disabled"
            :readonly="field.readonly"
            :min="field.min"
            :max="field.max"
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
          >

          <!-- DateTime -->
          <input
            v-else-if="field.type === 'datetime'"
            :id="field.name"
            v-model="formData[field.name]"
            type="datetime-local"
            :name="field.name"
            :required="field.required"
            :disabled="field.disabled"
            :readonly="field.readonly"
            :min="field.min"
            :max="field.max"
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
          >

          <!-- Time -->
          <input
            v-else-if="field.type === 'time'"
            :id="field.name"
            v-model="formData[field.name]"
            type="time"
            :name="field.name"
            :required="field.required"
            :disabled="field.disabled"
            :readonly="field.readonly"
            :min="field.min"
            :max="field.max"
            :step="field.step"
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
          >

          <!-- File -->
          <div
            v-else-if="field.type === 'file'"
            class="mt-1"
          >
            <input
              :id="field.name"
              type="file"
              :name="field.name"
              :required="field.required"
              :disabled="field.disabled"
              :multiple="field.multiple"
              :accept="field.accept"
              class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100"
              @change="handleFileChange($event, field.name)"
            >
            <p
              v-if="formData[field.name]"
              class="mt-2 text-sm text-gray-600"
            >
              <span v-if="Array.isArray(formData[field.name])">
                {{ formData[field.name].length }} file(s) selected
              </span>
              <span v-else>
                {{ formData[field.name]?.name || 'File selected' }}
              </span>
            </p>
          </div>

          <!-- Custom Component -->
          <component
            :is="loadCustomComponent(field.customComponent)"
            v-else-if="field.type === 'custom' && field.customComponent"
            :id="field.name"
            v-model="formData[field.name]"
            :name="field.name"
            :field="field"
            :component-name="field.customComponent"
            v-bind="field.customProps"
            class="mt-1"
          />

          <!-- Help Text -->
          <p
            v-if="field.helpText"
            class="mt-1 text-sm text-gray-500"
          >
            {{ field.helpText }}
          </p>

          <!-- Error Message -->
          <div
            v-if="errors[field.name]"
            class="mt-1 text-sm text-red-600"
          >
            {{ errors[field.name][0] }}
          </div>
        </div>
      </div>
    </div>

    <div class="flex justify-end gap-4">
      <button
        v-if="metadata.cancelButton"
        type="button"
        class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50"
        @click="handleCancel"
      >
        {{ metadata.cancelButton.label || 'Cancel' }}
      </button>
      <button
        type="submit"
        :disabled="isSubmitting"
        class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700"
      >
        {{ isSubmitting ? 'Submitting...' : (metadata.submitButton?.label || 'Submit') }}
      </button>
    </div>
  </form>
</template>

<script setup lang="ts">
import { ref, reactive, watch, computed, defineAsyncComponent } from 'vue';
import type { FormMetadata, FormFieldMetadata } from '@/types/metadata';

interface Props {
  metadata: FormMetadata;
  initialData?: Record<string, any>;
}

const props = defineProps<Props>();
const emit = defineEmits<{
  submit: [data: Record<string, any>];
  cancel: [];
}>();

const formData = reactive<Record<string, any>>({});
const errors = reactive<Record<string, string[]>>({});
const isSubmitting = ref(false);
const customComponents = new Map<string, any>();

const DEFAULT_MULTISELECT_SIZE = 5;
const MAX_MULTISELECT_SIZE = 8;

watch(() => props.metadata, (metadata) => {
  metadata.sections.forEach(section => {
    section.fields.forEach(field => {
      if (field.defaultValue !== undefined && formData[field.name] === undefined) {
        if (field.type === 'checkbox' && field.options) {
          formData[field.name] = Array.isArray(field.defaultValue) ? field.defaultValue : [];
        } else if (field.type === 'multiselect') {
          formData[field.name] = Array.isArray(field.defaultValue) ? field.defaultValue : [];
        } else {
          formData[field.name] = field.defaultValue;
        }
      } else if (formData[field.name] === undefined) {
        if (field.type === 'checkbox' && field.options) {
          formData[field.name] = [];
        } else if (field.type === 'multiselect') {
          formData[field.name] = [];
        }
      }
    });
  });
}, { immediate: true });

watch(() => props.initialData, (data) => {
  if (data) Object.assign(formData, data);
}, { immediate: true });

function visibleFields(fields: FormFieldMetadata[]): FormFieldMetadata[] {
  return fields.filter(field => {
    if (field.visible === false) return false;
    
    if (field.dependsOn) {
      const dependentValue = formData[field.dependsOn.field];
      if (Array.isArray(field.dependsOn.value)) {
        return field.dependsOn.value.includes(dependentValue);
      }
      return dependentValue === field.dependsOn.value;
    }
    
    return true;
  });
}

function getFieldClass(field: FormFieldMetadata): string {
  return field.type === 'textarea' ? 'sm:col-span-2' : 'sm:col-span-1';
}

function handleFileChange(event: Event, fieldName: string) {
  const target = event.target as HTMLInputElement;
  if (target.files) {
    if (target.multiple) {
      formData[fieldName] = Array.from(target.files);
    } else {
      formData[fieldName] = target.files[0] || null;
    }
  }
}

function loadCustomComponent(componentName: string) {
  if (!customComponents.has(componentName)) {
    const component = defineAsyncComponent({
      loader: () => import(`@/components/custom/${componentName}.vue`).catch(() => {
        console.warn(`Custom component ${componentName} not found`);
        return import('@/components/common/PlaceholderComponent.vue');
      }),
      errorComponent: {
        template: '<div class="border-2 border-dashed border-gray-300 rounded-md p-4 text-center text-gray-500"><p class="text-sm">Custom component placeholder</p><p class="text-xs mt-1">Component: {{ componentName }}</p></div>',
        props: ['componentName']
      }
    });
    customComponents.set(componentName, component);
  }
  return customComponents.get(componentName);
}

async function handleSubmit() {
  isSubmitting.value = true;
  clearErrors();
  
  try {
    emit('submit', { ...formData });
  } catch (error) {
    console.error('Form submission error:', error);
  } finally {
    isSubmitting.value = false;
  }
}

function clearErrors() {
  Object.keys(errors).forEach(key => delete errors[key]);
}

function handleCancel() {
  emit('cancel');
}

defineExpose({
  formData,
  errors,
  setErrors: (newErrors: Record<string, string[]>) => {
    Object.assign(errors, newErrors);
  },
  clearErrors,
  reset: () => {
    Object.keys(formData).forEach(key => delete formData[key]);
    clearErrors();
    
    // Reinitialize form with default values
    props.metadata.sections.forEach(section => {
      section.fields.forEach(field => {
        if (field.defaultValue !== undefined) {
          if (field.type === 'checkbox' && field.options) {
            formData[field.name] = Array.isArray(field.defaultValue) ? field.defaultValue : [];
          } else if (field.type === 'multiselect') {
            formData[field.name] = Array.isArray(field.defaultValue) ? field.defaultValue : [];
          } else {
            formData[field.name] = field.defaultValue;
          }
        } else {
          if (field.type === 'checkbox' && field.options) {
            formData[field.name] = [];
          } else if (field.type === 'multiselect') {
            formData[field.name] = [];
          }
        }
      });
    });
  }
});
</script>
