import { useCallback, useEffect, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import { api } from '../api/client.js';
import { useAuth } from '../auth/AuthContext.jsx';
import {
    Alert, Card, Field, PriorityBadge, STATUS_LABELS, StatusBadge,
    buttonClass, inputClass, secondaryButtonClass,
} from '../components/ui.jsx';

const formatDate = (value) => value ? new Date(value).toLocaleString('es') : '—';

export default function RequestDetail() {
    const { id } = useParams();
    const { user } = useAuth();
    const [request, setRequest] = useState(null);
    const [officers, setOfficers] = useState([]);
    const [officerId, setOfficerId] = useState('');
    const [note, setNote] = useState('');
    const [observation, setObservation] = useState('');
    const [error, setError] = useState(null);
    const [busy, setBusy] = useState(false);

    const load = useCallback(() => api(`/requests/${id}`)
        .then((payload) => setRequest(payload.data))
        .catch((err) => setError(err)), [id]);

    useEffect(() => { load(); }, [load]);
    useEffect(() => {
        if (['admin', 'supervisor'].includes(user.role)) {
            api('/officers').then((payload) => setOfficers(payload.data || [])).catch(() => {});
        }
    }, [user.role]);

    if (error && !request) {
        return <Alert error={error} />;
    }

    if (!request) {
        return <p className="text-slate-500">Cargando solicitud…</p>;
    }

    const run = async (action) => {
        setError(null);
        setBusy(true);
        try {
            await action();
            setNote('');
            setObservation('');
            await load();
        } catch (err) {
            setError(err);
        } finally {
            setBusy(false);
        }
    };

    const changeStatus = (status, requiresNote = false) => run(async () => {
        if (requiresNote && !note.trim()) {
            throw new Error('Debe registrar una observación o justificación.');
        }
        await api(`/requests/${id}/status`, { method: 'PATCH', body: { status, note } });
    });

    const assign = () => run(async () => {
        await api(`/requests/${id}/assignments`, {
            method: 'POST',
            body: { assigned_to: Number(officerId) },
        });
    });

    const addObservation = () => run(async () => {
        await api(`/requests/${id}/observations`, { method: 'POST', body: { body: observation } });
    });

    const isOfficer = user.role === 'funcionario';
    const isSupervisor = user.role === 'supervisor';
    const canAssign = ['admin', 'supervisor'].includes(user.role);
    const canAssignNow = canAssign && ['REGISTRADA', 'ASIGNADA', 'EN_PROCESO', 'REABIERTA'].includes(request.status);

    return (
        <div className="space-y-6">
            <div className="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <Link to="/requests" className="text-sm text-indigo-600 hover:underline">← Volver a solicitudes</Link>
                    <h1 className="mt-2 text-2xl font-bold text-slate-900">{request.code}</h1>
                    <p className="mt-1 text-sm text-slate-500">Registrada el {formatDate(request.created_at)}</p>
                </div>
                <div className="flex gap-2">
                    <StatusBadge status={request.status} />
                    <PriorityBadge priority={request.priority} />
                </div>
            </div>

            <Alert error={error} />

            <div className="grid gap-6 lg:grid-cols-3">
                <div className="space-y-6 lg:col-span-2">
                    <Card title="Información de la solicitud">
                        <dl className="grid gap-4 sm:grid-cols-2">
                            <div><dt className="text-xs uppercase text-slate-400">Categoría</dt><dd className="mt-1 font-medium">{request.category?.name}</dd></div>
                            <div><dt className="text-xs uppercase text-slate-400">Solicitante</dt><dd className="mt-1 font-medium">{request.applicant?.name}</dd></div>
                            <div><dt className="text-xs uppercase text-slate-400">Responsable</dt><dd className="mt-1 font-medium">{request.assignee || 'Sin asignar'}</dd></div>
                            <div><dt className="text-xs uppercase text-slate-400">Última actualización</dt><dd className="mt-1 font-medium">{formatDate(request.updated_at)}</dd></div>
                            <div className="sm:col-span-2"><dt className="text-xs uppercase text-slate-400">Descripción</dt><dd className="mt-1 whitespace-pre-wrap">{request.description}</dd></div>
                        </dl>
                    </Card>

                    <Card title="Historial de estados">
                        <ol className="space-y-3">
                            {(request.status_history || []).map((item) => (
                                <li key={item.id} className="rounded-lg border border-slate-100 bg-slate-50 p-3 text-sm">
                                    <div className="flex flex-wrap justify-between gap-2">
                                        <span className="font-medium">
                                            {item.from_status ? `${STATUS_LABELS[item.from_status]} → ` : ''}
                                            {STATUS_LABELS[item.to_status]}
                                        </span>
                                        <span className="text-xs text-slate-400">{formatDate(item.created_at)}</span>
                                    </div>
                                    <p className="mt-1 text-xs text-slate-500">{item.changed_by?.name || 'Sistema'}</p>
                                    {item.note && <p className="mt-2 rounded bg-white p-2 text-slate-600">{item.note}</p>}
                                </li>
                            ))}
                        </ol>
                    </Card>

                    <Card title="Observaciones y respuestas">
                        {(request.observations || []).length === 0
                            ? <p className="text-sm text-slate-400">Aún no hay observaciones.</p>
                            : (
                                <ul className="space-y-3">
                                    {request.observations.map((item) => (
                                        <li key={item.id} className="rounded-lg border border-slate-100 p-3 text-sm">
                                            <div className="flex justify-between text-xs text-slate-400">
                                                <span>{item.user?.name} · {item.kind}</span>
                                                <span>{formatDate(item.created_at)}</span>
                                            </div>
                                            <p className="mt-2 whitespace-pre-wrap">{item.body}</p>
                                        </li>
                                    ))}
                                </ul>
                            )}
                    </Card>

                    <Card title="Historial de asignaciones">
                        {(request.assignments || []).length === 0
                            ? <p className="text-sm text-slate-400">Aún no hay asignaciones.</p>
                            : (
                                <ul className="space-y-2 text-sm">
                                    {request.assignments.map((item) => (
                                        <li key={item.id} className="flex flex-wrap justify-between gap-2 border-b border-slate-100 py-2">
                                            <span>{item.previous_assignee || 'Sin responsable'} → <strong>{item.new_assignee}</strong></span>
                                            <span className="text-xs text-slate-400">{item.assigned_by} · {formatDate(item.created_at)}</span>
                                        </li>
                                    ))}
                                </ul>
                            )}
                    </Card>
                </div>

                <div className="space-y-6">
                    {canAssignNow && (
                        <Card title={request.assigned_to ? 'Reasignar funcionario' : 'Asignar funcionario'}>
                            <div className="space-y-3">
                                <Field label="Funcionario">
                                    <select value={officerId} onChange={(e) => setOfficerId(e.target.value)} className={inputClass}>
                                        <option value="">Seleccione…</option>
                                        {officers.map((officer) => <option key={officer.id} value={officer.id}>{officer.name}</option>)}
                                    </select>
                                </Field>
                                <button type="button" disabled={busy || !officerId} onClick={assign} className={`${buttonClass} w-full`}>
                                    Guardar asignación
                                </button>
                            </div>
                        </Card>
                    )}

                    {isOfficer && ['ASIGNADA', 'REABIERTA'].includes(request.status) && (
                        <Card title="Atender solicitud">
                            <button type="button" disabled={busy} onClick={() => changeStatus('EN_PROCESO')} className={`${buttonClass} w-full`}>
                                Iniciar atención
                            </button>
                        </Card>
                    )}

                    {isOfficer && request.status === 'EN_PROCESO' && (
                        <Card title="Marcar como resuelta">
                            <Field label="Solución u observación final">
                                <textarea rows={3} value={note} onChange={(e) => setNote(e.target.value)} className={inputClass} />
                            </Field>
                            <button type="button" disabled={busy} onClick={() => changeStatus('RESUELTA', true)} className={`${buttonClass} mt-3 w-full`}>
                                Registrar solución
                            </button>
                        </Card>
                    )}

                    {isSupervisor && request.status === 'RESUELTA' && (
                        <Card title="Autorizar cierre">
                            <Field label="Observación (opcional)">
                                <textarea rows={3} value={note} onChange={(e) => setNote(e.target.value)} className={inputClass} />
                            </Field>
                            <button type="button" disabled={busy} onClick={() => changeStatus('CERRADA')} className={`${buttonClass} mt-3 w-full`}>
                                Autorizar cierre
                            </button>
                        </Card>
                    )}

                    {isSupervisor && request.status === 'CERRADA' && (
                        <Card title="Reabrir solicitud">
                            <Field label="Justificación">
                                <textarea rows={3} value={note} onChange={(e) => setNote(e.target.value)} className={inputClass} />
                            </Field>
                            <button type="button" disabled={busy} onClick={() => changeStatus('REABIERTA', true)} className={`${buttonClass} mt-3 w-full`}>
                                Reabrir solicitud
                            </button>
                        </Card>
                    )}

                    {isSupervisor && ['REGISTRADA', 'ASIGNADA'].includes(request.status) && (
                        <Card title="Rechazar solicitud">
                            <Field label="Motivo del rechazo">
                                <textarea rows={3} value={note} onChange={(e) => setNote(e.target.value)} className={inputClass} />
                            </Field>
                            <button type="button" disabled={busy} onClick={() => changeStatus('RECHAZADA', true)} className={`${secondaryButtonClass} mt-3 w-full`}>
                                Rechazar solicitud
                            </button>
                        </Card>
                    )}

                    {isOfficer && request.status !== 'CERRADA' && (
                        <Card title="Nueva observación">
                            <Field label="Observación">
                                <textarea rows={3} value={observation} onChange={(e) => setObservation(e.target.value)} className={inputClass} />
                            </Field>
                            <button type="button" disabled={busy || !observation.trim()} onClick={addObservation} className={`${buttonClass} mt-3 w-full`}>
                                Registrar observación
                            </button>
                        </Card>
                    )}
                </div>
            </div>
        </div>
    );
}
