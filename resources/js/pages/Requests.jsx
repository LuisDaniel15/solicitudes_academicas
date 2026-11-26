import { useEffect, useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { api } from '../api/client.js';
import { useAuth } from '../auth/AuthContext.jsx';
import {
    Alert, DataTable, Field, Modal, PRIORITY_LABELS, STATUS_LABELS,
    PriorityBadge, StatusBadge, buttonClass, inputClass, secondaryButtonClass,
} from '../components/ui.jsx';

export default function Requests() {
    const { user } = useAuth();
    const navigate = useNavigate();
    const canAssign = ['admin', 'supervisor'].includes(user.role);

    const [rows, setRows] = useState([]);
    const [categories, setCategories] = useState([]);
    const [officers, setOfficers] = useState([]);
    const [filters, setFilters] = useState({ status: '', category_id: '', priority: '', search: '' });
    const [error, setError] = useState(null);
    const [assigning, setAssigning] = useState(null);
    const [officerId, setOfficerId] = useState('');

    const load = () => api('/requests', { params: filters })
        .then((payload) => setRows(payload.data || []))
        .catch((err) => setError(err));

    useEffect(() => { load(); }, [filters]);
    useEffect(() => {
        api('/categories').then((payload) => setCategories(payload.data || [])).catch(() => {});
        if (canAssign) {
            api('/officers').then((payload) => setOfficers(payload.data || [])).catch(() => {});
        }
    }, [canAssign]);

    const updateFilter = (key, value) => setFilters((current) => ({ ...current, [key]: value }));

    const submitAssignment = async (event) => {
        event.preventDefault();
        setError(null);
        try {
            await api(`/requests/${assigning.id}/assignments`, {
                method: 'POST',
                body: { assigned_to: Number(officerId) },
            });
            setAssigning(null);
            setOfficerId('');
            load();
        } catch (err) {
            setError(err);
        }
    };

    const columns = [
        { key: 'code', label: 'Código', render: (row) => <span className="font-medium text-indigo-700">{row.code}</span> },
        { key: 'description', label: 'Descripción', render: (row) => <span className="line-clamp-2 max-w-md">{row.description}</span> },
        { key: 'category', label: 'Categoría', render: (row) => row.category?.name || '—' },
        { key: 'priority', label: 'Prioridad', render: (row) => <PriorityBadge priority={row.priority} /> },
        { key: 'status', label: 'Estado', render: (row) => <StatusBadge status={row.status} /> },
        { key: 'assignee', label: 'Responsable', render: (row) => row.assignee || 'Sin asignar' },
        {
            key: 'actions',
            label: 'Acciones',
            render: (row) => canAssign ? (
                <button
                    type="button"
                    onClick={(event) => { event.stopPropagation(); setAssigning(row); setOfficerId(row.assigned_to || ''); }}
                    className="text-sm font-medium text-indigo-600 hover:underline"
                >
                    {row.assigned_to ? 'Reasignar' : 'Asignar'}
                </button>
            ) : <span className="text-slate-400">—</span>,
        },
    ];

    return (
        <div className="space-y-6">
            <div className="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h1 className="text-2xl font-bold text-slate-900">Solicitudes</h1>
                    <p className="mt-1 text-sm text-slate-500">Consulta, filtra y gestiona las solicitudes</p>
                </div>
                {user.role === 'estudiante' && (
                    <Link to="/requests/new" className={buttonClass}>Nueva solicitud</Link>
                )}
            </div>

            <Alert error={error} />

            <div className="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <DataTable
                    columns={columns}
                    rows={rows}
                    onRowClick={(row) => navigate(`/requests/${row.id}`)}
                    filters={
                        <>
                            <input value={filters.search} onChange={(e) => updateFilter('search', e.target.value)} placeholder="Buscar código o descripción" className={`${inputClass} max-w-xs`} />
                            <select value={filters.status} onChange={(e) => updateFilter('status', e.target.value)} className={`${inputClass} max-w-48`}>
                                <option value="">Todos los estados</option>
                                {Object.entries(STATUS_LABELS).map(([value, label]) => <option key={value} value={value}>{label}</option>)}
                            </select>
                            <select value={filters.category_id} onChange={(e) => updateFilter('category_id', e.target.value)} className={`${inputClass} max-w-48`}>
                                <option value="">Todas las categorías</option>
                                {categories.map((category) => <option key={category.id} value={category.id}>{category.name}</option>)}
                            </select>
                            <select value={filters.priority} onChange={(e) => updateFilter('priority', e.target.value)} className={`${inputClass} max-w-40`}>
                                <option value="">Todas las prioridades</option>
                                {Object.entries(PRIORITY_LABELS).map(([value, label]) => <option key={value} value={value}>{label}</option>)}
                            </select>
                        </>
                    }
                />
            </div>

            {assigning && (
                <Modal title={assigning.assigned_to ? 'Reasignar solicitud' : 'Asignar solicitud'} onClose={() => setAssigning(null)}>
                    <form onSubmit={submitAssignment} className="space-y-4">
                        <p className="text-sm text-slate-500">Solicitud <strong>{assigning.code}</strong></p>
                        <Field label="Funcionario responsable">
                            <select required value={officerId} onChange={(e) => setOfficerId(e.target.value)} className={inputClass}>
                                <option value="">Seleccione un funcionario</option>
                                {officers.map((officer) => <option key={officer.id} value={officer.id}>{officer.name}</option>)}
                            </select>
                        </Field>
                        <div className="flex justify-end gap-3">
                            <button type="button" onClick={() => setAssigning(null)} className={secondaryButtonClass}>Cancelar</button>
                            <button type="submit" className={buttonClass}>Guardar</button>
                        </div>
                    </form>
                </Modal>
            )}
        </div>
    );
}
