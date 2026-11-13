import type { LogEvent, EstadoTraslado, TipoTraslado } from '@/types/fleet';
import {
    CircleDot,
    Flag,
    Fuel,
    MapPin,
    Navigation,
    StickyNote,
    TriangleAlert,
    type LucideIcon,
} from '@lucide/vue';

export const TZ = 'America/Santiago';

export const tripStatusMeta: Record<EstadoTraslado, { label: string; class: string }> = {
    solicitado: {
        label: 'Solicitado',
        class: 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-300',
    },
    autorizado: {
        label: 'Autorizado',
        class: 'border-sky-200 bg-sky-50 text-sky-700 dark:border-sky-500/30 dark:bg-sky-500/10 dark:text-sky-300',
    },
    en_curso: {
        label: 'En curso',
        class: 'border-violet-200 bg-violet-50 text-violet-700 dark:border-violet-500/30 dark:bg-violet-500/10 dark:text-violet-300',
    },
    finalizado: {
        label: 'Finalizado',
        class: 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-300',
    },
    cancelado: {
        label: 'Cancelado',
        class: 'border-zinc-200 bg-zinc-100 text-zinc-600 dark:border-zinc-500/30 dark:bg-zinc-500/10 dark:text-zinc-400',
    },
};

export const EstadoTrasladoOrder: EstadoTraslado[] = ['solicitado', 'autorizado', 'en_curso', 'finalizado', 'cancelado'];
export const tripStatusOrder = EstadoTrasladoOrder;

export const tripTypeLabel: Record<TipoTraslado, string> = {
    spontaneous: 'Espontáneo',
    scheduled: 'Programado',
};

export const logEventMeta: Record<LogEvent, { label: string; icon: LucideIcon; class: string }> = {
    status: { label: 'Cambio de estado', icon: CircleDot, class: 'bg-sky-100 text-sky-700 dark:bg-sky-500/15 dark:text-sky-300' },
    departure: { label: 'Salida', icon: Navigation, class: 'bg-violet-100 text-violet-700 dark:bg-violet-500/15 dark:text-violet-300' },
    checkpoint: { label: 'Punto de control', icon: MapPin, class: 'bg-zinc-100 text-zinc-700 dark:bg-zinc-500/15 dark:text-zinc-300' },
    incident: { label: 'Incidente', icon: TriangleAlert, class: 'bg-red-100 text-red-700 dark:bg-red-500/15 dark:text-red-300' },
    refuel: { label: 'Carga de combustible', icon: Fuel, class: 'bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300' },
    arrival: { label: 'Llegada', icon: Flag, class: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300' },
    note: { label: 'Nota', icon: StickyNote, class: 'bg-zinc-100 text-zinc-700 dark:bg-zinc-500/15 dark:text-zinc-300' },
};

/** Eventos que el chofer puede registrar manualmente durante el viaje. */
export const manualLogEvents: LogEvent[] = ['checkpoint', 'refuel', 'incident', 'note'];

export function formatDateTime(value?: string | null): string {
    if (!value) return '—';
    return new Intl.DateTimeFormat('es-CL', {
        dateStyle: 'medium',
        timeStyle: 'short',
        timeZone: TZ,
    }).format(new Date(value));
}

export function formatKm(value?: number | string | null): string {
    if (value === null || value === undefined || value === '') return '—';
    return `${new Intl.NumberFormat('es-CL').format(Number(value))} km`;
}

export function formatDuration(from?: string | null, to?: string | null): string {
    if (!from || !to) return '—';
    const minutes = Math.max(0, Math.round((new Date(to).getTime() - new Date(from).getTime()) / 60000));
    const h = Math.floor(minutes / 60);
    const m = minutes % 60;
    return h ? `${h} h ${m} min` : `${m} min`;
}

/** Valor mínimo para <input type="datetime-local"> (ahora, hora local). */
export function nowForInput(): string {
    const d = new Date();
    d.setMinutes(d.getMinutes() - d.getTimezoneOffset());
    return d.toISOString().slice(0, 16);
}

/** Debounce simple para filtros de búsqueda. */
export function debounce<T extends (...args: never[]) => void>(fn: T, wait = 300) {
    let t: ReturnType<typeof setTimeout> | undefined;
    return (...args: Parameters<T>) => {
        clearTimeout(t);
        t = setTimeout(() => fn(...args), wait);
    };
}
