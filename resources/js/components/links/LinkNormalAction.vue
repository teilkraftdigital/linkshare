<script setup lang="ts">
import { Trash2, Pencil, RefreshCw } from 'lucide-vue-next';
import { useI18n } from 'vue-i18n';
import { Button } from '@/components/ui/button';
import type { Link } from '@/types/dashboard';
const { t } = useI18n();

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
        :aria-label="
            t('links.actions.refetchMetaAriaLabel', { title: link.title })
        "
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
        :aria-label="t('links.actions.editAriaLabel', { title: link.title })"
        @click="startEdit(link)"
    >
        <Pencil class="size-3.5" />
    </Button>
    <Button
        variant="ghost"
        size="icon"
        class="size-7"
        :aria-label="t('links.actions.deleteAriaLabel', { title: link.title })"
        @click="confirmDelete(link)"
    >
        <Trash2 class="size-3.5 text-destructive" />
    </Button>
</template>
