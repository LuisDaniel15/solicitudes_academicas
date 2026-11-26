import { Navigate, Outlet } from 'react-router-dom';
import { useAuth } from './AuthContext.jsx';

export default function ProtectedRoute({ roles }) {
    const { user, ready } = useAuth();

    if (!ready) {
        return (
            <div className="flex min-h-screen items-center justify-center text-slate-500">
                Cargando SIGESU…
            </div>
        );
    }

    if (!user) {
        return <Navigate to="/login" replace />;
    }

    if (roles && !roles.includes(user.role)) {
        return <Navigate to="/dashboard" replace />;
    }

    return <Outlet />;
}
