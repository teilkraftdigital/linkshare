<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { Trash2 } from 'lucide-vue-next';
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';
import TagController from '@/actions/App/Http/Controllers/Dashboard/TagController';
import ConfirmModal from '@/components/shared/ConfirmModal.vue';
import Heading from '@/components/shared/Heading.vue';
import TagCreateForm from '@/components/tags/TagCreateForm.vue';
import TagItem from '@/components/tags/TagItem.vue';
import { Button } from '@/components/ui/button';
import { useToast } from '@/composables/useToast';
import { i18n } from '@/i18n';
import { index } from '@/routes/dashboard/tags';
import type { Tag } from '@/types/dashboard';

type Props = {
    tags: Tag[];
    showTrashed: boolean;
};

const props = defineProps<Props>();

const { t } = useI18n();

defineOptions({
    layout: {
        breadcrumbs: [{ title: i18n.global.t('tags.pageTitle'), href: index() }],
    },
});

const { toast } = useToast();

const deleteTarget = ref<Tag | null>(null);
const forceDeleteTarget = ref<Tag | null>(null);

function toggleTrashed() {
    router.get(index(), props.showTrashed ? {} : { trashed: '1' }, {
        preserveState: false,
    });
}

function confirmDelete(tag: Tag) {
    deleteTarget.value = tag;
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

function restoreTag(tag: Tag) {
    router.post(
        TagController.restore.url(tag),
        {},
        {
            preserveScroll: true,
            onSuccess: () => toast(t('tags.restored'), 'success'),
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
        <TagCreateForm v-if="!showTrashed" />

        <!-- Tag list -->
        <ul class="flex flex-col gap-2">
            <TagItem
                v-for="tag in tags"
                :key="tag.id"
                :tag="tag"
                :showTrashed="showTrashed"
                @confirm-delete="confirmDelete"
                @restore="restoreTag"
                @force-delete="confirmForceDelete"
            />
        </ul>

        <p v-if="tags.length === 0" class="text-sm text-muted-foreground">
            {{
                showTrashed ? t('tags.emptyTrashed') : t('tags.empty')
            }}
        </p>
    </div>

    <ConfirmModal
        :open="deleteTarget !== null"
        :title="t('tags.delete.title')"
        :description="t('tags.delete.description', { name: deleteTarget?.name })"
        :confirm-label="t('tags.delete.confirm')"
        @update:open="
            (val) => {
                if (!val) deleteTarget = null;
            }
        "
        @confirm="deleteTag"
    />

    <ConfirmModal
        :open="forceDeleteTarget !== null"
        :title="t('tags.forceDeleteDialog.title')"
        :description="t('tags.forceDeleteDialog.description', { name: forceDeleteTarget?.name })"
        :confirm-label="t('tags.forceDeleteDialog.confirm')"
        @update:open="
            (val) => {
                if (!val) forceDeleteTarget = null;
            }
        "
        @confirm="forceDeleteTag"
    />
</template>
