<script setup lang="ts">
import { ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import type { Tag } from '@/types/dashboard';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import TagSelect from './TagSelect.vue';
import { useTagCreate } from '@/composables/useTagCreate';

const { t } = useI18n();
const { createError: tagCreateError, createTag } = useTagCreate();

type Props = {
    open: boolean;
    tags: Tag[];
};

const props = defineProps<Props>();

const emit = defineEmits<{
    'update:open': [value: boolean];
    confirm: [tagIds: number[]];
}>();

const localTags = ref<Tag[]>([...props.tags]);
const selectedTagIds = ref<number[]>([]);

watch(
    () => props.tags,
    (tags) => {
        localTags.value = [...tags];
    },
);

function handleConfirm() {
    emit('confirm', selectedTagIds.value);
    selectedTagIds.value = [];
}

function handleOpenChange(val: boolean) {
    if (!val) {
        selectedTagIds.value = [];
    }
    emit('update:open', val);
}

async function handleTagCreated(name: string) {
    const tag = await createTag(name);

    if (tag) {
        localTags.value = [...localTags.value, tag];
        selectedTagIds.value = [...selectedTagIds.value, tag.id];
    }
}
</script>

<template>
    <Dialog :open="open" @update:open="handleOpenChange">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>{{
                    t('links.bulk.addTags.modalTitle')
                }}</DialogTitle>
            </DialogHeader>

            <TagSelect
                :tags="localTags"
                v-model="selectedTagIds"
                :create-error="tagCreateError"
                @tag-created="handleTagCreated"
            />

            <DialogFooter>
                <Button variant="outline" @click="handleOpenChange(false)">
                    {{ t('confirmModal.cancel') }}
                </Button>
                <Button
                    :disabled="selectedTagIds.length === 0"
                    @click="handleConfirm"
                >
                    {{ t('links.bulk.addTags.confirm') }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
