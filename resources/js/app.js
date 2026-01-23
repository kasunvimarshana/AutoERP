import './bootstrap';
import { createApp } from 'vue';
import { createPinia } from 'pinia';
import router from './router';
import i18n from './i18n';
import App from './App.vue';

// Create Pinia store
const pinia = createPinia();

// Create and mount Vue app
const app = createApp(App);

app.use(pinia);
app.use(router);
app.use(i18n);

app.mount('#app');
