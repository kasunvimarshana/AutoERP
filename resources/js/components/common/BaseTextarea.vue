<template>
  <div class="w-full">
    <label v-if="label" :for="textareaId" class="block text-sm font-medium text-gray-700 mb-1">
      {{ label }}
      <span v-if="required" class="text-red-500">*</span>
    </label>
    
    <textarea
      :id="textareaId"
      :value="modelValue"
      :placeholder="placeholder"
      :disabled="disabled"
      :readonly="readonly"
      :required="required"
      :rows="rows"
      :class="textareaClasses"
      @input="$emit('update:modelValue', $event.target.value)"
      @blur="$emit('blur')"
      @focus="$emit('focus')"
    ></textarea>
    
    <p v-if="error" class="mt-1 text-sm text-red-600">{{ error }}</p>
    <p v-else-if="hint" class="mt-1 text-sm text-gray-500">{{ hint }}</p>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  modelValue: {
    type: String,
    default: '',
  },
  label: {
    type: String,
    default: '',
  },
  placeholder: {
    type: String,
    default: '',
  },
  disabled: {
    type: Boolean,
    default: false,
  },
  readonly: {
    type: Boolean,
    default: false,
  },
  required: {
    type: Boolean,
    default: false,
  },
  rows: {
    type: Number,
    default: 3,
  },
  error: {
    type: String,
    default: '',
  },
  hint: {
    type: String,
    default: '',
  },
  textareaId: {
    type: String,
    default: () => {
      // Generate unique ID using timestamp and counter
      if (typeof crypto !== 'undefined' && crypto.randomUUID) {
        return `textarea-${crypto.randomUUID()}`;
      }
      return `textarea-${Date.now()}-${Math.floor(Math.random() * 10000)}`;
    },
  },
});

defineEmits(['update:modelValue', 'blur', 'focus']);

const textareaClasses = computed(() => {
  const baseClasses = 'block w-full rounded-md shadow-sm sm:text-sm focus:outline-none transition-colors duration-150';
  
  const stateClasses = props.error
    ? 'border-red-300 text-red-900 placeholder-red-300 focus:ring-red-500 focus:border-red-500'
    : 'border-gray-300 focus:ring-indigo-500 focus:border-indigo-500';
  
  const disabledClasses = props.disabled || props.readonly
    ? 'bg-gray-100 cursor-not-allowed'
    : 'bg-white';

  return `${baseClasses} ${stateClasses} ${disabledClasses}`;
});
</script>
