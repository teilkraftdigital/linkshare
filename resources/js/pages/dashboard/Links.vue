<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import { CheckSquare, Trash2 } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import LinkController from '@/actions/App/Http/Controllers/Dashboard/LinkController';
import DeleteBulkLinksController from '@/actions/App/Http/Controllers/Dashboard/BulkActions/DeleteBulkLinksController';
import ForceDeleteBulkLinksController from '@/actions/App/Http/Controllers/Dashboard/BulkActions/ForceDeleteBulkLinksController';
import MoveBulkBucketController from '@/actions/App/Http/Controllers/Dashboard/BulkActions/MoveBulkBucketController';
import RestoreBulkLinksController from '@/actions/App/Http/Controllers/Dashboard/BulkActions/RestoreBulkLinksController';
import BulkActionBar from '@/components/links/BulkActionBar.vue';
import BulkMoveBucketModal from '@/components/links/BulkMoveBucketModal.vue';
import BulkSelectRow from '@/components/links/BulkSelectRow.vue';
import LinkCreateForm from '@/components/links/LinkCreateForm.vue';
import LinkFilter from '@/components/links/LinkFilter.vue';
import LinkItem from '@/components/links/LinkItem.vue';
import ConfirmModal from '@/components/shared/ConfirmModal.vue';
import Heading from '@/components/shared/Heading.vue';
import Pagination from '@/components/shared/Pagination.vue';
import { Button } from '@/components/ui/button';
import { useBulkSelection } from '@/composables/useBulkSelection';
import { useDuplicateCheck } from '@/composables/useDuplicateCheck';
import { useToast } from '@/composables/useToast';
import { i18n } from '@/i18n';
import { index } from '@/routes/dashboard/links';
import type { Bucket, Link, Paginator, Tag, Filters } from '@/types/dashboard';

type Props = {
    links: Paginator<Link>;
    buckets: Bucket[];
    tags: Tag[];
    inboxBucketId: number;
    filters: Filters;
    showTrashed: boolean;
};

const props = defineProps<Props>();

const { t } = useI18n();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: i18n.global.t('links.pageTitle'),
                href: index(),
            },
        ],
    },
});

const { toast } = useToast();

// — Bulk selection —
const {
    bulkMode,
    selectedCount,
    toggleMode,
    toggleId,
    selectAll,
    clearSelection,
    isSelected,
    selectedIds,
    getSelectedIds,
} = useBulkSelection();

const pageIds = computed(() => props.links.data.map((l) => l.id));

// Clear selection on pagination change
watch(
    () => props.links.current_page,
    () => clearSelection(),
);

function bulkDelete() {
    const ids = getSelectedIds();
    router.delete(DeleteBulkLinksController.url(), {
        data: { link_ids: ids },
        preserveScroll: true,
        onSuccess: () => {
            toast(t('links.bulk.deleted', ids.length), 'success');
            clearSelection();
        },
    });
}

function bulkRestore() {
    const ids = getSelectedIds();
    router.post(
        RestoreBulkLinksController.url(),
        { link_ids: ids },
        {
            preserveScroll: true,
            onSuccess: () => {
                toast(t('links.bulk.restored', ids.length), 'success');
                clearSelection();
            },
        },
    );
}

const bulkMoveBucketOpen = ref(false);

function bulkMoveBucket(bucketId: number) {
    const ids = getSelectedIds();
    const bucket = props.buckets.find((b) => b.id === bucketId);
    router.patch(
        MoveBulkBucketController.url(),
        { link_ids: ids, bucket_id: bucketId },
        {
            preserveScroll: true,
            onSuccess: () => {
                bulkMoveBucketOpen.value = false;
                toast(
                    `${t('links.bulk.moveBucket.moved', ids.length)} → ${bucket?.name}`,
                    'success',
                );
                clearSelection();
            },
        },
    );
}

const bulkForceDeleteConfirmOpen = ref(false);

function bulkForceDelete() {
    const ids = getSelectedIds();
    router.delete(ForceDeleteBulkLinksController.url(), {
        data: { link_ids: ids },
        preserveScroll: true,
        onSuccess: () => {
            bulkForceDeleteConfirmOpen.value = false;
            toast(t('links.bulk.forceDeleted', ids.length), 'success');
            clearSelection();
        },
    });
}

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
const deleteTarget = ref<Link | null>(null);
const forceDeleteTarget = ref<Link | null>(null);
const refetchingLinkId = ref<number | null>(null);

const page = usePage();

watch(
    () => page.props.flash,
    (flash) => {
        if ((flash as Record<string, unknown>)?.refetch_success) {
            toast(t('links.metaUpdated'), 'success');
        } else if ((flash as Record<string, unknown>)?.refetch_failed) {
            toast(t('links.metaFailed'), 'destructive');
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
    if (searchTimer) {
        clearTimeout(searchTimer);
    }

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
function confirmDelete(link: Link) {
    deleteTarget.value = link;
}

function deleteLink() {
    if (!deleteTarget.value) {
        return;
    }

    router.delete(LinkController.destroy.url(deleteTarget.value), {
        preserveScroll: true,
        onSuccess: () => {
            deleteTarget.value = null;
            toast(t('links.deleted'), 'success');
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
            onSuccess: () => toast(t('links.restored'), 'success'),
        },
    );
}

function confirmForceDelete(link: Link) {
    forceDeleteTarget.value = link;
}

function forceDeleteLink() {
    if (!forceDeleteTarget.value) {
        return;
    }

    router.delete(LinkController.forceDelete.url(forceDeleteTarget.value), {
        preserveScroll: true,
        onSuccess: () => {
            forceDeleteTarget.value = null;
            toast(t('links.forceDeleted'), 'success');
        },
    });
}
</script>

<template>
    <Head :title="t('links.pageTitle')" />

    <div class="flex flex-col gap-8 p-4">
        <div class="flex items-start justify-between gap-4">
            <Heading
                :title="t('links.pageTitle')"
                :description="t('links.description')"
            />
            <div class="flex items-center gap-1">
                <Button
                    variant="ghost"
                    size="sm"
                    :class="bulkMode ? 'text-primary' : 'text-muted-foreground'"
                    @click="toggleMode"
                >
                    <CheckSquare class="size-4" />
                    {{
                        bulkMode
                            ? t('links.bulk.cancelMode')
                            : t('links.bulk.toggleMode')
                    }}
                </Button>
                <Button
                    variant="ghost"
                    size="sm"
                    :class="
                        showTrashed
                            ? 'text-destructive'
                            : 'text-muted-foreground'
                    "
                    @click="toggleTrashed"
                >
                    <Trash2 class="size-4" />
                    {{ t('common.trash') }}
                </Button>
            </div>
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
            :title="t('links.duplicate.title')"
            :description="t('links.duplicate.description')"
            :confirm-label="t('links.duplicate.confirm')"
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

        <!-- Bulk select row -->
        <BulkSelectRow
            v-if="bulkMode"
            :selected-count="selectedCount"
            :total="links.data.length"
            @select-all="selectAll(pageIds)"
            @clear-all="clearSelection"
        />

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
                :bulk-mode="bulkMode"
                :selected="isSelected(link.id)"
                @confirm-delete="confirmDelete"
                @restore="restoreLink"
                @confirm-force-delete="confirmForceDelete"
                @refetch-meta="refetchMeta"
                @toggle-select="toggleId"
            />
        </ul>

        <p v-if="links.data.length === 0" class="text-sm text-muted-foreground">
            {{
                showTrashed
                    ? t('links.emptyTrashed')
                    : hasActiveFilters
                      ? t('links.emptyFiltered')
                      : t('links.empty')
            }}
        </p>

        <!-- Pagination -->
        <Pagination v-if="links.last_page > 1" :items="links" />
    </div>

    <ConfirmModal
        :open="deleteTarget !== null"
        :title="t('links.delete.title')"
        :description="
            t('links.delete.description', { title: deleteTarget?.title })
        "
        :confirm-label="t('links.delete.confirm')"
        @update:open="
            (val) => {
                if (!val) deleteTarget = null;
            }
        "
        @confirm="deleteLink"
    />

    <!-- Bulk action bar -->
    <BulkActionBar
        v-if="bulkMode && selectedCount > 0"
        :selected-count="selectedCount"
        :show-trashed="showTrashed"
        @close="toggleMode"
        @bulk-delete="bulkDelete"
        @bulk-restore="bulkRestore"
        @bulk-move-bucket="bulkMoveBucketOpen = true"
        @bulk-force-delete="bulkForceDeleteConfirmOpen = true"
    />

    <!-- Bulk move bucket modal -->
    <BulkMoveBucketModal
        :open="bulkMoveBucketOpen"
        :buckets="buckets"
        @update:open="bulkMoveBucketOpen = $event"
        @confirm="bulkMoveBucket"
    />

    <!-- Bulk force delete confirm -->
    <ConfirmModal
        :open="bulkForceDeleteConfirmOpen"
        :title="t('links.bulk.forceDeleteDialog.title')"
        :description="t('links.bulk.forceDeleteDialog.description', selectedCount)"
        :confirm-label="t('links.bulk.forceDeleteDialog.confirm')"
        @update:open="(val) => { if (!val) bulkForceDeleteConfirmOpen = false; }"
        @confirm="bulkForceDelete"
    />

    <ConfirmModal
        :open="forceDeleteTarget !== null"
        :title="t('links.forceDeleteDialog.title')"
        :description="
            t('links.forceDeleteDialog.description', {
                title: forceDeleteTarget?.title,
            })
        "
        :confirm-label="t('links.forceDeleteDialog.confirm')"
        @update:open="
            (val) => {
                if (!val) forceDeleteTarget = null;
            }
        "
        @confirm="forceDeleteLink"
    />
</template>
