import { useEffect, useState } from 'react';
import { api } from '../api/client.js';
import {
    Alert, Card, DataTable, Field, Modal, ROLE_LABELS,
    buttonClass, inputClass, secondaryButtonClass,
} from '../components/ui.jsx';

const emptyForm = { name: '', email: '', password: '', password_confirmation: '', role: 'estudiante' };

export default function Users() {
    const [rows, setRows] = useState([]);
    const [form, setForm] = useState(emptyForm);
    const [editing, setEditing] = useState(null);
    const [showForm, setShowForm] = useState(false);
    const [error, setError] = useState(null);
    const [errors, setErrors] = useState({});

    const load = () => api('/users').then((payload) => setRows(payload.data || [])).catch((err) => setError(err));
    useEffect(() => { load(); }, []);

    const update = (key, value) => setForm((current) => ({ ...current, [key]: value }));

    const openCreate = () => { setForm(emptyForm); setEditing(null); setShowForm(true); };
    const openEdit = (user) => {
        setForm({ ...emptyForm, name: user.name, email: user.email, role: user.role });
        setEditing(user);
        setShowForm(true);
    };

    const submit = async (event) => {
        event.preventDefault();
        setError(null);
        setErrors({});
        try {
            const body = { ...form };
            if (editing) {
                delete body.password;
                delete body.password_confirmation;
                await api(`/users/${editing.id}`, { method: 'PATCH', body });
            } else {
                await api('/users', { method: 'POST', body });
            }
            setShowForm(false);
            load();
        } catch (err) {
            setError(err);
            setErrors(err.errors || {});
        }
    };

    const toggleActive = async (user) => {
        setError(null);
        try {
            await api(`/users/${user.id}/toggle-active`, { method: 'PATCH' });
            load();
        } catch (err) {
            setError(err);
        }
    };

    const columns = [
        { key: 'name', label: 'Nombre' },
        { key: 'email', label: 'Correo' },
        { key: 'role', label: 'Rol', render: (row) => ROLE_LABELS[row.role] },
        { key: 'active', label: 'Estado', render: (row) => row.active ? 'Activo' : 'Inactivo' },
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
                    <h1 className="text-2xl font-bold text-slate-900">Usuarios</h1>
                    <p className="mt-1 text-sm text-slate-500">Administre los usuarios y sus roles</p>
                </div>
                <button type="button" onClick={openCreate} className={buttonClass}>Nuevo usuario</button>
            </div>

            <Alert error={error} />

            <Card>
                <DataTable columns={columns} rows={rows} searchKeys={['name', 'email']} />
            </Card>

            {showForm && (
                <Modal title={editing ? 'Editar usuario' : 'Nuevo usuario'} onClose={() => setShowForm(false)}>
                    <form onSubmit={submit} className="space-y-4">
                        <Field label="Nombre" error={errors.name?.[0]}>
                            <input required value={form.name} onChange={(e) => update('name', e.target.value)} className={inputClass} />
                        </Field>
                        <Field label="Correo electrónico" error={errors.email?.[0]}>
                            <input type="email" required value={form.email} onChange={(e) => update('email', e.target.value)} className={inputClass} />
                        </Field>
                        <Field label="Rol" error={errors.role?.[0]}>
                            <select value={form.role} onChange={(e) => update('role', e.target.value)} className={inputClass}>
                                {Object.entries(ROLE_LABELS).map(([value, label]) => <option key={value} value={value}>{label}</option>)}
                            </select>
                        </Field>
                        <Field label={editing ? 'Nueva contraseña (opcional)' : 'Contraseña'} error={errors.password?.[0]}>
                            <input type="password" required={!editing} minLength={8} value={form.password} onChange={(e) => update('password', e.target.value)} className={inputClass} />
                        </Field>
                        {!editing && (
                            <Field label="Confirmar contraseña">
                                <input type="password" required minLength={8} value={form.password_confirmation} onChange={(e) => update('password_confirmation', e.target.value)} className={inputClass} />
                            </Field>
                        )}
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
