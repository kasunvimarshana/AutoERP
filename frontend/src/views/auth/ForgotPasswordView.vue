<script setup lang="ts">
import { ref } from 'vue'
import { useNotification } from '@/composables/useNotification'
import { authService } from '@/services/authService'
import BaseInput from '@/components/ui/BaseInput.vue'
import BaseButton from '@/components/ui/BaseButton.vue'

const notification = useNotification()

const email = ref('')
const error = ref('')
const isLoading = ref(false)
const isSuccess = ref(false)

const validateEmail = () => {
  if (!email.value) {
    error.value = 'Email is required'
    return false
  }
  if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value)) {
    error.value = 'Please enter a valid email'
    return false
  }
  error.value = ''
  return true
}

const handleSubmit = async () => {
  if (!validateEmail()) {
    return
  }

  isLoading.value = true

  try {
    await authService.forgotPassword({ email: email.value })
    isSuccess.value = true
    notification.success('Password reset link sent to your email', 'Success')
  } catch (err: any) {
    notification.error(err.message || 'Failed to send reset link', 'Error')
    error.value = err.message
  } finally {
    isLoading.value = false
  }
}
</script>

<template>
  <div class="forgot-password-view">
    <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">Forgot Password?</h2>
    <p class="text-gray-600 dark:text-gray-400 mb-6">
      Enter your email address and we'll send you a link to reset your password.
    </p>

    <div v-if="isSuccess" class="p-4 mb-6 bg-green-50 dark:bg-green-900 border border-green-200 dark:border-green-700 rounded-lg">
      <p class="text-sm text-green-800 dark:text-green-200">
        We've sent a password reset link to <strong>{{ email }}</strong>. Please check your email and follow the instructions.
      </p>
    </div>

    <form v-else @submit.prevent="handleSubmit" class="space-y-4">
      <BaseInput
        id="email"
        v-model="email"
        type="email"
        label="Email Address"
        placeholder="Enter your email"
        :error="error"
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
        Send Reset Link
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
