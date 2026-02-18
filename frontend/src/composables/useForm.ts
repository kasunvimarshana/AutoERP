import { ref, reactive, computed } from 'vue';
import { object, AnySchema } from 'yup';

interface FormOptions {
  initialValues?: Record<string, any>;
  validationSchema?: AnySchema;
  onSubmit?: (values: Record<string, any>) => Promise<void> | void;
}

export function useForm(options: FormOptions = {}) {
  const { initialValues = {}, validationSchema, onSubmit } = options;

  const values = reactive<Record<string, any>>({ ...initialValues });
  const errors = reactive<Record<string, string>>({});
  const touched = reactive<Record<string, boolean>>({});
  const isSubmitting = ref(false);

  const isValid = computed(() => {
    return Object.keys(errors).length === 0;
  });

  const isDirty = computed(() => {
    return Object.keys(values).some(key => values[key] !== initialValues[key]);
  });

  const setFieldValue = (field: string, value: any) => {
    values[field] = value;
    validateField(field);
  };

  const setFieldTouched = (field: string, isTouched = true) => {
    touched[field] = isTouched;
  };

  const setFieldError = (field: string, error: string) => {
    errors[field] = error;
  };

  const clearFieldError = (field: string) => {
    delete errors[field];
  };

  const validateField = async (field: string) => {
    if (!validationSchema) return true;

    try {
      const fieldSchema = (validationSchema as any).fields[field];
      if (fieldSchema) {
        await fieldSchema.validate(values[field]);
        clearFieldError(field);
        return true;
      }
    } catch (err: any) {
      setFieldError(field, err.message);
      return false;
    }

    return true;
  };

  const validateForm = async () => {
    if (!validationSchema) return true;

    try {
      await validationSchema.validate(values, { abortEarly: false });
      Object.keys(errors).forEach(key => delete errors[key]);
      return true;
    } catch (err: any) {
      if (err.inner) {
        err.inner.forEach((error: any) => {
          if (error.path) {
            errors[error.path] = error.message;
          }
        });
      }
      return false;
    }
  };

  const handleSubmit = async (e?: Event) => {
    if (e) {
      e.preventDefault();
    }

    // Mark all fields as touched
    Object.keys(values).forEach(key => {
      touched[key] = true;
    });

    // Validate form
    const valid = await validateForm();
    if (!valid) return;

    if (onSubmit) {
      isSubmitting.value = true;
      try {
        await onSubmit(values);
      } finally {
        isSubmitting.value = false;
      }
    }
  };

  const resetForm = () => {
    Object.keys(values).forEach(key => {
      values[key] = initialValues[key];
    });
    Object.keys(errors).forEach(key => delete errors[key]);
    Object.keys(touched).forEach(key => delete touched[key]);
  };

  const setValues = (newValues: Record<string, any>) => {
    Object.assign(values, newValues);
  };

  const setErrors = (newErrors: Record<string, string>) => {
    Object.assign(errors, newErrors);
  };

  return {
    values,
    errors,
    touched,
    isValid,
    isDirty,
    isSubmitting,
    setFieldValue,
    setFieldTouched,
    setFieldError,
    clearFieldError,
    validateField,
    validateForm,
    handleSubmit,
    resetForm,
    setValues,
    setErrors,
  };
}
