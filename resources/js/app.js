import './bootstrap';

// Import AdminLTE and Bootstrap CSS
import 'admin-lte/dist/css/adminlte.min.css';
import 'bootstrap/dist/css/bootstrap.min.css';

// Import Font Awesome
import '@fortawesome/fontawesome-free/css/all.min.css';

// Import AdminLTE JS
import 'bootstrap/dist/js/bootstrap.bundle.min.js';
import 'admin-lte/dist/js/adminlte.min.js';

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
