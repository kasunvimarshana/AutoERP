<template>
  <div>
    <h2 class="text-2xl font-bold text-secondary-900 mb-6">{{ $t('auth.login') }}</h2>
    
    <form @submit.prevent="handleLogin" class="space-y-4">
      <BaseInput
        v-model="form.email"
        type="email"
        :label="$t('auth.email')"
        :placeholder="$t('auth.email')"
        :error="errors.email"
        required
      />
      
      <BaseInput
        v-model="form.password"
        type="password"
        :label="$t('auth.password')"
        :placeholder="$t('auth.password')"
        :error="errors.password"
        required
      />
      
      <div class="flex items-center justify-between">
        <label class="flex items-center">
          <input type="checkbox" v-model="form.remember" class="rounded border-secondary-300" />
          <span class="ml-2 text-sm text-secondary-600">Remember me</span>
        </label>
        <router-link :to="{ name: 'ForgotPassword' }" class="text-sm text-primary-600 hover:text-primary-700">
          {{ $t('auth.forgotPassword') }}
        </router-link>
      </div>
      
      <BaseButton
        type="submit"
        variant="primary"
        :loading="loading"
        class="w-full"
      >
        {{ $t('auth.login') }}
      </BaseButton>
      
      <p class="text-center text-sm text-secondary-600">
        Don't have an account?
        <router-link :to="{ name: 'Register' }" class="text-primary-600 hover:text-primary-700 font-medium">
          {{ $t('auth.register') }}
        </router-link>
      </p>
    </form>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../store/authStore'
import BaseInput from '@/components/common/BaseInput.vue'
import BaseButton from '@/components/common/BaseButton.vue'

const router = useRouter()
const authStore = useAuthStore()

const form = ref({
  email: '',
  password: '',
  remember: false
})

const errors = ref({})
const loading = ref(false)

const handleLogin = async () => {
  errors.value = {}
  loading.value = true
  
  try {
    await authStore.login({
      email: form.value.email,
      password: form.value.password
    })
    router.push({ name: 'Dashboard' })
  } catch (error) {
    if (error.response?.data?.errors) {
      errors.value = error.response.data.errors
    } else {
      errors.value = { email: error.response?.data?.message || 'Login failed' }
    }
  } finally {
    loading.value = false
  }
}
</script>
