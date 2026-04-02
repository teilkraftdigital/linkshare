<script setup lang="ts">
import { Trash2, Pencil } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import type { Tag } from '@/types/dashboard';

type Props = {
    tag: Tag;
};

defineProps<Props>();

const events = defineEmits<{
    edit: [tag: Tag];
    delete: [tag: Tag];
}>();

function startEdit(tag: Tag) {
    events('edit', tag);
}

function confirmDelete(tag: Tag) {
    events('delete', tag);
}
</script>
<template>
    <Button
        variant="ghost"
        size="icon"
        :aria-label="$t('tags.actions.editAriaLabel', { name: tag.name })"
        @click="startEdit(tag)"
    >
        <Pencil class="size-4" />
    </Button>

    <Button
        variant="ghost"
        size="icon"
        :aria-label="$t('tags.actions.deleteAriaLabel', { name: tag.name })"
        @click="confirmDelete(tag)"
    >
        <Trash2 class="size-4 text-destructive" />
    </Button>
</template>
