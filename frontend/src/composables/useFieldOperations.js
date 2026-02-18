/**
 * useFieldOperations Composable
 * 
 * Provides utilities for working with metadata fields including:
 * - Field visibility logic
 * - Field permission checks
 * - Conditional field rendering
 * - Field value transformations
 * - Field validation helpers
 */

import { computed } from 'vue';
import { usePermissions } from './usePermissions';

export function useFieldOperations(fields, formData, context = 'form') {
  const { hasPermission } = usePermissions();

  /**
   * Get visible fields based on context, permissions, and conditions
   */
  const visibleFields = computed(() => {
    if (!fields.value || !Array.isArray(fields.value)) {
      return [];
    }

    return fields.value
      .filter(field => isFieldVisible(field, formData.value, context))
      .sort((a, b) => (a.order || 0) - (b.order || 0));
  });

  /**
   * Check if field is visible based on multiple criteria
   */
  function isFieldVisible(field, currentFormData, currentContext) {
    // Check context-specific visibility flags
    const contextFlag = `is_visible_${currentContext}`;
    if (field[contextFlag] === false) {
      return false;
    }

    // Check explicit visible flag
    if (field.visible === false) {
      return false;
    }

    // Check field-level permissions
    if (!hasFieldPermission(field, 'view')) {
      return false;
    }

    // Check conditional visibility
    if (!evaluateConditions(field, currentFormData)) {
      return false;
    }

    return true;
  }

  /**
   * Check if user has permission for field action
   */
  function hasFieldPermission(field, action = 'view') {
    // If no permissions defined, allow access
    if (!field.permissions || typeof field.permissions !== 'object') {
      return true;
    }

    const permission = field.permissions[action];
    
    // If no specific permission required, allow access
    if (!permission) {
      return true;
    }

    // Check if user has the required permission
    return hasPermission.value(permission);
  }

  /**
   * Evaluate conditional visibility rules
   */
  function evaluateConditions(field, currentFormData) {
    const conditions = field.ui_config?.conditions || field.conditions;
    
    if (!conditions) {
      return true;
    }

    // Single condition object
    if (conditions.field) {
      return evaluateSingleCondition(conditions, currentFormData);
    }

    // Array of conditions (all must be true by default)
    if (Array.isArray(conditions)) {
      const logic = field.ui_config?.condition_logic || 'AND';
      
      if (logic === 'OR') {
        return conditions.some(condition => evaluateSingleCondition(condition, currentFormData));
      } else {
        return conditions.every(condition => evaluateSingleCondition(condition, currentFormData));
      }
    }

    return true;
  }

  /**
   * Evaluate single condition
   */
  function evaluateSingleCondition(condition, currentFormData) {
    const { field: conditionField, operator, value } = condition;
    const fieldValue = currentFormData[conditionField];

    switch (operator) {
      case 'equals':
      case '==':
        return fieldValue === value;
      
      case 'not_equals':
      case '!=':
        return fieldValue !== value;
      
      case 'in':
        return Array.isArray(value) && value.includes(fieldValue);
      
      case 'not_in':
        return Array.isArray(value) && !value.includes(fieldValue);
      
      case 'greater_than':
      case '>':
        return Number(fieldValue) > Number(value);
      
      case 'less_than':
      case '<':
        return Number(fieldValue) < Number(value);
      
      case 'greater_than_or_equal':
      case '>=':
        return Number(fieldValue) >= Number(value);
      
      case 'less_than_or_equal':
      case '<=':
        return Number(fieldValue) <= Number(value);
      
      case 'contains':
        return String(fieldValue).includes(String(value));
      
      case 'not_contains':
        return !String(fieldValue).includes(String(value));
      
      case 'is_empty':
        return !fieldValue || fieldValue === '' || (Array.isArray(fieldValue) && fieldValue.length === 0);
      
      case 'is_not_empty':
        return fieldValue && fieldValue !== '' && (!Array.isArray(fieldValue) || fieldValue.length > 0);
      
      case 'matches':
        try {
          const regex = new RegExp(value);
          return regex.test(String(fieldValue));
        } catch (e) {
          console.error('Invalid regex pattern:', value);
          return false;
        }
      
      default:
        console.warn('Unknown condition operator:', operator);
        return true;
    }
  }

  /**
   * Check if field is editable
   */
  function isFieldEditable(field) {
    // Check readonly flag
    if (field.is_readonly || field.readonly) {
      return false;
    }

    // Check system field flag
    if (field.is_system) {
      return false;
    }

    // Check edit permission
    if (!hasFieldPermission(field, 'edit')) {
      return false;
    }

    return true;
  }

  /**
   * Get field default value
   */
  function getFieldDefaultValue(field) {
    if (field.default_value !== undefined && field.default_value !== null) {
      return field.default_value;
    }

    // Type-based defaults
    switch (field.type) {
      case 'checkbox':
        return false;
      case 'number':
        return 0;
      case 'multiselect':
        return [];
      case 'date':
      case 'datetime':
        return null;
      default:
        return '';
    }
  }

  /**
   * Transform field value for display
   */
  function formatFieldValue(field, value) {
    if (value === null || value === undefined) {
      return '';
    }

    switch (field.type) {
      case 'date':
        return formatDate(value);
      
      case 'datetime':
        return formatDateTime(value);
      
      case 'number':
        return formatNumber(value, field.ui_config);
      
      case 'select':
      case 'radio':
        return getOptionLabel(field.options, value);
      
      case 'multiselect':
        return Array.isArray(value)
          ? value.map(v => getOptionLabel(field.options, v)).join(', ')
          : '';
      
      case 'checkbox':
        return value ? 'Yes' : 'No';
      
      default:
        return String(value);
    }
  }

  /**
   * Helper: Format date
   */
  function formatDate(value) {
    const date = new Date(value);
    return date.toLocaleDateString();
  }

  /**
   * Helper: Format datetime
   */
  function formatDateTime(value) {
    const date = new Date(value);
    return date.toLocaleString();
  }

  /**
   * Helper: Format number
   */
  function formatNumber(value, config = {}) {
    const num = Number(value);
    if (isNaN(num)) return value;

    const options = {};
    if (config.decimals !== undefined) {
      options.minimumFractionDigits = config.decimals;
      options.maximumFractionDigits = config.decimals;
    }
    if (config.currency) {
      options.style = 'currency';
      options.currency = config.currency;
    }

    return num.toLocaleString(undefined, options);
  }

  /**
   * Helper: Get option label
   */
  function getOptionLabel(options, value) {
    if (!Array.isArray(options)) return value;
    
    const option = options.find(opt => opt.value === value);
    return option ? option.label : value;
  }

  /**
   * Group fields by sections
   */
  const fieldsBySection = computed(() => {
    const sections = {};
    
    visibleFields.value.forEach(field => {
      const section = field.section || 'default';
      if (!sections[section]) {
        sections[section] = [];
      }
      sections[section].push(field);
    });

    return sections;
  });

  /**
   * Get required fields
   */
  const requiredFields = computed(() => {
    return visibleFields.value.filter(field => field.is_required || field.required);
  });

  /**
   * Get editable fields
   */
  const editableFields = computed(() => {
    return visibleFields.value.filter(field => isFieldEditable(field));
  });

  return {
    visibleFields,
    fieldsBySection,
    requiredFields,
    editableFields,
    isFieldVisible,
    isFieldEditable,
    hasFieldPermission,
    evaluateConditions,
    getFieldDefaultValue,
    formatFieldValue,
  };
}
