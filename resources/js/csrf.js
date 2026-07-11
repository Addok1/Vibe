/**
 * CSRF helpers — prefer Laravel XSRF-TOKEN cookie over meta tag.
 * Stale meta X-CSRF-TOKEN caused 419 "Session expired" on login.
 */
import axios from 'axios';

let refreshPromise = null;

export function readCookie(name) {
    const escaped = name.replace(/([.$?*|{}()[\]/+^])/g, '\\$1');
    const match = document.cookie.match(new RegExp(`(?:^|; )${escaped}=([^;]*)`));
    return match ? decodeURIComponent(match[1]) : null;
}

export function getMetaCsrfToken() {
    return document.head.querySelector('meta[name="csrf-token"]')?.content || null;
}

export function setMetaCsrfToken(token) {
    if (!token) return;
    let meta = document.head.querySelector('meta[name="csrf-token"]');
    if (!meta) {
        meta = document.createElement('meta');
        meta.setAttribute('name', 'csrf-token');
        document.head.appendChild(meta);
    }
    meta.setAttribute('content', token);
}

/**
 * Refresh session CSRF via lightweight endpoint (also renews XSRF-TOKEN cookie).
 */
export async function refreshCsrfToken() {
    if (!refreshPromise) {
        refreshPromise = axios
            .get('/csrf-token', {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                withCredentials: true,
                __skipCsrfRetry: true,
            })
            .then(({ data }) => {
                if (data?.token) {
                    setMetaCsrfToken(data.token);
                }
                return data?.token || getMetaCsrfToken();
            })
            .finally(() => {
                refreshPromise = null;
            });
    }
    return refreshPromise;
}

/**
 * POST with credentials; on 419 refresh CSRF once and retry.
 */
export async function csrfPost(url, payload = {}, config = {}) {
    await refreshCsrfToken();

    const token = getMetaCsrfToken();
    const body = token ? { ...payload, _token: token } : { ...payload };
    const headers = {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        ...(config.headers || {}),
    };
    // Never send stale meta as X-CSRF-TOKEN — cookie X-XSRF-TOKEN is authoritative
    delete headers['X-CSRF-TOKEN'];

    try {
        return await axios.post(url, body, {
            ...config,
            headers,
            withCredentials: true,
        });
    } catch (error) {
        if (error.response?.status === 419 && !config.__csrfRetried) {
            await refreshCsrfToken();
            const fresh = getMetaCsrfToken();
            return axios.post(
                url,
                fresh ? { ...payload, _token: fresh } : { ...payload },
                {
                    ...config,
                    headers,
                    withCredentials: true,
                    __csrfRetried: true,
                }
            );
        }
        throw error;
    }
}
