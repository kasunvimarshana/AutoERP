import './bootstrap';
import { createApp } from 'vue';
import { createPinia } from 'pinia';
import { createI18n } from 'vue-i18n';
import router from './router';
import App from './App.vue';

// Import global styles
import '../css/app.css';

// Create i18n instance
const i18n = createI18n({
    legacy: false,
    locale: 'en',
    fallbackLocale: 'en',
    messages: {
        en: {
            welcome: 'Welcome to AutoERP',
            dashboard: 'Dashboard',
            login: 'Login',
            logout: 'Logout',
        },
    },
});

// Create Pinia store
const pinia = createPinia();

// Create and mount Vue app
const app = createApp(App);

app.use(pinia);
app.use(i18n);
app.use(router);

app.mount('#app');
