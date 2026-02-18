import { ref, computed } from 'vue';

/**
 * Composable for form validation based on metadata rules
 */
export const useValidation = () => {
  const errors = ref({});
  const touched = ref({});

  /**
   * Validate a single field
   */
  const validateField = (field, value) => {
    const fieldErrors = [];

    // Required validation
    if (field.required) {
      if (value === null || value === undefined || value === '') {
        fieldErrors.push(`${field.label} is required`);
      }
    }

    // Type-specific validation
    if (value !== null && value !== undefined && value !== '') {
      switch (field.type) {
        case 'email':
          if (!isValidEmail(value)) {
            fieldErrors.push(`${field.label} must be a valid email address`);
          }
          break;

        case 'url':
          if (!isValidUrl(value)) {
            fieldErrors.push(`${field.label} must be a valid URL`);
          }
          break;

        case 'number':
          if (isNaN(value)) {
            fieldErrors.push(`${field.label} must be a number`);
          }
          break;
      }
    }

    // Custom validation rules
    if (field.validation && Array.isArray(field.validation)) {
      field.validation.forEach(rule => {
        if (typeof rule === 'string') {
          const error = applyStringRule(rule, field, value);
          if (error) fieldErrors.push(error);
        } else if (typeof rule === 'object') {
          const error = applyObjectRule(rule, field, value);
          if (error) fieldErrors.push(error);
        } else if (typeof rule === 'function') {
          const error = rule(value, field);
          if (error) fieldErrors.push(error);
        }
      });
    }

    // Min/Max validation for numbers
    if (field.type === 'number' && !isNaN(value)) {
      if (field.ui_config?.min !== undefined && value < field.ui_config.min) {
        fieldErrors.push(`${field.label} must be at least ${field.ui_config.min}`);
      }
      if (field.ui_config?.max !== undefined && value > field.ui_config.max) {
        fieldErrors.push(`${field.label} must not exceed ${field.ui_config.max}`);
      }
    }

    return fieldErrors.length > 0 ? fieldErrors[0] : null;
  };

  /**
   * Apply string-based validation rule
   */
  const applyStringRule = (rule, field, value) => {
    const parts = rule.split(':');
    const ruleName = parts[0];
    const ruleValue = parts[1];

    switch (ruleName) {
      case 'required':
        if (!value) return `${field.label} is required`;
        break;

      case 'email':
        if (value && !isValidEmail(value)) {
          return `${field.label} must be a valid email`;
        }
        break;

      case 'min':
        if (typeof value === 'string' && value.length < parseInt(ruleValue)) {
          return `${field.label} must be at least ${ruleValue} characters`;
        }
        if (typeof value === 'number' && value < parseInt(ruleValue)) {
          return `${field.label} must be at least ${ruleValue}`;
        }
        break;

      case 'max':
        if (typeof value === 'string' && value.length > parseInt(ruleValue)) {
          return `${field.label} must not exceed ${ruleValue} characters`;
        }
        if (typeof value === 'number' && value > parseInt(ruleValue)) {
          return `${field.label} must not exceed ${ruleValue}`;
        }
        break;

      case 'pattern':
        if (value && !new RegExp(ruleValue).test(value)) {
          return `${field.label} format is invalid`;
        }
        break;

      case 'alpha':
        if (value && !/^[a-zA-Z]+$/.test(value)) {
          return `${field.label} must contain only letters`;
        }
        break;

      case 'alphanumeric':
        if (value && !/^[a-zA-Z0-9]+$/.test(value)) {
          return `${field.label} must contain only letters and numbers`;
        }
        break;

      case 'numeric':
        if (value && !/^\d+$/.test(value)) {
          return `${field.label} must contain only numbers`;
        }
        break;
    }

    return null;
  };

  /**
   * Apply object-based validation rule
   */
  const applyObjectRule = (rule, field, value) => {
    if (rule.type === 'custom' && rule.validate) {
      return rule.validate(value, field);
    }
    return null;
  };

  /**
   * Validate all fields
   */
  const validateAll = (fields, formData) => {
    const newErrors = {};
    let isValid = true;

    fields.forEach(field => {
      const error = validateField(field, formData[field.name]);
      if (error) {
        newErrors[field.name] = error;
        isValid = false;
      }
    });

    errors.value = newErrors;
    return isValid;
  };

  /**
   * Mark field as touched
   */
  const touch = (fieldName) => {
    touched.value[fieldName] = true;
  };

  /**
   * Mark all fields as touched
   */
  const touchAll = (fields) => {
    fields.forEach(field => {
      touched.value[field.name] = true;
    });
  };

  /**
   * Clear errors
   */
  const clearErrors = (fieldName = null) => {
    if (fieldName) {
      delete errors.value[fieldName];
    } else {
      errors.value = {};
    }
  };

  /**
   * Clear touched state
   */
  const clearTouched = (fieldName = null) => {
    if (fieldName) {
      delete touched.value[fieldName];
    } else {
      touched.value = {};
    }
  };

  /**
   * Reset validation state
   */
  const reset = () => {
    errors.value = {};
    touched.value = {};
  };

  /**
   * Check if field has error
   */
  const hasError = (fieldName) => {
    return !!errors.value[fieldName];
  };

  /**
   * Get field error
   */
  const getError = (fieldName) => {
    return errors.value[fieldName] || null;
  };

  /**
   * Check if field is touched
   */
  const isTouched = (fieldName) => {
    return !!touched.value[fieldName];
  };

  /**
   * Check if form is valid
   */
  const isValid = computed(() => {
    return Object.keys(errors.value).length === 0;
  });

  return {
    // State
    errors,
    touched,
    isValid,

    // Methods
    validateField,
    validateAll,
    touch,
    touchAll,
    clearErrors,
    clearTouched,
    reset,
    hasError,
    getError,
    isTouched
  };
};

/**
 * Validation helper functions
 */
export const isValidEmail = (email) => {
  const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  return regex.test(email);
};

export const isValidUrl = (url) => {
  try {
    new URL(url);
    return true;
  } catch {
    return false;
  }
};

export const isValidPhone = (phone) => {
  const regex = /^[+]?[(]?[0-9]{1,4}[)]?[-\s.]?[(]?[0-9]{1,4}[)]?[-\s.]?[0-9]{1,9}$/;
  return regex.test(phone);
};

export const isValidPostalCode = (code, country = 'US') => {
  const patterns = {
    US: /^\d{5}(-\d{4})?$/,
    UK: /^[A-Z]{1,2}\d{1,2}[A-Z]?\s?\d[A-Z]{2}$/i,
    CA: /^[A-Z]\d[A-Z]\s?\d[A-Z]\d$/i
  };
  return patterns[country]?.test(code) || false;
};
