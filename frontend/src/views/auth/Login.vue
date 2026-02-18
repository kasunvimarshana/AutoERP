<template>
  <div class="login-container">
    <div class="login-card">
      <div class="logo-section">
        <h1 class="logo-text">AutoERP</h1>
        <p class="subtitle">Enterprise Resource Planning System</p>
      </div>
      
      <form @submit.prevent="handleLogin" class="login-form">
        <div v-if="errorMessage" class="error-alert">
          <ExclamationCircleIcon class="icon" />
          {{ errorMessage }}
        </div>
        
        <FormInput
          v-model="credentials.email"
          label="Email"
          type="email"
          placeholder="Enter your email"
          required
          :error="errors.email"
        />
        
        <FormInput
          v-model="credentials.password"
          label="Password"
          type="password"
          placeholder="Enter your password"
          required
          :error="errors.password"
        />
        
        <FormInput
          v-model="credentials.tenantSlug"
          label="Tenant"
          placeholder="demo-company"
          :error="errors.tenantSlug"
        />
        
        <button type="submit" class="btn-login" :disabled="loading">
          <span v-if="loading" class="spinner"></span>
          <span v-else>Sign In</span>
        </button>
      </form>
      
      <div class="footer-text">
        <p>Demo Credentials:</p>
        <p class="text-muted">Email: admin@demo.com | Password: password</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { ExclamationCircleIcon } from '@heroicons/vue/24/outline'
import FormInput from '../../components/forms/FormInput.vue'
import { useAuthStore } from '../../stores/authStore'

const router = useRouter()
const route = useRoute()
const authStore = useAuthStore()

const credentials = ref({
  email: '',
  password: '',
  tenantSlug: 'demo-company'
})

const errors = ref({})
const errorMessage = ref('')
const loading = ref(false)

const handleLogin = async () => {
  errorMessage.value = ''
  errors.value = {}
  loading.value = true
  
  try {
    const result = await authStore.login(
      credentials.value.email,
      credentials.value.password,
      credentials.value.tenantSlug
    )
    
    if (result.success) {
      const redirect = route.query.redirect || '/dashboard'
      router.push(redirect)
    } else {
      errorMessage.value = result.message || 'Login failed. Please check your credentials.'
    }
  } catch (error) {
    errorMessage.value = error.response?.data?.message || 'An error occurred during login.'
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
.login-container {
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 100vh;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  padding: 20px;
}

.login-card {
  width: 100%;
  max-width: 450px;
  background: white;
  border-radius: 16px;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
  padding: 40px;
}

.logo-section {
  text-align: center;
  margin-bottom: 32px;
}

.logo-text {
  font-size: 32px;
  font-weight: 700;
  color: #667eea;
  margin-bottom: 8px;
}

.subtitle {
  font-size: 14px;
  color: #6b7280;
}

.login-form {
  margin-bottom: 24px;
}

.error-alert {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 16px;
  background: #fee2e2;
  border: 1px solid #fecaca;
  border-radius: 8px;
  color: #991b1b;
  font-size: 14px;
  margin-bottom: 20px;
}

.error-alert .icon {
  width: 20px;
  height: 20px;
  flex-shrink: 0;
}

.btn-login {
  width: 100%;
  padding: 12px 24px;
  background: #667eea;
  color: white;
  border: none;
  border-radius: 8px;
  font-size: 16px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
  margin-top: 8px;
}

.btn-login:hover:not(:disabled) {
  background: #5568d3;
  transform: translateY(-1px);
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

.btn-login:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.spinner {
  display: inline-block;
  width: 20px;
  height: 20px;
  border: 3px solid rgba(255, 255, 255, 0.3);
  border-radius: 50%;
  border-top-color: white;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.footer-text {
  text-align: center;
  padding-top: 20px;
  border-top: 1px solid #e5e7eb;
}

.footer-text p {
  font-size: 13px;
  color: #6b7280;
  margin: 4px 0;
}

.text-muted {
  color: #9ca3af !important;
}
</style>
