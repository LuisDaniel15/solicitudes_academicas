import { createContext, useContext, useEffect, useMemo, useState } from 'react';
import { api, clearToken, getToken, setToken } from '../api/client.js';

const AuthContext = createContext(null);

export function AuthProvider({ children }) {
    const [user, setUser] = useState(null);
    const [ready, setReady] = useState(false);

    useEffect(() => {
        if (!getToken()) {
            setReady(true);
            return;
        }

        api('/me')
            .then((payload) => setUser(payload.user))
            .catch(() => clearToken())
            .finally(() => setReady(true));
    }, []);

    const login = async (email, password) => {
        const payload = await api('/login', { method: 'POST', body: { email, password } });
        setToken(payload.token);
        setUser(payload.user);
    };

    const logout = async () => {
        try {
            await api('/logout', { method: 'POST' });
        } finally {
            clearToken();
            setUser(null);
        }
    };

    const value = useMemo(() => ({ user, ready, login, logout }), [user, ready]);

    return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}

export function useAuth() {
    return useContext(AuthContext);
}
