export interface User {
    id: number;
    name: string;
    email: string;
    email_verified_at?: string;
    title?: string | null;
    company_id?: number | null;
}

export interface Company {
    id: number;
    name: string;
    industry: string;
    brand_primary_color?: string | null;
    logo_url?: string | null;
}

export type PageProps<
    T extends Record<string, unknown> = Record<string, unknown>,
> = T & {
    auth: {
        user: User | null;
        company: Company | null;
    };
    flash: {
        success?: string;
        error?: string;
        toast?: {
            message: string;
            type?: 'success' | 'error' | 'info' | 'warning';
        };
    };
};
