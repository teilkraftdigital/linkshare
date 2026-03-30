export type Bucket = {
    id: number;
    name: string;
    color: string;
    is_inbox: boolean;
    links_count?: number;
    deleted_at?: string | null;
};

export type Tag = {
    id: number;
    name: string;
    color: string;
    is_public: boolean;
    slug?: string;
    description?: string | null;
    links_count?: number;
    deleted_at?: string | null;
};

export type Link = {
    id: number;
    url: string;
    title: string;
    description: string | null;
    notes: string | null;
    bucket_id: number;
    bucket: Bucket;
    tags: Tag[];
    favicon_url: string | null;
    deleted_at?: string | null;
};

export type PaginatorLink = {
    url: string | null;
    label: string;
    active: boolean;
};

export type Paginator<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
    links: PaginatorLink[];
};
