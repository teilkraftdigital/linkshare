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
    parent_id?: number | null;
    slug?: string;
    description?: string;
    links_count?: number;
    children?: Tag[];
    parent?: Tag;
    deleted_at?: string | null;
    /** Populated in trash view: whether this tag's parent is also trashed */
    parent_trashed?: boolean;
};

export type TagCreatePayload = {
    name: string;
    parentId?: number;
    parentName?: string;
};

export type Link = {
    id: number;
    url: string;
    title: string;
    description?: string;
    notes?: string;
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

export type Filters = {
    bucket_id?: string;
    tag_id?: string;
    search?: string;
    trashed?: string;
};

export type SortKey = 'updated_at' | 'name' | 'links_count';

export const TAG_SEARCH_THRESHOLD = 8;

export type DashboardTag = Tag & {
    links_count: number;
    total_links_count: number;
    children_count: number;
    updated_at: string;
};

export type DashboardLink = {
    id: number;
    url: string;
    title: string;
    favicon_url: string | null;
    bucket: Bucket | null;
    tags: Tag[];
};
