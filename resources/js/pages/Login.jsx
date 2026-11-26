import { useState } from 'react';
import { Navigate, useNavigate } from 'react-router-dom';
import { useAuth } from '../auth/AuthContext.jsx';
import { Alert, buttonClass, inputClass } from '../components/ui.jsx';

export default function Login() {
    const { user, login } = useAuth();
    const navigate = useNavigate();
    const [email, setEmail] = useState('');
    const [password, setPassword] = useState('');
    const [error, setError] = useState(null);
    const [loading, setLoading] = useState(false);

    if (user) {
        return <Navigate to="/dashboard" replace />;
    }

    const handleSubmit = async (event) => {
        event.preventDefault();
        setError(null);
        setLoading(true);
        try {
            await login(email, password);
            navigate('/dashboard', { replace: true });
        } catch (err) {
            setError(err);
        } finally {
            setLoading(false);
        }
    };

    return (
        <div className="flex min-h-screen items-center justify-center bg-gradient-to-br from-indigo-50 to-slate-100 p-4">
            <div className="w-full max-w-md rounded-2xl border border-slate-200 bg-white p-8 shadow-lg">
                <div className="mb-8 text-center">
                    <div className="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-xl bg-indigo-600 text-xl font-bold text-white">
                        S
                    </div>
                    <h1 className="text-2xl font-bold text-slate-900">SIGESU</h1>
                    <p className="mt-1 text-sm text-slate-500">Sistema de Gestión de Solicitudes Universitarias</p>
                </div>

                <Alert error={error} />

                <form onSubmit={handleSubmit} className="space-y-4">
                    <label className="block">
                        <span className="mb-1 block text-sm font-medium text-slate-700">Correo electrónico</span>
                        <input
                            type="email"
                            required
                            value={email}
                            onChange={(event) => setEmail(event.target.value)}
                            className={inputClass}
                            placeholder="usuario@sigesu.test"
                        />
                    </label>
                    <label className="block">
                        <span className="mb-1 block text-sm font-medium text-slate-700">Contraseña</span>
                        <input
                            type="password"
                            required
                            value={password}
                            onChange={(event) => setPassword(event.target.value)}
                            className={inputClass}
                            placeholder="••••••••"
                        />
                    </label>
                    <button type="submit" disabled={loading} className={`${buttonClass} w-full`}>
                        {loading ? 'Ingresando…' : 'Iniciar sesión'}
                    </button>
                </form>

                <div className="mt-8 rounded-lg bg-slate-50 p-4 text-xs text-slate-500">
                    <p className="mb-2 font-semibold text-slate-600">Cuentas de demostración (clave: password123)</p>
                    <ul className="space-y-1">
                        <li>admin@sigesu.test · estudiante@sigesu.test</li>
                        <li>funcionario@sigesu.test · supervisor@sigesu.test</li>
                    </ul>
                </div>
            </div>
        </div>
    );
}
