import { useEffect, useState } from 'react';
import { Link, NavLink, Outlet, useNavigate } from 'react-router-dom';
import { api } from '../api/client.js';
import { useAuth } from '../auth/AuthContext.jsx';
import { ROLE_LABELS, secondaryButtonClass } from '../components/ui.jsx';

const NAVIGATION = {
    admin: [
        ['/dashboard', 'Panel general'],
        ['/requests', 'Solicitudes'],
        ['/users', 'Usuarios'],
        ['/categories', 'Categorías'],
        ['/reports', 'Reportes'],
        ['/notifications', 'Notificaciones'],
    ],
    estudiante: [
        ['/dashboard', 'Mi panel'],
        ['/requests/new', 'Nueva solicitud'],
        ['/requests', 'Mis solicitudes'],
        ['/notifications', 'Notificaciones'],
    ],
    funcionario: [
        ['/dashboard', 'Mi panel'],
        ['/requests', 'Solicitudes asignadas'],
        ['/notifications', 'Notificaciones'],
    ],
    supervisor: [
        ['/dashboard', 'Panel general'],
        ['/requests', 'Solicitudes'],
        ['/reports', 'Reportes'],
        ['/notifications', 'Notificaciones'],
    ],
};

export default function AppLayout() {
    const { user, logout } = useAuth();
    const navigate = useNavigate();
    const [open, setOpen] = useState(false);
    const [unread, setUnread] = useState(0);

    useEffect(() => {
        api('/notifications')
            .then((payload) => setUnread((payload.data || []).filter((item) => !item.read_at).length))
            .catch(() => {});
    }, []);

    const handleLogout = async () => {
        await logout();
        navigate('/login', { replace: true });
    };

    const links = NAVIGATION[user.role] || [];

    return (
        <div className="min-h-screen lg:flex">
            <aside className={`${open ? 'block' : 'hidden'} w-64 shrink-0 border-r border-slate-200 bg-white p-5 lg:block`}>
                <Link to="/dashboard" className="mb-8 flex items-center gap-2 text-xl font-bold text-indigo-600">
                    <span className="flex h-9 w-9 items-center justify-center rounded-lg bg-indigo-600 text-white">S</span>
                    SIGESU
                </Link>
                <nav className="space-y-1">
                    {links.map(([to, label]) => (
                        <NavLink
                            key={to}
                            to={to}
                            onClick={() => setOpen(false)}
                            className={({ isActive }) =>
                                `block rounded-lg px-3 py-2 text-sm font-medium ${isActive
                                    ? 'bg-indigo-50 text-indigo-700'
                                    : 'text-slate-600 hover:bg-slate-50'}`
                            }
                        >
                            {label}
                        </NavLink>
                    ))}
                </nav>
                <div className="mt-8 border-t border-slate-100 pt-4">
                    <p className="text-sm font-semibold text-slate-800">{user.name}</p>
                    <p className="mb-3 text-xs text-slate-500">{ROLE_LABELS[user.role]}</p>
                    <button type="button" onClick={handleLogout} className={`${secondaryButtonClass} w-full`}>
                        Cerrar sesión
                    </button>
                </div>
            </aside>

            <div className="min-w-0 flex-1">
                <header className="flex items-center justify-between border-b border-slate-200 bg-white px-5 py-4">
                    <button
                        type="button"
                        onClick={() => setOpen(!open)}
                        className="rounded-lg border border-slate-200 px-3 py-1.5 text-sm lg:hidden"
                    >
                        Menú
                    </button>
                    <div className="ml-auto flex items-center gap-4">
                        <Link to="/notifications" className="relative text-sm text-slate-600 hover:text-indigo-600">
                            🔔
                            {unread > 0 && (
                                <span className="absolute -right-2 -top-2 rounded-full bg-rose-500 px-1.5 text-[10px] text-white">
                                    {unread}
                                </span>
                            )}
                        </Link>
                        <span className="text-sm text-slate-500">{ROLE_LABELS[user.role]}</span>
                    </div>
                </header>
                <main className="mx-auto max-w-6xl p-5 lg:p-8">
                    <Outlet />
                </main>
            </div>
        </div>
    );
}
