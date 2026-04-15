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

defineProps<{
    open: boolean;
    tag: Tag | null;
}>();

const emit = defineEmits<{
    'update:open': [value: boolean];
    confirm: [];
}>();
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>{{ t('tags.restoreOrphan.title', { name: tag?.name }) }}</DialogTitle>
                <DialogDescription>{{ t('tags.restoreOrphan.description') }}</DialogDescription>
            </DialogHeader>

            <DialogFooter>
                <Button variant="outline" @click="emit('update:open', false)">
                    {{ t('common.cancel') }}
                </Button>
                <Button @click="emit('confirm')">
                    {{ t('tags.restoreOrphan.confirm') }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
