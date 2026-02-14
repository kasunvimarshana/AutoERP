import { ref, watchEffect } from 'vue'

export function useForm(initialValues, validationRules = {}) {
  const values = ref({ ...initialValues })
  const errors = ref({})
  const touched = ref({})
  const isSubmitting = ref(false)

  const validate = () => {
    const newErrors = {}
    
    Object.keys(validationRules).forEach(field => {
      const rules = validationRules[field]
      const value = values.value[field]
      
      for (const rule of rules) {
        const error = rule(value)
        if (error) {
          newErrors[field] = error
          break
        }
      }
    })
    
    errors.value = newErrors
    return Object.keys(newErrors).length === 0
  }

  const handleSubmit = (onSubmit) => {
    return async () => {
      isSubmitting.value = true
      
      if (validate()) {
        try {
          await onSubmit(values.value)
        } finally {
          isSubmitting.value = false
        }
      } else {
        isSubmitting.value = false
      }
    }
  }

  const reset = () => {
    values.value = { ...initialValues }
    errors.value = {}
    touched.value = {}
  }

  return {
    values,
    errors,
    touched,
    isSubmitting,
    validate,
    handleSubmit,
    reset
  }
}
