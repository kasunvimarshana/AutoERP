<script setup lang="ts">
import { ref } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuth } from '@/composables/useAuth'
import { useNotification } from '@/composables/useNotification'
import BaseInput from '@/components/ui/BaseInput.vue'
import BaseButton from '@/components/ui/BaseButton.vue'

const router = useRouter()
const route = useRoute()
const { login } = useAuth()
const notification = useNotification()

const form = ref({
  email: '',
  password: '',
  remember: false,
})

const errors = ref<Record<string, string>>({})
const isLoading = ref(false)

const validateForm = () => {
  errors.value = {}

  if (!form.value.email) {
    errors.value.email = 'Email is required'
  } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.value.email)) {
    errors.value.email = 'Please enter a valid email'
  }

  if (!form.value.password) {
    errors.value.password = 'Password is required'
  } else if (form.value.password.length < 6) {
    errors.value.password = 'Password must be at least 6 characters'
  }

  return Object.keys(errors.value).length === 0
}

const handleSubmit = async () => {
  if (!validateForm()) {
    return
  }

  isLoading.value = true

  try {
    await login({
      email: form.value.email,
      password: form.value.password,
      remember: form.value.remember,
    })

    notification.success('Login successful!', 'Welcome back')

    // Redirect to intended page or dashboard
    const redirect = (route.query.redirect as string) || '/dashboard'
    router.push(redirect)
  } catch (error: any) {
    notification.error(error.message || 'Login failed', 'Error')
    
    // Handle validation errors
    if (error.errors) {
      errors.value = error.errors
    }
  } finally {
    isLoading.value = false
  }
}
</script>

<template>
  <div class="login-view">
    <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">Sign In</h2>

    <form @submit.prevent="handleSubmit" class="space-y-4">
      <BaseInput
        id="email"
        v-model="form.email"
        type="email"
        label="Email Address"
        placeholder="Enter your email"
        :error="errors.email"
        :disabled="isLoading"
        required
      />

      <BaseInput
        id="password"
        v-model="form.password"
        type="password"
        label="Password"
        placeholder="Enter your password"
        :error="errors.password"
        :disabled="isLoading"
        required
      />

      <div class="flex items-center justify-between">
        <label class="flex items-center">
          <input
            v-model="form.remember"
            type="checkbox"
            class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
            :disabled="isLoading"
          />
          <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">Remember me</span>
        </label>

        <router-link
          to="/forgot-password"
          class="text-sm text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300"
        >
          Forgot password?
        </router-link>
      </div>

      <BaseButton
        type="submit"
        variant="primary"
        :loading="isLoading"
        :disabled="isLoading"
        full-width
      >
        Sign In
      </BaseButton>
    </form>

    <div class="mt-6 text-center">
      <p class="text-sm text-gray-600 dark:text-gray-400">
        Don't have an account?
        <router-link
          to="/register"
          class="font-medium text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300"
        >
          Sign up
        </router-link>
      </p>
    </div>
  </div>
</template>
