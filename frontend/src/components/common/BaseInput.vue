<template>
  <div class="mb-4">
    <label v-if="label" :for="inputId" class="block text-sm font-medium text-gray-700 mb-1">
      {{ label }}
      <span v-if="required" class="text-red-500">*</span>
    </label>
    <input
      :id="inputId"
      :type="type"
      :value="modelValue"
      :placeholder="placeholder"
      :disabled="disabled"
      :required="required"
      :class="inputClasses"
      @input="$emit('update:modelValue', $event.target.value)"
      @blur="$emit('blur', $event)"
    />
    <p v-if="error" class="mt-1 text-sm text-red-600">{{ error }}</p>
    <p v-else-if="hint" class="mt-1 text-sm text-gray-500">{{ hint }}</p>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { generateUniqueId } from '@/utils/id'

const props = defineProps({
  modelValue: [String, Number],
  label: String,
  type: {
    type: String,
    default: 'text'
  },
  placeholder: String,
  disabled: Boolean,
  required: Boolean,
  error: String,
  hint: String
})

defineEmits(['update:modelValue', 'blur'])

const inputId = generateUniqueId('input')

const inputClasses = computed(() => {
  const base = 'block w-full rounded-md shadow-sm sm:text-sm'
  const normal = 'border-gray-300 focus:border-blue-500 focus:ring-blue-500'
  const errorClass = 'border-red-300 focus:border-red-500 focus:ring-red-500'
  
  return [base, props.error ? errorClass : normal].join(' ')
})
</script>
