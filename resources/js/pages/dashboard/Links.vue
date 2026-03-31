<script setup lang="ts">
import { Form, Head, router, usePage } from '@inertiajs/vue3';
import { Trash2 } from 'lucide-vue-next';
import { ref, watch } from 'vue';
import LinkController from '@/actions/App/Http/Controllers/Dashboard/LinkController';
import { useDuplicateCheck } from '@/composables/useDuplicateCheck';
import { useToast } from '@/composables/useToast';
import ConfirmModal from '@/components/shared/ConfirmModal.vue';
import Heading from '@/components/shared/Heading.vue';
import InputError from '@/components/shared/InputError.vue';
import LinkCard from '@/components/links/LinkCard.vue';
import TagSelect from '@/components/links/TagSelect.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { index } from '@/routes/dashboard/links';
import Pagination from '@/components/shared/Pagination.vue';
import type { Bucket, Link, Paginator, Tag, Filters } from '@/types/dashboard';
import LinkFilter from '@/components/links/LinkFilter.vue';
import LinkNormalAction from '@/components/links/LinkNormalAction.vue';
import LinkTrashAction from '@/components/links/LinkTrashAction.vue';
import LinkCreateForm from '@/components/links/LinkCreateForm.vue';
import LinkItem from '@/components/links/LinkItem.vue';

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

const { exists: duplicateExists } = useDuplicateCheck();

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
const refetchingLinkId = ref<number | null>(null);

const page = usePage();

watch(
    () => page.props.flash,
    (flash) => {
        if ((flash as Record<string, unknown>)?.refetch_success) {
            toast('Metadaten aktualisiert', 'success');
        } else if ((flash as Record<string, unknown>)?.refetch_failed) {
            toast('Metadaten konnten nicht abgerufen werden', 'destructive');
        }
    },
);

function refetchMeta(link: Link) {
    refetchingLinkId.value = link.id;
    router.post(
        LinkController.refetchMeta.url(link),
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                refetchingLinkId.value = null;
            },
        },
    );
}

// — Filters —

const filterSearch = ref(props.filters.search ?? '');
const filterBucketId = ref(props.filters.bucket_id ?? '');
const filterTagId = ref(props.filters.tag_id ?? '');
const hasActiveFilters = ref(false);

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
    applyFilters(true);
}

watch(filterBucketId, () => applyFilters(true));
watch(filterTagId, () => applyFilters(true));
watch(filterSearch, () => applyFilters(false));

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
        <LinkCreateForm
            v-if="!showTrashed"
            :buckets="buckets"
            :tags="tags"
            :inbox-bucket-id="inboxBucketId"
            @created="handleCreateSubmit"
        />

        <ConfirmModal
            :open="duplicateConfirmOpen"
            title="Link bereits vorhanden"
            description="Ein Link mit dieser URL ist bereits gespeichert. Trotzdem hinzufügen?"
            confirm-label="Trotzdem hinzufügen"
            @update:open="duplicateConfirmOpen = $event"
            @confirm="confirmDuplicateSubmit"
            @clear="clearFilters"
        />

        <!-- Filters (hidden in trash view) -->
        <LinkFilter
            v-if="!showTrashed"
            :buckets="buckets"
            :tags="tags"
            v-model:search="filterSearch"
            v-model:bucket_id="filterBucketId"
            v-model:tag_id="filterTagId"
            v-model:hasActiveFilters="hasActiveFilters"
            @clear="clearFilters"
        />

        <!-- Pagination -->
        <Pagination v-if="links.last_page > 1" :items="links" />

        <!-- Link list -->
        <ul class="flex flex-col gap-2">
            <LinkItem
                v-for="link in links.data"
                :key="link.id"
                :link="link"
                :buckets="buckets"
                :tags="tags"
                :showTrashed="showTrashed"
                :refetchingLinkId="refetchingLinkId"
                @start-edit="startEdit"
                @confirm-delete="confirmDelete"
                @restore="restoreLink"
                @confirm-force-delete="confirmForceDelete"
                @refetch-meta="refetchMeta"
            />
        </ul>

        <p v-if="links.data.length === 0" class="text-sm text-muted-foreground">
            {{
                showTrashed
                    ? 'Keine gelöschten Links.'
                    : hasActiveFilters
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
