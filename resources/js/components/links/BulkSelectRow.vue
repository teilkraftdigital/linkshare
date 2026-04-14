<script setup lang="ts">
import { useI18n } from 'vue-i18n';
import { Checkbox } from '@/components/ui/checkbox';

const { t } = useI18n();

type Props = {
    selectedCount: number;
    total: number;
};

defineProps<Props>();

const emit = defineEmits<{
    'select-all': [];
    'clear-all': [];
}>();

function onCheckedChange(val: boolean | 'indeterminate') {
    if (val === true) {
        emit('select-all');
    } else {
        emit('clear-all');
    }
}
</script>

<template>
    <div class="flex items-center gap-3 text-sm text-muted-foreground">
        <Checkbox
            :model-value="selectedCount > 0 && selectedCount === total ? true : selectedCount > 0 ? 'indeterminate' : false"
            :aria-label="t('links.bulk.selectAllAriaLabel')"
            @update:model-value="onCheckedChange"
        />
        <span>{{ t('links.bulk.selectedCount', { count: selectedCount, total }) }}</span>
    </div>
</template>
