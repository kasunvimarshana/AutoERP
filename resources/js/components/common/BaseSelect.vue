<template>
  <div class="w-full">
    <label v-if="label" :for="selectId" class="block text-sm font-medium text-gray-700 mb-1">
      {{ label }}
      <span v-if="required" class="text-red-500">*</span>
    </label>
    
    <select
      :id="selectId"
      :value="modelValue"
      :disabled="disabled"
      :required="required"
      :class="selectClasses"
      @change="$emit('update:modelValue', $event.target.value)"
    >
      <option v-if="placeholder" value="" disabled>{{ placeholder }}</option>
      <option
        v-for="option in options"
        :key="getOptionValue(option)"
        :value="getOptionValue(option)"
      >
        {{ getOptionLabel(option) }}
      </option>
    </select>
    
    <p v-if="error" class="mt-1 text-sm text-red-600">{{ error }}</p>
    <p v-else-if="hint" class="mt-1 text-sm text-gray-500">{{ hint }}</p>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  modelValue: {
    type: [String, Number],
    default: '',
  },
  label: {
    type: String,
    default: '',
  },
  options: {
    type: Array,
    required: true,
  },
  valueKey: {
    type: String,
    default: 'value',
  },
  labelKey: {
    type: String,
    default: 'label',
  },
  placeholder: {
    type: String,
    default: 'Select an option',
  },
  disabled: {
    type: Boolean,
    default: false,
  },
  required: {
    type: Boolean,
    default: false,
  },
  error: {
    type: String,
    default: '',
  },
  hint: {
    type: String,
    default: '',
  },
  selectId: {
    type: String,
    default: () => {
      // Generate unique ID using timestamp and counter
      if (typeof crypto !== 'undefined' && crypto.randomUUID) {
        return `select-${crypto.randomUUID()}`;
      }
      return `select-${Date.now()}-${Math.floor(Math.random() * 10000)}`;
    },
  },
});

defineEmits(['update:modelValue']);

const selectClasses = computed(() => {
  const baseClasses = 'block w-full rounded-md shadow-sm sm:text-sm focus:outline-none transition-colors duration-150';
  
  const stateClasses = props.error
    ? 'border-red-300 text-red-900 focus:ring-red-500 focus:border-red-500'
    : 'border-gray-300 focus:ring-indigo-500 focus:border-indigo-500';
  
  const disabledClasses = props.disabled
    ? 'bg-gray-100 cursor-not-allowed'
    : 'bg-white';

  return `${baseClasses} ${stateClasses} ${disabledClasses}`;
});

function getOptionValue(option) {
  return typeof option === 'object' ? option[props.valueKey] : option;
}

function getOptionLabel(option) {
  return typeof option === 'object' ? option[props.labelKey] : option;
}
</script>
