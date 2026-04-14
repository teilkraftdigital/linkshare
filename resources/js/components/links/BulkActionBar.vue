<script setup lang="ts">
import { X } from 'lucide-vue-next';
import { useI18n } from 'vue-i18n';
import { Button } from '@/components/ui/button';

const { t } = useI18n();

type Props = {
    selectedCount: number;
    showTrashed: boolean;
};

defineProps<Props>();

defineEmits<{
    close: [];
}>();
</script>

<template>
    <div class="fixed inset-x-0 bottom-0 z-50 border-t bg-background shadow-lg">
        <div class="mx-auto flex max-w-5xl items-center justify-between gap-4 px-4 py-3">
            <span class="text-sm font-medium">
                {{ t('links.bulk.selectedLinks', { count: selectedCount }) }}
            </span>

            <div class="flex items-center gap-2">
                <!-- Action slots will be filled in subsequent phases -->
                <slot />

                <Button
                    variant="ghost"
                    size="sm"
                    @click="$emit('close')"
                >
                    <X class="size-4" />
                    {{ t('common.cancel') }}
                </Button>
            </div>
        </div>
    </div>
</template>
