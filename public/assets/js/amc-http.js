/**
 * AMC shared HTTP helper.
 *
 * One implementation of the JSON fetch wrapper for all pages (apron map,
 * master table, dashboard) so CSRF handling and response cleanup never
 * drift between them. Load this BEFORE the page script.
 */
window.AMC = (function () {
    'use strict';

    // Strips BOMs / stray output before the JSON payload (legacy PHP notices,
    // whitespace) and unwraps accidentally quoted JSON.
    function normalizeJsonResponse(text) {
        if (typeof text !== 'string') {
            return '';
        }

        let cleaned = text.replace(/^\uFEFF/, '').trim();
        const firstBrace = cleaned.indexOf('{');
        const firstBracket = cleaned.indexOf('[');
        let firstJsonIndex = -1;

        if (firstBrace !== -1 && firstBracket !== -1) {
            firstJsonIndex = Math.min(firstBrace, firstBracket);
        } else if (firstBrace !== -1) {
            firstJsonIndex = firstBrace;
        } else if (firstBracket !== -1) {
            firstJsonIndex = firstBracket;
        }

        if (firstJsonIndex > 0) {
            cleaned = cleaned.slice(firstJsonIndex);
        }

        const firstChar = cleaned.charAt(0);
        const secondChar = cleaned.charAt(1);
        if ((firstChar === '"' || firstChar === "'") && (secondChar === '{' || secondChar === '[')) {
            cleaned = cleaned.slice(1);
            const lastChar = cleaned.charAt(cleaned.length - 1);
            if (lastChar === firstChar) {
                cleaned = cleaned.slice(0, -1);
            }
        }

        return cleaned.trim();
    }

    function csrfToken() {
        const configs = [window.apronConfig, window.masterTableConfig, window.dashboardConfig];
        for (const cfg of configs) {
            if (cfg && cfg.csrfToken) {
                return cfg.csrfToken;
            }
        }
        const field = document.querySelector('input[name="csrf_token"]');
        return field ? field.value : '';
    }

    function fetchJson(url, options = {}) {
        const fetchOptions = { credentials: 'same-origin', ...options };

        const method = (fetchOptions.method || 'GET').toUpperCase();
        const token = csrfToken();
        if (method !== 'GET' && token) {
            fetchOptions.headers = { 'X-CSRF-Token': token, ...(fetchOptions.headers || {}) };
        }

        return fetch(url, fetchOptions).then(async response => {
            const raw = await response.text();
            const cleaned = normalizeJsonResponse(raw);

            if (!response.ok) {
                let message = cleaned || response.statusText || `HTTP ${response.status}`;
                try {
                    const parsed = JSON.parse(cleaned);
                    if (parsed && parsed.message) {
                        message = parsed.message;
                    }
                } catch (e) { /* not JSON — keep raw message */ }
                const error = new Error(message);
                error.status = response.status;
                error.raw = raw;
                throw error;
            }

            if (!cleaned) {
                return {};
            }

            try {
                return JSON.parse(cleaned);
            } catch (parseError) {
                const error = new Error(`Invalid JSON response: ${parseError.message}`);
                error.raw = raw;
                error.status = response.status;
                throw error;
            }
        });
    }

    return { fetchJson, csrfToken, normalizeJsonResponse };
})();
