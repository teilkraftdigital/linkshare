<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { Trash2 } from 'lucide-vue-next';
import { ref } from 'vue';
import TagController from '@/actions/App/Http/Controllers/Dashboard/TagController';
import ConfirmModal from '@/components/shared/ConfirmModal.vue';
import Heading from '@/components/shared/Heading.vue';
import TagCreateForm from '@/components/tags/TagCreateForm.vue';
import TagItem from '@/components/tags/TagItem.vue';
import { Button } from '@/components/ui/button';
import { useToast } from '@/composables/useToast';
import { index } from '@/routes/dashboard/tags';
import type { Tag } from '@/types/dashboard';

type Props = {
    tags: Tag[];
    showTrashed: boolean;
};

const props = defineProps<Props>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Tags', href: index() }],
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
            toast('Tag gelöscht', 'success');
        },
    });
}

function restoreTag(tag: Tag) {
    router.post(
        TagController.restore.url(tag),
        {},
        {
            preserveScroll: true,
            onSuccess: () => toast('Tag wiederhergestellt', 'success'),
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
            toast('Tag endgültig gelöscht', 'success');
        },
    });
}
</script>

<template>
    <Head title="Tags" />

    <div class="flex flex-col gap-8 p-4">
        <div class="flex items-start justify-between gap-4">
            <Heading
                title="Tags"
                description="Verwalte deine Tags und deren Sichtbarkeit."
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
                showTrashed ? 'Keine gelöschten Tags.' : 'Keine Tags vorhanden.'
            }}
        </p>
    </div>

    <ConfirmModal
        :open="deleteTarget !== null"
        title="Tag Löschen?"
        :description="`Tag '${deleteTarget?.name}' löschen? Diese Aktion kann rückgängig gemacht werden.`"
        confirm-label="Löschen"
        @update:open="
            (val) => {
                if (!val) deleteTarget = null;
            }
        "
        @confirm="deleteTag"
    />

    <ConfirmModal
        :open="forceDeleteTarget !== null"
        title="Endgültig löschen?"
        :description="`'${forceDeleteTarget?.name}' wird permanent gelöscht und kann nicht wiederhergestellt werden.`"
        confirm-label="Endgültig löschen"
        @update:open="
            (val) => {
                if (!val) forceDeleteTarget = null;
            }
        "
        @confirm="forceDeleteTag"
    />
</template>
