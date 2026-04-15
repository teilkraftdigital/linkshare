<script setup lang="ts">
import { useI18n } from 'vue-i18n';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import type { Tag } from '@/types/dashboard';

const { t } = useI18n();

const props = defineProps<{
    open: boolean;
    tag: Tag | null;
}>();

const emit = defineEmits<{
    'update:open': [value: boolean];
    cascade: [];
    orphan: [];
}>();
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>{{ t('tags.deleteWithChildren.title', { name: tag?.name }) }}</DialogTitle>
                <DialogDescription>
                    {{ t('tags.deleteWithChildren.description', tag?.children?.length ?? 0) }}
                </DialogDescription>
            </DialogHeader>

            <DialogFooter class="flex-col gap-2 sm:flex-col">
                <Button variant="destructive" @click="emit('cascade')">
                    {{ t('tags.deleteWithChildren.cascade') }}
                </Button>
                <Button variant="outline" @click="emit('orphan')">
                    {{ t('tags.deleteWithChildren.orphan') }}
                </Button>
                <Button variant="ghost" @click="emit('update:open', false)">
                    {{ t('common.cancel') }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
