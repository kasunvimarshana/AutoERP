<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
      <div>
        <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
          {{ $t('auth.createAccount') }}
        </h2>
        <p class="mt-2 text-center text-sm text-gray-600">
          Create a new account
        </p>
      </div>
      
      <form class="mt-8 space-y-6" @submit.prevent="handleRegister">
        <div v-if="authStore.error" class="rounded-md bg-red-50 p-4">
          <p class="text-sm text-red-800">{{ authStore.error }}</p>
        </div>
        
        <div class="space-y-4">
          <BaseInput
            v-model="form.name"
            label="Full Name"
            type="text"
            required
          />
          
          <BaseInput
            v-model="form.email"
            :label="$t('auth.email')"
            type="email"
            required
          />
          
          <BaseInput
            v-model="form.password"
            :label="$t('auth.password')"
            type="password"
            required
          />
          
          <BaseInput
            v-model="form.password_confirmation"
            :label="$t('auth.confirmPassword')"
            type="password"
            required
          />
        </div>

        <div>
          <button
            type="submit"
            :disabled="authStore.loading"
            class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            <span v-if="authStore.loading">Loading...</span>
            <span v-else>{{ $t('auth.signUp') }}</span>
          </button>
        </div>
        
        <div class="text-center">
          <router-link to="/login" class="text-sm text-blue-600 hover:text-blue-500">
            Already have an account? {{ $t('auth.signIn') }}
          </router-link>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { reactive } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@stores/auth'
import BaseInput from '@components/common/BaseInput.vue'

const router = useRouter()
const authStore = useAuthStore()

const form = reactive({
  name: '',
  email: '',
  password: '',
  password_confirmation: ''
})

const handleRegister = async () => {
  try {
    await authStore.register(form)
    router.push('/')
  } catch (error) {
    console.error('Registration error:', error)
  }
}
</script>
