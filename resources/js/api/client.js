const TOKEN_KEY = 'sigesu_token';

export const getToken = () => localStorage.getItem(TOKEN_KEY);
export const setToken = (token) => localStorage.setItem(TOKEN_KEY, token);
export const clearToken = () => localStorage.removeItem(TOKEN_KEY);

export async function api(path, { method = 'GET', body, params } = {}) {
    const headers = { Accept: 'application/json' };

    if (body !== undefined) {
        headers['Content-Type'] = 'application/json';
    }

    const token = getToken();
    if (token) {
        headers.Authorization = `Bearer ${token}`;
    }

    let url = `/api${path}`;
    if (params) {
        const query = new URLSearchParams(
            Object.entries(params).filter(([, value]) => value !== '' && value !== null && value !== undefined),
        );
        if (query.toString()) {
            url += `?${query}`;
        }
    }

    const response = await fetch(url, {
        method,
        headers,
        body: body !== undefined ? JSON.stringify(body) : undefined,
    });

    let payload = null;
    try {
        payload = await response.json();
    } catch {
        payload = null;
    }

    if (!response.ok) {
        const error = new Error(payload?.message || `Error ${response.status}`);
        error.status = response.status;
        error.errors = payload?.errors || {};
        throw error;
    }

    return payload;
}

export const unwrap = (promise) => promise.then((payload) => payload.data ?? payload);
