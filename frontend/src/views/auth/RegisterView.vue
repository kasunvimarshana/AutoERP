<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuth } from '@/composables/useAuth'
import { useNotification } from '@/composables/useNotification'
import BaseInput from '@/components/ui/BaseInput.vue'
import BaseButton from '@/components/ui/BaseButton.vue'

const router = useRouter()
const { register } = useAuth()
const notification = useNotification()

const form = ref({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
  phone: '',
  tenant_name: '',
})

const errors = ref<Record<string, string>>({})
const isLoading = ref(false)

const validateForm = () => {
  errors.value = {}

  if (!form.value.name) {
    errors.value.name = 'Name is required'
  }

  if (!form.value.email) {
    errors.value.email = 'Email is required'
  } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.value.email)) {
    errors.value.email = 'Please enter a valid email'
  }

  if (!form.value.password) {
    errors.value.password = 'Password is required'
  } else if (form.value.password.length < 8) {
    errors.value.password = 'Password must be at least 8 characters'
  }

  if (form.value.password !== form.value.password_confirmation) {
    errors.value.password_confirmation = 'Passwords do not match'
  }

  if (!form.value.tenant_name) {
    errors.value.tenant_name = 'Business name is required'
  }

  return Object.keys(errors.value).length === 0
}

const handleSubmit = async () => {
  if (!validateForm()) {
    return
  }

  isLoading.value = true

  try {
    await register(form.value)

    notification.success('Registration successful!', 'Welcome')
    router.push('/dashboard')
  } catch (error: any) {
    notification.error(error.message || 'Registration failed', 'Error')
    
    if (error.errors) {
      errors.value = error.errors
    }
  } finally {
    isLoading.value = false
  }
}
</script>

<template>
  <div class="register-view">
    <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">Create Account</h2>

    <form @submit.prevent="handleSubmit" class="space-y-4">
      <BaseInput
        id="name"
        v-model="form.name"
        type="text"
        label="Full Name"
        placeholder="Enter your full name"
        :error="errors.name"
        :disabled="isLoading"
        required
      />

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
        id="phone"
        v-model="form.phone"
        type="tel"
        label="Phone Number"
        placeholder="Enter your phone number"
        :error="errors.phone"
        :disabled="isLoading"
      />

      <BaseInput
        id="tenant_name"
        v-model="form.tenant_name"
        type="text"
        label="Business Name"
        placeholder="Enter your business name"
        :error="errors.tenant_name"
        :disabled="isLoading"
        required
      />

      <BaseInput
        id="password"
        v-model="form.password"
        type="password"
        label="Password"
        placeholder="Create a password"
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
        placeholder="Confirm your password"
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
        Create Account
      </BaseButton>
    </form>

    <div class="mt-6 text-center">
      <p class="text-sm text-gray-600 dark:text-gray-400">
        Already have an account?
        <router-link
          to="/login"
          class="font-medium text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300"
        >
          Sign in
        </router-link>
      </p>
    </div>
  </div>
</template>
