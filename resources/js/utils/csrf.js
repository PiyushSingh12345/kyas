let cachedToken = '';

export function readCookie(name) {
    const match = document.cookie.match(new RegExp('(^|;\\s*)' + name + '=([^;]*)'));
    return match ? decodeURIComponent(match[2]) : '';
}

export function getCsrfToken() {
    const meta = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    if (meta) {
        cachedToken = meta;
        return meta;
    }

    if (cachedToken) {
        return cachedToken;
    }

    const xsrf = readCookie('XSRF-TOKEN');
    if (xsrf) {
        return xsrf;
    }

    return '';
}

export function syncCsrfToken(token) {
    if (!token || typeof token !== 'string') {
        return;
    }

    cachedToken = token;

    let meta = document.querySelector('meta[name="csrf-token"]');
    if (!meta) {
        meta = document.createElement('meta');
        meta.name = 'csrf-token';
        document.head.appendChild(meta);
    }

    meta.setAttribute('content', token);

    if (window.axios?.defaults?.headers?.common) {
        window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token;
    }
}

export function getCsrfHeaders(existingHeaders = {}) {
    const headers = new Headers(existingHeaders);

    if (!headers.has('Accept')) {
        headers.set('Accept', 'application/json');
    }

    if (!headers.has('X-Requested-With')) {
        headers.set('X-Requested-With', 'XMLHttpRequest');
    }

    if (!headers.has('X-CSRF-TOKEN') && !headers.has('X-XSRF-TOKEN')) {
        const xsrf = readCookie('XSRF-TOKEN');
        const token = getCsrfToken();

        if (xsrf) {
            headers.set('X-XSRF-TOKEN', xsrf);
        } else if (token) {
            headers.set('X-CSRF-TOKEN', token);
        }
    }

    return headers;
}

function isSameOriginRequest(url) {
    if (url.startsWith('/')) {
        return true;
    }

    try {
        const parsed = new URL(url, window.location.origin);
        return parsed.origin === window.location.origin;
    } catch {
        return false;
    }
}

export function installFetchCsrfInterceptor() {
    if (window.__kyasCsrfFetchInstalled) {
        return;
    }

    window.__kyasCsrfFetchInstalled = true;

    const nativeFetch = window.fetch.bind(window);

    window.fetch = (input, init = {}) => {
        const request = input instanceof Request ? input : null;
        const url = request ? request.url : String(input);
        const method = (init.method ?? request?.method ?? 'GET').toUpperCase();
        const isMutation = !['GET', 'HEAD', 'OPTIONS'].includes(method);

        if (!isMutation || !isSameOriginRequest(url)) {
            return nativeFetch(input, init);
        }

        const nextInit = {
            ...init,
            credentials: init.credentials ?? 'same-origin',
            headers: getCsrfHeaders(init.headers ?? request?.headers),
        };

        return nativeFetch(input, nextInit);
    };
}
