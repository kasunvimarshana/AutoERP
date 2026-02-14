<template>
  <div>
    <h2 class="text-2xl font-bold text-secondary-900 mb-6">{{ $t('auth.register') }}</h2>
    
    <form @submit.prevent="handleRegister" class="space-y-4">
      <BaseInput
        v-model="form.name"
        type="text"
        :label="$t('user.name')"
        :placeholder="$t('user.name')"
        :error="errors.name"
        required
      />
      
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
      
      <BaseInput
        v-model="form.confirmPassword"
        type="password"
        :label="$t('auth.confirmPassword')"
        :placeholder="$t('auth.confirmPassword')"
        :error="errors.confirmPassword"
        required
      />
      
      <BaseButton
        type="submit"
        variant="primary"
        :loading="loading"
        class="w-full"
      >
        {{ $t('auth.register') }}
      </BaseButton>
      
      <p class="text-center text-sm text-secondary-600">
        Already have an account?
        <router-link :to="{ name: 'Login' }" class="text-primary-600 hover:text-primary-700 font-medium">
          {{ $t('auth.login') }}
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
  name: '',
  email: '',
  password: '',
  confirmPassword: ''
})

const errors = ref({})
const loading = ref(false)

const handleRegister = async () => {
  errors.value = {}
  
  if (form.value.password !== form.value.confirmPassword) {
    errors.value.confirmPassword = 'Passwords do not match'
    return
  }
  
  loading.value = true
  
  try {
    await authStore.register({
      name: form.value.name,
      email: form.value.email,
      password: form.value.password
    })
    router.push({ name: 'Dashboard' })
  } catch (error) {
    if (error.response?.data?.errors) {
      errors.value = error.response.data.errors
    } else {
      errors.value = { email: error.response?.data?.message || 'Registration failed' }
    }
  } finally {
    loading.value = false
  }
}
</script>
