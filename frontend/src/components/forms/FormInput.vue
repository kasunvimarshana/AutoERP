<template>
  <div class="form-group">
    <label v-if="label" :for="id" class="form-label">
      {{ label }}
      <span v-if="required" class="required">*</span>
    </label>
    <input
      :id="id"
      :type="type"
      :value="modelValue"
      @input="$emit('update:modelValue', $event.target.value)"
      :placeholder="placeholder"
      :disabled="disabled"
      :required="required"
      class="form-input"
      :class="{ error: error }"
    />
    <span v-if="error" class="error-message">{{ error }}</span>
    <span v-if="hint" class="hint-text">{{ hint }}</span>
  </div>
</template>

<script setup>
defineProps({
  id: String,
  label: String,
  type: { type: String, default: 'text' },
  modelValue: [String, Number],
  placeholder: String,
  disabled: Boolean,
  required: Boolean,
  error: String,
  hint: String
})

defineEmits(['update:modelValue'])
</script>

<style scoped>
.form-group {
  margin-bottom: 20px;
}

.form-label {
  display: block;
  margin-bottom: 6px;
  font-size: 14px;
  font-weight: 500;
  color: #374151;
}

.required {
  color: #ef4444;
}

.form-input {
  width: 100%;
  padding: 10px 12px;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  font-size: 14px;
  color: #1f2937;
  transition: all 0.2s;
}

.form-input:focus {
  outline: none;
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.form-input:disabled {
  background: #f9fafb;
  cursor: not-allowed;
}

.form-input.error {
  border-color: #ef4444;
}

.error-message {
  display: block;
  margin-top: 6px;
  font-size: 12px;
  color: #ef4444;
}

.hint-text {
  display: block;
  margin-top: 6px;
  font-size: 12px;
  color: #6b7280;
}
</style>
