import { createApp } from 'vue'
import { createPinia } from 'pinia'
import { createI18n } from 'vue-i18n'
import App from './App.vue'
import router from './router'
import '../css/app.css'

// i18n configuration
const i18n = createI18n({
  legacy: false,
  locale: 'en',
  fallbackLocale: 'en',
  messages: {
    en: {
      welcome: 'Welcome to AutoERP',
      login: 'Login',
      register: 'Register',
      dashboard: 'Dashboard',
      products: 'Products',
      inventory: 'Inventory',
      customers: 'Customers',
      invoices: 'Invoices',
      analytics: 'Analytics'
    }
  }
})

const app = createApp(App)

app.use(createPinia())
app.use(router)
app.use(i18n)

app.mount('#app')
