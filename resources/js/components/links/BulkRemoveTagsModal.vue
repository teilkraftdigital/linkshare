<script setup lang="ts">
import { X } from 'lucide-vue-next';
import { useI18n } from 'vue-i18n';
import type { Tag } from '@/types/dashboard';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { COLOR_BG, COLOR_BG_OPACITY, HAS_COLOR } from '@/lib/colors';

const { t } = useI18n();

type TagWithCount = Tag & { count: number };

type Props = {
    open: boolean;
    tags: TagWithCount[];
};

defineProps<Props>();

const emit = defineEmits<{
    'update:open': [value: boolean];
    remove: [tagId: number];
}>();
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>{{ t('links.bulk.removeTags.modalTitle') }}</DialogTitle>
            </DialogHeader>

            <p v-if="tags.length === 0" class="text-sm text-muted-foreground">
                {{ t('links.bulk.removeTags.empty') }}
            </p>

            <div v-else class="flex flex-wrap gap-2">
                <button
                    v-for="tag in tags"
                    :key="tag.id"
                    type="button"
                    class="flex items-center gap-1.5 rounded-full px-2 py-1 text-sm transition-opacity hover:opacity-70"
                    :class="HAS_COLOR(tag.color) ? COLOR_BG_OPACITY[tag.color] : 'bg-gray-100 dark:bg-gray-800'"
                    :aria-label="`${tag.name} entfernen`"
                    @click="emit('remove', tag.id)"
                >
                    <span
                        class="size-2 shrink-0 rounded-full"
                        :class="COLOR_BG[tag.color] ?? 'bg-gray-400'"
                    />
                    <span>{{ tag.name }}</span>
                    <span class="text-xs text-muted-foreground">{{ tag.count }}</span>
                    <X class="size-3" />
                </button>
            </div>

            <div class="flex justify-end">
                <Button variant="outline" @click="emit('update:open', false)">
                    {{ t('common.close') }}
                </Button>
            </div>
        </DialogContent>
    </Dialog>
</template>
