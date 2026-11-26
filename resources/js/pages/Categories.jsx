import { useEffect, useState } from 'react';
import { api } from '../api/client.js';
import {
    Alert, Card, DataTable, Field, Modal,
    buttonClass, inputClass, secondaryButtonClass,
} from '../components/ui.jsx';

export default function Categories() {
    const [rows, setRows] = useState([]);
    const [form, setForm] = useState({ name: '', description: '' });
    const [editing, setEditing] = useState(null);
    const [showForm, setShowForm] = useState(false);
    const [error, setError] = useState(null);
    const [errors, setErrors] = useState({});

    const load = () => api('/categories').then((payload) => setRows(payload.data || [])).catch((err) => setError(err));
    useEffect(() => { load(); }, []);

    const update = (key, value) => setForm((current) => ({ ...current, [key]: value }));

    const openCreate = () => { setForm({ name: '', description: '' }); setEditing(null); setShowForm(true); };
    const openEdit = (category) => {
        setForm({ name: category.name, description: category.description || '' });
        setEditing(category);
        setShowForm(true);
    };

    const submit = async (event) => {
        event.preventDefault();
        setError(null);
        setErrors({});
        try {
            if (editing) {
                await api(`/categories/${editing.id}`, { method: 'PATCH', body: form });
            } else {
                await api('/categories', { method: 'POST', body: form });
            }
            setShowForm(false);
            load();
        } catch (err) {
            setError(err);
            setErrors(err.errors || {});
        }
    };

    const toggleActive = async (category) => {
        setError(null);
        try {
            await api(`/categories/${category.id}/toggle-active`, { method: 'PATCH' });
            load();
        } catch (err) {
            setError(err);
        }
    };

    const columns = [
        { key: 'name', label: 'Nombre' },
        { key: 'description', label: 'Descripción', render: (row) => row.description || '—' },
        { key: 'active', label: 'Estado', render: (row) => row.active ? 'Activa' : 'Inactiva' },
        {
            key: 'actions', label: 'Acciones', render: (row) => (
                <div className="flex gap-3">
                    <button type="button" onClick={(e) => { e.stopPropagation(); openEdit(row); }} className="text-indigo-600 hover:underline">Editar</button>
                    <button type="button" onClick={(e) => { e.stopPropagation(); toggleActive(row); }} className="text-slate-600 hover:underline">
                        {row.active ? 'Desactivar' : 'Activar'}
                    </button>
                </div>
            ),
        },
    ];

    return (
        <div className="space-y-6">
            <div className="flex items-center justify-between">
                <div>
                    <h1 className="text-2xl font-bold text-slate-900">Categorías</h1>
                    <p className="mt-1 text-sm text-slate-500">Gestione las categorías de solicitudes</p>
                </div>
                <button type="button" onClick={openCreate} className={buttonClass}>Nueva categoría</button>
            </div>

            <Alert error={error} />

            <Card>
                <DataTable columns={columns} rows={rows} searchKeys={['name', 'description']} />
            </Card>

            {showForm && (
                <Modal title={editing ? 'Editar categoría' : 'Nueva categoría'} onClose={() => setShowForm(false)}>
                    <form onSubmit={submit} className="space-y-4">
                        <Field label="Nombre" error={errors.name?.[0]}>
                            <input required value={form.name} onChange={(e) => update('name', e.target.value)} className={inputClass} />
                        </Field>
                        <Field label="Descripción" error={errors.description?.[0]}>
                            <textarea rows={3} value={form.description} onChange={(e) => update('description', e.target.value)} className={inputClass} />
                        </Field>
                        <div className="flex justify-end gap-3">
                            <button type="button" onClick={() => setShowForm(false)} className={secondaryButtonClass}>Cancelar</button>
                            <button type="submit" className={buttonClass}>Guardar</button>
                        </div>
                    </form>
                </Modal>
            )}
        </div>
    );
}
