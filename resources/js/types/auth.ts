export type User = {
    id: number;
    rut: string;
    name: string;
    apellido_paterno: string;
    apellido_materno: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
    [key: string]: unknown;
};

export type Auth = {
    user: User;
};
