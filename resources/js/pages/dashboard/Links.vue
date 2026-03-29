<script setup lang="ts">
import { Form, Head, router } from '@inertiajs/vue3';
import { Loader2, Pencil, Search, Trash2, X } from 'lucide-vue-next';
import { ref, watch } from 'vue';
import LinkController from '@/actions/App/Http/Controllers/Dashboard/LinkController';
import { useMetaFetch } from '@/composables/useMetaFetch';
import { useToast } from '@/composables/useToast';
import ConfirmModal from '@/components/ConfirmModal.vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import LinkCard from '@/components/LinkCard.vue';
import TagSelect from '@/components/TagSelect.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { index } from '@/routes/dashboard/links';
import { Pagination, type Paginator } from '@/components/ui/pagination';

type Bucket = {
    id: number;
    name: string;
    color: string;
    is_inbox: boolean;
};

type Tag = {
    id: number;
    name: string;
    color: string;
    is_public: boolean;
};

type Link = {
    id: number;
    url: string;
    title: string;
    description: string | null;
    notes: string | null;
    bucket_id: number;
    bucket: Bucket;
    tags: Tag[];
};

type Filters = {
    bucket_id?: string;
    tag_id?: string;
    search?: string;
};

type Props = {
    links: Paginator<Link>;
    buckets: Bucket[];
    tags: Tag[];
    inboxBucketId: number;
    filters: Filters;
};

const props = defineProps<Props>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Links', href: index() }],
    },
});

const { toast } = useToast();

// — Create form state —
const createUrl = ref('');
const createTitle = ref('');
const createDescription = ref('');
const createBucketId = ref<number>(props.inboxBucketId);
const createTagIds = ref<number[]>([]);

const {
    fetching: metaFetching,
    failed: metaFailed,
    fetch: fetchMeta,
} = useMetaFetch((meta) => {
    if (meta.title && !createTitle.value) createTitle.value = meta.title;
    if (meta.description && !createDescription.value)
        createDescription.value = meta.description;
});

// — Edit state —
const editingLink = ref<Link | null>(null);
const editBucketId = ref<number>(0);
const editTagIds = ref<number[]>([]);

const deleteTarget = ref<Link | null>(null);

// — Filters —
const filterSearch = ref(props.filters.search ?? '');
const filterBucketId = ref(props.filters.bucket_id ?? '');
const filterTagId = ref(props.filters.tag_id ?? '');

let searchTimer: ReturnType<typeof setTimeout> | null = null;

function applyFilters(immediate = false) {
    if (searchTimer) clearTimeout(searchTimer);

    const doApply = () => {
        router.get(
            index(),
            {
                search: filterSearch.value || undefined,
                bucket_id: filterBucketId.value || undefined,
                tag_id: filterTagId.value || undefined,
            },
            { preserveState: true, replace: true },
        );
    };

    if (immediate) {
        doApply();
    } else {
        searchTimer = setTimeout(doApply, 400);
    }
}

function clearFilters() {
    filterSearch.value = '';
    filterBucketId.value = '';
    filterTagId.value = '';
    applyFilters(true);
}

watch(filterBucketId, () => applyFilters(true));
watch(filterTagId, () => applyFilters(true));
watch(filterSearch, () => applyFilters(false));

const hasActiveFilters = () =>
    filterSearch.value || filterBucketId.value || filterTagId.value;

// — CRUD —
function startEdit(link: Link) {
    editingLink.value = link;
    editBucketId.value = link.bucket_id;
    editTagIds.value = link.tags.map((t) => t.id);
}

function cancelEdit() {
    editingLink.value = null;
}

function confirmDelete(link: Link) {
    deleteTarget.value = link;
}

function deleteLink() {
    if (!deleteTarget.value) return;
    router.delete(LinkController.destroy.url(deleteTarget.value), {
        preserveScroll: true,
        onSuccess: () => {
            deleteTarget.value = null;
        },
    });
}
</script>

<template>
    <Head title="Links" />

    <div class="flex flex-col gap-8 p-4">
        <Heading title="Links" description="Manage your saved links" />

        <!-- Create form -->
        <Form
            v-bind="LinkController.store.form()"
            :options="{ preserveScroll: true }"
            class="flex flex-col gap-4 rounded-lg border p-4"
            v-slot="{ errors, processing }"
            @success="
                () => {
                    createUrl = '';
                    createTitle = '';
                    createDescription = '';
                    createBucketId = props.inboxBucketId;
                    createTagIds = [];
                    toast('Link added', 'success');
                }
            "
        >
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="flex flex-col gap-2">
                    <Label for="link-url">URL</Label>
                    <div class="relative">
                        <Input
                            id="link-url"
                            v-model="createUrl"
                            name="url"
                            type="url"
                            placeholder="https://example.com"
                            autocomplete="off"
                            @input="fetchMeta(createUrl)"
                        />
                        <Loader2
                            v-if="metaFetching"
                            class="absolute top-2.5 right-2.5 size-4 animate-spin text-muted-foreground"
                        />
                    </div>
                    <p v-if="metaFailed" class="text-xs text-muted-foreground">
                        Could not load metadata for this URL.
                    </p>
                    <InputError :message="errors.url" />
                </div>

                <div class="flex flex-col gap-2">
                    <Label for="link-title">Title</Label>
                    <Input
                        id="link-title"
                        v-model="createTitle"
                        name="title"
                        placeholder="Link title"
                        autocomplete="off"
                    />
                    <InputError :message="errors.title" />
                </div>

                <div class="flex flex-col gap-2">
                    <Label for="link-description">Description</Label>
                    <Textarea
                        id="link-description"
                        v-model="createDescription"
                        name="description"
                        placeholder="Optional description"
                        class="resize-none"
                        rows="2"
                    />
                    <InputError :message="errors.description" />
                </div>

                <div class="flex flex-col gap-2">
                    <Label for="link-notes"
                        >Notes
                        <span class="text-muted-foreground"
                            >(private)</span
                        ></Label
                    >
                    <Textarea
                        id="link-notes"
                        name="notes"
                        placeholder="Private notes"
                        class="resize-none"
                        rows="2"
                    />
                    <InputError :message="errors.notes" />
                </div>
            </div>

            <div class="flex flex-wrap gap-6">
                <div class="flex flex-col gap-2">
                    <Label for="link-bucket">Bucket</Label>
                    <select
                        id="link-bucket"
                        name="bucket_id"
                        :value="createBucketId"
                        class="rounded-md border border-input bg-background px-3 py-2 text-sm shadow-xs outline-none focus-visible:ring-2"
                        @change="
                            createBucketId = Number(
                                ($event.target as HTMLSelectElement).value,
                            )
                        "
                    >
                        <option
                            v-for="bucket in buckets"
                            :key="bucket.id"
                            :value="bucket.id"
                        >
                            {{ bucket.name }}
                        </option>
                    </select>
                    <InputError :message="errors.bucket_id" />
                </div>

                <div v-if="tags.length > 0" class="flex flex-col gap-2">
                    <Label>Tags</Label>
                    <TagSelect :tags="tags" v-model="createTagIds" />
                    <InputError :message="errors['tag_ids']" />
                </div>
            </div>

            <Button type="submit" :disabled="processing" class="self-start">
                Add
            </Button>
        </Form>

        <!-- Filters -->
        <div class="flex flex-wrap gap-3">
            <div class="relative min-w-48 flex-1">
                <Search
                    class="absolute top-2.5 left-2.5 size-4 text-muted-foreground"
                />
                <Input
                    v-model="filterSearch"
                    placeholder="Search links…"
                    class="pl-8"
                />
            </div>

            <select
                v-model="filterBucketId"
                class="rounded-md border border-input bg-background px-3 py-2 text-sm shadow-xs outline-none focus-visible:ring-2"
                aria-label="Filter by bucket"
            >
                <option value="">All buckets</option>
                <option
                    v-for="bucket in buckets"
                    :key="bucket.id"
                    :value="String(bucket.id)"
                >
                    {{ bucket.name }}
                </option>
            </select>

            <select
                v-if="tags.length > 0"
                v-model="filterTagId"
                class="rounded-md border border-input bg-background px-3 py-2 text-sm shadow-xs outline-none focus-visible:ring-2"
                aria-label="Filter by tag"
            >
                <option value="">All tags</option>
                <option
                    v-for="tag in tags"
                    :key="tag.id"
                    :value="String(tag.id)"
                >
                    {{ tag.name }}
                </option>
            </select>

            <Button
                v-if="hasActiveFilters()"
                variant="ghost"
                size="icon"
                aria-label="Clear filters"
                @click="clearFilters"
            >
                <X class="size-4" />
            </Button>
        </div>

        <!-- Pagination -->
        <Pagination v-if="links.last_page > 1" :items="links" />

        <!-- Link list -->
        <ul class="flex flex-col gap-2">
            <li v-for="link in links.data" :key="link.id">
                <!-- Inline edit form -->
                <template v-if="editingLink?.id === link.id">
                    <Form
                        v-bind="LinkController.update.form(link)"
                        :options="{ preserveScroll: true }"
                        class="flex flex-col gap-4 rounded-lg border px-4 py-3"
                        v-slot="{ errors, processing }"
                        @success="cancelEdit"
                    >
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="flex flex-col gap-2">
                                <Label
                                    :for="`edit-url-${link.id}`"
                                    class="sr-only"
                                    >URL</Label
                                >
                                <Input
                                    :id="`edit-url-${link.id}`"
                                    name="url"
                                    type="url"
                                    :default-value="link.url"
                                    placeholder="https://example.com"
                                />
                                <InputError :message="errors.url" />
                            </div>

                            <div class="flex flex-col gap-2">
                                <Label
                                    :for="`edit-title-${link.id}`"
                                    class="sr-only"
                                    >Title</Label
                                >
                                <Input
                                    :id="`edit-title-${link.id}`"
                                    name="title"
                                    :default-value="link.title"
                                    placeholder="Link title"
                                />
                                <InputError :message="errors.title" />
                            </div>

                            <div class="flex flex-col gap-2">
                                <Label
                                    :for="`edit-desc-${link.id}`"
                                    class="sr-only"
                                    >Description</Label
                                >
                                <Textarea
                                    :id="`edit-desc-${link.id}`"
                                    name="description"
                                    :default-value="link.description ?? ''"
                                    placeholder="Optional description"
                                    class="resize-none"
                                    rows="2"
                                />
                                <InputError :message="errors.description" />
                            </div>

                            <div class="flex flex-col gap-2">
                                <Label
                                    :for="`edit-notes-${link.id}`"
                                    class="sr-only"
                                    >Notes</Label
                                >
                                <Textarea
                                    :id="`edit-notes-${link.id}`"
                                    name="notes"
                                    :default-value="link.notes ?? ''"
                                    placeholder="Private notes"
                                    class="resize-none"
                                    rows="2"
                                />
                                <InputError :message="errors.notes" />
                            </div>
                        </div>

                        <div class="flex flex-wrap gap-6">
                            <div class="flex flex-col gap-2">
                                <Label :for="`edit-bucket-${link.id}`"
                                    >Bucket</Label
                                >
                                <select
                                    :id="`edit-bucket-${link.id}`"
                                    name="bucket_id"
                                    :value="editBucketId"
                                    class="rounded-md border border-input bg-background px-3 py-2 text-sm shadow-xs outline-none focus-visible:ring-2"
                                    @change="
                                        editBucketId = Number(
                                            ($event.target as HTMLSelectElement)
                                                .value,
                                        )
                                    "
                                >
                                    <option
                                        v-for="bucket in buckets"
                                        :key="bucket.id"
                                        :value="bucket.id"
                                    >
                                        {{ bucket.name }}
                                    </option>
                                </select>
                                <InputError :message="errors.bucket_id" />
                            </div>

                            <div
                                v-if="tags.length > 0"
                                class="flex flex-col gap-2"
                            >
                                <Label>Tags</Label>
                                <TagSelect :tags="tags" v-model="editTagIds" />
                            </div>
                        </div>

                        <div class="flex gap-2">
                            <Button
                                type="submit"
                                size="sm"
                                :disabled="processing"
                                >Save</Button
                            >
                            <Button
                                type="button"
                                size="sm"
                                variant="outline"
                                @click="cancelEdit"
                            >
                                Cancel
                            </Button>
                        </div>
                    </Form>
                </template>

                <template v-else>
                    <div class="group relative">
                        <LinkCard
                            :title="link.title"
                            :url="link.url"
                            :description="link.description"
                            :bucket="link.bucket"
                            :tags="link.tags"
                        />
                        <div
                            class="absolute top-3 right-3 flex gap-1 opacity-0 transition-opacity group-hover:opacity-100"
                        >
                            <Button
                                variant="ghost"
                                size="icon"
                                class="size-7"
                                :aria-label="`Edit ${link.title}`"
                                @click="startEdit(link)"
                            >
                                <Pencil class="size-3.5" />
                            </Button>
                            <Button
                                variant="ghost"
                                size="icon"
                                class="size-7"
                                :aria-label="`Delete ${link.title}`"
                                @click="confirmDelete(link)"
                            >
                                <Trash2 class="size-3.5 text-destructive" />
                            </Button>
                        </div>
                    </div>
                </template>
            </li>
        </ul>

        <p v-if="links.data.length === 0" class="text-sm text-muted-foreground">
            {{
                hasActiveFilters()
                    ? 'No links match your filters.'
                    : 'No links yet. Add your first link above.'
            }}
        </p>

        <!-- Pagination -->
        <Pagination v-if="links.last_page > 1" :items="links" />
    </div>

    <ConfirmModal
        :open="deleteTarget !== null"
        title="Delete link?"
        :description="`Delete '${deleteTarget?.title}'? This action cannot be undone.`"
        confirm-label="Delete"
        @update:open="
            (val) => {
                if (!val) deleteTarget = null;
            }
        "
        @confirm="deleteLink"
    />
</template>
