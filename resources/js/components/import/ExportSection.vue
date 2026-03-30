<script setup lang="ts">
import { Download } from 'lucide-vue-next';
import { ref } from 'vue';
import ExportModal from './ExportModal.vue';
import { Button } from '@/components/ui/button';
import type { Bucket, Tag } from '@/types/dashboard';

defineProps<{
    buckets: Bucket[];
    tags: Tag[];
}>();

const open = ref(false);
</script>

<template>
    <ExportModal v-model:open="open" :buckets="buckets" :tags="tags" />

    <div class="space-y-3">
        <div>
            <h2 class="text-sm font-semibold">Exportieren</h2>
            <p class="mt-0.5 text-sm text-muted-foreground">
                Alle aktiven Links, Buckets und Tags als JSON-Datei herunterladen.
                Einträge im Papierkorb sind nicht enthalten.
            </p>
        </div>
        <Button variant="outline" @click="open = true">
            <Download class="mr-2 size-4" />
            Exportieren
        </Button>
    </div>
</template>
