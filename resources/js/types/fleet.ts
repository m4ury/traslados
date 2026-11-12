export interface Marca {
    id: number;
    name: string;
    is_active: boolean;
    vehicle_models_count?: number;
    vehicle_models?: Modelo[];
}

export interface Modelo {
    id: number;
    marca_id: number;
    name: string;
    is_active: boolean;
    marca?: Pick<Marca, 'id' | 'name'>;
    vehicles_count?: number;
}

export interface Vehiculo {
    id: number;
    patente: string;
    marca_id: number;
    modelo_id: number;
    anio: number;
    actual_km: number;
    activo: boolean;
    /* in_trip?: boolean; */
    marca?: Pick<Marca, 'id' | 'name'>;
    modelo?: Pick<Modelo, 'id' | 'name'>;
}

export interface Conductor {
    id: number;
    user_id: number | null;
    full_name: string;
    rut: string;
    phone?: string | null;
    en_turno?: boolean;
    activo?: boolean;
}

export type EstadoTraslado = 'solicitado' | 'autorizado' | 'en_progreso' | 'finalizado' | 'cancelado';
export type TipoTraslado = 'spontaneous' | 'scheduled';
export type LogEvent = 'status' | 'departure' | 'checkpoint' | 'incident' | 'refuel' | 'arrival' | 'note';

export interface UserRef {
    id: number;
    name: string;
}

export interface Bitacora {
    id: number;
    traslado_id: number;
    event_type: LogEvent;
    logged_at: string;
    mileage_km: number | null;
    location: string | null;
    description: string | null;
    user?: UserRef | null;
}

export interface Traslado {
    id: number;
    tipo_traslado: TipoTraslado;
    estado: EstadoTraslado;
    vehiculo_id: number | null;
    conductor_id: number | null;
    scheduled_at: string | null;
    salida_at: string | null;
    return_at: string | null;
    origin: string;
    destination: string;
    purpose: string | null;
    passengers_count: number;
    initial_mileage_km: number | null;
    final_mileage_km: number | null;
    distance_km: number | null;
    notes: string | null;
    created_at: string;
    vehicle?: Vehiculo | null;
    conductor?: Conductor | null;
    requester?: UserRef | null;
    authorizer?: UserRef | null;
    logs?: Bitacora[];
}

export interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

export interface Paginated<T> {
    data: T[];
    links: PaginationLink[];
    current_page: number;
    last_page: number;
    per_page: number;
    from: number | null;
    to: number | null;
    total: number;
}
