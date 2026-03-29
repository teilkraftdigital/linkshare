export type Bucket = {
    id: number;
    name: string;
    color: string;
    is_inbox: boolean;
    links_count?: number;
};

export type Tag = {
    id: number;
    name: string;
    color: string;
    is_public: boolean;
    slug?: string;
    description?: string | null;
    links_count?: number;
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
};
