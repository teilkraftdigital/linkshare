<script setup lang="ts">
import { ref } from 'vue';
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

const { t } = useI18n();

type Props = {
    open: boolean;
    tags: Tag[];
};

defineProps<Props>();

const emit = defineEmits<{
    'update:open': [value: boolean];
    confirm: [tagIds: number[]];
}>();

const selectedTagIds = ref<number[]>([]);

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
</script>

<template>
    <Dialog :open="open" @update:open="handleOpenChange">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>{{ t('links.bulk.addTags.modalTitle') }}</DialogTitle>
            </DialogHeader>

            <TagSelect
                :tags="tags"
                v-model="selectedTagIds"
            />

            <DialogFooter>
                <Button variant="outline" @click="handleOpenChange(false)">
                    {{ t('confirmModal.cancel') }}
                </Button>
                <Button :disabled="selectedTagIds.length === 0" @click="handleConfirm">
                    {{ t('links.bulk.addTags.confirm') }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
