<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { Trash2 } from 'lucide-vue-next';
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';
import TagController from '@/actions/App/Http/Controllers/Dashboard/TagController';
import ConfirmModal from '@/components/shared/ConfirmModal.vue';
import Heading from '@/components/shared/Heading.vue';
import TagCreateForm from '@/components/tags/TagCreateForm.vue';
import TagDeleteWithChildrenModal from '@/components/tags/TagDeleteWithChildrenModal.vue';
import TagItem from '@/components/tags/TagItem.vue';
import TagRestoreOrphanModal from '@/components/tags/TagRestoreOrphanModal.vue';
import TagRestoreWithChildrenModal from '@/components/tags/TagRestoreWithChildrenModal.vue';
import { Button } from '@/components/ui/button';
import { useToast } from '@/composables/useToast';
import { i18n } from '@/i18n';
import { index } from '@/routes/dashboard/tags';
import type { Tag } from '@/types/dashboard';

type Props = {
    tags: Tag[];
    rootTags: Tag[];
    showTrashed: boolean;
};

const props = defineProps<Props>();

const { t } = useI18n();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: i18n.global.t('tags.pageTitle'), href: index() },
        ],
    },
});

const { toast } = useToast();

// Delete
const deleteTarget = ref<Tag | null>(null);
const deleteWithChildrenTarget = ref<Tag | null>(null);

// Force delete
const forceDeleteTarget = ref<Tag | null>(null);

// Restore
const restoreWithChildrenTarget = ref<Tag | null>(null);
const restoreOrphanTarget = ref<Tag | null>(null);

// When user clicks "Add child" on a tag card — pre-fill create form with parent
const addChildParentId = ref<number | null>(null);

function toggleTrashed() {
    router.get(index(), props.showTrashed ? {} : { trashed: '1' }, {
        preserveState: false,
    });
}

function confirmDelete(tag: Tag) {
    if ((tag.children?.length ?? 0) > 0) {
        deleteWithChildrenTarget.value = tag;
    } else {
        deleteTarget.value = tag;
    }
}

function deleteTag() {
    if (!deleteTarget.value) {
        return;
    }

    router.delete(TagController.destroy.url(deleteTarget.value), {
        preserveScroll: true,
        onSuccess: () => {
            deleteTarget.value = null;
            toast(t('tags.deleted'), 'success');
        },
    });
}

function deleteTagCascade() {
    if (!deleteWithChildrenTarget.value) {
        return;
    }

    router.delete(TagController.destroy.url(deleteWithChildrenTarget.value), {
        data: { cascade: true },
        preserveScroll: true,
        onSuccess: () => {
            deleteWithChildrenTarget.value = null;
            toast(t('tags.deleted'), 'success');
        },
    });
}

function deleteTagOrphan() {
    if (!deleteWithChildrenTarget.value) {
        return;
    }

    router.delete(TagController.destroy.url(deleteWithChildrenTarget.value), {
        data: { cascade: false },
        preserveScroll: true,
        onSuccess: () => {
            deleteWithChildrenTarget.value = null;
            toast(t('tags.deleted'), 'success');
        },
    });
}

function restoreTag(tag: Tag) {
    const trashedChildren = tag.children ?? [];

    if (trashedChildren.length > 0) {
        restoreWithChildrenTarget.value = tag;
    } else if (tag.parent_trashed) {
        restoreOrphanTarget.value = tag;
    } else {
        router.post(
            TagController.restore.url(tag),
            {},
            {
                preserveScroll: true,
                onSuccess: () => toast(t('tags.restored'), 'success'),
            },
        );
    }
}

function restoreTagWithChildren(childIds: number[]) {
    if (!restoreWithChildrenTarget.value) {
        return;
    }

    router.post(
        TagController.restore.url(restoreWithChildrenTarget.value),
        { child_ids: childIds },
        {
            preserveScroll: true,
            onSuccess: () => {
                restoreWithChildrenTarget.value = null;
                toast(t('tags.restored'), 'success');
            },
        },
    );
}

function restoreTagAsOrphan() {
    if (!restoreOrphanTarget.value) {
        return;
    }

    router.post(
        TagController.restore.url(restoreOrphanTarget.value),
        { orphan: true },
        {
            preserveScroll: true,
            onSuccess: () => {
                restoreOrphanTarget.value = null;
                toast(t('tags.restored'), 'success');
            },
        },
    );
}

function confirmForceDelete(tag: Tag) {
    forceDeleteTarget.value = tag;
}

function forceDeleteTag() {
    if (!forceDeleteTarget.value) {
        return;
    }

    router.delete(TagController.forceDelete.url(forceDeleteTarget.value), {
        preserveScroll: true,
        onSuccess: () => {
            forceDeleteTarget.value = null;
            toast(t('tags.forceDeleted'), 'success');
        },
    });
}

function handleAddChild(parentTag: Tag) {
    addChildParentId.value = parentTag.id;
    // Scroll create form into view
    document
        .getElementById('tag-create-form')
        ?.scrollIntoView({ behavior: 'smooth' });
}
</script>

<template>
    <Head :title="t('tags.pageTitle')" />

    <div class="flex flex-col gap-8 p-4">
        <div class="flex items-start justify-between gap-4">
            <Heading
                :title="t('tags.pageTitle')"
                :description="t('tags.description')"
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

        <!-- Create form (hidden in trash view) -->
        <div v-if="!showTrashed" id="tag-create-form">
            <TagCreateForm
                :root-tags="rootTags"
                :prefill-parent-id="addChildParentId"
                @success="addChildParentId = null"
            />
        </div>

        <!-- Tag list -->
        <ul class="flex flex-col gap-2">
            <TagItem
                v-for="tag in tags"
                :key="tag.id"
                :tag="tag"
                :show-trashed="showTrashed"
                @confirm-delete="confirmDelete"
                @restore="restoreTag"
                @force-delete="confirmForceDelete"
                @add-child="handleAddChild"
            />
        </ul>

        <p v-if="tags.length === 0" class="text-sm text-muted-foreground">
            {{ showTrashed ? t('tags.emptyTrashed') : t('tags.empty') }}
        </p>
    </div>

    <!-- Simple delete confirm -->
    <ConfirmModal
        :open="deleteTarget !== null"
        :title="t('tags.delete.title')"
        :description="
            t('tags.delete.description', { name: deleteTarget?.name })
        "
        :confirm-label="t('tags.delete.confirm')"
        @update:open="
            (val) => {
                if (!val) deleteTarget = null;
            }
        "
        @confirm="deleteTag"
    />

    <!-- Delete with children choice -->
    <TagDeleteWithChildrenModal
        :open="deleteWithChildrenTarget !== null"
        :tag="deleteWithChildrenTarget"
        @update:open="
            (val) => {
                if (!val) deleteWithChildrenTarget = null;
            }
        "
        @cascade="deleteTagCascade"
        @orphan="deleteTagOrphan"
    />

    <!-- Restore with children checkboxes -->
    <TagRestoreWithChildrenModal
        :open="restoreWithChildrenTarget !== null"
        :tag="restoreWithChildrenTarget"
        @update:open="
            (val) => {
                if (!val) restoreWithChildrenTarget = null;
            }
        "
        @confirm="restoreTagWithChildren"
    />

    <!-- Restore orphan child (parent also trashed) -->
    <TagRestoreOrphanModal
        :open="restoreOrphanTarget !== null"
        :tag="restoreOrphanTarget"
        @update:open="
            (val) => {
                if (!val) restoreOrphanTarget = null;
            }
        "
        @confirm="restoreTagAsOrphan"
    />

    <ConfirmModal
        :open="forceDeleteTarget !== null"
        :title="t('tags.forceDeleteDialog.title')"
        :description="
            t('tags.forceDeleteDialog.description', {
                name: forceDeleteTarget?.name,
            })
        "
        :confirm-label="t('tags.forceDeleteDialog.confirm')"
        @update:open="
            (val) => {
                if (!val) forceDeleteTarget = null;
            }
        "
        @confirm="forceDeleteTag"
    />
</template>
