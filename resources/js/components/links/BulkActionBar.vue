<script setup lang="ts">
import { FolderInput, RotateCcw, Trash2, X } from 'lucide-vue-next';
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
    'bulk-delete': [];
    'bulk-restore': [];
    'bulk-force-delete': [];
    'bulk-move-bucket': [];
}>();
</script>

<template>
    <div class="fixed inset-x-0 bottom-0 z-50 border-t bg-background shadow-lg">
        <div class="mx-auto flex max-w-5xl items-center justify-between gap-4 px-4 py-3">
            <span class="text-sm font-medium">
                {{ t('links.bulk.selectedLinks', { count: selectedCount }) }}
            </span>

            <div class="flex items-center gap-2">
                <!-- Normal view actions -->
                <template v-if="!showTrashed">
                    <slot name="normal-actions" />
                    <Button
                        variant="ghost"
                        size="sm"
                        @click="$emit('bulk-move-bucket')"
                    >
                        <FolderInput class="size-4" />
                        {{ t('links.bulk.moveBucket.button') }}
                    </Button>
                    <Button
                        variant="ghost"
                        size="sm"
                        class="text-destructive hover:text-destructive"
                        @click="$emit('bulk-delete')"
                    >
                        <Trash2 class="size-4" />
                        {{ t('common.delete') }}
                    </Button>
                </template>

                <!-- Trash view actions -->
                <template v-else>
                    <slot name="trash-actions" />
                    <Button
                        variant="ghost"
                        size="sm"
                        @click="$emit('bulk-restore')"
                    >
                        <RotateCcw class="size-4" />
                        {{ t('common.restore') }}
                    </Button>
                    <Button
                        variant="ghost"
                        size="sm"
                        class="text-destructive hover:text-destructive"
                        @click="$emit('bulk-force-delete')"
                    >
                        <Trash2 class="size-4" />
                        {{ t('common.forceDelete') }}
                    </Button>
                </template>

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
