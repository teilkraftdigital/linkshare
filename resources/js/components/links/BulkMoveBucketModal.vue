<script setup lang="ts">
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';
import type { Bucket } from '@/types/dashboard';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

const { t } = useI18n();

type Props = {
    open: boolean;
    buckets: Bucket[];
};

defineProps<Props>();

const emit = defineEmits<{
    'update:open': [value: boolean];
    confirm: [bucketId: number];
}>();

const selectedBucketId = ref<string>('');

function handleConfirm() {
    if (!selectedBucketId.value) {
        return;
    }
    emit('confirm', Number(selectedBucketId.value));
    selectedBucketId.value = '';
}

function handleOpenChange(val: boolean) {
    if (!val) {
        selectedBucketId.value = '';
    }
    emit('update:open', val);
}
</script>

<template>
    <Dialog :open="open" @update:open="handleOpenChange">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>{{ t('links.bulk.moveBucket.modalTitle') }}</DialogTitle>
            </DialogHeader>

            <Select v-model="selectedBucketId">
                <SelectTrigger>
                    <SelectValue :placeholder="t('links.bulk.moveBucket.selectPlaceholder')" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem
                        v-for="bucket in buckets"
                        :key="bucket.id"
                        :value="String(bucket.id)"
                    >
                        {{ bucket.name }}
                    </SelectItem>
                </SelectContent>
            </Select>

            <DialogFooter>
                <Button variant="outline" @click="handleOpenChange(false)">
                    {{ t('confirmModal.cancel') }}
                </Button>
                <Button :disabled="!selectedBucketId" @click="handleConfirm">
                    {{ t('links.bulk.moveBucket.confirm') }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
