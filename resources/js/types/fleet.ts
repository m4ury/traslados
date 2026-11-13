export interface Marca {
    id: number;
    name: string;
    is_active: boolean;
    vehicle_models_count?: number;
    modelos?: Modelo[];
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
    in_trip?: boolean;
    marca?: Pick<Marca, 'id' | 'name'>;
    modelo?: Pick<Modelo, 'id' | 'name'>;
}

export interface Conductor {
    id: number;
    user_id: number | null;
    nombres: string;
    rut: string;
    phone?: string | null;
    en_turno?: boolean;
    activo?: boolean;
}

export type EstadoTraslado = 'solicitado' | 'autorizado' | 'en_curso' | 'finalizado' | 'cancelado';
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
    programado_at: string | null;
    salida_at: string | null;
    regreso_at: string | null;
    origen: string;
    destino: string;
    proposito: string | null;
    cantidad_pasajeros: number;
    init_km: number | null;
    final_km: number | null;
    distancia_km: number | null;
    observaciones: string | null;
    created_at: string;
    vehiculo?: Vehiculo | null;
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
