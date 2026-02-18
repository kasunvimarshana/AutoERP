<template>
  <div
    class="form-field-wrapper"
    :class="fieldWrapperClass"
  >
    <!-- Label -->
    <label
      v-if="field.label && field.type !== 'checkbox'"
      :for="fieldId"
      class="block text-sm font-medium text-gray-700 mb-1"
    >
      {{ field.label }}
      <span
        v-if="field.required"
        class="text-red-500 ml-1"
      >*</span>
      <span
        v-if="field.helpText"
        class="ml-1 text-gray-400 text-xs"
      >
        <svg
          class="inline w-4 h-4"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
          />
        </svg>
      </span>
    </label>

    <!-- Help Text -->
    <p
      v-if="field.helpText && showHelpText"
      class="text-xs text-gray-500 mb-2"
    >
      {{ field.helpText }}
    </p>

    <!-- Field Input -->
    <div class="relative">
      <!-- Text Input -->
      <input
        v-if="field.type === 'text' || field.type === 'email' || field.type === 'password'"
        :id="fieldId"
        v-model="localValue"
        :type="field.type"
        :name="field.name"
        :placeholder="field.placeholder"
        :required="field.required"
        :disabled="field.disabled"
        :readonly="field.readonly"
        :class="inputClasses"
        @blur="handleBlur"
        @input="handleInput"
      >

      <!-- Number Input -->
      <input
        v-else-if="field.type === 'number'"
        :id="fieldId"
        v-model.number="localValue"
        type="number"
        :name="field.name"
        :placeholder="field.placeholder"
        :required="field.required"
        :disabled="field.disabled"
        :readonly="field.readonly"
        :min="field.min"
        :max="field.max"
        :step="field.step || 1"
        :class="inputClasses"
        @blur="handleBlur"
        @input="handleInput"
      >

      <!-- Textarea -->
      <textarea
        v-else-if="field.type === 'textarea'"
        :id="fieldId"
        v-model="localValue"
        :name="field.name"
        :placeholder="field.placeholder"
        :required="field.required"
        :disabled="field.disabled"
        :readonly="field.readonly"
        :rows="field.rows || 3"
        :class="inputClasses"
        @blur="handleBlur"
        @input="handleInput"
      />

      <!-- Select -->
      <select
        v-else-if="field.type === 'select'"
        :id="fieldId"
        v-model="localValue"
        :name="field.name"
        :required="field.required"
        :disabled="field.disabled"
        :class="inputClasses"
        @blur="handleBlur"
        @change="handleInput"
      >
        <option
          value=""
          disabled
        >
          {{ field.placeholder || 'Select...' }}
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

      <!-- Multi-Select -->
      <select
        v-else-if="field.type === 'multiselect'"
        :id="fieldId"
        v-model="localValue"
        :name="field.name"
        :required="field.required"
        :disabled="field.disabled"
        multiple
        :class="inputClasses"
        @blur="handleBlur"
        @change="handleInput"
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

      <!-- Checkbox (Single) -->
      <label
        v-else-if="field.type === 'checkbox'"
        :for="fieldId"
        class="flex items-center cursor-pointer"
      >
        <input
          :id="fieldId"
          v-model="localValue"
          type="checkbox"
          :name="field.name"
          :required="field.required"
          :disabled="field.disabled"
          class="w-4 h-4 text-primary-600 border-gray-300 rounded focus:ring-primary-500"
          @blur="handleBlur"
          @change="handleInput"
        >
        <span class="ml-2 text-sm text-gray-700">
          {{ field.label }}
          <span
            v-if="field.required"
            class="text-red-500 ml-1"
          >*</span>
        </span>
      </label>

      <!-- Radio Buttons -->
      <div
        v-else-if="field.type === 'radio'"
        class="space-y-2"
      >
        <label
          v-for="option in field.options"
          :key="option.value"
          class="flex items-center cursor-pointer"
        >
          <input
            v-model="localValue"
            type="radio"
            :name="field.name"
            :value="option.value"
            :required="field.required"
            :disabled="field.disabled || option.disabled"
            class="w-4 h-4 text-primary-600 border-gray-300 focus:ring-primary-500"
            @blur="handleBlur"
            @change="handleInput"
          >
          <span class="ml-2 text-sm text-gray-700">{{ option.label }}</span>
        </label>
      </div>

      <!-- Date Input -->
      <input
        v-else-if="field.type === 'date'"
        :id="fieldId"
        v-model="localValue"
        type="date"
        :name="field.name"
        :required="field.required"
        :disabled="field.disabled"
        :readonly="field.readonly"
        :class="inputClasses"
        @blur="handleBlur"
        @change="handleInput"
      >

      <!-- DateTime Input -->
      <input
        v-else-if="field.type === 'datetime'"
        :id="fieldId"
        v-model="localValue"
        type="datetime-local"
        :name="field.name"
        :required="field.required"
        :disabled="field.disabled"
        :readonly="field.readonly"
        :class="inputClasses"
        @blur="handleBlur"
        @change="handleInput"
      >

      <!-- Time Input -->
      <input
        v-else-if="field.type === 'time'"
        :id="fieldId"
        v-model="localValue"
        type="time"
        :name="field.name"
        :required="field.required"
        :disabled="field.disabled"
        :readonly="field.readonly"
        :class="inputClasses"
        @blur="handleBlur"
        @change="handleInput"
      >

      <!-- File Input -->
      <input
        v-else-if="field.type === 'file'"
        :id="fieldId"
        type="file"
        :name="field.name"
        :required="field.required"
        :disabled="field.disabled"
        :accept="field.accept"
        :multiple="field.multiple"
        :class="inputClasses"
        @blur="handleBlur"
        @change="handleFileChange"
      >

      <!-- Custom Component -->
      <component
        :is="loadCustomComponent(field.customComponent)"
        v-else-if="field.type === 'custom' && field.customComponent"
        :id="fieldId"
        v-model="localValue"
        :field="field"
        v-bind="field.customProps"
        @blur="handleBlur"
        @update:model-value="handleInput"
      />

      <!-- Prefix/Suffix Icons -->
      <div
        v-if="field.prefix"
        class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"
      >
        <span class="text-gray-500 sm:text-sm">{{ field.prefix }}</span>
      </div>
      <div
        v-if="field.suffix"
        class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none"
      >
        <span class="text-gray-500 sm:text-sm">{{ field.suffix }}</span>
      </div>
    </div>

    <!-- Validation Errors -->
    <div
      v-if="errors && errors.length > 0"
      class="mt-1"
    >
      <p
        v-for="(error, index) in errors"
        :key="index"
        class="text-xs text-red-600"
      >
        {{ error }}
      </p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, defineAsyncComponent } from 'vue';
import type { FormFieldMetadata } from '@/types/metadata';

interface Props {
  field: FormFieldMetadata;
  modelValue: any;
  errors?: string[];
  showHelpText?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  showHelpText: true,
  errors: () => []
});

const emit = defineEmits<{
  'update:modelValue': [value: any];
  'blur': [];
  'input': [];
}>();

// Local value for v-model
const localValue = ref(props.modelValue);

// Watch for external changes
watch(() => props.modelValue, (newValue) => {
  localValue.value = newValue;
});

// Watch for local changes and emit
watch(localValue, (newValue) => {
  emit('update:modelValue', newValue);
});

// Computed
const fieldId = computed(() => `field-${props.field.name}`);

const fieldWrapperClass = computed(() => {
  const classes = [];
  
  if (props.field.type === 'checkbox') {
    classes.push('col-span-full');
  }
  
  return classes.join(' ');
});

const inputClasses = computed(() => {
  const base = 'block w-full rounded-md shadow-sm sm:text-sm';
  const border = props.errors && props.errors.length > 0
    ? 'border-red-300 focus:border-red-500 focus:ring-red-500'
    : 'border-gray-300 focus:border-primary-500 focus:ring-primary-500';
  const state = props.field.disabled ? 'bg-gray-100 cursor-not-allowed' : '';
  
  return `${base} ${border} ${state}`.trim();
});

// Methods
const handleInput = () => {
  emit('input');
};

const handleBlur = () => {
  emit('blur');
};

const handleFileChange = (event: Event) => {
  const target = event.target as HTMLInputElement;
  const files = target.files;
  
  if (files) {
    localValue.value = props.field.multiple ? Array.from(files) : files[0];
    handleInput();
  }
};

const loadCustomComponent = (componentName: string) => {
  try {
    return defineAsyncComponent(() => 
      import(`@/components/forms/fields/${componentName}.vue`)
    );
  } catch (error) {
    console.error(`Failed to load custom component: ${componentName}`, error);
    return null;
  }
};
</script>

<style scoped>
/* Additional field-specific styles */
textarea {
  resize: vertical;
}

select[multiple] {
  min-height: 120px;
}
</style>
