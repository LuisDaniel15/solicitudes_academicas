import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { api } from '../api/client.js';
import { useAuth } from '../auth/AuthContext.jsx';
import { STATUS_LABELS, StatCard, buttonClass, secondaryButtonClass } from '../components/ui.jsx';

export default function Dashboard() {
    const { user } = useAuth();
    const [summary, setSummary] = useState(null);
    const canSeeReports = ['admin', 'supervisor'].includes(user.role);

    useEffect(() => {
        const endpoint = canSeeReports ? '/reports/summary' : '/requests';
        api(endpoint)
            .then((payload) => {
                if (canSeeReports) {
                    setSummary(payload);
                } else {
                    const rows = payload.data || [];
                    setSummary({ total: rows.length, by_status: {}, by_category: [] });
                }
            })
            .catch(() => {});
    }, [canSeeReports]);

    const statusEntries = Object.entries(summary?.by_status || {}).filter(([, value]) => value > 0);

    return (
        <div className="space-y-6">
            <div>
                <h1 className="text-2xl font-bold text-slate-900">Bienvenido, {user.name}</h1>
                <p className="mt-1 text-sm text-slate-500">Resumen general del sistema SIGESU</p>
            </div>

            <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <StatCard label="Total de solicitudes" value={summary?.total ?? '—'} />
                {statusEntries.slice(0, 3).map(([status, value]) => (
                    <StatCard key={status} label={STATUS_LABELS[status]} value={value} tone="bg-emerald-500" />
                ))}
            </div>

            <div className="grid gap-4 md:grid-cols-2">
                <section className="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 className="mb-3 text-lg font-semibold">Estado de las solicitudes</h2>
                    {statusEntries.length === 0 ? (
                        <p className="text-sm text-slate-400">Aún no hay solicitudes registradas.</p>
                    ) : (
                        <ul className="space-y-2 text-sm">
                            {statusEntries.map(([status, value]) => (
                                <li key={status} className="flex justify-between border-b border-slate-100 py-1">
                                    <span>{STATUS_LABELS[status]}</span>
                                    <span className="font-semibold">{value}</span>
                                </li>
                            ))}
                        </ul>
                    )}
                </section>
                <section className="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 className="mb-3 text-lg font-semibold">Accesos rápidos</h2>
                    <div className="flex flex-wrap gap-3">
                        {user.role === 'estudiante' && (
                            <Link to="/requests/new" className={buttonClass}>Nueva solicitud</Link>
                        )}
                        <Link to="/requests" className={secondaryButtonClass}>Ver solicitudes</Link>
                        {canSeeReports && (
                            <Link to="/reports" className={secondaryButtonClass}>Ver reportes</Link>
                        )}
                    </div>
                </section>
            </div>
        </div>
    );
}
