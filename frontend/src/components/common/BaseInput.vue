<template>
  <div class="mb-4">
    <label v-if="label" :for="id" class="block text-sm font-medium text-secondary-700 mb-2">
      {{ label }}
      <span v-if="required" class="text-red-500">*</span>
    </label>
    <input
      :id="id"
      :type="type"
      :value="modelValue"
      :placeholder="placeholder"
      :disabled="disabled"
      :required="required"
      :class="inputClasses"
      @input="$emit('update:modelValue', $event.target.value)"
      @blur="$emit('blur')"
      @focus="$emit('focus')"
    />
    <p v-if="error" class="mt-1 text-sm text-red-600">{{ error }}</p>
    <p v-else-if="hint" class="mt-1 text-sm text-secondary-500">{{ hint }}</p>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  id: String,
  modelValue: [String, Number],
  type: {
    type: String,
    default: 'text'
  },
  label: String,
  placeholder: String,
  disabled: Boolean,
  required: Boolean,
  error: String,
  hint: String
})

defineEmits(['update:modelValue', 'blur', 'focus'])

const inputClasses = computed(() => {
  return [
    'input',
    props.error && 'border-red-500 focus:ring-red-500',
    props.disabled && 'bg-secondary-100 cursor-not-allowed'
  ].filter(Boolean).join(' ')
})
</script>
