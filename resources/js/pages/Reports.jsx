import { useEffect, useState } from 'react';
import { api } from '../api/client.js';
import { Alert, Card, STATUS_LABELS, StatCard } from '../components/ui.jsx';

export default function Reports() {
    const [summary, setSummary] = useState(null);
    const [error, setError] = useState(null);

    useEffect(() => {
        api('/reports/summary').then(setSummary).catch((err) => setError(err));
    }, []);

    if (!summary) {
        return <Alert error={error} />;
    }

    return (
        <div className="space-y-6">
            <div>
                <h1 className="text-2xl font-bold text-slate-900">Reportes</h1>
                <p className="mt-1 text-sm text-slate-500">Estadísticas básicas de las solicitudes</p>
            </div>

            <div className="grid gap-4 sm:grid-cols-3">
                <StatCard label="Total de solicitudes" value={summary.total} />
                <StatCard label="Estados registrados" value={Object.keys(summary.by_status).length} tone="bg-sky-500" />
                <StatCard label="Categorías" value={summary.by_category.length} tone="bg-emerald-500" />
            </div>

            <div className="grid gap-6 md:grid-cols-2">
                <Card title="Solicitudes por estado">
                    <table className="w-full text-sm">
                        <thead className="text-left text-xs uppercase text-slate-400">
                            <tr><th className="pb-2">Estado</th><th className="pb-2 text-right">Cantidad</th></tr>
                        </thead>
                        <tbody className="divide-y divide-slate-100">
                            {Object.entries(summary.by_status).map(([status, total]) => (
                                <tr key={status}>
                                    <td className="py-2">{STATUS_LABELS[status]}</td>
                                    <td className="py-2 text-right font-semibold">{total}</td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </Card>

                <Card title="Solicitudes por categoría">
                    <table className="w-full text-sm">
                        <thead className="text-left text-xs uppercase text-slate-400">
                            <tr><th className="pb-2">Categoría</th><th className="pb-2 text-right">Cantidad</th></tr>
                        </thead>
                        <tbody className="divide-y divide-slate-100">
                            {summary.by_category.map((category) => (
                                <tr key={category.id}>
                                    <td className="py-2">{category.name}</td>
                                    <td className="py-2 text-right font-semibold">{category.total}</td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </Card>
            </div>
        </div>
    );
}
