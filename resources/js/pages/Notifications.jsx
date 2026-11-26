import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { api } from '../api/client.js';
import { Alert, Card, buttonClass, secondaryButtonClass } from '../components/ui.jsx';

const formatDate = (value) => value ? new Date(value).toLocaleString('es') : '';

export default function Notifications() {
    const [rows, setRows] = useState([]);
    const [error, setError] = useState(null);

    const load = () => api('/notifications').then((payload) => setRows(payload.data || [])).catch((err) => setError(err));
    useEffect(() => { load(); }, []);

    const markRead = async (notification) => {
        setError(null);
        try {
            await api(`/notifications/${notification.id}/read`, { method: 'PATCH' });
            load();
        } catch (err) {
            setError(err);
        }
    };

    const markAll = async () => {
        setError(null);
        try {
            await api('/notifications/read-all', { method: 'PATCH' });
            load();
        } catch (err) {
            setError(err);
        }
    };

    return (
        <div className="space-y-6">
            <div className="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h1 className="text-2xl font-bold text-slate-900">Notificaciones</h1>
                    <p className="mt-1 text-sm text-slate-500">Avisos internos sobre sus solicitudes</p>
                </div>
                {rows.some((item) => !item.read_at) && (
                    <button type="button" onClick={markAll} className={secondaryButtonClass}>Marcar todas como leídas</button>
                )}
            </div>

            <Alert error={error} />

            <Card>
                {rows.length === 0
                    ? <p className="text-sm text-slate-400">No tiene notificaciones.</p>
                    : (
                        <ul className="divide-y divide-slate-100">
                            {rows.map((item) => (
                                <li key={item.id} className={`flex flex-wrap items-center justify-between gap-3 py-3 ${item.read_at ? '' : 'bg-indigo-50/40'}`}>
                                    <div>
                                        <p className="font-medium text-slate-800">{item.title}</p>
                                        <p className="text-sm text-slate-600">{item.body}</p>
                                        <p className="mt-1 text-xs text-slate-400">{formatDate(item.created_at)}</p>
                                    </div>
                                    <div className="flex items-center gap-3">
                                        {item.request_id && (
                                            <Link to={`/requests/${item.request_id}`} className="text-sm text-indigo-600 hover:underline">Ver solicitud</Link>
                                        )}
                                        {!item.read_at && (
                                            <button type="button" onClick={() => markRead(item)} className={buttonClass}>Marcar leída</button>
                                        )}
                                    </div>
                                </li>
                            ))}
                        </ul>
                    )}
            </Card>
        </div>
    );
}
