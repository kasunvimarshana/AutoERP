<template>
  <form :id="formId" class="space-y-4" @submit.prevent="handleSubmit">
    <div
      v-for="field in fields"
      :key="field.name"
      :class="field.hidden ? 'hidden' : ''"
    >
      <label
        v-if="field.label && field.type !== 'checkbox'"
        :for="`${formId}-${field.name}`"
        class="block text-sm font-medium text-gray-700 mb-1"
      >
        {{ field.label }}
        <span v-if="field.required" class="text-red-500">*</span>
      </label>

      <!-- Text / email / password / number / tel -->
      <input
        v-if="['text', 'email', 'password', 'number', 'tel', 'url', 'search'].includes(field.type ?? 'text')"
        :id="`${formId}-${field.name}`"
        v-model="localValues[field.name]"
        :type="field.type ?? 'text'"
        :required="field.required"
        :placeholder="field.placeholder"
        :disabled="field.disabled || disabled"
        :min="field.min"
        :max="field.max"
        :step="field.step"
        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:bg-gray-50 disabled:text-gray-400"
      />

      <!-- Textarea -->
      <textarea
        v-else-if="field.type === 'textarea'"
        :id="`${formId}-${field.name}`"
        v-model="(localValues[field.name] as string)"
        :required="field.required"
        :placeholder="field.placeholder"
        :disabled="field.disabled || disabled"
        :rows="field.rows ?? 3"
        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:bg-gray-50 resize-y"
      />

      <!-- Select -->
      <select
        v-else-if="field.type === 'select'"
        :id="`${formId}-${field.name}`"
        v-model="localValues[field.name]"
        :required="field.required"
        :disabled="field.disabled || disabled"
        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white disabled:bg-gray-50"
      >
        <option v-if="field.placeholder" value="" disabled>{{ field.placeholder }}</option>
        <option
          v-for="opt in field.options"
          :key="String(opt.value)"
          :value="opt.value"
        >
          {{ opt.label }}
        </option>
      </select>

      <!-- Checkbox -->
      <div v-else-if="field.type === 'checkbox'" class="flex items-center gap-2">
        <input
          :id="`${formId}-${field.name}`"
          v-model="localValues[field.name]"
          type="checkbox"
          :disabled="field.disabled || disabled"
          class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
        />
        <label :for="`${formId}-${field.name}`" class="text-sm text-gray-700">
          {{ field.label }}
        </label>
      </div>

      <!-- Field-level error -->
      <p v-if="fieldErrors[field.name]" class="mt-1 text-xs text-red-600">
        {{ fieldErrors[field.name] }}
      </p>
    </div>

    <!-- Form-level error -->
    <div
      v-if="error"
      class="bg-red-50 border border-red-200 text-red-700 text-sm rounded px-3 py-2"
    >
      {{ error }}
    </div>

    <!-- Default submit button (can be replaced by parent via footer slot) -->
    <slot name="footer" :submitting="submitting">
      <div class="flex justify-end gap-2 pt-2">
        <button
          v-if="cancelLabel"
          type="button"
          :disabled="submitting"
          class="px-4 py-2 text-sm text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50"
          @click="$emit('cancel')"
        >
          {{ cancelLabel }}
        </button>
        <button
          type="submit"
          :disabled="submitting || disabled"
          class="flex items-center gap-2 px-4 py-2 text-sm text-white bg-blue-600 rounded-lg hover:bg-blue-700 disabled:opacity-60"
        >
          <AppSpinner v-if="submitting" size="sm" />
          {{ submitting ? savingLabel : submitLabel }}
        </button>
      </div>
    </slot>
  </form>
</template>

<script setup lang="ts">
import { reactive, watch } from 'vue';
import AppSpinner from '@/components/AppSpinner.vue';

export interface FormFieldOption {
  label: string;
  value: string | number | boolean;
}

export interface FormField {
  name: string;
  label?: string;
  type?: 'text' | 'email' | 'password' | 'number' | 'tel' | 'url' | 'search' | 'textarea' | 'select' | 'checkbox';
  required?: boolean;
  placeholder?: string;
  disabled?: boolean;
  hidden?: boolean;
  options?: FormFieldOption[];
  rows?: number;
  min?: string | number;
  max?: string | number;
  step?: string | number;
}

const props = withDefaults(
  defineProps<{
    formId?: string;
    fields: FormField[];
    modelValue: Record<string, unknown>;
    error?: string | null;
    fieldErrors?: Record<string, string>;
    submitting?: boolean;
    disabled?: boolean;
    submitLabel?: string;
    savingLabel?: string;
    cancelLabel?: string;
  }>(),
  {
    formId: 'dynamic-form',
    error: null,
    fieldErrors: () => ({}),
    submitting: false,
    disabled: false,
    submitLabel: 'Save',
    savingLabel: 'Saving…',
    cancelLabel: '',
  },
);

const emit = defineEmits<{
  'update:modelValue': [value: Record<string, unknown>];
  submit: [value: Record<string, unknown>];
  cancel: [];
}>();

// Maintain a reactive local copy, sync both ways
const localValues = reactive<Record<string, unknown>>({ ...props.modelValue });

watch(
  () => props.modelValue,
  (v) => {
    Object.assign(localValues, v);
  },
  { deep: true },
);

watch(localValues, (v) => {
  emit('update:modelValue', { ...v });
});

function handleSubmit(): void {
  emit('submit', { ...localValues });
}
</script>
