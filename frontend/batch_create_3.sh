#!/bin/bash

# Form Input Component
cat > src/components/forms/FormInput.vue << 'EOF'
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
EOF

# Form Select Component
cat > src/components/forms/FormSelect.vue << 'EOF'
<template>
  <div class="form-group">
    <label v-if="label" :for="id" class="form-label">
      {{ label }}
      <span v-if="required" class="required">*</span>
    </label>
    <select
      :id="id"
      :value="modelValue"
      @change="$emit('update:modelValue', $event.target.value)"
      :disabled="disabled"
      :required="required"
      class="form-select"
      :class="{ error: error }"
    >
      <option value="" disabled>{{ placeholder || 'Select an option' }}</option>
      <option
        v-for="option in options"
        :key="option.value"
        :value="option.value"
      >
        {{ option.label }}
      </option>
    </select>
    <span v-if="error" class="error-message">{{ error }}</span>
  </div>
</template>

<script setup>
defineProps({
  id: String,
  label: String,
  modelValue: [String, Number],
  options: { type: Array, default: () => [] },
  placeholder: String,
  disabled: Boolean,
  required: Boolean,
  error: String
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

.form-select {
  width: 100%;
  padding: 10px 12px;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  font-size: 14px;
  color: #1f2937;
  background: white;
  transition: all 0.2s;
}

.form-select:focus {
  outline: none;
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.form-select:disabled {
  background: #f9fafb;
  cursor: not-allowed;
}

.form-select.error {
  border-color: #ef4444;
}

.error-message {
  display: block;
  margin-top: 6px;
  font-size: 12px;
  color: #ef4444;
}
</style>
EOF

# Form Textarea Component
cat > src/components/forms/FormTextarea.vue << 'EOF'
<template>
  <div class="form-group">
    <label v-if="label" :for="id" class="form-label">
      {{ label }}
      <span v-if="required" class="required">*</span>
    </label>
    <textarea
      :id="id"
      :value="modelValue"
      @input="$emit('update:modelValue', $event.target.value)"
      :placeholder="placeholder"
      :disabled="disabled"
      :required="required"
      :rows="rows"
      class="form-textarea"
      :class="{ error: error }"
    ></textarea>
    <span v-if="error" class="error-message">{{ error }}</span>
  </div>
</template>

<script setup>
defineProps({
  id: String,
  label: String,
  modelValue: String,
  placeholder: String,
  disabled: Boolean,
  required: Boolean,
  rows: { type: Number, default: 4 },
  error: String
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

.form-textarea {
  width: 100%;
  padding: 10px 12px;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  font-size: 14px;
  color: #1f2937;
  font-family: inherit;
  resize: vertical;
  transition: all 0.2s;
}

.form-textarea:focus {
  outline: none;
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.form-textarea:disabled {
  background: #f9fafb;
  cursor: not-allowed;
}

.form-textarea.error {
  border-color: #ef4444;
}

.error-message {
  display: block;
  margin-top: 6px;
  font-size: 12px;
  color: #ef4444;
}
</style>
EOF

echo "Form components created"
