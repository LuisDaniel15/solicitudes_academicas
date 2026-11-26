import { useEffect, useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { api } from '../api/client.js';
import { Alert, Field, PRIORITY_LABELS, buttonClass, inputClass, secondaryButtonClass } from '../components/ui.jsx';

export default function RequestCreate() {
    const navigate = useNavigate();
    const [categories, setCategories] = useState([]);
    const [form, setForm] = useState({ category_id: '', description: '', priority: 'medium' });
    const [errors, setErrors] = useState({});
    const [error, setError] = useState(null);
    const [loading, setLoading] = useState(false);

    useEffect(() => {
        api('/categories')
            .then((payload) => setCategories((payload.data || []).filter((category) => category.active)))
            .catch((err) => setError(err));
    }, []);

    const update = (key, value) => setForm((current) => ({ ...current, [key]: value }));

    const handleSubmit = async (event) => {
        event.preventDefault();
        setError(null);
        setErrors({});
        setLoading(true);
        try {
            const payload = await api('/requests', { method: 'POST', body: form });
            navigate(`/requests/${payload.data.id}`, { replace: true });
        } catch (err) {
            setError(err);
            setErrors(err.errors || {});
        } finally {
            setLoading(false);
        }
    };

    return (
        <div className="mx-auto max-w-3xl space-y-6">
            <div>
                <h1 className="text-2xl font-bold text-slate-900">Nueva solicitud</h1>
                <p className="mt-1 text-sm text-slate-500">Complete los datos de la solicitud que desea registrar</p>
            </div>

            <Alert error={error} />

            <form onSubmit={handleSubmit} className="space-y-5 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <Field label="Categoría" error={errors.category_id?.[0]}>
                    <select required value={form.category_id} onChange={(e) => update('category_id', e.target.value)} className={inputClass}>
                        <option value="">Seleccione una categoría</option>
                        {categories.map((category) => <option key={category.id} value={category.id}>{category.name}</option>)}
                    </select>
                </Field>
                <Field label="Prioridad" error={errors.priority?.[0]}>
                    <select value={form.priority} onChange={(e) => update('priority', e.target.value)} className={inputClass}>
                        {Object.entries(PRIORITY_LABELS).map(([value, label]) => <option key={value} value={value}>{label}</option>)}
                    </select>
                </Field>
                <Field label="Descripción" error={errors.description?.[0]}>
                    <textarea
                        required
                        minLength={10}
                        rows={6}
                        value={form.description}
                        onChange={(e) => update('description', e.target.value)}
                        className={inputClass}
                        placeholder="Describa detalladamente lo que necesita…"
                    />
                </Field>
                <div className="flex justify-end gap-3">
                    <Link to="/requests" className={secondaryButtonClass}>Cancelar</Link>
                    <button type="submit" disabled={loading} className={buttonClass}>
                        {loading ? 'Registrando…' : 'Registrar solicitud'}
                    </button>
                </div>
            </form>
        </div>
    );
}
