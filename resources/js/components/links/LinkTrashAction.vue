<script setup lang="ts">
import { Trash2, RotateCcw } from 'lucide-vue-next';
import { useI18n } from 'vue-i18n';
import { Button } from '@/components/ui/button';
import type { Link } from '@/types/dashboard';
const { t } = useI18n();

type Props = {
    link: Link;
};

defineProps<Props>();

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
        :aria-label="t('links.actions.restoreAriaLabel', { title: link.title })"
        @click="restoreLink(link)"
    >
        <RotateCcw class="size-3.5" />
    </Button>
    <Button
        variant="ghost"
        size="icon"
        class="size-7"
        :aria-label="
            t('links.actions.forceDeleteAriaLabel', { title: link.title })
        "
        @click="confirmForceDelete(link)"
    >
        <Trash2 class="size-3.5 text-destructive" />
    </Button>
</template>
