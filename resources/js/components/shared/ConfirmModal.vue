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

const { t } = useI18n();

type Props = {
    open: boolean;
    title?: string;
    description?: string;
    confirmLabel?: string;
};

defineProps<Props>();

const emit = defineEmits<{
    'update:open': [value: boolean];
    confirm: [];
}>();
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>{{ title ?? t('confirmModal.defaultTitle') }}</DialogTitle>
                <DialogDescription>{{ description ?? t('confirmModal.defaultDescription') }}</DialogDescription>
            </DialogHeader>

            <DialogFooter>
                <Button variant="outline" @click="emit('update:open', false)">
                    {{ t('confirmModal.cancel') }}
                </Button>
                <Button variant="destructive" @click="emit('confirm')">
                    {{ confirmLabel ?? t('confirmModal.defaultLabel') }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
