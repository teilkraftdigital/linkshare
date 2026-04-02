<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import { Trash2 } from 'lucide-vue-next';
import { ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { i18n } from '@/i18n';
import LinkController from '@/actions/App/Http/Controllers/Dashboard/LinkController';
import LinkCreateForm from '@/components/links/LinkCreateForm.vue';
import LinkFilter from '@/components/links/LinkFilter.vue';
import LinkItem from '@/components/links/LinkItem.vue';
import ConfirmModal from '@/components/shared/ConfirmModal.vue';
import Heading from '@/components/shared/Heading.vue';
import Pagination from '@/components/shared/Pagination.vue';
import { Button } from '@/components/ui/button';
import { useDuplicateCheck } from '@/composables/useDuplicateCheck';
import { useToast } from '@/composables/useToast';
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
            <Button
                variant="ghost"
                size="sm"
                :class="
                    showTrashed ? 'text-destructive' : 'text-muted-foreground'
                "
                @click="toggleTrashed"
            >
                <Trash2 class="size-4" />
                {{ t('common.trash') }}
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
                @confirm-delete="confirmDelete"
                @restore="restoreLink"
                @confirm-force-delete="confirmForceDelete"
                @refetch-meta="refetchMeta"
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
