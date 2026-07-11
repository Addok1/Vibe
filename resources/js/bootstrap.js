/**
 * We'll load the axios HTTP library which allows us to easily issue requests
 * to our Laravel back-end. This library automatically handles sending the
 * CSRF token as a header based on the value of the "XSRF" token cookie.
 */

import * as bootstrap from 'bootstrap';
window.bootstrap = bootstrap

import axios from 'axios';
import { refreshCsrfToken, setMetaCsrfToken } from './csrf';

window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
window.axios.defaults.withCredentials = true;
window.axios.defaults.xsrfCookieName = 'XSRF-TOKEN';
window.axios.defaults.xsrfHeaderName = 'X-XSRF-TOKEN';

// Do NOT set X-CSRF-TOKEN from meta globally.
// Laravel prefers X-CSRF-TOKEN over X-XSRF-TOKEN; a stale meta tag causes 419.

window.axios.interceptors.request.use((config) => {
    if (config.headers) {
        delete config.headers['X-CSRF-TOKEN'];
        if (typeof config.headers.delete === 'function') {
            config.headers.delete('X-CSRF-TOKEN');
        }
    }
    return config;
});

window.axios.interceptors.response.use(
    (response) => response,
    async (error) => {
        const config = error.config;
        if (
            error.response?.status === 419 &&
            config &&
            !config.__csrfRetry &&
            !config.__skipCsrfRetry
        ) {
            config.__csrfRetry = true;
            try {
                const token = await refreshCsrfToken();
                if (token) {
                    setMetaCsrfToken(token);
                }
                if (config.headers) {
                    delete config.headers['X-CSRF-TOKEN'];
                }
                if (config.data && typeof config.data === 'object' && !(config.data instanceof FormData)) {
                    config.data = { ...config.data, _token: token };
                }
                return window.axios.request(config);
            } catch (_) {
                // fall through
            }
        }
        return Promise.reject(error);
    }
);

/**
 * Echo exposes an expressive API for subscribing to channels and listening
 * for events that are broadcast by Laravel. Echo and event broadcasting
 * allows your team to easily build robust real-time web applications.
 */

// import Echo from 'laravel-echo';

// import Pusher from 'pusher-js';
// window.Pusher = Pusher;

// window.Echo = new Echo({
//     broadcaster: 'pusher',
//     key: import.meta.env.VITE_PUSHER_APP_KEY,
//     cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER ?? 'mt1',
//     wsHost: import.meta.env.VITE_PUSHER_HOST ? import.meta.env.VITE_PUSHER_HOST : `ws-${import.meta.env.VITE_PUSHER_APP_CLUSTER}.pusher.com`,
//     wsPort: import.meta.env.VITE_PUSHER_PORT ?? 80,
//     wssPort: import.meta.env.VITE_PUSHER_PORT ?? 443,
//     forceTLS: (import.meta.env.VITE_PUSHER_SCHEME ?? 'https') === 'https',
//     enabledTransports: ['ws', 'wss'],
// });
