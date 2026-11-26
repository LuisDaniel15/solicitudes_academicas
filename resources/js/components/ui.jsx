import { useMemo, useState } from 'react';

export const STATUS_LABELS = {
    REGISTRADA: 'Registrada',
    ASIGNADA: 'Asignada',
    EN_PROCESO: 'En proceso',
    RESUELTA: 'Resuelta',
    CERRADA: 'Cerrada',
    RECHAZADA: 'Rechazada',
    REABIERTA: 'Reabierta',
};

export const STATUS_STYLES = {
    REGISTRADA: 'bg-slate-100 text-slate-700',
    ASIGNADA: 'bg-sky-100 text-sky-700',
    EN_PROCESO: 'bg-indigo-100 text-indigo-700',
    RESUELTA: 'bg-emerald-100 text-emerald-700',
    CERRADA: 'bg-zinc-200 text-zinc-700',
    RECHAZADA: 'bg-rose-100 text-rose-700',
    REABIERTA: 'bg-amber-100 text-amber-700',
};

export const PRIORITY_LABELS = { low: 'Baja', medium: 'Media', high: 'Alta' };
export const PRIORITY_STYLES = {
    low: 'bg-slate-100 text-slate-600',
    medium: 'bg-amber-100 text-amber-700',
    high: 'bg-rose-100 text-rose-700',
};

export const ROLE_LABELS = {
    admin: 'Administrador',
    estudiante: 'Estudiante',
    funcionario: 'Funcionario',
    supervisor: 'Supervisor',
};

export function StatusBadge({ status }) {
    return (
        <span className={`rounded-full px-2.5 py-1 text-xs font-medium ${STATUS_STYLES[status] || 'bg-slate-100'}`}>
            {STATUS_LABELS[status] || status}
        </span>
    );
}

export function PriorityBadge({ priority }) {
    return (
        <span className={`rounded-full px-2.5 py-1 text-xs font-medium ${PRIORITY_STYLES[priority] || 'bg-slate-100'}`}>
            {PRIORITY_LABELS[priority] || priority}
        </span>
    );
}

export function Card({ title, children, className = '' }) {
    return (
        <section className={`rounded-xl border border-slate-200 bg-white p-5 shadow-sm ${className}`}>
            {title && <h2 className="mb-4 text-lg font-semibold text-slate-800">{title}</h2>}
            {children}
        </section>
    );
}

export function StatCard({ label, value, tone = 'bg-indigo-500' }) {
    return (
        <div className="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <div className={`mb-3 h-1.5 w-10 rounded-full ${tone}`} />
            <p className="text-sm text-slate-500">{label}</p>
            <p className="mt-1 text-2xl font-semibold text-slate-900">{value}</p>
        </div>
    );
}

export function Field({ label, error, children, className = '' }) {
    return (
        <label className={`block ${className}`}>
            <span className="mb-1 block text-sm font-medium text-slate-700">{label}</span>
            {children}
            {error && <span className="mt-1 block text-xs text-rose-600">{error}</span>}
        </label>
    );
}

export const inputClass = 'w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500';
export const buttonClass = 'inline-flex items-center justify-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-indigo-700 disabled:opacity-50';
export const secondaryButtonClass = 'inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50 disabled:opacity-50';

export function Alert({ error }) {
    if (!error) return null;
    const message = typeof error === 'string' ? error : error.message;
    return (
        <div className="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
            {message}
        </div>
    );
}

export function Modal({ title, onClose, children }) {
    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4">
            <div className="w-full max-w-md rounded-xl bg-white p-6 shadow-xl">
                <div className="mb-4 flex items-center justify-between">
                    <h3 className="text-lg font-semibold">{title}</h3>
                    <button type="button" onClick={onClose} className="text-slate-400 hover:text-slate-700" aria-label="Cerrar">
                        ✕
                    </button>
                </div>
                {children}
            </div>
        </div>
    );
}

export function DataTable({ columns, rows = [], searchKeys = [], filters, onRowClick, empty = 'No hay registros.' }) {
    const [query, setQuery] = useState('');

    const visible = useMemo(() => {
        if (!query.trim()) return rows;
        const term = query.trim().toLowerCase();
        return rows.filter((row) => searchKeys.some((key) =>
            String(row[key] ?? '').toLowerCase().includes(term)));
    }, [query, rows, searchKeys]);

    return (
        <div>
            <div className="mb-4 flex flex-wrap items-center gap-3">
                {searchKeys.length > 0 && (
                    <input
                        type="search"
                        value={query}
                        onChange={(event) => setQuery(event.target.value)}
                        placeholder="Buscar…"
                        className={`${inputClass} max-w-xs`}
                    />
                )}
                {filters}
            </div>
            <div className="overflow-x-auto rounded-lg border border-slate-200">
                <table className="w-full min-w-[640px] text-left text-sm">
                    <thead className="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                        <tr>
                            {columns.map((column) => <th key={column.key} className="px-4 py-3">{column.label}</th>)}
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-slate-100">
                        {visible.map((row, index) => (
                            <tr
                                key={row.id ?? index}
                                onClick={onRowClick ? () => onRowClick(row) : undefined}
                                className={onRowClick ? 'cursor-pointer transition hover:bg-slate-50' : ''}
                            >
                                {columns.map((column) => (
                                    <td key={column.key} className="px-4 py-3 align-middle">
                                        {column.render ? column.render(row) : row[column.key]}
                                    </td>
                                ))}
                            </tr>
                        ))}
                        {visible.length === 0 && (
                            <tr><td colSpan={columns.length} className="px-4 py-8 text-center text-slate-400">{empty}</td></tr>
                        )}
                    </tbody>
                </table>
            </div>
            <p className="mt-2 text-xs text-slate-400">{visible.length} registro(s)</p>
        </div>
    );
}
