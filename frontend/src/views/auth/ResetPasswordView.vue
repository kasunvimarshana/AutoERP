<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useNotification } from '@/composables/useNotification'
import { authService } from '@/services/authService'
import BaseInput from '@/components/ui/BaseInput.vue'
import BaseButton from '@/components/ui/BaseButton.vue'

const router = useRouter()
const route = useRoute()
const notification = useNotification()

const form = ref({
  email: '',
  token: '',
  password: '',
  password_confirmation: '',
})

const errors = ref<Record<string, string>>({})
const isLoading = ref(false)

onMounted(() => {
  // Get token and email from query params
  form.value.token = route.query.token as string || ''
  form.value.email = route.query.email as string || ''

  if (!form.value.token) {
    notification.error('Invalid reset link', 'Error')
    router.push('/forgot-password')
  }
})

const validateForm = () => {
  errors.value = {}

  if (!form.value.password) {
    errors.value.password = 'Password is required'
  } else if (form.value.password.length < 8) {
    errors.value.password = 'Password must be at least 8 characters'
  }

  if (form.value.password !== form.value.password_confirmation) {
    errors.value.password_confirmation = 'Passwords do not match'
  }

  return Object.keys(errors.value).length === 0
}

const handleSubmit = async () => {
  if (!validateForm()) {
    return
  }

  isLoading.value = true

  try {
    await authService.resetPassword(form.value)
    notification.success('Password reset successful!', 'Success')
    router.push('/login')
  } catch (error: any) {
    notification.error(error.message || 'Password reset failed', 'Error')
    
    if (error.errors) {
      errors.value = error.errors
    }
  } finally {
    isLoading.value = false
  }
}
</script>

<template>
  <div class="reset-password-view">
    <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">Reset Password</h2>
    <p class="text-gray-600 dark:text-gray-400 mb-6">
      Enter your new password below.
    </p>

    <form @submit.prevent="handleSubmit" class="space-y-4">
      <BaseInput
        id="email"
        v-model="form.email"
        type="email"
        label="Email Address"
        :disabled="true"
      />

      <BaseInput
        id="password"
        v-model="form.password"
        type="password"
        label="New Password"
        placeholder="Enter new password"
        :error="errors.password"
        :disabled="isLoading"
        required
        hint="Must be at least 8 characters"
      />

      <BaseInput
        id="password_confirmation"
        v-model="form.password_confirmation"
        type="password"
        label="Confirm Password"
        placeholder="Confirm new password"
        :error="errors.password_confirmation"
        :disabled="isLoading"
        required
      />

      <BaseButton
        type="submit"
        variant="primary"
        :loading="isLoading"
        :disabled="isLoading"
        full-width
      >
        Reset Password
      </BaseButton>
    </form>

    <div class="mt-6 text-center">
      <router-link
        to="/login"
        class="text-sm text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300"
      >
        ← Back to Sign In
      </router-link>
    </div>
  </div>
</template>
