<script setup lang="ts">
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';
import type { Tag } from '@/types/dashboard';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { COLOR_BG, COLOR_BG_OPACITY, HAS_COLOR } from '@/lib/colors';

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

const selectedTagIds = ref<Set<number>>(new Set());

function toggleTag(id: number) {
    const next = new Set(selectedTagIds.value);
    if (next.has(id)) {
        next.delete(id);
    } else {
        next.add(id);
    }
    selectedTagIds.value = next;
}

function handleConfirm() {
    emit('confirm', Array.from(selectedTagIds.value));
    selectedTagIds.value = new Set();
}

function handleOpenChange(val: boolean) {
    if (!val) {
        selectedTagIds.value = new Set();
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

            <div class="flex max-h-64 flex-col gap-2 overflow-y-auto">
                <label
                    v-for="tag in tags"
                    :key="tag.id"
                    class="flex cursor-pointer items-center gap-3 rounded-md px-2 py-1.5 hover:bg-muted"
                >
                    <Checkbox
                        :model-value="selectedTagIds.has(tag.id)"
                        @update:model-value="toggleTag(tag.id)"
                    />
                    <span
                        class="flex items-center gap-1.5 rounded-full px-2 py-0.5 text-sm"
                        :class="HAS_COLOR(tag.color) ? COLOR_BG_OPACITY[tag.color] : 'bg-gray-100 dark:bg-gray-800'"
                    >
                        <span
                            class="size-2 rounded-full"
                            :class="COLOR_BG[tag.color] ?? 'bg-gray-400'"
                        />
                        {{ tag.name }}
                    </span>
                </label>
            </div>

            <DialogFooter>
                <Button variant="outline" @click="handleOpenChange(false)">
                    {{ t('confirmModal.cancel') }}
                </Button>
                <Button :disabled="selectedTagIds.size === 0" @click="handleConfirm">
                    {{ t('links.bulk.addTags.confirm') }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
