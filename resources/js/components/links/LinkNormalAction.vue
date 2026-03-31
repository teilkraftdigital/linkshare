<script setup lang="ts">
import { Trash2, Pencil, RefreshCw } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import type { Link } from '@/types/dashboard';

type Props = {
    link: Link;
    refetchingLinkId: number | null;
};

defineProps<Props>();

const events = defineEmits<{
    edit: [link: Link];
    delete: [link: Link];
    refetchMeta: [link: Link];
}>();

function startEdit(link: Link) {
    events('edit', link);
}

function confirmDelete(link: Link) {
    events('delete', link);
}

function refetchMeta(link: Link) {
    events('refetchMeta', link);
}
</script>

<template>
    <Button
        variant="ghost"
        size="icon"
        class="size-7"
        :aria-label="`Metadaten neu abrufen für ${link.title}`"
        :disabled="refetchingLinkId === link.id"
        @click="refetchMeta(link)"
    >
        <RefreshCw
            class="size-3.5"
            :class="refetchingLinkId === link.id ? 'animate-spin' : ''"
        />
    </Button>
    <Button
        variant="ghost"
        size="icon"
        class="size-7"
        :aria-label="`Editiere ${link.title}`"
        @click="startEdit(link)"
    >
        <Pencil class="size-3.5" />
    </Button>
    <Button
        variant="ghost"
        size="icon"
        class="size-7"
        :aria-label="`Lösche ${link.title}`"
        @click="confirmDelete(link)"
    >
        <Trash2 class="size-3.5 text-destructive" />
    </Button>
</template>
