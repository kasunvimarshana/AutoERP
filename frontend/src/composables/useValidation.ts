/**
 * Enhanced Form Validation Composable
 * Provides comprehensive client-side validation
 */

import { ref, computed, watch, Ref } from 'vue';

export interface ValidationRule {
  type: 'required' | 'email' | 'min' | 'max' | 'pattern' | 'custom' | 'minLength' | 'maxLength' | 'numeric' | 'url' | 'phone' | 'date' | 'time' | 'datetime' | 'matches';
  message?: string;
  value?: any;
  validator?: (value: any) => boolean | Promise<boolean>;
}

export interface FieldValidation {
  field: string;
  rules: ValidationRule[];
  validateOn?: 'blur' | 'change' | 'submit';
}

export interface ValidationError {
  field: string;
  message: string;
}

export interface ValidationState {
  [field: string]: {
    errors: string[];
    touched: boolean;
    validating: boolean;
    valid: boolean;
  };
}

export function useValidation(validations: FieldValidation[] = []) {
  const validationState = ref<ValidationState>({});
  const isValidating = ref(false);

  // Initialize validation state for all fields
  validations.forEach(({ field }) => {
    validationState.value[field] = {
      errors: [],
      touched: false,
      validating: false,
      valid: true,
    };
  });

  // Computed
  const errors = computed(() => {
    const allErrors: ValidationError[] = [];
    Object.entries(validationState.value).forEach(([field, state]) => {
      state.errors.forEach(message => {
        allErrors.push({ field, message });
      });
    });
    return allErrors;
  });

  const hasErrors = computed(() => errors.value.length > 0);
  
  const isValid = computed(() => {
    return Object.values(validationState.value).every(state => state.valid);
  });

  const touchedFields = computed(() => {
    return Object.entries(validationState.value)
      .filter(([_, state]) => state.touched)
      .map(([field]) => field);
  });

  /**
   * Validate a single value against rules
   */
  const validateValue = async (value: any, rules: ValidationRule[]): Promise<string[]> => {
    const errors: string[] = [];

    for (const rule of rules) {
      let isInvalid = false;
      let errorMessage = rule.message || 'Validation failed';

      switch (rule.type) {
        case 'required':
          if (value === null || value === undefined || value === '' || (Array.isArray(value) && value.length === 0)) {
            isInvalid = true;
            errorMessage = rule.message || 'This field is required';
          }
          break;

        case 'email':
          if (value && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(String(value))) {
            isInvalid = true;
            errorMessage = rule.message || 'Please enter a valid email address';
          }
          break;

        case 'url':
          if (value) {
            try {
              new URL(String(value));
            } catch {
              isInvalid = true;
              errorMessage = rule.message || 'Please enter a valid URL';
            }
          }
          break;

        case 'phone':
          if (value && !/^[\d\s\-\+\(\)]+$/.test(String(value))) {
            isInvalid = true;
            errorMessage = rule.message || 'Please enter a valid phone number';
          }
          break;

        case 'numeric':
          if (value && !/^\d+(\.\d+)?$/.test(String(value))) {
            isInvalid = true;
            errorMessage = rule.message || 'This field must be numeric';
          }
          break;

        case 'min':
          if (value !== null && value !== undefined) {
            const numValue = Number(value);
            if (!isNaN(numValue) && numValue < rule.value) {
              isInvalid = true;
              errorMessage = rule.message || `Value must be at least ${rule.value}`;
            }
          }
          break;

        case 'max':
          if (value !== null && value !== undefined) {
            const numValue = Number(value);
            if (!isNaN(numValue) && numValue > rule.value) {
              isInvalid = true;
              errorMessage = rule.message || `Value must be at most ${rule.value}`;
            }
          }
          break;

        case 'minLength':
          if (value && String(value).length < rule.value) {
            isInvalid = true;
            errorMessage = rule.message || `Must be at least ${rule.value} characters`;
          }
          break;

        case 'maxLength':
          if (value && String(value).length > rule.value) {
            isInvalid = true;
            errorMessage = rule.message || `Must be at most ${rule.value} characters`;
          }
          break;

        case 'pattern':
          if (value && rule.value instanceof RegExp && !rule.value.test(String(value))) {
            isInvalid = true;
            errorMessage = rule.message || 'Invalid format';
          }
          break;

        case 'date':
          if (value && isNaN(Date.parse(String(value)))) {
            isInvalid = true;
            errorMessage = rule.message || 'Please enter a valid date';
          }
          break;

        case 'custom':
          if (rule.validator) {
            try {
              const result = await rule.validator(value);
              if (!result) {
                isInvalid = true;
              }
            } catch (error) {
              isInvalid = true;
              errorMessage = error instanceof Error ? error.message : String(error);
            }
          }
          break;

        case 'matches':
          if (rule.value && value !== rule.value) {
            isInvalid = true;
            errorMessage = rule.message || 'Values do not match';
          }
          break;
      }

      if (isInvalid) {
        errors.push(errorMessage);
      }
    }

    return errors;
  };

  /**
   * Validate a single field
   */
  const validateField = async (field: string, value: any): Promise<boolean> => {
    const validation = validations.find(v => v.field === field);
    if (!validation) return true;

    if (!validationState.value[field]) {
      validationState.value[field] = {
        errors: [],
        touched: false,
        validating: false,
        valid: true,
      };
    }

    validationState.value[field].validating = true;
    
    const fieldErrors = await validateValue(value, validation.rules);
    
    validationState.value[field].errors = fieldErrors;
    validationState.value[field].valid = fieldErrors.length === 0;
    validationState.value[field].validating = false;

    return validationState.value[field].valid;
  };

  /**
   * Validate all fields
   */
  const validateAll = async (formData: Record<string, any>): Promise<boolean> => {
    isValidating.value = true;

    const validationPromises = validations.map(({ field }) => {
      return validateField(field, formData[field]);
    });

    const results = await Promise.all(validationPromises);
    isValidating.value = false;

    return results.every(result => result === true);
  };

  /**
   * Mark field as touched
   */
  const touchField = (field: string) => {
    if (validationState.value[field]) {
      validationState.value[field].touched = true;
    }
  };

  /**
   * Mark all fields as touched
   */
  const touchAll = () => {
    Object.keys(validationState.value).forEach(field => {
      validationState.value[field].touched = true;
    });
  };

  /**
   * Clear field errors
   */
  const clearFieldErrors = (field: string) => {
    if (validationState.value[field]) {
      validationState.value[field].errors = [];
      validationState.value[field].valid = true;
    }
  };

  /**
   * Clear all errors
   */
  const clearAllErrors = () => {
    Object.keys(validationState.value).forEach(field => {
      validationState.value[field].errors = [];
      validationState.value[field].valid = true;
    });
  };

  /**
   * Reset validation state
   */
  const reset = () => {
    Object.keys(validationState.value).forEach(field => {
      validationState.value[field] = {
        errors: [],
        touched: false,
        validating: false,
        valid: true,
      };
    });
  };

  /**
   * Get field errors
   */
  const getFieldErrors = (field: string): string[] => {
    return validationState.value[field]?.errors || [];
  };

  /**
   * Check if field has errors
   */
  const hasFieldErrors = (field: string): boolean => {
    return getFieldErrors(field).length > 0;
  };

  /**
   * Check if field is valid
   */
  const isFieldValid = (field: string): boolean => {
    return validationState.value[field]?.valid ?? true;
  };

  /**
   * Check if field is touched
   */
  const isFieldTouched = (field: string): boolean => {
    return validationState.value[field]?.touched ?? false;
  };

  /**
   * Set server errors (from API response)
   */
  const setServerErrors = (serverErrors: Record<string, string[]>) => {
    Object.entries(serverErrors).forEach(([field, messages]) => {
      if (validationState.value[field]) {
        validationState.value[field].errors = messages;
        validationState.value[field].valid = false;
        validationState.value[field].touched = true;
      }
    });
  };

  return {
    // State
    validationState,
    isValidating,

    // Computed
    errors,
    hasErrors,
    isValid,
    touchedFields,

    // Methods
    validateField,
    validateAll,
    touchField,
    touchAll,
    clearFieldErrors,
    clearAllErrors,
    reset,
    getFieldErrors,
    hasFieldErrors,
    isFieldValid,
    isFieldTouched,
    setServerErrors,
  };
}

/**
 * Common validation rule builders
 */
export const ValidationRules = {
  required: (message?: string): ValidationRule => ({
    type: 'required',
    message: message || 'This field is required',
  }),

  email: (message?: string): ValidationRule => ({
    type: 'email',
    message: message || 'Please enter a valid email address',
  }),

  min: (value: number, message?: string): ValidationRule => ({
    type: 'min',
    value,
    message: message || `Value must be at least ${value}`,
  }),

  max: (value: number, message?: string): ValidationRule => ({
    type: 'max',
    value,
    message: message || `Value must be at most ${value}`,
  }),

  minLength: (value: number, message?: string): ValidationRule => ({
    type: 'minLength',
    value,
    message: message || `Must be at least ${value} characters`,
  }),

  maxLength: (value: number, message?: string): ValidationRule => ({
    type: 'maxLength',
    value,
    message: message || `Must be at most ${value} characters`,
  }),

  pattern: (regex: RegExp, message?: string): ValidationRule => ({
    type: 'pattern',
    value: regex,
    message: message || 'Invalid format',
  }),

  numeric: (message?: string): ValidationRule => ({
    type: 'numeric',
    message: message || 'This field must be numeric',
  }),

  url: (message?: string): ValidationRule => ({
    type: 'url',
    message: message || 'Please enter a valid URL',
  }),

  phone: (message?: string): ValidationRule => ({
    type: 'phone',
    message: message || 'Please enter a valid phone number',
  }),

  custom: (validator: (value: any) => boolean | Promise<boolean>, message?: string): ValidationRule => ({
    type: 'custom',
    validator,
    message: message || 'Validation failed',
  }),

  matches: (compareValue: any, message?: string): ValidationRule => ({
    type: 'matches',
    value: compareValue,
    message: message || 'Values do not match',
  }),
};
