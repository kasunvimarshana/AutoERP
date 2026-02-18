import { ref, computed, watch } from 'vue';
import type { FormFieldMetadata } from '@/types/metadata';

interface ValidationRule {
  type: string;
  value?: any;
  message: string;
}

interface ValidationResult {
  valid: boolean;
  errors: Record<string, string[]>;
}

/**
 * Helper function to check if a value exists
 */
const hasValue = (value: any): boolean => {
  return value !== null && value !== undefined && value !== '';
};

/**
 * Advanced form validation composable with metadata-driven rules
 */
export function useFormValidation(fields: FormFieldMetadata[], formData: Record<string, any>) {
  const errors = ref<Record<string, string[]>>({});
  const touchedFields = ref<Set<string>>(new Set());

  // Parse validation rules from metadata
  const parseValidationRules = (field: FormFieldMetadata): ValidationRule[] => {
    const rules: ValidationRule[] = [];

    if (!field.validation) return rules;

    // Required validation
    if (field.required || field.validation.required) {
      rules.push({
        type: 'required',
        message: field.validation.requiredMessage || `${field.label} is required`,
      });
    }

    // Min/Max length
    if (field.validation.minLength) {
      rules.push({
        type: 'minLength',
        value: field.validation.minLength,
        message: field.validation.minLengthMessage || 
          `${field.label} must be at least ${field.validation.minLength} characters`,
      });
    }

    if (field.validation.maxLength) {
      rules.push({
        type: 'maxLength',
        value: field.validation.maxLength,
        message: field.validation.maxLengthMessage || 
          `${field.label} must not exceed ${field.validation.maxLength} characters`,
      });
    }

    // Min/Max value (for numbers)
    if (field.validation.min !== undefined) {
      rules.push({
        type: 'min',
        value: field.validation.min,
        message: field.validation.minMessage || 
          `${field.label} must be at least ${field.validation.min}`,
      });
    }

    if (field.validation.max !== undefined) {
      rules.push({
        type: 'max',
        value: field.validation.max,
        message: field.validation.maxMessage || 
          `${field.label} must not exceed ${field.validation.max}`,
      });
    }

    // Pattern (regex)
    if (field.validation.pattern) {
      rules.push({
        type: 'pattern',
        value: field.validation.pattern,
        message: field.validation.patternMessage || 
          `${field.label} format is invalid`,
      });
    }

    // Email validation
    if (field.type === 'email' || field.validation.email) {
      rules.push({
        type: 'email',
        message: field.validation.emailMessage || 'Invalid email address',
      });
    }

    // Custom validation rules
    if (field.validation.custom) {
      rules.push({
        type: 'custom',
        value: field.validation.custom,
        message: field.validation.customMessage || 'Validation failed',
      });
    }

    return rules;
  };

  // Validate a single field
  const validateField = (field: FormFieldMetadata): string[] => {
    const fieldErrors: string[] = [];
    const value = formData[field.name];
    const rules = parseValidationRules(field);

    for (const rule of rules) {
      switch (rule.type) {
        case 'required':
          if (value === null || value === undefined || value === '' || 
              (Array.isArray(value) && value.length === 0)) {
            fieldErrors.push(rule.message);
          }
          break;

        case 'minLength':
          if (hasValue(value) && String(value).length < rule.value) {
            fieldErrors.push(rule.message);
          }
          break;

        case 'maxLength':
          if (hasValue(value) && String(value).length > rule.value) {
            fieldErrors.push(rule.message);
          }
          break;

        case 'min':
          if (hasValue(value) && Number(value) < rule.value) {
            fieldErrors.push(rule.message);
          }
          break;

        case 'max':
          if (hasValue(value) && Number(value) > rule.value) {
            fieldErrors.push(rule.message);
          }
          break;

        case 'pattern':
          if (hasValue(value) && !new RegExp(rule.value).test(String(value))) {
            fieldErrors.push(rule.message);
          }
          break;

        case 'email':
          if (hasValue(value) && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(String(value))) {
            fieldErrors.push(rule.message);
          }
          break;

        case 'custom':
          // Custom validation function
          if (typeof rule.value === 'function') {
            const result = rule.value(value, formData);
            if (result !== true) {
              fieldErrors.push(typeof result === 'string' ? result : rule.message);
            }
          }
          break;
      }
    }

    return fieldErrors;
  };

  // Validate all fields
  const validateAll = (): ValidationResult => {
    const newErrors: Record<string, string[]> = {};
    let valid = true;

    for (const field of fields) {
      // Skip fields that are not visible
      if (field.visible === false) continue;

      // Skip fields with dependencies that are not met
      if (field.dependsOn) {
        const dependencyValue = formData[field.dependsOn.field];
        if (dependencyValue !== field.dependsOn.value) continue;
      }

      const fieldErrors = validateField(field);
      if (fieldErrors.length > 0) {
        newErrors[field.name] = fieldErrors;
        valid = false;
      }
    }

    errors.value = newErrors;
    return { valid, errors: newErrors };
  };

  // Validate a single field by name
  const validateOne = (fieldName: string): void => {
    const field = fields.find(f => f.name === fieldName);
    if (!field) return;

    const fieldErrors = validateField(field);
    
    if (fieldErrors.length > 0) {
      errors.value[fieldName] = fieldErrors;
    } else {
      delete errors.value[fieldName];
    }
  };

  // Mark field as touched
  const touchField = (fieldName: string): void => {
    touchedFields.value.add(fieldName);
  };

  // Check if field is touched
  const isFieldTouched = (fieldName: string): boolean => {
    return touchedFields.value.has(fieldName);
  };

  // Get errors for a field
  const getFieldErrors = (fieldName: string): string[] => {
    return errors.value[fieldName] || [];
  };

  // Check if field has errors
  const hasFieldErrors = (fieldName: string): boolean => {
    return !!errors.value[fieldName] && errors.value[fieldName].length > 0;
  };

  // Clear all errors
  const clearErrors = (): void => {
    errors.value = {};
  };

  // Clear errors for a specific field
  const clearFieldErrors = (fieldName: string): void => {
    delete errors.value[fieldName];
  };

  // Check if form is valid
  const isValid = computed(() => {
    return Object.keys(errors.value).length === 0;
  });

  // Auto-validate on data change
  watch(() => formData, () => {
    // Only validate fields that have been touched
    for (const fieldName of touchedFields.value) {
      validateOne(fieldName);
    }
  }, { deep: true });

  return {
    errors,
    touchedFields,
    validateAll,
    validateOne,
    touchField,
    isFieldTouched,
    getFieldErrors,
    hasFieldErrors,
    clearErrors,
    clearFieldErrors,
    isValid,
  };
}
