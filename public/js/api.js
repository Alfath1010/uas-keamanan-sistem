/**
 * Shared API client for the demo frontend. Wraps fetch() against the
 * existing REST API (api_spec.md Chapter 6) and manages the ALS session
 * lifecycle (crypto_design.md §4.2) for ALS-protected endpoints.
 *
 * Token storage: sessionStorage. This is an auth SESSION credential (like a
 * cookie), not private key material, so it doesn't fall under the
 * "downloadable file, re-upload each time" rule that applies to private keys.
 */

const API_BASE = '/api/v1';

const Api = {
    getToken() {
        return sessionStorage.getItem('auth_token');
    },

    setToken(token) {
        sessionStorage.setItem('auth_token', token);
    },

    clearToken() {
        sessionStorage.removeItem('auth_token');
        sessionStorage.removeItem('self_uuid');
        sessionStorage.removeItem('self_email');
    },

    getSelfUuid() {
        return sessionStorage.getItem('self_uuid');
    },

    setSelfUuid(uuid) {
        sessionStorage.setItem('self_uuid', uuid);
    },

    getSelfEmail() {
        return sessionStorage.getItem('self_email');
    },

    setSelfEmail(email) {
        sessionStorage.setItem('self_email', email);
    },

    isLoggedIn() {
        return !!this.getToken();
    },

    /** Plain (non-ALS) authenticated/unauthenticated JSON request. */
    async request(method, path, body = null) {
        const headers = { 'Content-Type': 'application/json', 'Accept': 'application/json' };
        const token = this.getToken();
        if (token) headers['Authorization'] = `Bearer ${token}`;

        const response = await fetch(API_BASE + path, {
            method,
            headers,
            body: body !== null ? JSON.stringify(body) : undefined,
        });

        const data = await response.json();
        if (!response.ok) {
            throw new ApiError(data.message || 'Permintaan gagal', data.error_code || null, response.status);
        }
        return data;
    },

    /**
     * Establishes a fresh ALS session (crypto_design.md §4.2.3). Re-run on
     * every page that needs ALS-protected endpoints — the session key lives
     * only in JS memory for this page's lifetime, never persisted.
     */
    async establishAlsSession() {
        await window.SecureMessaging.ECC.ready();
        const client = window.SecureMessaging.ECC.generateKeyPair();

        const response = await this.request('POST', '/als/handshake', {
            client_public_key: client.publicKey,
        });

        const sharedSecret = window.SecureMessaging.ECC.sharedSecret(
            client.privateKey, response.data.server_public_key,
        );

        const sessionKey = await window.SecureMessaging.ALS.deriveSessionKey(
            sharedSecret, client.publicKey, response.data.server_public_key,
        );

        return { sessionUuid: response.data.session_uuid, sessionKey };
    },

    /** POST/GET through an established ALS session — encrypts request (if any), decrypts response. */
    async alsRequest(method, path, body, session) {
        const headers = {
            'Accept': 'application/json',
            'Authorization': `Bearer ${this.getToken()}`,
            'X-ALS-Session': session.sessionUuid,
        };

        // Browsers forbid attaching a body to GET/HEAD requests (the Fetch
        // spec throws a TypeError) — and there is nothing meaningful to
        // encrypt for a GET anyway, since everything it needs is in the
        // URL. The server's DecryptAlsPayload middleware mirrors this: it
        // only requires/decrypts a body for methods other than GET/HEAD.
        const fetchOptions = { method, headers };
        if (method !== 'GET' && method !== 'HEAD') {
            const plaintext = JSON.stringify(body ?? {});
            const envelope = await window.SecureMessaging.ALS.encrypt(plaintext, session.sessionKey);
            headers['Content-Type'] = 'application/json';
            fetchOptions.body = JSON.stringify(envelope);
        }

        const response = await fetch(API_BASE + path, fetchOptions);

        const responseEnvelope = await response.json();
        const decrypted = await window.SecureMessaging.ALS.decrypt(responseEnvelope, session.sessionKey);
        const data = JSON.parse(decrypted);

        if (!response.ok) {
            throw new ApiError(data.message || 'Permintaan gagal', data.error_code || null, response.status);
        }
        return data;
    },
};

class ApiError extends Error {
    constructor(message, errorCode, statusCode) {
        super(message);
        this.errorCode = errorCode;
        this.statusCode = statusCode;
    }
}

window.Api = Api;
window.ApiError = ApiError;
