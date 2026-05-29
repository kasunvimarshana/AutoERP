import axios from 'axios';
import { getBootstrap } from '../../config/bootstrap';

const bootstrap = getBootstrap();

export const httpClient = axios.create({
    baseURL: bootstrap.apiBaseUrl,
    withCredentials: true,
    headers: {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
    },
});

httpClient.interceptors.request.use((config) => {
    const token = document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content;

    if (token) {
        config.headers['X-CSRF-TOKEN'] = token;
    }

    return config;
});
