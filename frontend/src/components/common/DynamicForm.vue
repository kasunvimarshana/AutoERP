<template>
  <form @submit.prevent="handleSubmit" class="dynamic-form">
    <div v-for="field in visibleFields" :key="field.name" class="form-field">
      <!-- Rich Text Editor -->
      <div v-if="field.type === 'richtext'" class="form-group">
        <RichTextEditor
          v-model="formData[field.name]"
          :label="field.label"
          :required="field.required"
          :disabled="disabled"
          :placeholder="field.ui_config?.placeholder || ''"
          :help="field.ui_config?.help"
          :error="errors[field.name]"
          :toolbar="field.ui_config?.toolbar || ['bold', 'italic', 'underline', 'list', 'link']"
          @blur="validateField(field.name)"
        />
      </div>

      <!-- File Upload -->
      <div v-else-if="field.type === 'file'" class="form-group">
        <FileUpload
          v-model="formData[field.name]"
          :label="field.label"
          :required="field.required"
          :disabled="disabled"
          :accept="field.ui_config?.accept || ''"
          :multiple="field.ui_config?.multiple || false"
          :help="field.ui_config?.help"
          :error="errors[field.name]"
          @change="validateField(field.name)"
        />
      </div>

      <!-- Multi-Select -->
      <div v-else-if="field.type === 'multiselect'" class="form-group">
        <MultiSelect
          v-model="formData[field.name]"
          :options="field.options || []"
          :label="field.label"
          :required="field.required"
          :disabled="disabled"
          :placeholder="field.ui_config?.placeholder || 'Select options...'"
          :searchable="field.ui_config?.searchable !== false"
          :help="field.ui_config?.help"
          :error="errors[field.name]"
          @change="validateField(field.name)"
        />
      </div>

      <!-- Text Input -->
      <div v-else-if="isTextType(field.type)" class="form-group">
        <label :for="field.name" class="form-label">
          {{ field.label }}
          <span v-if="field.required" class="required">*</span>
        </label>
        <input
          :id="field.name"
          v-model="formData[field.name]"
          :type="field.type"
          :placeholder="field.ui_config?.placeholder || ''"
          :required="field.required"
          :readonly="field.readonly"
          :disabled="disabled"
          class="form-input"
          @blur="validateField(field.name)"
        />
        <span v-if="field.ui_config?.help" class="form-help">
          {{ field.ui_config.help }}
        </span>
        <span v-if="errors[field.name]" class="form-error">
          {{ errors[field.name] }}
        </span>
      </div>

      <!-- Textarea -->
      <div v-else-if="field.type === 'textarea'" class="form-group">
        <label :for="field.name" class="form-label">
          {{ field.label }}
          <span v-if="field.required" class="required">*</span>
        </label>
        <textarea
          :id="field.name"
          v-model="formData[field.name]"
          :placeholder="field.ui_config?.placeholder || ''"
          :required="field.required"
          :readonly="field.readonly"
          :disabled="disabled"
          :rows="field.ui_config?.rows || 3"
          class="form-textarea"
          @blur="validateField(field.name)"
        />
        <span v-if="field.ui_config?.help" class="form-help">
          {{ field.ui_config.help }}
        </span>
        <span v-if="errors[field.name]" class="form-error">
          {{ errors[field.name] }}
        </span>
      </div>

      <!-- Select -->
      <div v-else-if="field.type === 'select'" class="form-group">
        <label :for="field.name" class="form-label">
          {{ field.label }}
          <span v-if="field.required" class="required">*</span>
        </label>
        <select
          :id="field.name"
          v-model="formData[field.name]"
          :required="field.required"
          :disabled="disabled || field.readonly"
          class="form-select"
          @change="validateField(field.name)"
        >
          <option value="">Select {{ field.label }}</option>
          <option
            v-for="option in field.options"
            :key="option.value"
            :value="option.value"
          >
            {{ option.label }}
          </option>
        </select>
        <span v-if="field.ui_config?.help" class="form-help">
          {{ field.ui_config.help }}
        </span>
        <span v-if="errors[field.name]" class="form-error">
          {{ errors[field.name] }}
        </span>
      </div>

      <!-- Checkbox -->
      <div v-else-if="field.type === 'checkbox'" class="form-group">
        <label class="form-checkbox-label">
          <input
            :id="field.name"
            v-model="formData[field.name]"
            type="checkbox"
            :disabled="disabled || field.readonly"
            class="form-checkbox"
          />
          <span>{{ field.label }}</span>
        </label>
        <span v-if="field.ui_config?.help" class="form-help">
          {{ field.ui_config.help }}
        </span>
        <span v-if="errors[field.name]" class="form-error">
          {{ errors[field.name] }}
        </span>
      </div>

      <!-- Date -->
      <div v-else-if="field.type === 'date'" class="form-group">
        <label :for="field.name" class="form-label">
          {{ field.label }}
          <span v-if="field.required" class="required">*</span>
        </label>
        <input
          :id="field.name"
          v-model="formData[field.name]"
          type="date"
          :required="field.required"
          :readonly="field.readonly"
          :disabled="disabled"
          class="form-input"
          @blur="validateField(field.name)"
        />
        <span v-if="field.ui_config?.help" class="form-help">
          {{ field.ui_config.help }}
        </span>
        <span v-if="errors[field.name]" class="form-error">
          {{ errors[field.name] }}
        </span>
      </div>

      <!-- Number -->
      <div v-else-if="field.type === 'number'" class="form-group">
        <label :for="field.name" class="form-label">
          {{ field.label }}
          <span v-if="field.required" class="required">*</span>
        </label>
        <input
          :id="field.name"
          v-model.number="formData[field.name]"
          type="number"
          :placeholder="field.ui_config?.placeholder || ''"
          :required="field.required"
          :readonly="field.readonly"
          :disabled="disabled"
          :min="field.ui_config?.min"
          :max="field.ui_config?.max"
          :step="field.ui_config?.step || 'any'"
          class="form-input"
          @blur="validateField(field.name)"
        />
        <span v-if="field.ui_config?.help" class="form-help">
          {{ field.ui_config.help }}
        </span>
        <span v-if="errors[field.name]" class="form-error">
          {{ errors[field.name] }}
        </span>
      </div>

      <!-- Add more field types as needed -->
    </div>

    <div v-if="!hideActions" class="form-actions">
      <button
        type="button"
        @click="handleCancel"
        :disabled="disabled"
        class="btn btn-secondary"
      >
        Cancel
      </button>
      <button
        type="submit"
        :disabled="disabled || !isValid"
        class="btn btn-primary"
      >
        {{ submitLabel }}
      </button>
    </div>
  </form>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import RichTextEditor from '../fields/RichTextEditor.vue';
import FileUpload from '../fields/FileUpload.vue';
import MultiSelect from '../fields/MultiSelect.vue';

const props = defineProps({
  fields: {
    type: Array,
    required: true,
    default: () => []
  },
  modelValue: {
    type: Object,
    default: () => ({})
  },
  disabled: {
    type: Boolean,
    default: false
  },
  submitLabel: {
    type: String,
    default: 'Submit'
  },
  hideActions: {
    type: Boolean,
    default: false
  }
});

const emit = defineEmits(['update:modelValue', 'submit', 'cancel']);

const formData = ref({});
const errors = ref({});
const touched = ref({});

// Computed visible fields with conditional visibility support
const visibleFields = computed(() => {
  return props.fields.filter(field => {
    // Check base visibility
    if (field.is_visible_form === false || field.visible === false) {
      return false;
    }
    
    // Check conditional visibility
    if (field.ui_config?.condition) {
      const condition = field.ui_config.condition;
      if (condition.field && condition.value !== undefined) {
        const fieldValue = formData.value[condition.field];
        if (condition.operator === 'equals') {
          return fieldValue === condition.value;
        } else if (condition.operator === 'not_equals') {
          return fieldValue !== condition.value;
        } else if (condition.operator === 'in') {
          return Array.isArray(condition.value) && condition.value.includes(fieldValue);
        }
      }
    }
    
    return true;
  });
});

// Initialize form data with default values
onMounted(() => {
  const initialData = {};
  props.fields.forEach(field => {
    if (props.modelValue && props.modelValue[field.name] !== undefined) {
      initialData[field.name] = props.modelValue[field.name];
    } else if (field.default !== undefined && field.default !== null) {
      initialData[field.name] = field.default;
    } else {
      initialData[field.name] = field.type === 'checkbox' ? false : '';
    }
  });
  formData.value = initialData;
});

// Watch for changes and emit
watch(formData, (newValue) => {
  emit('update:modelValue', newValue);
}, { deep: true });

// Watch for external model changes
watch(() => props.modelValue, (newValue) => {
  if (newValue && Object.keys(newValue).length > 0) {
    formData.value = { ...formData.value, ...newValue };
  }
}, { deep: true });

const isTextType = (type) => {
  return ['text', 'email', 'password', 'url', 'tel'].includes(type);
};

const validateField = (fieldName) => {
  touched.value[fieldName] = true;
  const field = props.fields.find(f => f.name === fieldName);
  
  if (!field) return;

  errors.value[fieldName] = '';

  // Required validation
  if (field.required && !formData.value[fieldName]) {
    errors.value[fieldName] = `${field.label} is required`;
    return;
  }

  // Custom validation rules
  if (field.validation && field.validation.length > 0) {
    // Simple validation rule implementation
    // You can extend this with more complex rules
    field.validation.forEach(rule => {
      if (typeof rule === 'string') {
        // Handle simple rules like 'email', 'min:3', 'max:100'
        if (rule === 'email') {
          const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
          if (formData.value[fieldName] && !emailRegex.test(formData.value[fieldName])) {
            errors.value[fieldName] = `${field.label} must be a valid email`;
          }
        }
      }
    });
  }
};

const isValid = computed(() => {
  return props.fields.every(field => {
    if (field.required && !formData.value[field.name]) {
      return false;
    }
    return !errors.value[field.name];
  });
});

const handleSubmit = () => {
  // Validate all fields
  props.fields.forEach(field => {
    validateField(field.name);
  });

  if (isValid.value) {
    emit('submit', formData.value);
  }
};

const handleCancel = () => {
  emit('cancel');
};

// Expose methods for parent component
defineExpose({
  validateField,
  formData,
  errors,
  isValid
});
</script>

<style scoped>
.dynamic-form {
  width: 100%;
}

.form-field {
  margin-bottom: 1.5rem;
}

.form-group {
  display: flex;
  flex-direction: column;
}

.form-label {
  font-weight: 500;
  margin-bottom: 0.5rem;
  color: #374151;
}

.required {
  color: #ef4444;
  margin-left: 0.25rem;
}

.form-input,
.form-textarea,
.form-select {
  padding: 0.5rem 0.75rem;
  border: 1px solid #d1d5db;
  border-radius: 0.375rem;
  font-size: 0.875rem;
  transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
}

.form-input:focus,
.form-textarea:focus,
.form-select:focus {
  outline: none;
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.form-input:disabled,
.form-textarea:disabled,
.form-select:disabled {
  background-color: #f3f4f6;
  cursor: not-allowed;
}

.form-checkbox-label {
  display: flex;
  align-items: center;
  cursor: pointer;
}

.form-checkbox {
  margin-right: 0.5rem;
  width: 1rem;
  height: 1rem;
  cursor: pointer;
}

.form-help {
  font-size: 0.75rem;
  color: #6b7280;
  margin-top: 0.25rem;
}

.form-error {
  font-size: 0.75rem;
  color: #ef4444;
  margin-top: 0.25rem;
}

.form-actions {
  display: flex;
  justify-content: flex-end;
  gap: 0.75rem;
  margin-top: 1.5rem;
  padding-top: 1.5rem;
  border-top: 1px solid #e5e7eb;
}

.btn {
  padding: 0.5rem 1rem;
  border-radius: 0.375rem;
  font-size: 0.875rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.15s ease-in-out;
  border: none;
}

.btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.btn-primary {
  background-color: #3b82f6;
  color: white;
}

.btn-primary:hover:not(:disabled) {
  background-color: #2563eb;
}

.btn-secondary {
  background-color: #e5e7eb;
  color: #374151;
}

.btn-secondary:hover:not(:disabled) {
  background-color: #d1d5db;
}
</style>
