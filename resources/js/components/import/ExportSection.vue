<script setup lang="ts">
import { Download } from 'lucide-vue-next';
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { Button } from '@/components/ui/button';
import type { Bucket, Tag } from '@/types/dashboard';
import ExportModal from './ExportModal.vue';

defineProps<{
    buckets: Bucket[];
    tags: Tag[];
}>();

const open = ref(false);
const { t } = useI18n();
</script>

<template>
    <ExportModal v-model:open="open" :buckets="buckets" :tags="tags" />

    <div class="space-y-3">
        <div>
            <h2 class="text-sm font-semibold">
                {{ t('import.export.title') }}
            </h2>
            <p class="mt-0.5 text-sm text-muted-foreground">
                {{ t('import.export.description') }}
            </p>
        </div>
        <Button variant="outline" @click="open = true">
            <Download class="mr-2 size-4" />
            {{ t('import.export.button') }}
        </Button>
    </div>
</template>
