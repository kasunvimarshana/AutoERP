<template>
  <div>
    <h2 class="text-2xl font-bold text-secondary-900 mb-6">{{ $t('auth.forgotPassword') }}</h2>
    
    <p class="text-secondary-600 mb-6">
      Enter your email address and we'll send you a link to reset your password.
    </p>
    
    <form @submit.prevent="handleSubmit" class="space-y-4">
      <BaseInput
        v-model="form.email"
        type="email"
        :label="$t('auth.email')"
        :placeholder="$t('auth.email')"
        :error="errors.email"
        required
      />
      
      <BaseButton
        type="submit"
        variant="primary"
        :loading="loading"
        class="w-full"
      >
        Send Reset Link
      </BaseButton>
      
      <p class="text-center text-sm text-secondary-600">
        Remember your password?
        <router-link :to="{ name: 'Login' }" class="text-primary-600 hover:text-primary-700 font-medium">
          {{ $t('auth.login') }}
        </router-link>
      </p>
    </form>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import authApi from '../api/authApi'
import BaseInput from '@/components/common/BaseInput.vue'
import BaseButton from '@/components/common/BaseButton.vue'

const form = ref({
  email: ''
})

const errors = ref({})
const loading = ref(false)

const handleSubmit = async () => {
  errors.value = {}
  loading.value = true
  
  try {
    await authApi.forgotPassword(form.value.email)
    alert('Password reset link sent to your email')
    form.value.email = ''
  } catch (error) {
    if (error.response?.data?.errors) {
      errors.value = error.response.data.errors
    } else {
      errors.value = { email: error.response?.data?.message || 'Failed to send reset link' }
    }
  } finally {
    loading.value = false
  }
}
</script>
