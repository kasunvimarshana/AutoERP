import './assets/main.css'

import { createApp } from 'vue'
import { createPinia } from 'pinia'

import App from './App.vue'
import router from './router'
import { useAuthStore } from './stores/auth'
import { useUiStore } from './stores/ui'

const app = createApp(App)

const pinia = createPinia()
app.use(pinia)
app.use(router)

// Initialize stores
const authStore = useAuthStore()
const uiStore = useUiStore()

// Initialize auth from storage
authStore.initializeAuth()

// Initialize theme
uiStore.initializeTheme()

app.mount('#app')
