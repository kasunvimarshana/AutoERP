import './bootstrap';
import { createApp } from 'vue';
import { createRouter, createWebHistory } from 'vue-router';

// Initialize Vue application
const app = createApp({});

// Configure router (placeholder - modules will register their own routes)
const router = createRouter({
    history: createWebHistory(),
    routes: [
        {
            path: '/',
            name: 'home',
            component: () => import('./components/Home.vue')
        }
    ]
});

app.use(router);

// Mount the application
app.mount('#app');
