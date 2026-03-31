<script setup lang="ts">
import { Trash2, RotateCcw } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import type { Link } from '@/types/dashboard';

type Props = {
    link: Link;
};
const props = defineProps<Props>();

const events = defineEmits<{
    restore: [link: Link];
    forceDelete: [link: Link];
}>();

function restoreLink(link: Link) {
    events('restore', link);
}

function confirmForceDelete(link: Link) {
    events('forceDelete', link);
}
</script>

<template>
    <Button
        variant="ghost"
        size="icon"
        class="size-7"
        :aria-label="`Stelle ${link.title} wieder her`"
        @click="restoreLink(link)"
    >
        <RotateCcw class="size-3.5" />
    </Button>
    <Button
        variant="ghost"
        size="icon"
        class="size-7"
        :aria-label="`Lösche permanent ${link.title}`"
        @click="confirmForceDelete(link)"
    >
        <Trash2 class="size-3.5 text-destructive" />
    </Button>
</template>
