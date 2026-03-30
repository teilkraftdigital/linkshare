<script setup lang="ts">
import { Form, Head, router } from '@inertiajs/vue3';
import { Loader2, Pencil, RotateCcw, Search, Trash2, X } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import LinkController from '@/actions/App/Http/Controllers/Dashboard/LinkController';
import { useDuplicateCheck } from '@/composables/useDuplicateCheck';
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
import type { Bucket, Link, Tag } from '@/types/dashboard';

type Filters = {
    bucket_id?: string;
    tag_id?: string;
    search?: string;
    trashed?: string;
};

type Props = {
    links: Paginator<Link>;
    buckets: Bucket[];
    tags: Tag[];
    inboxBucketId: number;
    filters: Filters;
    showTrashed: boolean;
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
const createNotes = ref('');
const createBucketId = ref<number>(props.inboxBucketId);
const createTagIds = ref<number[]>([]);

const isCreateFormDirty = computed(
    () =>
        createUrl.value !== '' ||
        createTitle.value !== '' ||
        createDescription.value !== '' ||
        createNotes.value !== '' ||
        createBucketId.value !== props.inboxBucketId ||
        createTagIds.value.length > 0,
);

function resetCreateForm() {
    createUrl.value = '';
    createTitle.value = '';
    createDescription.value = '';
    createNotes.value = '';
    createBucketId.value = props.inboxBucketId;
    createTagIds.value = [];
    resetDuplicate();
    resetMeta();
}

const {
    fetching: metaFetching,
    failed: metaFailed,
    faviconUrl: createFaviconUrl,
    fetch: fetchMeta,
    reset: resetMeta,
} = useMetaFetch((meta) => {
    if (meta.title && !createTitle.value) createTitle.value = meta.title;
    if (meta.description && !createDescription.value)
        createDescription.value = meta.description;
});

const {
    exists: duplicateExists,
    similar: duplicateSimilar,
    check: checkDuplicate,
    reset: resetDuplicate,
} = useDuplicateCheck();

const duplicateConfirmOpen = ref(false);
const pendingSubmit = ref<(() => void) | null>(null);

function handleCreateSubmit(submit: () => void) {
    if (duplicateExists.value) {
        pendingSubmit.value = submit;
        duplicateConfirmOpen.value = true;
    } else {
        submit();
    }
}

function confirmDuplicateSubmit() {
    duplicateConfirmOpen.value = false;
    pendingSubmit.value?.();
    pendingSubmit.value = null;
}

// — Edit state —
const editingLink = ref<Link | null>(null);
const editBucketId = ref<number>(0);
const editTagIds = ref<number[]>([]);

const deleteTarget = ref<Link | null>(null);
const forceDeleteTarget = ref<Link | null>(null);

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
            toast('Link gelöscht', 'success');
        },
    });
}

function toggleTrashed() {
    router.get(index(), props.showTrashed ? {} : { trashed: '1' }, {
        preserveState: false,
    });
}

function restoreLink(link: Link) {
    router.post(
        LinkController.restore.url(link),
        {},
        {
            preserveScroll: true,
            onSuccess: () => toast('Link wiederhergestellt', 'success'),
        },
    );
}

function confirmForceDelete(link: Link) {
    forceDeleteTarget.value = link;
}

function forceDeleteLink() {
    if (!forceDeleteTarget.value) return;
    router.delete(LinkController.forceDelete.url(forceDeleteTarget.value), {
        preserveScroll: true,
        onSuccess: () => {
            forceDeleteTarget.value = null;
            toast('Link endgültig gelöscht', 'success');
        },
    });
}
</script>

<template>
    <Head title="Links" />

    <div class="flex flex-col gap-8 p-4">
        <div class="flex items-start justify-between gap-4">
            <Heading
                title="Links"
                description="Verwalte deine gespeicherten Links"
            />
            <Button
                variant="ghost"
                size="sm"
                :class="
                    showTrashed ? 'text-destructive' : 'text-muted-foreground'
                "
                @click="toggleTrashed"
            >
                <Trash2 class="size-4" />
                Papierkorb
            </Button>
        </div>

        <!-- Create form -->
        <Form
            v-if="!showTrashed"
            v-bind="LinkController.store.form()"
            :options="{ preserveScroll: true }"
            class="flex flex-col gap-4 rounded-lg border p-4"
            v-slot="{ errors, processing, submit }"
            @success="
                () => {
                    resetCreateForm();
                    toast('Link gespeichert', 'success');
                }
            "
        >
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="flex flex-col gap-2">
                    <Label for="link-url">URL</Label>
                    <div class="relative">
                        <img
                            v-if="createFaviconUrl"
                            :src="createFaviconUrl"
                            class="absolute top-2.5 left-2.5 size-4 rounded-sm object-contain"
                            alt=""
                            @error="
                                (
                                    $event.target as HTMLImageElement
                                ).style.display = 'none'
                            "
                        />
                        <Input
                            id="link-url"
                            v-model="createUrl"
                            name="url"
                            type="url"
                            placeholder="https://example.com"
                            autocomplete="off"
                            :class="createFaviconUrl ? 'pl-8' : ''"
                            @input="
                                fetchMeta(createUrl);
                                checkDuplicate(createUrl);
                            "
                        />
                        <Loader2
                            v-if="metaFetching"
                            class="absolute top-2.5 right-2.5 size-4 animate-spin text-muted-foreground"
                        />
                    </div>
                    <p
                        v-if="duplicateExists"
                        class="text-xs text-amber-600 dark:text-amber-400"
                    >
                        Dieser Link ist bereits vorhanden.
                    </p>
                    <p
                        v-else-if="duplicateSimilar"
                        class="text-xs text-amber-600 dark:text-amber-400"
                    >
                        Ein ähnlicher Link ist bereits vorhanden.
                    </p>
                    <p
                        v-else-if="metaFailed"
                        class="text-xs text-muted-foreground"
                    >
                        Metadaten für diese URL konnten nicht geladen werden.
                    </p>
                    <InputError :message="errors.url" />
                </div>

                <div class="flex flex-col gap-2">
                    <Label for="link-title">Titel</Label>
                    <Input
                        id="link-title"
                        v-model="createTitle"
                        name="title"
                        placeholder="Link-Titel"
                        autocomplete="off"
                    />
                    <InputError :message="errors.title" />
                </div>

                <div class="flex flex-col gap-2">
                    <Label for="link-description">Beschreibung</Label>
                    <Textarea
                        id="link-description"
                        v-model="createDescription"
                        name="description"
                        placeholder="Optionale Beschreibung"
                        class="resize-none"
                        rows="2"
                    />
                    <InputError :message="errors.description" />
                </div>

                <div class="flex flex-col gap-2">
                    <Label for="link-notes">
                        Notizen
                        <span class="text-muted-foreground"> (privat)</span>
                    </Label>
                    <Textarea
                        id="link-notes"
                        v-model="createNotes"
                        name="notes"
                        placeholder="Private Notizen"
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

            <input
                v-if="createFaviconUrl"
                type="hidden"
                name="favicon_url"
                :value="createFaviconUrl"
            />

            <div class="flex gap-2 self-start">
                <Button
                    type="button"
                    :disabled="processing"
                    @click="handleCreateSubmit(submit)"
                >
                    Add
                </Button>
                <Button
                    v-if="isCreateFormDirty"
                    type="button"
                    variant="ghost"
                    size="icon"
                    aria-label="Clear form"
                    @click="resetCreateForm"
                >
                    <RotateCcw class="size-4" />
                </Button>
            </div>
        </Form>

        <ConfirmModal
            :open="duplicateConfirmOpen"
            title="Link bereits vorhanden"
            description="Ein Link mit dieser URL ist bereits gespeichert. Trotzdem hinzufügen?"
            confirm-label="Trotzdem hinzufügen"
            @update:open="duplicateConfirmOpen = $event"
            @confirm="confirmDuplicateSubmit"
        />

        <!-- Filters (hidden in trash view) -->
        <div v-if="!showTrashed" class="flex flex-wrap gap-3">
            <div class="relative min-w-48 flex-1">
                <Search
                    class="absolute top-2.5 left-2.5 size-4 text-muted-foreground"
                />
                <Input
                    v-model="filterSearch"
                    placeholder="Suche in deinen Links…"
                    class="pl-8"
                />
            </div>

            <select
                v-model="filterBucketId"
                class="rounded-md border border-input bg-background px-3 py-2 text-sm shadow-xs outline-none focus-visible:ring-2"
                aria-label="Filter nach Bucket"
            >
                <option value="">Alle Buckets</option>
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
                aria-label="Filter nach Tag"
            >
                <option value="">Alle Tags</option>
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
                aria-label="Filter zurücksetzen"
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
                <!-- Inline edit form (only in normal view) -->
                <template v-if="!showTrashed && editingLink?.id === link.id">
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
                                >
                                    URL
                                </Label>
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
                                >
                                    Titel
                                </Label>
                                <Input
                                    :id="`edit-title-${link.id}`"
                                    name="title"
                                    :default-value="link.title"
                                    placeholder="Link Titel"
                                />
                                <InputError :message="errors.title" />
                            </div>

                            <div class="flex flex-col gap-2">
                                <Label
                                    :for="`edit-desc-${link.id}`"
                                    class="sr-only"
                                >
                                    Beschreibung
                                </Label>
                                <Textarea
                                    :id="`edit-desc-${link.id}`"
                                    name="description"
                                    :default-value="link.description ?? ''"
                                    placeholder="Optionale Beschreibung"
                                    class="resize-none"
                                    rows="2"
                                />
                                <InputError :message="errors.description" />
                            </div>

                            <div class="flex flex-col gap-2">
                                <Label
                                    :for="`edit-notes-${link.id}`"
                                    class="sr-only"
                                >
                                    Notizen (privat)
                                </Label>
                                <Textarea
                                    :id="`edit-notes-${link.id}`"
                                    name="notes"
                                    :default-value="link.notes ?? ''"
                                    placeholder="Private Notizen"
                                    class="resize-none"
                                    rows="2"
                                />
                                <InputError :message="errors.notes" />
                            </div>
                        </div>

                        <div class="flex flex-wrap gap-6">
                            <div class="flex flex-col gap-2">
                                <Label :for="`edit-bucket-${link.id}`">
                                    Bucket
                                </Label>
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
                            >
                                Speichern
                            </Button>
                            <Button
                                type="button"
                                size="sm"
                                variant="outline"
                                @click="cancelEdit"
                            >
                                Abbrechen
                            </Button>
                        </div>
                    </Form>
                </template>

                <template v-else>
                    <div
                        class="group relative"
                        :class="showTrashed ? 'opacity-60' : ''"
                    >
                        <LinkCard
                            :title="link.title"
                            :url="link.url"
                            :description="link.description"
                            :favicon_url="link.favicon_url"
                            :bucket="link.bucket"
                            :tags="link.tags"
                        />
                        <div
                            class="absolute top-3 right-3 flex gap-1 opacity-0 transition-opacity group-hover:opacity-100"
                        >
                            <!-- Normal actions -->
                            <template v-if="!showTrashed">
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
                            </template>

                            <!-- Trash actions -->
                            <template v-else>
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    class="size-7"
                                    :aria-label="`Restore ${link.title}`"
                                    @click="restoreLink(link)"
                                >
                                    <RotateCcw class="size-3.5" />
                                </Button>
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    class="size-7"
                                    :aria-label="`Permanently delete ${link.title}`"
                                    @click="confirmForceDelete(link)"
                                >
                                    <Trash2 class="size-3.5 text-destructive" />
                                </Button>
                            </template>
                        </div>
                    </div>
                </template>
            </li>
        </ul>

        <p v-if="links.data.length === 0" class="text-sm text-muted-foreground">
            {{
                showTrashed
                    ? 'Keine gelöschten Links.'
                    : hasActiveFilters()
                      ? 'Keine Links entsprechen deinen Filtern.'
                      : 'Noch keine Links. Füge deinen ersten Link oben hinzu.'
            }}
        </p>

        <!-- Pagination -->
        <Pagination v-if="links.last_page > 1" :items="links" />
    </div>

    <ConfirmModal
        :open="deleteTarget !== null"
        title="Link löschen?"
        :description="`'${deleteTarget?.title}' wird gelöscht. Diese Aktion kann rückgängig gemacht werden.`"
        confirm-label="Löschen"
        @update:open="
            (val) => {
                if (!val) deleteTarget = null;
            }
        "
        @confirm="deleteLink"
    />

    <ConfirmModal
        :open="forceDeleteTarget !== null"
        title="Endgültig löschen?"
        :description="`'${forceDeleteTarget?.title}' wird permanent gelöscht und kann nicht wiederhergestellt werden.`"
        confirm-label="Endgültig löschen"
        @update:open="
            (val) => {
                if (!val) forceDeleteTarget = null;
            }
        "
        @confirm="forceDeleteLink"
    />
</template>
