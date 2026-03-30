<script setup lang="ts">
import { Trash2, RotateCcw } from 'lucide-vue-next';

import { Button } from '@/components/ui/button';
import type { Tag } from '@/types/dashboard';

type Props = {
    tag: Tag;
};

defineProps<Props>();

const events = defineEmits<{
    restore: [tag: Tag];
    'force-delete': [tag: Tag];
}>();

function restoreTag(tag: Tag) {
    events('restore', tag);
}

function confirmForceDelete(tag: Tag) {
    events('force-delete', tag);
}
</script>

<template>
    <Button
        variant="ghost"
        size="icon"
        :aria-label="`Wiederherstellen ${tag.name}`"
        @click="restoreTag(tag)"
    >
        <RotateCcw class="size-4" />
    </Button>

    <Button
        variant="ghost"
        size="icon"
        :aria-label="`Endgültig löschen ${tag.name}`"
        @click="confirmForceDelete(tag)"
    >
        <Trash2 class="size-4 text-destructive" />
    </Button>
</template>
